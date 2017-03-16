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

        if (!$objs) {
            $null = null;
            return $null;   // reference has to be returned
        } 
        
        $obj = reset($objs);
        return $obj;
    }
    
    static function get_mime_type($filename){
        $ext = substr($filename, strrpos($filename, ".")+1);
        
        switch (strtolower($ext)){
        case "txt":     return "text/plain";
        case "html":    return "text/html";
        case "jpeg":
        case "jpg":     return "image/jpeg";
        case "png":     return "image/png";
        case "gif":     return "image/gif";
        case "mp3":     return "audio/mpeg";
        case "wav":     return "audio/wav";
        case "avi":     return "video/x-msvideo";
        case "mp4":     return "video/mp4";
        default:        return "application/octet-string";
        }
        
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

        $offset = 60 * 60 * 24;

        header('Content-Description: File Transfer');
        header('Content-Type: '.$this->content_type);
        header('Content-Disposition: attachment; filename='.basename($filename));
        header('Content-Transfer-Encoding: binary');
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");
        header("Cache-Control: max-age=$offset, must-revalidate");
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
    public $ordering;
    public $type;
    public $point_to; // point to solution
    
    private $hints = null;

    const TYPE_REGULAR = "regular";
    const TYPE_COIN    = "coin";
    const TYPE_HIDDEN  = "hidden";
    const TYPE_SPECIAL = "special";

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
                     c.".$cc->type.", 
                     c.".$cc->comment.", 
                     c.".$cc->ordering." 
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
                                                   $row[$cc->type],
                                                   $row[$cc->comment],
                                                   $row[$cc->cgrp_id],
                                                   $row[$cc->ordering]);
        }
        $res->free();
        return $out;
    }

    function __construct($id, $ref_id, $filename, $content_type, $type, $comment, $cgrp_id, $ordering, $point_to=array()){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);
        
        $this->cgrp_id = $cgrp_id;
        $this->type = $type;
        $this->ordering = $ordering;
        $this->point_to = $point_to;
    }

    function insert(){
        global $data, $config;

        /* table's name */
        $tc_name  = &$config->data_sql->iquest_clue->table_name;
        $ts_name  = &$config->data_sql->iquest_clue2solution->table_name;
        /* col names */
        $cc      = &$config->data_sql->iquest_clue->cols;
        $cs      = &$config->data_sql->iquest_clue2solution->cols;
    
        $q = "insert into ".$tc_name."(
                    ".$cc->id.",
                    ".$cc->ref_id.",
                    ".$cc->filename.",
                    ".$cc->content_type.",
                    ".$cc->type.",
                    ".$cc->comment.", 
                    ".$cc->cgrp_id.",
                    ".$cc->ordering."
              )
              values(
                    ".$data->sql_format($this->id,              "s").",
                    ".$data->sql_format($this->ref_id,          "s").",
                    ".$data->sql_format($this->filename,        "s").",
                    ".$data->sql_format($this->content_type,    "s").",
                    ".$data->sql_format($this->type,            "s").",
                    ".$data->sql_format($this->comment,         "S").",
                    ".$data->sql_format($this->cgrp_id,         "s").",
                    ".$data->sql_format($this->ordering,        "n")."
              )";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);


        foreach($this->point_to as $sol_id){
            $q = "insert into ".$ts_name."(
                        ".$cs->clue_id.",
                        ".$cs->solution_id."
                  )
                  values(
                        ".$data->sql_format($this->id,  "s").",
                        ".$data->sql_format($sol_id,    "s")."
                  )";
            
            $res=$data->db->query($q);
            if ($data->dbIsError($res)) throw new DBException($res);
        }
    }

    
    function get_accessible_hints($team_id){
        if (!is_null($this->hints)) return $this->hints;
    
        $opt = array("clue_id" => $this->id,
                     "team_id" => $team_id,
                     "accessible" => true);
    
        $this->hints = Iquest_Hint::fetch($opt);
        return $this->hints;
    }

    function get_all_hints(){
        if (!is_null($this->hints)) return $this->hints;

        $opt = array("clue_id" => $this->id);
        $this->hints = Iquest_Hint::fetch($opt);

        return $this->hints;
    }

    function to_smarty(){
        $out = parent::to_smarty();
        $out['type'] = $this->type;
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
    public $ordering;
    public $gained_at;

    protected $clues=null;

    /**
     *  Instantiate clue group by id
     */         
    static function &by_id($id){
        static $cache = array();

        if (array_key_exists($id, $cache)) return $cache[$id];

        $objs = static::fetch(array("id"=>$id));
        if (!$objs) {
            $cache[$id] = null;
        }
        else{
            $obj = reset($objs);
            $cache[$id] = $obj;
        } 
        
        return $cache[$id];
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

        // default ordering
        $order_by = $co->gained_at." desc";
        if (isset($opt['orderby'])) $order_by = $cc->$opt['orderby'];


        // If team_id is specified, set the gained_at attribute of clue group
        if (isset($opt['team_id'])){
            $q2 = "select UNIX_TIMESTAMP(o.".$co->gained_at.") 
                   from ".$to_name." o 
                   where o.".$co->team_id." = ".$data->sql_format($opt['team_id'], "n")." and
                         o.".$co->cgrp_id."=c.".$cc->id;
        }
        else{
            $q2 = "NULL";
        }

        // Fetch only clue groups available to a team. Make sense only together 
        // with $opt['team_id']
        if (!empty($opt['available_only']))  $qw[] = "!isnull((".$q2."))";

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        $q = "select c.".$cc->id.",
                     c.".$cc->ref_id.",
                     c.".$cc->name.",
                     c.".$cc->ordering.",
                     (".$q2.") as ".$co->gained_at." 
              from ".$tc_name." c ".
              $qw." 
              order by ".$order_by;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$cc->id]] =  new Iquest_ClueGrp($row[$cc->id], 
                                                      $row[$cc->ref_id],
                                                      $row[$cc->name],
                                                      $row[$cc->ordering],
                                                      $row[$co->gained_at]);
        }
        $res->free();
        return $out;
    }

    static function is_accessible($cgrp_id, $team_id){
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

    static function fetch_cgrp_open($opt=array()){
        global $data, $config;

        /* table's name */
        $to_name = &$config->data_sql->iquest_cgrp_open->table_name;
        /* col names */
        $co      = &$config->data_sql->iquest_cgrp_open->cols;

        $qw = array();

        if (isset($opt['team_id']))  $qw[] = "o.".$co->team_id." = ".$data->sql_format($opt['team_id'], "n");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select o.".$co->team_id.", 
                     o.".$co->cgrp_id.",
                     UNIX_TIMESTAMP(o.".$co->gained_at.") as ".$co->gained_at." 
              from ".$to_name." o ".$qw;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$co->cgrp_id]][$row[$co->team_id]] = $row[$co->gained_at];
        }
        $res->free();
        return $out;
    }

    /**
     *  Fetch all clue groups that leads to the solution
     *  
     *  If $team_id is provided, the 'gained_at' attribute of clue group is 
     *  correctly filled               
     */         
    static function fetch_by_pointing_to_solution($solution_id, $team_id){
        global $data, $config;

        /* table's name */
        $ts_name = &$config->data_sql->iquest_clue2solution->table_name;
        $tc_name = &$config->data_sql->iquest_clue->table_name;
        $tg_name = &$config->data_sql->iquest_cgrp->table_name;
        $to_name = &$config->data_sql->iquest_cgrp_open->table_name;
        /* col names */
        $cs      = &$config->data_sql->iquest_clue2solution->cols;
        $cc      = &$config->data_sql->iquest_clue->cols;
        $cg      = &$config->data_sql->iquest_cgrp->cols;
        $co      = &$config->data_sql->iquest_cgrp_open->cols;

        $qw = array();
        $qw[] = "s.".$cs->solution_id." = ".$data->sql_format($solution_id, "s");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        // needed for 'gained_at' attribute
        $q2 = "select UNIX_TIMESTAMP(o.".$co->gained_at.") 
               from ".$to_name." o 
               where o.".$co->team_id." = ".$data->sql_format($team_id, "N")." and
                     o.".$co->cgrp_id."=g.".$cg->id;

        $q = "select g.".$cg->id.",
                     g.".$cg->ref_id.",
                     g.".$cg->name.",
                     g.".$cg->ordering.",
                     (".$q2.") as ".$co->gained_at." 
              from ".$ts_name." s
                join ".$tc_name." c on c.".$cc->id."=s.".$cs->clue_id."
                join ".$tg_name." g on g.".$cg->id."=c.".$cc->cgrp_id.
              $qw." 
              order by ".$co->gained_at." desc";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$cg->id]] =  new Iquest_ClueGrp($row[$cg->id], 
                                                      $row[$cg->ref_id],
                                                      $row[$cg->name],
                                                      $row[$cg->ordering],
                                                      $row[$co->gained_at]);
        }
        $res->free();
        return $out;
    }

    function __construct($id, $ref_id, $name, $ordering, $gained_at=null){

        $this->id =         $id;
        $this->ref_id =     $ref_id;
        $this->name =       $name;
        $this->ordering =   $ordering;
        $this->gained_at =  $gained_at;
    }


    function insert(){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_cgrp->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_cgrp->cols;
    
        $q = "insert into ".$t_name."(
                    ".$c->id.",
                    ".$c->ref_id.",
                    ".$c->name.",
                    ".$c->ordering."
              )
              values(
                    ".$data->sql_format($this->id,              "s").",
                    ".$data->sql_format($this->ref_id,          "s").",
                    ".$data->sql_format($this->name,            "s").",
                    ".$data->sql_format($this->ordering,        "n")."
              )";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
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

    function get_next_hint_for_sale($team_id){

        $opt = array("cgrp_id" => $this->id,
                     "team_id" => $team_id,
                     "for_sale" => true,
                     "not_accessible" => true, // hint to be sold can't be accessible yet
                     "order_by" => "ordering");

        $hints = Iquest_Hint::fetch($opt);

        if (!$hints) return null;
        
        return reset($hints);
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
    public $for_sale;
    public $price;
    public $ordering;

    /**
     *  Fetch hits form DB
     */         
    static function fetch($opt=array()){
        global $data, $config;

        $data->connect_to_db();

        /* table's name */
        $th_name  = &$config->data_sql->iquest_hint->table_name;
        $tc_name  = &$config->data_sql->iquest_clue->table_name;
        $tt_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $ch      = &$config->data_sql->iquest_hint->cols;
        $cc      = &$config->data_sql->iquest_clue->cols;
        $ct      = &$config->data_sql->iquest_hint_team->cols;

        $qw = array();
        $join = array();
        $cols = "";
        if (isset($opt['clue_id'])) $qw[] = "h.".$ch->clue_id." = ".$data->sql_format($opt['clue_id'], "s");
        if (isset($opt['ref_id']))  $qw[] = "h.".$ch->ref_id." = ".$data->sql_format($opt['ref_id'], "s");
        if (isset($opt['team_id'])){
            $qw[] = "t.".$ct->team_id." = ".$data->sql_format($opt['team_id'], "s");
            $join[] = " join ".$tt_name." t on h.".$ch->id." = t.".$ct->hint_id;
            $cols .= ", UNIX_TIMESTAMP(t.".$ct->show_at.") as ".$ct->show_at."
                      , ".$ct->for_sale;

            if (!empty($opt['accessible'])){
                $qw[] = "t.".$ct->show_at." <= now()";
            }

            if (!empty($opt['not_accessible'])){
                $qw[] = "t.".$ct->show_at." > now()";
            }

            if (!empty($opt['for_sale'])){
                $qw[] = "t.".$ct->for_sale." = 1";
            }
        }

        if (isset($opt['cgrp_id'])){
            $qw[] = "c.".$cc->cgrp_id." = ".$data->sql_format($opt['cgrp_id'], "s");
            $join[] = " join ".$tc_name." c on c.".$cc->id." = h.".$ch->clue_id;
        }

        if (isset($opt['unscheduled_team_id'])){
            //Get only those hints that are not scheduled to be shown to given team yet 
            $q2 = "select ".$ct->hint_id." 
                   from ".$tt_name." 
                   where ".$ct->team_id." = ".$data->sql_format($opt['unscheduled_team_id'], "s");
                   
            $qw[] = "h.".$ch->id." not in (".$q2.")";
        }


        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $o_order_by = (isset($opt['order_by'])) ? $opt['order_by'] : "timeout";
        $o_order_desc = (!empty($opt['order_desc'])) ? "desc" : "";

        $q = "select h.".$ch->id.",
                     h.".$ch->ref_id.",
                     h.".$ch->clue_id.",
                     h.".$ch->filename.",
                     h.".$ch->content_type.",
                     time_to_sec(h.".$ch->timeout.") as ".$ch->timeout.", 
                     h.".$ch->price.",
                     h.".$ch->ordering.",
                     h.".$ch->comment.
                     $cols." 
              from ".$th_name." h ".implode(" ", $join).
              $qw;

        if ($o_order_by) {
            $q .= " order by ".$ch->$o_order_by." ".$o_order_desc;
        }

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            if (!isset($row[$ct->show_at]))  $row[$ct->show_at] = null;
            if (!isset($row[$ct->for_sale])) $row[$ct->for_sale] = null;
            
            $out[$row[$ch->id]] =  new Iquest_Hint($row[$ch->id], 
                                                   $row[$ch->ref_id],
                                                   $row[$ch->filename],
                                                   $row[$ch->content_type],
                                                   $row[$ch->comment],
                                                   $row[$ch->clue_id],
                                                   $row[$ch->timeout],
                                                   $row[$ch->price],
                                                   $row[$ch->ordering],
                                                   $row[$ct->show_at],
                                                   $row[$ct->for_sale]);
        }
        $res->free();
        return $out;
    }

    /**
     *  Schedule time to show new hint for team $team_id.
     *  This function do not check whether it is already scheduled!     
     */         
    static function schedule($id, $team_id, $timeout, $for_sale){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_hint_team->cols;

        // if timeout is not specified set it to far future, the hint has to be buyed then
        if (!$timeout) $timeout = 2147483647; // (2^31)-1 - max integer value on 32bit systems

        $q="insert into ".$t_name." (
                    ".$c->hint_id.", 
                    ".$c->team_id.", 
                    ".$c->show_at.",
                    ".$c->for_sale.")
            values (".$data->sql_format($id,        "s").",
                    ".$data->sql_format($team_id,   "n").",
                    addtime(now(), sec_to_time(".$data->sql_format($timeout, "n").")),
                    ".$data->sql_format($for_sale,  "n").")";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    
        return true;
    }

    /**
     *  De-schedule displaying of hint by $clue_id for team $team_id.
     *  If the hint is not displayed yet, it will not be displayed never.     
     */             
    static function deschedule($clue_ids, $team_id){
        global $data, $config;
    
        /* table's name */
        $th_name  = &$config->data_sql->iquest_hint->table_name;
        $tt_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $ch      = &$config->data_sql->iquest_hint->cols;
        $ct      = &$config->data_sql->iquest_hint_team->cols;

        $q2 = "select ".$ch->id."
               from ".$th_name."
               where ".$data->get_sql_in($ch->clue_id, $clue_ids, true);

        $qw = array();
        $qw[] = $ct->team_id." = ".$data->sql_format($team_id, "n");
        $qw[] = $ct->hint_id." in (".$q2.")";
        $qw[] = $ct->show_at." > now()";
        $qw = " where ".implode(' and ', $qw);

        $q = "delete from ".$tt_name.$qw;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    
        return true;
    }

    /**
     *  Buy the hint.    
     *  Change scheduled time to show the hint for team $team_id to NOW and
     *  mark the hint it is not longer for sale.
     *       
     *  The hint must be already scheduled. This function do not check whether 
     *  it is already scheduled!
     *  
     *  This function also do not check price of the hint               
     */         
    static function buy($id, $team_id){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_hint_team->cols;

        $q = "update ".$t_name." set 
                ".$c->show_at." = now(),
                ".$c->for_sale." = 0
              where ".$c->hint_id." = ".$data->sql_format($id,      "s")." and 
                    ".$c->team_id." = ".$data->sql_format($team_id, "n");

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    
        return true;
    }

    static function get_next_scheduled($team_id){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_hint_team->cols;

        $qw = array();
        $qw[] = $c->show_at." > now()";
        $qw[] = $c->team_id." = ".$data->sql_format($team_id, "n");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select UNIX_TIMESTAMP(".$c->show_at.") as ".$c->show_at.",
                     ".$c->hint_id." 
              from ".$t_name.$qw;

        $q .= " order by ".$c->show_at;
        $q .= $data->get_sql_limit_phrase(0, 1);

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = null;
        if ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out=array("show_at"     => $row[$c->show_at],
                       "hint_id" => $row[$c->hint_id]);
        }
        $res->free();
        
        return $out;
    }

    function __construct($id, $ref_id, $filename, $content_type, $comment, $clue_id, $timeout, $price, $ordering, $show_at=null, $for_sale=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);
        
        $this->clue_id = $clue_id;
        $this->timeout = $timeout;
        $this->price = $price;
        $this->ordering = $ordering;
        $this->show_at = $show_at;
        $this->for_sale = $for_sale;
    }

    function insert(){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_hint->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_hint->cols;
    
        $q = "insert into ".$t_name."(
                    ".$c->id.",
                    ".$c->ref_id.",
                    ".$c->filename.",
                    ".$c->content_type.",
                    ".$c->comment.", 
                    ".$c->clue_id.",
                    ".$c->timeout.",
                    ".$c->price.",
                    ".$c->ordering."
              )
              values(
                    ".$data->sql_format($this->id,              "s").",
                    ".$data->sql_format($this->ref_id,          "s").",
                    ".$data->sql_format($this->filename,        "s").",
                    ".$data->sql_format($this->content_type,    "s").",
                    ".$data->sql_format($this->comment,         "S").",
                    ".$data->sql_format($this->clue_id,         "s").",
                    sec_to_time(".$data->sql_format($this->timeout, "n")."),
                    ".$data->sql_format($this->price,           "n").",
                    ".$data->sql_format($this->ordering,        "n")."
              )";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    }


    function to_smarty(){
        $out = parent::to_smarty();
        $out['timeout'] = gmdate("H:i:s", $this->timeout);
        $out['price'] = $this->price;

        return $out;
    }

}

