<?php

/**
 *  Common class for clues, hints and solutions
 *  Contain functions for manipulate the files. 
 */ 
class Iquest_file{
    public $id;
    public $ref_id;
    public $filename;
    public $content_type;
    public $comment;
    public $content = null;

    /**
     *  Instantiate obj by ref_id
     */         
    static function &by_ref_id($ref_id){
        $objs = static::fetch(array("ref_id"=>$ref_id));
        if (!$objs) return null;
        
        $obj = reset($objs);
        return $obj;
    }
    
    function __construct($id, $ref_id, $filename, $content_type, $comment){
        $this->id =             $id;
        $this->ref_id =         $ref_id;
        $this->filename =       $filename;
        $this->content_type =   strtolower($content_type);
        $this->comment =        $comment;
    }

    /**
     *  Get content of the file
     */         
    function get_content(){
        global $config;
        
        if (!is_null($this->content)) return $this->content;
    
        $filename = $config->iquest_data_dir.$this->filename;
        $content = file_get_contents($filename);
        
        if (false === $content){
            throw new RuntimeException("Can not read file: ".$filename);
        }
        
        $this->content = $content;
        
        return $this->content;
    }

    /**
     *  Flush content of the file for download
     */         
    function flush_content(){
        global $config;
        
        $filename = $config->iquest_data_dir.$this->filename;

        header('Content-Description: File Transfer');
        header('Content-Type: '.$this->content_type);
        header('Content-Disposition: attachment; filename='.basename($filename));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filename));
        ob_clean();
        flush();

        $success = readfile($filename);
       
        if (false === $success){
            throw new RuntimeException("Can not read file: ".$filename);
        }
    }

    /**
     *  Determine whether the content could be directly shown in the HTML code.
     *  
     *  So far only text files could be included.          
     */         
    function is_directly_shown(){
        $type_parts = explode("/", $this->content_type, 2);
    
        if ($type_parts[0]=="text") return true;
        return false;
    }


    function to_smarty($opt = array()){
        $out = array();
        $out['id'] = $this->id;
        $out['ref_id'] = $this->ref_id;
        $out['filename'] = basename($this->filename);
        $out['content_type'] = $this->content_type;
        $out['comment'] = $this->comment;
        $out['content'] = null;

        if ($this->is_directly_shown()){
            $out['content'] = $this->get_content();
        }

        return $out;
    }
}

class Iquest_Clue extends Iquest_file{
    public $cgrp_id;
    public $point_to; // point to solution
    
    private $hints = null;

    /**
     *  Fetch clues form DB
     */         
    static function fetch($opt=array()){
        global $data, $config;

        $data->connect_to_db();

        /* table's name */
        $tc_name  = &$config->data_sql->iquest_clue->table_name;
        /* col names */
        $cc      = &$config->data_sql->iquest_clue->cols;

        $qw = array();
        if (isset($opt['cgrp_id'])) $qw[] = "c.".$cc->cgrp_id." = ".$data->sql_format($opt['cgrp_id'], "s");
        if (isset($opt['ref_id']))  $qw[] = "c.".$cc->ref_id." = ".$data->sql_format($opt['ref_id'], "s");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        $q = "select c.".$cc->id.",
                     c.".$cc->ref_id.",
                     c.".$cc->cgrp_id.",
                     c.".$cc->filename.",
                     c.".$cc->content_type.",
                     c.".$cc->comment." 
              from ".$tc_name." c ".
              $qw."
              order by c.".$cc->ordering;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$cc->id]] =  new Iquest_Clue($row[$cc->id], 
                                                   $row[$cc->ref_id],
                                                   $row[$cc->filename],
                                                   $row[$cc->content_type],
                                                   $row[$cc->comment],
                                                   $row[$cc->cgrp_id]);
        }
        $res->free();
        return $out;
    }

    function __construct($id, $ref_id, $filename, $content_type, $comment, $cgrp_id, $point_to=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);
        
        $this->cgrp_id = $cgrp_id;
        $this->point_to = $point_to;
    }
    
    function get_accessible_hints($team_id){
        if (!is_null($this->hints)) return $this->hints;
    
        $opt = array("clue_id" => $this->id,
                     "team_id" => $team_id,
                     "accessible" => true);
    
        $this->hints = Iquest_Hint::fetch($opt);
        return $this->hints;
    }

    function to_smarty(){
        $out = parent::to_smarty();
        $out['hints'] = array();

        if (!is_null($this->hints)){
            foreach($this->hints as $k => $v){
                $out['hints'][] = $this->hints[$k]->to_smarty();
            }
        }

        return $out;
    }

}


