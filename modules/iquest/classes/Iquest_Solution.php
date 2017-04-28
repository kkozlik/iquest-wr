<?php
require_once("Iquest_file.php");
require_once("Iquest_Solution_Next_Cgrp.php");

class Iquest_Solution extends Iquest_file{

    const CD_START_ALL =    "all";
    const CD_START_SINGLE = "single";

    public $next_cgrps;
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
//TODO:                                                       $row[$cc->cgrp_id],
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

//TODO:                     s.".$cs->cgrp_id.",
        $q = "select s.".$cs->id.",
                     s.".$cs->ref_id.",
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
//TODO:                                                       $row[$cs->cgrp_id],
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
//TODO:                         $cgrp_id, 
                         $timeout, $countdown_start, $key, $coin_value, $stub, $show_at=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);
        
        $this->name = $name;
//TODO:        $this->cgrp_id = $cgrp_id;
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

    /**
     * Load values for $this->next_cgrps array from database
     *
     * @return void
     */
    private function load_next_cgrps(){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_solution_next_cgrp->table_name;
        /* col names */
        $c     = &$config->data_sql->iquest_solution_next_cgrp->cols;


        $qw = array();
        $qw[] = "n.".$c->solution_id." = ".$data->sql_format($this->id, "s");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select n.".$c->cgrp_id.",
                     n.".$c->condition."
              from ".$t_name." n ".
              $qw;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$c->cgrp_id]] =  new Iquest_Solution_Next_Cgrp($row[$c->cgrp_id], 
                                                                     $row[$c->condition]);
        }
        $res->free();
        $this->next_cgrps = $out;
    }

    /**
     * Get array of IDs of clue groups that are gained by entering key of this solution
     *
     * @return array
     */
    public function get_next_cgrp_ids(){
        if (is_null($this->next_cgrps)){
            $this->load_next_cgrps();
        }

        $cgrp_ids = array();
        foreach($this->next_cgrps as $next_cgrp) $cgrp_ids[] = $next_cgrp->cgrp_id;

        return $cgrp_ids;
    }

    function insert(){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_solution->table_name;
        $tn_name = &$config->data_sql->iquest_solution_next_cgrp->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_solution->cols;
        $cn     = &$config->data_sql->iquest_solution_next_cgrp->cols;
    
        $q = "insert into ".$t_name."(
                    ".$c->id.",
                    ".$c->ref_id.",
                    ".$c->filename.",
                    ".$c->content_type.",
                    ".$c->comment.", 
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
                    ".$data->sql_format($this->name,            "s").",
                    ".$data->sql_format($this->key,             "s").",
                    ".$data->sql_format($this->coin_value,      "n").",
                    ".$data->sql_format($this->stub,            "n").",
                    ".$data->sql_format($this->countdown_start,   "s").",
                    sec_to_time(".$data->sql_format($this->timeout, "n").")
              )";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    
        foreach($this->next_cgrps as $next_cgrp){
            $q = "insert into ".$tn_name."(
                        ".$cn->solution_id.",
                        ".$cn->cgrp_id.",
                        ".$cn->condition."
                  )
                  values(
                        ".$data->sql_format($this->id,              "s").",
                        ".$data->sql_format($next_cgrp->cgrp_id,    "s").",
                        ".$data->sql_format($next_cgrp->condition,  "s")."
                  )";
            
            $res=$data->db->query($q);
            if ($data->dbIsError($res)) throw new DBException($res);
        }
    
    }

    function to_smarty(){
        $out = parent::to_smarty();
        $out['name'] = $this->name;

        return $out;
    }
}