class Iquest_key{

    /**
     *  Instantiate object of $class by key
     */         
    static function &obj_by_key($key, $class){

        $key = self::canonicalize_key($key, $class);

        sw_log("Matching key: '$key', class: '$class'", PEAR_LOG_DEBUG);
        
        $objs = $class::fetch(array("key"=>$key));
        if (!$objs) {
            $null = null; //reference have to be returned
            return $null;
        }
        
        $obj = reset($objs);
        return $obj;
    }

    static function canonicalize_key($key, $class){

        // Get common not required prefix of the key and make sure it does not 
        // contain non-alphanumeric characters
        $key_prefix = Iquest_Options::get(Iquest_Options::KEY_PREFIX);
        $key_prefix = strtolower($key_prefix);
        $key_prefix = preg_replace("/[^a-z0-9]/", "", $key_prefix);


        // remove diacritics
        $key = remove_diacritics($key);
        // to lowercase
        $key = strtolower($key);
        // remove non-alphanumeric
        $key = preg_replace("/[^a-z0-9]/", "", $key);

        // remove the optional prefix (it was "I.Q:") from the key
        if ($key_prefix) $key = preg_replace("/^$key_prefix/", "", $key);

        return $key;
    }

}

class Iquest_Solution extends Iquest_file{

    const CD_START_ALL =    "all";
    const CD_START_SINGLE = "single";