class Iquest_ClueGrp{
    public $id;
    public $ref_id;
    public $name;
    public $gained_at;

    protected $clues=null;

    /**
     *  Instantiate clue group by id
     */         
    static function &by_id($id){
        $objs = static::fetch(array("id"=>$id));
        if (!$objs) return null;
        
        $obj = reset($objs);
        return $obj;
    }


    /**
     *  Open new clue group for team $team_id.
     *  This function do not check whether it is already opened!     
     */         
    static function open($id, $team_id){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_cgrp_open->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_cgrp_open->cols;

        $q="insert into ".$t_name." (
                    ".$c->cgrp_id.", 
                    ".$c->team_id.", 
                    ".$c->gained_at.")
            values (".$data->sql_format($id,        "s").",
                    ".$data->sql_format($team_id,   "n").",
                    now())";


        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    
        return true;
    }

    static function fetch($opt=array()){
        global $data, $config;

        /* table's name */
        $tc_name = &$config->data_sql->iquest_cgrp->table_name;
        $to_name = &$config->data_sql->iquest_cgrp_open->table_name;
        /* col names */
        $cc      = &$config->data_sql->iquest_cgrp->cols;
        $co      = &$config->data_sql->iquest_cgrp_open->cols;

        $qw = array();

        if (isset($opt['id']))      $qw[] = "c.".$cc->id." = ".$data->sql_format($opt['id'], "s");
        if (isset($opt['ref_id']))  $qw[] = "c.".$cc->ref_id." = ".$data->sql_format($opt['ref_id'], "s");
        if (isset($opt['team_id'])) $qw[] = "o.".$co->team_id." = ".$data->sql_format($opt['team_id'], "n");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        $q = "select c.".$cc->id.",
                     c.".$cc->ref_id.",
                     c.".$cc->name.",
                     UNIX_TIMESTAMP(o.".$co->gained_at.") as ".$co->gained_at." 
              from ".$tc_name." c join ".$to_name." o on c.".$cc->id."=o.".$co->cgrp_id.
              $qw." 
              order by ".$co->gained_at." desc";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$cc->id]] =  new Iquest_ClueGrp($row[$cc->id], 
                                                      $row[$cc->ref_id],
                                                      $row[$cc->name],
                                                      $row[$co->gained_at]);
        }
        $res->free();
        return $out;
    }

    static function is_accessible($team_id, $cgrp_id){
        global $data, $config;

        /* table's name */
        $to_name = &$config->data_sql->iquest_cgrp_open->table_name;
        /* col names */
        $co      = &$config->data_sql->iquest_cgrp_open->cols;

        $qw = array();
        $qw[] = "o.".$co->team_id."=".$data->sql_format($team_id, "n");
        $qw[] = "o.".$co->cgrp_id."=".$data->sql_format($cgrp_id, "s");

        $qw = " where ".implode(' and ', $qw);

        $q = "select count(*) 
              from ".$to_name." o ".$qw;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $row = $res->fetchRow(MDB2_FETCHMODE_ORDERED);
        $out = !empty($row[0]);
        $res->free();

        return $out;
    }

    function __construct($id, $ref_id, $name, $gained_at=null){

        $this->id =         $id;
        $this->ref_id =     $ref_id;
        $this->name =       $name;
        $this->gained_at =  $gained_at;
    }

    function get_clues(){
        if (!is_null($this->clues)) return $this->clues;
        $this->load_clues();
        return $this->clues;
    }

    function load_clues(){
        $this->clues = Iquest_Clue::fetch(array("cgrp_id"=>$this->id));
        return $this->clues;
    }

    function to_smarty($opt = array()){
        $out = array();
        $out['id'] = $this->id;
        $out['ref_id'] = $this->ref_id;
        $out['name'] = $this->name;
        $out['gained_at_ts'] = $this->gained_at;
        $out['gained_at'] = date("H:i:s", $this->gained_at);

        return $out;
    }

}


class Iquest_Hint extends Iquest_file{
    public $clue_id;
    public $timeout;
    public $show_at;

