<?php

class Iquest_ClueGrp{
    public $id;
    public $ref_id;
    public $name;
    public $gained_at;

    function __construct($id, $ref_id, $name, $gained_at=null){

        $this->id =         $id;
        $this->ref_id =     $ref_id;
        $this->name =       $name;
        $this->gained_at =  $gained_at;
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
    static function get_clue_grps_team($team_id){
        global $lang_str, $data, $config;

        $data->connect_to_db();

        /* table's name */
        $tc_name = &$config->data_sql->iquest_cgrp->table_name;
        $to_name = &$config->data_sql->iquest_cgrp_open->table_name;
        /* col names */
        $cc      = &$config->data_sql->iquest_cgrp->cols;
        $co      = &$config->data_sql->iquest_cgrp_open->cols;

        $q = "select c.".$cc->id.",
                     c.".$cc->ref_id.",
                     c.".$cc->name.",
                     UNIX_TIMESTAMP(o.".$co->gained_at.") as ".$co->gained_at." 
              from ".$tc_name." c join ".$to_name." o on c.".$cc->id."=o.".$co->cgrp_id." 
              where o.".$co->team_id."=".$data->sql_format($team_id, "n")."
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