    public $cgrp_id;
    public $name;
    public $key;
    public $timeout;
    public $countdown_start;
    public $show_at;
    public $coin_value;
    public $stub;

    /**
     *  Instantiate solution by key
     */         
    static function &by_key($key){
        return Iquest_key::obj_by_key($key, get_called_class());
    }

    static function canonicalize_key($key){
        return Iquest_key::canonicalize_key($key, get_called_class());
    }

    /**
     *  Fetch all solutions that are accessible to the team
     */         
    static function fetch_accessible($team_id){
        $opt = array("team_id" => $team_id,
                     "accessible" => true);
    
        return static::fetch($opt);
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

        sw_log("Iquest_Solution::fetch: options: ".json_encode($opt), PEAR_LOG_DEBUG);


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
                $qw[] = "t.".$ct->show_at." <= now()";
            }

            $order = " order by ".$ct->show_at." desc";
        }


        if (isset($opt['filter']['coin_value']))  $qw[] = $opt['filter']['coin_value']->to_sql_float("c.".$cc->coin_value);


        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        $q = "select c.".$cc->id.",
                     c.".$cc->ref_id.",
                     c.".$cc->cgrp_id.",
                     c.".$cc->filename.",
                     c.".$cc->content_type.",
                     time_to_sec(c.".$cc->timeout.") as ".$cc->timeout.", 
                     c.".$cc->countdown_start.",
                     c.".$cc->comment.",
                     c.".$cc->name.",
                     c.".$cc->key.",
                     c.".$cc->coin_value.",
                     c.".$cc->stub.
                     $cols." 
              from ".$tc_name." c ".implode(" ", $join).
              $qw.$order;

        sw_log("Iquest_Solution::fetch: query: $q", PEAR_LOG_DEBUG);

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
                                                       $row[$cc->countdown_start],
                                                       $row[$cc->key],
                                                       $row[$cc->coin_value],
                                                       $row[$cc->stub],
                                                       $row[$ct->show_at]);
        }
        $res->free();
        return $out;
    }


    /**
     *  Fetch solutions by the clue-group-id that leads to the solution.
     *  
     *  If team_id provided the fetched solutions contain correct
     *  'show_at' attribute for the team.                
     */         
    static function fetch_by_opening_cgrp($cgrp_id, $team_id=null){
        global $data, $config;

        /* table's name */
        $tcs_name= &$config->data_sql->iquest_clue2solution->table_name;
        $tc_name = &$config->data_sql->iquest_clue->table_name;
        $ts_name = &$config->data_sql->iquest_solution->table_name;
        $tt_name = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $ccs     = &$config->data_sql->iquest_clue2solution->cols;
        $cc      = &$config->data_sql->iquest_clue->cols;
        $cs      = &$config->data_sql->iquest_solution->cols;
        $ct      = &$config->data_sql->iquest_solution_team->cols;

        $qw = array();
        $qw[] = "c.".$cc->cgrp_id." = ".$data->sql_format($cgrp_id, "s");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        // needed for 'show-at' attribute
        $q2 = "select UNIX_TIMESTAMP(t.".$ct->show_at.") 
               from ".$tt_name." t 
               where t.".$ct->team_id." = ".$data->sql_format($team_id, "N")." and
                     t.".$ct->solution_id."=s.".$cs->id;

        $q = "select s.".$cs->id.",
                     s.".$cs->ref_id.",
                     s.".$cs->cgrp_id.",
                     s.".$cs->filename.",
                     s.".$cs->content_type.",
                     time_to_sec(s.".$cs->timeout.") as ".$cs->timeout.", 
                     s.".$cs->countdown_start.",
                     s.".$cs->comment.",
                     s.".$cs->name.",
                     s.".$cs->key.",
                     s.".$cs->coin_value.",
                     s.".$cs->stub.",
                     (".$q2.") as ".$ct->show_at."  
              from ".$ts_name." s
                join ".$tcs_name." cs on cs.".$ccs->solution_id."=s.".$cs->id."
                join ".$tc_name." c on c.".$cc->id."=cs.".$ccs->clue_id.
              $qw;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$cs->id]] =  new Iquest_Solution($row[$cs->id], 
                                                       $row[$cs->ref_id],
                                                       $row[$cs->filename],
                                                       $row[$cs->content_type],
                                                       $row[$cs->comment],
                                                       $row[$cs->name],
                                                       $row[$cs->cgrp_id],
                                                       $row[$cs->timeout],
                                                       $row[$cs->countdown_start],
                                                       $row[$cs->key],
                                                       $row[$cs->coin_value],
                                                       $row[$cs->stub],
                                                       $row[$ct->show_at]);
        }
        $res->free();
        return $out;
    }


    /**
     *  De-schedule displaying of solution $id for team $team_id.
     *  If the solution is not displayed yet, it will not be displayed never.     
     */             
    static function deschedule($id, $team_id){
        global $data, $config;
    
        /* table's name */
        $tt_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $ct      = &$config->data_sql->iquest_solution_team->cols;

        $qw = array();

        if (is_array($id))  $qw[] = $data->get_sql_in($ct->solution_id, $id, true);
        else                $qw[] = $ct->solution_id." = ".$data->sql_format($id, "s");
        
        $qw[] = $ct->team_id." = ".$data->sql_format($team_id, "n");
        $qw[] = $ct->show_at." > now()";
        $qw = " where ".implode(' and ', $qw);

        $q = "delete from ".$tt_name.$qw;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    
        return true;
    }

    /**
     *  Schedule displaying of solution for team $team_id.
     *  This function do not check whether solution is already scheduled!     
     */         
    static function schedule($id, $team_id, $timeout){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_solution_team->cols;

        $q="insert into ".$t_name." (
                    ".$c->solution_id.", 
                    ".$c->team_id.", 
                    ".$c->show_at.")
            values (".$data->sql_format($id,        "s").",
                    ".$data->sql_format($team_id,   "n").",
                    addtime(now(), sec_to_time(".$data->sql_format($timeout, "n").")))";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    
        return true;
    }

    static function get_next_scheduled($team_id){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_solution_team->cols;

        $qw = array();
        $qw[] = $c->show_at." > now()";
        $qw[] = $c->team_id." = ".$data->sql_format($team_id, "n");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select UNIX_TIMESTAMP(".$c->show_at.") as ".$c->show_at.",
                     ".$c->solution_id." 
              from ".$t_name.$qw;

        $q .= " order by ".$c->show_at;
        $q .= $data->get_sql_limit_phrase(0, 1);

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = null;
        if ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out=array("show_at"     => $row[$c->show_at],
                       "solution_id" => $row[$c->solution_id]);
        }
        $res->free();
        
        return $out;
    }

    function __construct($id, $ref_id, $filename, $content_type, $comment, $name, 
                         $cgrp_id, $timeout, $countdown_start, $key, $coin_value, $stub, $show_at=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);
        
        $this->name = $name;
        $this->cgrp_id = $cgrp_id;
        $this->timeout = $timeout;
        $this->countdown_start = $countdown_start;
        $this->key = $key;
        $this->coin_value = $coin_value;
        $this->stub = $stub;
        $this->show_at = $show_at;
    }
    
    /**
     *  Check whether the solution is reachable by given team.
     *  The solution is reachable if the team gained at least one clue leading 
     *  to the solution.
     *  
     *  @return bool                     
     */         
    function is_reachable($team_id){
    
        $clue_grps = Iquest_ClueGrp::fetch_by_pointing_to_solution($this->id, $team_id);

        foreach($clue_grps as $clue_grp){
            // if any of the clue groups is gained the solution is reachable
            if (!is_null($clue_grp->gained_at)){
                return true;
            }
        }

        return false;
    }

    /**
     *  Retrieve the show_at value for given team
     */         
    function get_show_at($team_id){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_solution_team->cols;

        $qw = array();
        $qw[] = $c->solution_id." = ".$data->sql_format($this->id, "s");
        $qw[] = $c->team_id." = ".$data->sql_format($team_id, "n");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select UNIX_TIMESTAMP(".$c->show_at.") as ".$c->show_at."
              from ".$t_name.$qw;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        if ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $this->show_at = $row[$c->show_at];
        }
        else{
            $this->show_at = null;
        }
        $res->free();
        
        return $this->show_at;
    }


    function insert(){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_solution->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_solution->cols;
    
        $q = "insert into ".$t_name."(
                    ".$c->id.",
                    ".$c->ref_id.",
                    ".$c->filename.",
                    ".$c->content_type.",
                    ".$c->comment.", 
                    ".$c->cgrp_id.",
                    ".$c->name.",
                    ".$c->key.",
                    ".$c->coin_value.",
                    ".$c->stub.",
                    ".$c->countdown_start.",
                    ".$c->timeout."
              )
              values(
                    ".$data->sql_format($this->id,              "s").",
                    ".$data->sql_format($this->ref_id,          "s").",
                    ".$data->sql_format($this->filename,        "s").",
                    ".$data->sql_format($this->content_type,    "s").",
                    ".$data->sql_format($this->comment,         "S").",
                    ".$data->sql_format($this->cgrp_id,         "s").",
                    ".$data->sql_format($this->name,            "s").",
                    ".$data->sql_format($this->key,             "s").",
                    ".$data->sql_format($this->coin_value,      "n").",
                    ".$data->sql_format($this->stub,            "n").",
                    ".$data->sql_format($this->countdown_start,   "s").",
                    sec_to_time(".$data->sql_format($this->timeout, "n").")
              )";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    }

    function to_smarty(){
        $out = parent::to_smarty();
        $out['name'] = $this->name;

        return $out;
    }
}


