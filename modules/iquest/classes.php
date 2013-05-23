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
              $qw;

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
              $qw;

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

    function __construct($id, $ref_id, $filename, $content_type, $comment, $clue_id, $timeout, $show_at=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);
        
        $this->clue_id = $clue_id;
        $this->timeout = $timeout;
        $this->show_at = $show_at;
    }
}

class Iquest{


    /**
     *  Return list of clue groups available to a team
     */ 
    static function get_clue_grps_team($team_id, $opt=array()){
        global $data, $config;

        $data->connect_to_db();

        /* table's name */
        $tc_name = &$config->data_sql->iquest_cgrp->table_name;
        $to_name = &$config->data_sql->iquest_cgrp_open->table_name;
        /* col names */
        $cc      = &$config->data_sql->iquest_cgrp->cols;
        $co      = &$config->data_sql->iquest_cgrp_open->cols;

        $qw = array();
        $qw[] = "o.".$co->team_id."=".$data->sql_format($team_id, "n");

        if (isset($opt['ref_id']))  $qw[] = "c.".$cc->ref_id." = ".$data->sql_format($opt['ref_id'], "s");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        $q = "select c.".$cc->id.",
                     c.".$cc->ref_id.",
                     c.".$cc->name.",
                     UNIX_TIMESTAMP(o.".$co->gained_at.") as ".$co->gained_at." 
              from ".$tc_name." c join ".$to_name." o on c.".$cc->id."=o.".$co->cgrp_id.
              $qw." 
              order by ".$co->gained_at;

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




}

?>
