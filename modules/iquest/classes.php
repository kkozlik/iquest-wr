<?php

class Iquest_file{
    public $id;
    public $ref_id;
    public $filename;
    public $content_type;
    public $comment;

    function __construct($id, $ref_id, $filename, $content_type, $comment){

        $this->id =             $id;
        $this->ref_id =         $ref_id;
        $this->filename =       $filename;
        $this->content_type =   $content_type;
        $this->comment =        $comment;
    }

    function to_smarty($opt = array()){
        $out = array();
        $out['id'] = $this->id;
        $out['ref_id'] = $this->ref_id;
        $out['filename'] = basename($this->filename);
        $out['content_type'] = $this->content_type;
        $out['comment'] = $this->comment;

        return $out;
    }
}

class Iquest_Clue extends Iquest_file{
    public $cgrp_id;
    public $point_to; // point to solution

    function __construct($id, $ref_id, $filename, $content_type, $comment, $cgrp_id, $point_to=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);
        
        $this->cgrp_id = $cgrp_id;
        $this->point_to = $point_to;
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
        global $data, $config;

        $data->connect_to_db();

        /* table's name */
        $tc_name  = &$config->data_sql->iquest_clue->table_name;
        /* col names */
        $cc      = &$config->data_sql->iquest_clue->cols;

        $qw = array();
        $qw[] = "c.".$cc->cgrp_id."=".$data->sql_format($this->id, "s");

//        if (isset($opt['ref_id']))  $qw[] = "c.".$cc->ref_id." = ".$this->sql_format($opt['ref_id'], "s");

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

        $this->clues = $out;
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