class Iquest{

    /**
     *  Check whether contest already started (START_TIME passed)
     */         
    static function is_started(){
        $start_time = Iquest_Options::get(Iquest_Options::START_TIME);
    
        if (time() < $start_time) return false;
        return true;
    }

    /**
     *  Check whether contest is over (END_TIME passed or team is deactivated)
     */         
    static function is_over(){
        // check that team is active (team is deactivated when it give up the contest)
        if (!$_SESSION['auth']->is_active()) return true;
    
        $end_time = Iquest_Options::get(Iquest_Options::END_TIME);
    
        if (time() < $end_time) return false;
        return true;
    }

    /**
     *  Start the content for the team:
     *  
     *  1. Open initial clue group
     *  2. Schedule showing of hints
     *  3. Schedule showing of solution                    
     */         
    static function start($team_id){
        // Make sure it's time to start contest
        if (!self::is_started()) return;

        $cgrp_id = Iquest_Options::get(Iquest_Options::INITIAL_CGRP_ID);

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";
        sw_log($log_prefix."*** Starting contest for Team", PEAR_LOG_INFO);

        // 1. Open new clue group
        self::_open_cgrp($cgrp_id, $team_id, $log_prefix);

        // 2. Schedule show time for new hints
        self::_schedule_new_hints($cgrp_id, $team_id, $log_prefix);

        // 3. If team gained all clues that lead to some task_solution
        //    schedule showing of the solution
        self::_schedule_solution($cgrp_id, $team_id, $log_prefix);
    }


    static function buy_hint($hint, $team_id){
        global $data;

        $data->transaction_start();

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";

        // 1. Spend coin from wallet
        sw_log($log_prefix."*** Spending coins ({$hint->price})", PEAR_LOG_INFO);

        $team = Iquest_Team::fetch_by_id($team_id);
        $team->wallet_spend_money($hint->price);

        // 2. Mark the hint as bought
        sw_log($log_prefix."*** Marking the hint as bought (ID={$hint->id})", PEAR_LOG_INFO);

        Iquest_Hint::buy($hint->id, $team_id);
    
        $data->transaction_commit();
    }


    static function solution_found($solution, $team_id){
        global $data;
    
        /**
         *  1. Close current task (only if the show_at time did not pass)
         *     Table: task_solution_team.show_at = never           
         *                 
         *  2. Open new clue group
         *     Table: open_cgrp_team.gained_at = now
         *
         *  3. Schedule show time for new hints
         *     Table: hint_team.show_at = now+timeout
         *     
         *  4. If team gained all clues that lead to some task_solution
         *     set the show_at time
         *     Table: task_solution_team.show_at = now+timeout                                               
         *
         *  5. Hints that has not been displayed and are not needed any more
         *     should not be never showed:          
         *     Table: hint_team.show_at = newer
         *              
         *  6. Solutions that has not been displayed and are not needed any more
         *     should not be never showed.
         *     Different with the [1] is that this step walk throught whole the
         *     graph of clues/solutions and search for the solutions that are
         *     not realy needed to reach the final task.                                     
         *     Table: task_solution_team.show_at = never
         *     
         *  7. Update team ranks                             
         */                                   

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";
    
        $data->transaction_start();
    
        // 1. Close current task
        sw_log($log_prefix."*** Closing solution (ID={$solution->id})", PEAR_LOG_INFO);
        Iquest_Solution::deschedule($solution->id, $team_id);    

        // 2. Open new clue group
        self::_open_cgrp($solution->cgrp_id, $team_id, $log_prefix);

        self::gain_coins($team_id, $solution->coin_value);

        // 3. Schedule show time for new hints
        if (!$solution->stub){
            self::_schedule_new_hints($solution->cgrp_id, $team_id, $log_prefix);
        }

        // 4. If team gained all clues that lead to some task_solution
        //    schedule showing of the solution
        if (!$solution->stub){
            self::_schedule_solution($solution->cgrp_id, $team_id, $log_prefix);
        }
                
        // 5. Hints that has not been displayed and are not needed any more
        //    should not be never showed:          

        sw_log($log_prefix."*** Check what hints could be de-scheduled to show.", PEAR_LOG_INFO);

        $graph = new Iquest_solution_graph($team_id);
        $del_clue_ids = $graph->get_unneded_clues();

        sw_log($log_prefix."    Clue not more needed: (IDs=".implode(", ", $del_clue_ids).")", PEAR_LOG_INFO);
        
        if ($del_clue_ids){
            Iquest_Hint::deschedule($del_clue_ids, $team_id);
        }


        // 6. Solutions that has not been displayed and are not needed any more
        //    should not be never showed:          

        $del_solution_ids = $graph->get_unneded_solutions();
        sw_log($log_prefix."    Solutions not more needed: (IDs=".implode(", ", $del_solution_ids).")", PEAR_LOG_INFO);
        if ($del_solution_ids){
            Iquest_Solution::deschedule($del_solution_ids, $team_id);
        }


        // 7. Update ranks
        $team_distance = $graph->get_distance_to_finish();
        Iquest_team_rank::update_rank($team_id, $team_distance);
        sw_log($log_prefix."    New distance to finish: $team_distance", PEAR_LOG_INFO);
        
        unset($graph);
        
        $data->transaction_commit();


        // send notification that team solved the task
        self::_send_notifications($solution, $team_id);
    }

