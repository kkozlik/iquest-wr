<?php

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
        $order_by = $co->gained_at." desc, ".$cc->ordering;
        if (isset($opt['orderby'])) $order_by = $cc->{$opt['orderby']};


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
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$cc->id]] =  new Iquest_ClueGrp($row[$cc->id],
                                                      $row[$cc->ref_id],
                                                      $row[$cc->name],
                                                      $row[$cc->ordering],
                                                      $row[$co->gained_at]);
        }
        $res->closeCursor();
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
        $res->setFetchMode(PDO::FETCH_NUM);

        $row = $res->fetch();
        $out = !empty($row[0]);
        $res->closeCursor();

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
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$co->cgrp_id]][$row[$co->team_id]] = $row[$co->gained_at];
        }
        $res->closeCursor();
        return $out;
    }

    /**
     *  Fetch all clue groups that leads to the solution
     *
     *  If $team_id is provided, the 'gained_at' attribute of clue group is
     *  correctly filled
     */
    static function fetch_by_pointing_to_solution($solution_id, $team_id=null){
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
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$cg->id]] =  new Iquest_ClueGrp($row[$cg->id],
                                                      $row[$cg->ref_id],
                                                      $row[$cg->name],
                                                      $row[$cg->ordering],
                                                      $row[$co->gained_at]);
        }
        $res->closeCursor();
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