    /**
     *  Fetch hits form DB
     */         
    static function fetch($opt=array()){
        global $data, $config;

        $data->connect_to_db();

        /* table's name */
        $tc_name  = &$config->data_sql->iquest_hint->table_name;
        $tt_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $cc      = &$config->data_sql->iquest_hint->cols;
        $ct      = &$config->data_sql->iquest_hint_team->cols;

        $qw = array();
        $join = array();
        $cols = "";
        if (isset($opt['clue_id'])) $qw[] = "c.".$cc->clue_id." = ".$data->sql_format($opt['clue_id'], "s");
        if (isset($opt['ref_id']))  $qw[] = "c.".$cc->ref_id." = ".$data->sql_format($opt['ref_id'], "s");
        if (isset($opt['team_id'])){
            $qw[] = "t.".$ct->team_id." = ".$data->sql_format($opt['team_id'], "s");
            $join[] = " join ".$tt_name." t on c.".$cc->id." = t.".$ct->hint_id;
            $cols .= ", UNIX_TIMESTAMP(t.".$ct->show_at.") as ".$ct->show_at." ";

            if (!empty($opt['accessible'])){
                $qw[] = "t.".$ct->show_at." < now()";
            }
        }

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        $q = "select c.".$cc->id.",
                     c.".$cc->ref_id.",
                     c.".$cc->clue_id.",
                     c.".$cc->filename.",
                     c.".$cc->content_type.",
                     time_to_sec(c.".$cc->timeout.") as ".$cc->timeout.", 
                     c.".$cc->comment.
                     $cols." 
              from ".$tc_name." c ".implode(" ", $join).
              $qw."
              order by c.".$cc->timeout;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            if (!isset($row[$ct->show_at])) $row[$ct->show_at] = null;
            
            $out[$row[$cc->id]] =  new Iquest_Hint($row[$cc->id], 
                                                   $row[$cc->ref_id],
                                                   $row[$cc->filename],
                                                   $row[$cc->content_type],
                                                   $row[$cc->comment],
                                                   $row[$cc->clue_id],
                                                   $row[$cc->timeout],
                                                   $row[$ct->show_at]);
        }
        $res->free();
        return $out;
    }

    /**
     *  Open new hint for team $team_id.
     *  This function do not check whether it is already opened!     
     */         
    static function open($id, $team_id, $timeout){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_hint_team->cols;

        $q="insert into ".$t_name." (
                    ".$c->hint_id.", 
                    ".$c->team_id.", 
                    ".$c->show_at.")
            values (".$data->sql_format($id,        "s").",
                    ".$data->sql_format($team_id,   "n").",
                    addtime(now(), sec_to_time(".$data->sql_format($timeout, "n").")))";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    
        return true;
    }

    function __construct($id, $ref_id, $filename, $content_type, $comment, $clue_id, $timeout, $show_at=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);
        
        $this->clue_id = $clue_id;
        $this->timeout = $timeout;
        $this->show_at = $show_at;
    }
}


class Iquest_Solution extends Iquest_file{
    public $cgrp_id;
    public $name;
    public $key;
    public $timeout;
    public $show_at;

    /**
     *  Instantiate solution by key
     */         
    static function &by_key($key){

        // remove diacritics
        $key = Str_Replace(
                    Array("á","č","ď","é","ě","í","ľ","ň","ó","ř","š","ť","ú","ů","ý ","ž","Á","Č","Ď","É","Ě","Í","Ľ","Ň","Ó","Ř","Š","Ť","Ú","Ů","Ý","Ž"),
                    Array("a","c","d","e","e","i","l","n","o","r","s","t","u","u","y ","z","A","C","D","E","E","I","L","N","O","R","S","T","U","U","Y","Z"),
                    $key);
        // to lowercase
        $key = strtolower($key);
        // remove non-alphanumeric
        $key = preg_replace("/[^a-z0-9]/", "", $key);
        // remove initial "iq" (it was "I.Q:")
        $key = preg_replace("/^iq/", "", $key);

        sw_log("Matching key: '".$key."'", PEAR_LOG_DEBUG);

        $objs = static::fetch(array("key"=>$key));
        if (!$objs) return null;
        
        $obj = reset($objs);
        return $obj;
    }