    /**
     *  Add coins to the wallet of the team
     */         
    public static function gain_coins($team_id, $value){
        global $lang_str;

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";
        
        if ($value > 0){
            sw_log($log_prefix."*** Gained coins ($value)", PEAR_LOG_INFO);

            Iquest_info_msg::add_msg(
                str_replace("<value>", 
                            $value, 
                            $lang_str['iquest_msg_coin_gained']), "coin");

            $team = Iquest_Team::fetch_by_id($team_id);
            $team->wallet_add_money($value);
        }
    }

    /**
     *  Open new clue group
     */         
    private static function _open_cgrp($cgrp_id, $team_id, $log_prefix){
        if (!Iquest_ClueGrp::is_accessible($cgrp_id, $team_id)){
            sw_log($log_prefix."*** Opening clue group (ID=$cgrp_id)", PEAR_LOG_INFO);
            Iquest_ClueGrp::open($cgrp_id, $team_id);
        }
    }


    /**
     *  Schedule show time for new hints
     */         
    private static function _schedule_new_hints($cgrp_id, $team_id, $log_prefix){
        sw_log($log_prefix."*** Schedule show times for new hints.", PEAR_LOG_INFO);

        $clue_grp = &Iquest_ClueGrp::by_id($cgrp_id);
        if (!$clue_grp){
            throw new RuntimeException("Clue group '".$cgrp_id."' does not exists. ");
        }

        $clues = $clue_grp->get_clues();
        foreach($clues as $k=>$v){
            $opt = array("clue_id" => $v->id,
                         "unscheduled_team_id"=>$team_id); // Only hints not scheduled yet
            $hints = Iquest_Hint::fetch($opt);

            foreach($hints as $hk=>$hv){
                sw_log($log_prefix."    scheduling to show hint (ID={$hv->id}) after ".gmdate('H:i:s', $hv->timeout), PEAR_LOG_INFO);
                Iquest_Hint::schedule($hv->id, $team_id, $hv->timeout, ($hv->price > 0));
            }

            unset($hints);
        }
        
        unset($clues);
        unset($clue_grp);
    }


    /**
     *  If team gained all clues that lead to some task_solution schedule 
     *  showing of the solution
     */         
    private static function _schedule_solution($cgrp_id, $team_id, $log_prefix){

        sw_log($log_prefix."*** Check what solutions could be scheduled to show.", PEAR_LOG_INFO);

        // fetch list of solutions that are opened by gaining the clue group
        $opening_solutions = Iquest_Solution::fetch_by_opening_cgrp($cgrp_id, $team_id);

        foreach($opening_solutions as $opening_solution){
            sw_log($log_prefix."    * Checking solution (ID={$opening_solution->id})", PEAR_LOG_INFO);
            
            // if solution is already scheduled, skip it
            if (!is_null($opening_solution->show_at)){
                sw_log($log_prefix."      It's already scheduled to ".date($opening_solution->show_at), PEAR_LOG_INFO);
                continue;
            }

            // If solution is already solved, skip it.
            // Solution is solved if the team gained the clue group to which the solution points
            if (Iquest_ClueGrp::is_accessible($opening_solution->cgrp_id, $team_id)){
                sw_log($log_prefix."      It's already solved", PEAR_LOG_INFO);
                continue;
            }


            $schedule_solution = true;
        if ($opening_solution->countdown_start==Iquest_Solution::CD_START_ALL){
            // fetch list of all clue groups that opens the solution
            $clue_grps = Iquest_ClueGrp::fetch_by_pointing_to_solution($opening_solution->id, $team_id);
            foreach($clue_grps as $clue_grp){
                // if any of the clue groups is not gained yet, do not schedule
                // the solution
                if (is_null($clue_grp->gained_at)){
                    sw_log($log_prefix."      Clue group (ID={$clue_grp->id}) not gained yet. Not schedule the solution.", PEAR_LOG_INFO);
                    $schedule_solution = false;
                    break;
                }
            }
        }
            
            unset($clue_grps);
            
            if ($schedule_solution){
                if ($opening_solution->timeout > 0){
                    sw_log($log_prefix."      Scheduling show solution (ID={$opening_solution->id}) after ".gmdate('H:i:s', $opening_solution->timeout), PEAR_LOG_INFO);
                    Iquest_Solution::schedule($opening_solution->id, $team_id, $opening_solution->timeout);
                }
                else{
                    sw_log($log_prefix."      Solution (ID={$opening_solution->id}) should not be scheduled to show due to it's timeout is not set.", PEAR_LOG_INFO);
                }
            }
        }
        
        unset($opening_solutions);
    }
    
    
    /**
     *  Send notifications to email once a team found a solution
     */         
    private static function _send_notifications($solution, $team_id){
        global $config;

        // if notification for the solution is not configured, exit
        if (!isset($config->iquest->notifications[$solution->id])) return;
        
        // get team name
        $team = Iquest_Team::fetch_by_id($team_id);


        // prepare email
        $to = implode(",", $config->iquest->notifications[$solution->id]);
        $subject = "Team {$team->name} solved task {$solution->name}";
        $body = $subject."\n At: ".date("H:i:s");
    
        // set UTF-8 headers
        $subject = "=?UTF-8?B?".base64_encode($subject)."?=";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .='Content-type: text/plain; charset=utf-8'."\r\n";
    
        // send the email
        mail($to, $subject, $body, $headers);
    }
}

abstract class Iquest_graph_abstract{

    public static function escape_dot($str){
        return '"'.str_replace('"', '\"', $str).'"';
    }

    /**
     *  Visualize the graph using graphviz
     */         
    public function image_graph(){
        global $config;

        // prepare specification of file descriptors
        $descriptorspec = array(
           0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
           1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
           2 => array("file", "/dev/null", "a")                 // no stderr 
        );

        // execute the graphviz command
        $cmd = $config->iquest->graphviz_cmd." -Tsvg";
        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException("Failed to execute graphviz!");
        }

        // $pipes now looks like this:
        // 0 => writeable handle connected to child stdin
        // 1 => readable handle connected to child stdout
    
        // Write DOT representation of the graph to stdin of the graphviz
        fwrite($pipes[0], $this->get_dot()); 
        fclose($pipes[0]);
    
        // read the image data
        $image_data = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
    
        // It is important that you close any pipes before calling
        // proc_close in order to avoid a deadlock
        $return_value = proc_close($process);
    
        // Return image to the browser
        header('Content-Description: File Transfer');
        header('Content-type: image/svg+xml');
//      header('Content-Disposition: attachment; filename="graph.png"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($image_data));

        echo $image_data;
    }

    /**
     *  Generate graph representation in DOT language (for graphviz)
     */         
    abstract protected function get_dot();
}

/**
 *  Node of the Iquest_solution_graph graph
 */ 
class Iquest_solution_graph_node{
    const TYPE_CLUE = "clue";
    const TYPE_SOLUTION = "solution";

    private $obj;
    private $type;
    // flag indicating the node has been visited
    public $visited = false;

    // attributes for type=solution
    public $solved = false;

    // attributes  for type=clue
    public $gained = false;


    function __construct($type, &$obj){
        $this->type = $type;
        $this->obj = &$obj;
    }
    
    function is_solution(){
        return ($this->type==self::TYPE_SOLUTION);
    }
    
    function is_clue(){
        return ($this->type==self::TYPE_CLUE);
    }

    function get_obj(){
        return $this->obj;
    }
    
    function get_node_id(){
    
        if ($this->is_solution()) return "S_".$this->obj->id;
        if ($this->is_clue())     return "C_".$this->obj->id;
        
        throw new UnexpectedValueException("Unknown type of graph node");
    }
    
    /**
     *  Return representation of the node in dot language
     */         
    public function to_dot(){
        $dot = "[";

        if ($this->type == self::TYPE_SOLUTION){
            $dot .= "shape=box";
            $dot .=  $this->solved ? ",fontcolor=green" : ""; 
        }
        else{
            $start_cgrp_id = Iquest_Options::get(Iquest_Options::INITIAL_CGRP_ID);
            $final_cgrp_id = Iquest_Options::get(Iquest_Options::FINAL_CGRP_ID);
            $giveitup_cgrp_id = Iquest_Options::get(Iquest_Options::REVEAL_GOAL_CGRP_ID);


            if ($this->obj->cgrp_id == $giveitup_cgrp_id){
                $dot .= "shape=octagon";
                $dot .= ",style=filled";
                $dot .= ",color=cyan";
            }
            else{
                if ($this->obj->cgrp_id == $start_cgrp_id){
                    $dot .= "shape=doublecircle";
                }
                elseif ($this->obj->cgrp_id == $final_cgrp_id){
                    $dot .= "shape=doubleoctagon";
                }
                else{
                    $dot .= "shape=ellipse";
                }

                $dot .= ",style=filled";
                $dot .= $this->gained ? ",color=green" : ",color=red";
            }

        }

        if ($this->visited){
            $dot .= ",label=<<FONT color=\"#990000\">Visited: </FONT>\"".$this->obj->id."\">";
        }
        else{
            $dot .= ",label=\"".$this->obj->id."\"";
        }

        $dot .= "]";
        return $dot;
    }

}

