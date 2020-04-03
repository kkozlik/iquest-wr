<?php
require_once("Iquest_file.php");

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
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$cc->id]] =  new Iquest_Clue($row[$cc->id],
                                                   $row[$cc->ref_id],
                                                   $row[$cc->filename],
                                                   $row[$cc->content_type],
                                                   $row[$cc->type],
                                                   $row[$cc->comment],
                                                   $row[$cc->cgrp_id],
                                                   $row[$cc->ordering]);
        }
        $res->closeCursor();
        return $out;
    }

    protected static function fetchPointedSolutionIds($clue_id){
        global $data, $config;

        $ts_name  = &$config->data_sql->iquest_clue2solution->table_name;
        $cs      = &$config->data_sql->iquest_clue2solution->cols;

        $q = "select ".$cs->solution_id."
              from ".$ts_name."
              where {$cs->clue_id}=".$data->sql_format($clue_id, "s");

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[] = $row[$cs->solution_id];
        }
        $res->closeCursor();
        return $out;
    }

    function __construct($id, $ref_id, $filename, $content_type, $type, $comment, $cgrp_id, $ordering, $point_to=null){
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
        }
    }


    public function is_blowable($team_id){

        $team = Iquest_Team::fetch_by_id($team_id);
        if ($team->bomb < 1) return false;

        $point_to = $this->get_point_to();
        if (!$point_to) return false;
        if (count($point_to) > 1) return false;  // cannot blow-up, we do not know which solution of those pointed shall be blown

        $point_to = reset($point_to);
        $scheduled_solutions = Iquest_Solution::get_scheduled_solutions($team_id);

        foreach($scheduled_solutions as $solution){
            if ($solution['solution_id'] == $point_to) return true;
        }

        return false;
    }

    public function get_point_to(){
        if (!is_null($this->point_to)) return $this->point_to;
        $this->point_to = self::fetchPointedSolutionIds($this->id);
        return $this->point_to;
    }

    public function get_solution_to_blow_up(){
        $point_to = $this->get_point_to();
        if (!$point_to) return false;
        if (count($point_to) > 1) return false;  // cannot blow-up, we do not know which solution of those pointed shall be blown

        $point_to = reset($point_to);

        return Iquest_Solution::by_id($point_to);
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