    /**
     *  Fetch solution from DB
     */         
    static function fetch($opt=array()){
        global $data, $config;

        /* table's name */
        $tc_name  = &$config->data_sql->iquest_solution->table_name;
        $tt_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $cc      = &$config->data_sql->iquest_solution->cols;
        $ct      = &$config->data_sql->iquest_solution_team->cols;

        $qw = array();
        $join = array();
        $cols = "";
        $order = "";
        if (isset($opt['ref_id']))  $qw[] = "c.".$cc->ref_id." = ".$data->sql_format($opt['ref_id'], "s");
        if (isset($opt['key']))     $qw[] = "c.".$cc->key." = ".$data->sql_format($opt['key'], "s");
        if (isset($opt['team_id'])){
            $qw[] = "t.".$ct->team_id." = ".$data->sql_format($opt['team_id'], "s");
            $join[] = " join ".$tt_name." t on c.".$cc->id." = t.".$ct->solution_id;
            $cols .= ", UNIX_TIMESTAMP(t.".$ct->show_at.") as ".$ct->show_at." ";

            if (!empty($opt['accessible'])){
                $qw[] = "t.".$ct->show_at." < now()";
            }

            $order = " order by ".$ct->show_at." desc";
        }

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        $q = "select c.".$cc->id.",
                     c.".$cc->ref_id.",
                     c.".$cc->cgrp_id.",
                     c.".$cc->filename.",
                     c.".$cc->content_type.",
                     time_to_sec(c.".$cc->timeout.") as ".$cc->timeout.", 
                     c.".$cc->comment.",
                     c.".$cc->name.",
                     c.".$cc->key.
                     $cols." 
              from ".$tc_name." c ".implode(" ", $join).
              $qw.$order;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            if (!isset($row[$ct->show_at])) $row[$ct->show_at] = null;
            
            $out[$row[$cc->id]] =  new Iquest_Solution($row[$cc->id], 
                                                       $row[$cc->ref_id],
                                                       $row[$cc->filename],
                                                       $row[$cc->content_type],
                                                       $row[$cc->comment],
                                                       $row[$cc->name],
                                                       $row[$cc->cgrp_id],
                                                       $row[$cc->timeout],
                                                       $row[$cc->key],
                                                       $row[$ct->show_at]);
        }
        $res->free();
        return $out;
    }


    /**
     *  Close solution $id for team $team_id.
     *  If the solution is not displayed yet, it will not be displayed never.     
     */             
    static function close_solution($id, $team_id){
        global $data, $config;
    
        /* table's name */
        $tt_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $ct      = &$config->data_sql->iquest_solution_team->cols;

        $qw = array();
        $qw[] = $ct->team_id." = ".$data->sql_format($team_id, "n");
        $qw[] = $ct->solution_id." = ".$data->sql_format($id, "s");
        $qw[] = $ct->show_at." >= now()";
        $qw = " where ".implode(' and ', $qw);

        $q = "delete from ".$tt_name.$qw;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    
        return true;
    }


    function __construct($id, $ref_id, $filename, $content_type, $comment, $name, $cgrp_id, $timeout, $key, $show_at=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);
        
        $this->name = $name;
        $this->cgrp_id = $cgrp_id;
        $this->timeout = $timeout;
        $this->key = $key;
        $this->show_at = $show_at;
    }

    function to_smarty(){
        $out = parent::to_smarty();
        $out['name'] = $this->name;

        return $out;
    }
}


class Iquest{



    static function get_accessible_solutions($team_id){
        $opt = array("team_id" => $team_id,
                     "accessible" => true);
    
        return Iquest_Solution::fetch($opt);
    }


    static function solution_found($solution, $team_id){
    
        /**
         *  1. Close current task (only if the show_at time did not pass)
         *     Table: task_solution_team.show_at = never           
         *                 
         *  2. Open new clue group
         *     Table: open_cgrp_team.gained_at = now
         *
         *  3. Determine when to show new hints
         *     Table: hint_team.show_at = now+timeout
         *     
         *  4. If team gained all clues that lead to some task_solution
         *     set the show_at time
         *     Table: task_solution_team.show_at = now+timeout                                               
         *
         *  5. Hints that has not been displayed and are not needed any more
         *     should not be never showed:          
         *     Table: hint_team.show_at = newer
         */                                   
    
        // 1. Close current task
        Iquest_Solution::close_solution($solution->id, $team_id);    

        // 2. Open new clue group
        if (!Iquest_ClueGrp::is_accessible($solution->cgrp_id, $team_id)){
            Iquest_ClueGrp::open($solution->cgrp_id, $team_id);
        }

        $clue_grp = &Iquest_ClueGrp::by_id($solution->cgrp_id);
        if (!$clue_grp){
            throw new RuntimeException("Clue group '".$solution->cgrp_id."' does not exists. ".
                               "Referenced by solution: ".json_encode($solution));
        }

        // 3. Determine when to show new hints
        $clues = $clue_grp->get_clues();
        foreach($clues as $k=>$v){
            $opt = array("clue_id" => $v->id);
            $hints = Iquest_Hint::fetch($opt);

            foreach($hints as $hk=>$hv){
                Iquest_Hint::open($hv->id, $team_id, $hv->timeout);
            }
        }

    }

}

?>