/**
 *  Class holding graph of clue/solution dependencies 
 */ 
class Iquest_solution_graph extends Iquest_graph_abstract{
    private $team_id;
    private $cgroups;
    private $solutions;
    private $nodes = array();
    private $edges = array();
    private $reverse_edges = array();


    /**
     *  Create the graph for a team
     */         
    function __construct($team_id){
        $this->team_id = $team_id;

        // fetch clue groups and solutions
        $opt = array("team_id" => $this->team_id);
        $this->cgroups = Iquest_ClueGrp::fetch($opt);
        $this->cgrp_open = Iquest_ClueGrp::fetch_cgrp_open($opt);
        $this->solutions = Iquest_Solution::fetch();

        // create clue => solution edges
        $this->load_clue2solution();

        // walk through all solutions
        foreach($this->solutions as &$solution){

            // create nodes for task solutions
            $this->nodes["S_".$solution->id] = 
                new Iquest_solution_graph_node(Iquest_solution_graph_node::TYPE_SOLUTION, $solution);

            // if there is a clue group that is gained by solving a task solution
            if (isset($this->cgroups[$solution->cgrp_id])){
                $cgrp = &$this->cgroups[$solution->cgrp_id];

                // if team has gained the clue group, mark the solution as solved
                if ($cgrp->gained_at){
                    $this->nodes["S_".$solution->id]->solved = true;
                }

                // fetch all clues and create the solution => clue edges
                $clues = $cgrp->get_clues();
                foreach($clues as &$clue){
                    if (!isset($this->edges["S_".$solution->id])) $this->edges["S_".$solution->id] = array();
                    $this->edges["S_".$solution->id][] = "C_".$clue->id;
                    
                    if (!isset($this->reverse_edges["C_".$clue->id])) $this->reverse_edges["C_".$clue->id] = array();
                    $this->reverse_edges["C_".$clue->id][] = "S_".$solution->id;
                }
            }

            // For dead-end waypoints there is no real clue group defined. 
            // So check directly the open_cgrp table so we know whether it is solved. 
            elseif (isset($this->cgrp_open[$solution->cgrp_id])){
                $this->nodes["S_".$solution->id]->solved = true;
            }
        }

        // walk through all clue groups
        foreach($this->cgroups as &$cgroup){
            // get clues of the group
            $clues = $cgroup->get_clues();
            foreach($clues as &$clue){
                // create graph nodes for the clues
                $this->nodes["C_".$clue->id] = 
                    new Iquest_solution_graph_node(Iquest_solution_graph_node::TYPE_CLUE, $clue);
                    
                // if team has gained the clue group, mark the clue as gained
                if ($cgroup->gained_at){
                    $this->nodes["C_".$clue->id]->gained = true;
                }
            }
        }
    }

    /**
     *  Load clue2solution linkings and create the clue=>solution graph edges
     */         
    private function load_clue2solution(){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_clue2solution->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_clue2solution->cols;

        // fetch the whole clue2solution DB table
        $q = "select ".$c->clue_id.",
                     ".$c->solution_id."
              from ".$t_name;
    
        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        // walk through the rows
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            // and create the clue=>solution edges
            if (!isset($this->edges["C_".$row[$c->clue_id]])) $this->edges["C_".$row[$c->clue_id]] = array();
            $this->edges["C_".$row[$c->clue_id]][] = "S_".$row[$c->solution_id];

            // create the reversed edges as well
            if (!isset($this->reverse_edges["S_".$row[$c->solution_id]])) $this->reverse_edges["S_".$row[$c->solution_id]] = array();
            $this->reverse_edges["S_".$row[$c->solution_id]][] = "C_".$row[$c->clue_id];
        }
        $res->free();
    } 


    /**
     *  Mark all nodes from which the final task could be reached without
     *  meeting already solved tasks in the way.
     *            
     *  It is done by walking the graph in reversed order from the final task.
     *  
     *  If the $include_coin_waypoints is set to TRUE, include also nodes
     *  from which any waypoint valuated by coins could be reached.               
     */         
    protected function mark_accessible_nodes($include_coin_waypoints){

        // reset all nodes visited flag
        foreach($this->nodes as &$node) $node->visited = false;

        $queue = array();

        $final_cgrp_id = Iquest_Options::get(Iquest_Options::FINAL_CGRP_ID);

        if (!isset($this->cgroups[$final_cgrp_id])){
            throw new UnexpectedValueException("Invalid ID of final clue group: '$final_cgrp_id'");
        }

        $clues = $this->cgroups[$final_cgrp_id]->get_clues();

        if (!$clues){
            throw new UnexpectedValueException("Final clue group: '$final_cgrp_id' does not contain any clues");
        }

        //add nodes of final clue group to the queue
        foreach($clues as &$clue) $queue[] = "C_".$clue->id; 


        sw_log("mark_accessible_nodes: queue: ".json_encode($queue), PEAR_LOG_DEBUG);

        // if we should include also nodes accessible from waypoints with coins
        if ($include_coin_waypoints){

            // get the solutions that gain some coins
            $opt = array();
            $opt['filter']['coin_value'] = new Filter("coin_value", 0, ">");
            $solutions = Iquest_Solution::fetch($opt);
            
            // add the related nodes to the queue too
            foreach($solutions as $solution){
                if (!isset($this->nodes["S_".$solution->id])){
                    throw new UnexpectedValueException("Node for solution ID='{$solution->id}' does not exists in the graph.");
                }
                
                // but only if they are not solved yet
                if ($this->nodes["S_".$solution->id]->solved) continue;
                
                //add node to the queue
                $queue[] = "S_".$solution->id;
            }
        }

        sw_log("mark_accessible_nodes: queue2: ".json_encode($queue), PEAR_LOG_DEBUG);
        
        // as long as there are nodes in in the queue, fetch node from the queue...
        while(!is_null($node_id = array_shift($queue))){
            // and set it's visited flag to true
            $this->nodes[$node_id]->visited = true;

            sw_log("mark_accessible_nodes: visited node: $node_id", PEAR_LOG_DEBUG);

            // We are walking the graph in reversed order. If there are any
            // edges leading TO this node walk through them. Get all nodes
            // FROM what leads edge TO current node 
            if (isset($this->reverse_edges[$node_id])){
                foreach($this->reverse_edges[$node_id] as $from_node_id){

                    // if the node has been already visited, skip it
                    if ($this->nodes[$from_node_id]->visited) continue;

                    // if the node is task solution that is solved, skip it
                    if ($this->nodes[$from_node_id]->is_solution() and
                        $this->nodes[$from_node_id]->solved)  continue;

                    // add node to queue
                    $queue[] = $from_node_id;
                    sw_log("mark_accessible_nodes: adding node: $from_node_id", PEAR_LOG_DEBUG);
                }
            }
            sw_log("mark_accessible_nodes: queue3: ".json_encode($queue), PEAR_LOG_DEBUG);
        }
    
    }

    /**
     *  Check whether graph is continuous. I.e. whether all nodes could be 
     *  accessed from initial clue group.
     */         
    public function check_graph_continuous(){

        // reset all nodes visited flag
        foreach($this->nodes as &$node) $node->visited = false;

        $queue = array();

        $initial_cgrp_id = Iquest_Options::get(Iquest_Options::INITIAL_CGRP_ID);

        if (!isset($this->cgroups[$initial_cgrp_id])){
            throw new UnexpectedValueException("Invalid ID of initial clue group: '$initial_cgrp_id'");
        }

        $clues = $this->cgroups[$initial_cgrp_id]->get_clues();

        if (!$clues){
            throw new UnexpectedValueException("Initial clue group: '$initial_cgrp_id' does not contain any clues");
        }

        //add nodes of initial clue group to the queue
        foreach($clues as &$clue) $queue[] = "C_".$clue->id; 

        sw_log("mark_accessible_nodes: queue: ".json_encode($queue), PEAR_LOG_DEBUG);

        // as long as there are nodes in in the queue, fetch node from the queue...
        while(!is_null($node_id = array_shift($queue))){
            // and set it's visited flag to true
            $this->nodes[$node_id]->visited = true;

            sw_log("mark_accessible_nodes: visited node: $node_id", PEAR_LOG_DEBUG);

            // We are walking the graph in reversed order. If there are any
            // edges leading TO this node walk through them. Get all nodes
            // FROM what leads edge TO current node 
            if (isset($this->edges[$node_id])){
                foreach($this->edges[$node_id] as $to_node_id){

                    // if the node has been already visited, skip it
                    if ($this->nodes[$to_node_id]->visited) continue;

                    // add node to queue
                    $queue[] = $to_node_id;
                    sw_log("mark_accessible_nodes: adding node: $to_node_id", PEAR_LOG_DEBUG);
                }
            }
            sw_log("mark_accessible_nodes: queue3: ".json_encode($queue), PEAR_LOG_DEBUG);
        }


        $reveal_goal_cgrp_id = Iquest_Options::get(Iquest_Options::REVEAL_GOAL_CGRP_ID);
        if (!isset($this->cgroups[$reveal_goal_cgrp_id])){
            throw new UnexpectedValueException("Invalid ID of reveal goal clue group: '$reveal_goal_cgrp_id'");
        }


        $unaccessible_cgrps = array();
        $unaccessible_solutions = array();

        foreach($this->nodes as &$node){
            if (!$node->visited) {
                if ($node->is_clue() and 
                    $node->get_obj()->cgrp_id==$reveal_goal_cgrp_id){
                    
                    // It is OK that clue group revaling goal is not accessible.
                    // Skip it.
                    continue;
                } 

                if ($node->is_clue())       $unaccessible_cgrps[]     = $node->get_obj()->cgrp_id;
                if ($node->is_solution())   $unaccessible_solutions[] = $node->get_obj()->id;
            }
        }
    
        $err = "";
        if ($unaccessible_cgrps){
            $err .= "Clue groups: ".
                    implode(", ", array_unique($unaccessible_cgrps)).
                    " are not accessible from the start clue group.\n";
        }

        if ($unaccessible_solutions){
            $err .= "Solutions: ".
                    implode(", ", array_unique($unaccessible_solutions)).
                    " are not accessible from the start clue group.\n";
        }

        return $err;
    }

    /**
     *  Return list of IDs of clues that has been already gained by the team,
     *  but that are not needed anymore (has been used to solve a task solution).
     *  
     *  It is done by walking the graph in reversed order from the final task.
     *  All clues that are reachable from the final task or from any waypoint
     *  valuated by coins (not meeting a solved task) are still needed.                         
     */         
    public function get_unneded_clues(){

        $this->mark_accessible_nodes(true);

        // create the list of unneded clues, walk through all nodes
        $unneded_clues = array();
        foreach($this->nodes as &$node){
            // if the node is not clue skip it
            if (!$node->is_clue()) continue;
            // if the node has been visited, the clue is still needed. Skip it
            if ($node->visited) continue;
            // If the clue has not been gained yet, it do not belong to our scope. Skip it.
            if (!$node->gained) continue;

            // All the rest of nodes should be added to the array
            $unneded_clues[] = $node->get_obj()->id;
        }

        return  $unneded_clues;
    }


    /**
     *  Return list of IDs of task solutions that are not needed anymore 
     *  (Either has been already solved or solving them do not help with
     *  reaching final task).
     *  
     *  It is done by walking the graph in reversed order from the final task.
     *  All solutions that are rachable from the final task (not meeting a solved task)
     *  are still needed.                         
     */         
    public function get_unneded_solutions(){

        $this->mark_accessible_nodes(false);

        // create the list of unneded solutions, walk through all nodes
        $unneded_solutions = array();
        foreach($this->nodes as &$node){
            // if the node is not solutions skip it
            if (!$node->is_solution()) continue;
            // if the node has been visited, the solution is still needed. Skip it
            if ($node->visited) continue;

            // All the rest of nodes should be added to the array
            $unneded_solutions[] = $node->get_obj()->id;
        }

        return  $unneded_solutions;
    }


    /**
     *  Get distance to finish of the contest
     *  
     *  It is done by walking the graph in reversed order from the final task.
     *  All nodes that are rachable from the final task (not meeting a solved task)
     *  are counted to the distance.
     */         
    public function get_distance_to_finish(){

        $this->mark_accessible_nodes(false);

        $dist = 0;
        $cgrps_visited = array();
        foreach($this->nodes as &$node){
            // do not count nodes that are not visited
            if (!$node->visited) continue;

            if ($node->is_clue()){
                // Whole clue-group should be counted only once. 
                // Therefore the $dist should not be incremented on every clue.
                // Visited clue groups are counted in separate array
                $clue = $node->get_obj();
                $cgrps_visited[$clue->cgrp_id] = true;
                continue;
            }
            
            // node is a solution
            $dist++;
        }
        
        // add the number of visited clue groups
        $dist += count($cgrps_visited);
        
        return $dist;
    }


    /**
     *  Get solutions that are not solved yet, but the team has at least one clue
     *  to the solution.     
     */         
    public function get_active_solutions(){
        $out = array();

        // walk throught all graph nodes
        foreach($this->nodes as &$node){
            // skip those nodes that are not solutions ot that are already solved
            if (!$node->is_solution()) continue;
            if ($node->solved) continue;

            // walk through all nodes that points to current node
            $node_id = $node->get_node_id();
            if (isset($this->reverse_edges[$node_id])){
                foreach($this->reverse_edges[$node_id] as $node2_id){
                    // If the node is not clue or if it is not gained yet, skip it
                    $node2 = $this->nodes[$node2_id];
                    if (!$node2->is_clue()) continue;
                    if (!$node2->gained) continue;
                    
                    // add the solution to the output
                    $out[] = $node->get_obj();
                }
            }
        }
        
        return $out;
    }


    /**
     *  Generate graph representation in DOT language (for graphviz)
     */         
    protected function get_dot(){
        $out = "digraph G {\n";

        foreach($this->nodes as $k => $node){
            $out .= self::escape_dot($k)." ".$node->to_dot().";\n";
        }

        foreach($this->edges as $k1 => $v1){
            foreach($this->edges[$k1] as $v2){
                $out .= self::escape_dot($k1)." -> ".self::escape_dot($v2).";\n";
            }
        }

        $out .= "}\n";

        return $out;
    }
}

/**
 *  Class generate image of simplified contest graph 
 */ 
class Iquest_contest_graph_simplified extends Iquest_graph_abstract{
    private $team_id;
    private $cgroups;
    private $solutions;
    private $clue2solution;

    /* Hide names of not visited clue groups */
    protected $hide_names = true;

    /* URL of the screen that display clue groups */
    protected $cgrp_url = null;
    
    /* Flag indicating that unknown clue groups shall not be displayed as hyperlinks */
    protected $link_unknown_cgrps = false;


    /**
     *  Create the graph for a team
     */         
    function __construct($team_id){
        $this->team_id = $team_id;

        // fetch clue groups and solutions
        $opt = array("team_id" => $this->team_id);
        $this->cgroups = Iquest_ClueGrp::fetch($opt);
        $this->solutions = Iquest_Solution::fetch();

        // create clue => solution edges
        $this->load_clue2solution();
    }

    /**
     *  Set URL of the screen that display clue groups 
     */
    public function set_cgrp_url($url){
        $this->cgrp_url = $url;
    }

    /** 
     *  Set flag indicating whether unknown clue groups shall not be displayed as hyperlinks 
     */
    public function link_unknown_cgrps($val){
        $this->link_unknown_cgrps = $val;
    }

    /** 
     *  Hide names of not visited clue groups 
     */
    public function hide_names($val){
        $this->hide_names = $val;
    }

    protected function get_dot(){
        $out = "digraph G {\n";
//        $out .= "bgcolor=\"#cccccc\"\n";
        $out .= "bgcolor=\"transparent\"\n";
        $out .= "pad=0.3\n";
        $out .= "rankdir=LR\n";
        $out .= "nodesep=0.7\n";
        $out .= "ranksep=0.9\n";

        // generate graph edges

        $connected_cgroups = array();
        $edges = array();
        // walk through all clue groups
        foreach($this->cgroups as &$cgroup){
            // get clues of the group
            $clues = $cgroup->get_clues();
            foreach($clues as &$clue){
                if (empty($this->clue2solution[$clue->id])) continue;
                $solution_ids = $this->clue2solution[$clue->id];
                foreach($solution_ids as $solution_id){
                    if (empty($this->solutions[$solution_id])) continue;
                    $solution = $this->solutions[$solution_id];
                    if (empty($this->cgroups[$solution->cgrp_id])) continue;
                    $next_cgrp = $this->cgroups[$solution->cgrp_id];


                    $edges[] = self::escape_dot($cgroup->ref_id)." -> ".self::escape_dot($next_cgrp->ref_id).";\n";
                    $connected_cgroups[$cgroup->id] = true;
                    $connected_cgroups[$next_cgrp->id] = true;
                }
            }
        }

        // output graph nodes from clue groups
        $reveal_goal_cgrp_id = Iquest_Options::get(Iquest_Options::REVEAL_GOAL_CGRP_ID);

        foreach($this->cgroups as &$cgroup){
            // skip the "reveal the goal" cgrp if it is not connected by any edge
            if ($reveal_goal_cgrp_id == $cgroup->id and !isset($connected_cgroups[$cgroup->id])) continue;

            $out .= self::escape_dot($cgroup->ref_id)." ".$this->cgroup2dot($cgroup).";\n";
        }
        
        // output graph edges

        $edges = array_unique($edges);
        $out .= implode("", $edges);

        $out .= "}\n";

        return $out;
    }

    protected function cgroup2dot($cgroup){

        $cgrp_url = "";
        if ($this->cgrp_url){
            $cgrp_url = str_replace("<ID>", RawURLEncode($cgroup->ref_id), $this->cgrp_url);
        }

        $dot = "[";
        
        $dot .= "shape=circle,";
        $dot .= "width=0.3,";
        $dot .= "penwidth=3.0,";
        $dot .= "fixedsize=true,";
        $dot .= "style=filled,";
        $dot .= "color=black,";

        $xlabel = $cgroup->name;
        if ($cgroup->gained_at){
            $dot .= "fillcolor=white,";
        }
        else{
            $dot .= "fillcolor=grey,";
            if ($this->hide_names) $xlabel = "?";
            if (!$this->link_unknown_cgrps) $cgrp_url="";
        }

        if ($cgrp_url){
            $dot .= "URL=".self::escape_dot(htmlspecialchars($cgrp_url)).",";
            $dot .= "target=_parent,";
        }

        $dot .= "xlabel=".self::escape_dot($xlabel).",";
        $dot .= "label=\" \"";
        
        $dot .= "]";
        
        return $dot;
    }

    /**
     *  Load clue2solution linkings and create the clue=>solution graph edges
     */         
    private function load_clue2solution(){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_clue2solution->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_clue2solution->cols;

        // fetch the whole clue2solution DB table
        $q = "select ".$c->clue_id.",
                     ".$c->solution_id."
              from ".$t_name;
    
        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $this->clue2solution = array();

        // walk through the rows
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $this->clue2solution[$row[$c->clue_id]][] = $row[$c->solution_id];
        }
        $res->free();
    } 
}


class Iquest_Team{
    public $id;
    public $name;
    public $active;
    public $wallet;

    static function fetch_by_id($id){
        return reset(static::fetch(array("id" => $id)));
    }
    
    static function fetch($opt=array()){
        global $data, $config;

        /* table's name */
        $tt_name = &$config->data_sql->iquest_team->table_name;
        /* col names */
        $ct      = &$config->data_sql->iquest_team->cols;

        $o_order_by = (isset($opt['order_by'])) ? $opt['order_by'] : "";
        $o_order_desc = (!empty($opt['order_desc'])) ? "desc" : "";

        $qw = array();
        if (isset($opt['id']))      $qw[] = "t.".$ct->id." = ".$data->sql_format($opt['id'], "n");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        $q = "select t.".$ct->id.",
                     t.".$ct->name.",
                     t.".$ct->active.",
                     t.".$ct->wallet."
              from ".$tt_name." t ".
              $qw;

        if ($o_order_by) $q .= " order by ".$ct->$o_order_by." ".$o_order_desc;


        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$ct->id]] =  new Iquest_Team($row[$ct->id], 
                                                   $row[$ct->name],
                                                   $row[$ct->active],
                                                   $row[$ct->wallet]);
        }
        $res->free();
        return $out;
    }

    function __construct($id, $name, $active, $wallet){
        $this->id =         $id;
        $this->name =       $name;
        $this->active =     $active;
        $this->wallet =     $wallet;
    }
    
    function wallet_add_money($value){
        $this->wallet += $value;
        $this->update();
    }

    function wallet_spend_money($value){
        
        if ($this->wallet < $value) {
            throw new UnderflowException("Cannot spend $value, not enought money in the wallet. Wallet value: {$this->wallet}");
        }
        
        $this->wallet -= $value;
        $this->update();
    }
    
    private function update(){
        global $data, $config;

        /* table's name */
        $tt_name = &$config->data_sql->iquest_team->table_name;
        /* col names */
        $ct      = &$config->data_sql->iquest_team->cols;
        
        $q = "update ".$tt_name." set 
                ".$ct->wallet." = ".$data->sql_format($this->wallet, "n")."
              where ".$ct->id." = ".$data->sql_format($this->id, "n");
        
        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    }

    function to_smarty(){
        $out = array();
        $out['id'] = $this->id;
        $out['name'] = $this->name;
        $out['active'] = $this->active;
        $out['wallet'] = $this->wallet;
        return $out;
    }

}

class Iquest_team_rank{

    public $timestamp;
    public $distance;
    public $rank;
    public $team_id;


    /**
     *  Fetch clues form DB
     */         
    static function fetch($opt=array()){
        global $data, $config;

        $data->connect_to_db();

        /* table's name */
        $t_name  = &$config->data_sql->iquest_team_rank->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_team_rank->cols;

        $qw = array();
//        if (isset($opt['ref_id']))  $qw[] = "c.".$cc->ref_id." = ".$data->sql_format($opt['ref_id'], "s");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select UNIX_TIMESTAMP(".$c->timestamp.") as ".$c->timestamp.",
                     ".$c->distance.", 
                     ".$c->rank.", 
                     ".$c->team_id." 
              from ".$t_name.
              $qw."
              order by ".$c->timestamp;

        if (isset($opt['last'])){
            $q .= " desc";
            $q .= $data->get_sql_limit_phrase(0, 1);
        } 


        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$c->timestamp]] =  new Iquest_team_rank($row[$c->timestamp], 
                                                              json_decode($row[$c->distance], true),
                                                              json_decode($row[$c->rank], true),
                                                              $row[$c->team_id]);
        }
        $res->free();
        return $out;
    }

    static function update_rank($team_id, $distance){

        $teams = Iquest_Team::fetch();
        $team_nr = count($teams);

        $last_rank_obj = self::fetch(array("last"=>true));
        $last_rank_obj = reset($last_rank_obj);

        if ($last_rank_obj->distance[$team_id] != $distance){
            // set the new distance
            $last_rank_obj->distance[$team_id] = $distance;
    
            // calculate new rank of the team
            $new_rank = 1;
            foreach($last_rank_obj->distance as $t_id => $last_dist){
                if ($t_id == $team_id) continue; //skip current team
    
                if ($last_dist <= $distance) $new_rank++;        
            }
    
    
            // if the rank for the team is not set yet, use the $team_nr
            if (!isset($last_rank_obj->rank[$team_id])) $last_rank_obj->rank[$team_id] = $team_nr;

            // remember the old rank of the team and set the new one
            $old_rank = $last_rank_obj->rank[$team_id];
            $last_rank_obj->rank[$team_id] = $new_rank;
            
            // shift the ranks of the teams whose rank was between new_rank and old_rank
            foreach($last_rank_obj->rank as $t_id => $val){
                if ($t_id == $team_id) continue; //skip current team
    
                if ($last_rank_obj->rank[$t_id] >= $new_rank and 
                    $last_rank_obj->rank[$t_id] <  $old_rank and
                    $last_rank_obj->rank[$t_id] <  $team_nr) {  // do not let the ranks grow over the team number
                    
                    $last_rank_obj->rank[$t_id]++;
                }        
            }
        }

        $last_rank_obj->timestamp = time();
        $last_rank_obj->team_id = $team_id;
        $last_rank_obj->insert();
    }

    function __construct($timestamp, $distance, $rank, $team_id=null){
        $this->timestamp = $timestamp;
        $this->distance = $distance;
        $this->rank = $rank;
        $this->team_id = $team_id;
    }

    function insert(){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_team_rank->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_team_rank->cols;
    
        $q = "insert into ".$t_name."(
                    ".$c->timestamp.",
                    ".$c->distance.",
                    ".$c->rank.",
                    ".$c->team_id."
              )
              values(
                    FROM_UNIXTIME(".$data->sql_format($this->timestamp, "n")."),
                    ".$data->sql_format(json_encode($this->distance),   "s").",
                    ".$data->sql_format(json_encode($this->rank),       "s").",
                    ".$data->sql_format($this->team_id,                 "N")."
              )";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    }

}


class Iquest_info_msg{

    public static function add_msg($msg, $type = null){
        global $controler;
        
        $info_msg=array();
        $info_msg['long'] = $msg;
        $info_msg['type'] = $type;
        $controler->add_message($info_msg);
    }


}


?>
