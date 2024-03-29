<?php
require_once("Iquest_file.php");
require_once("Iquest_Solution_Next_Cgrp.php");

class Iquest_Solution extends Iquest_file{

    const CD_START_ALL =    "all";
    const CD_START_SINGLE = "single";

    public $next_cgrps;
    public $name;
    public $key;
    public $regexp_key;
    public $timeout;
    public $countdown_start;
    public $coin_value;
    public $bomb_value;

    public $show_at = null;
    public $solved_at = null;
    public $time_shift = null;

    /**
     *  Instantiate solution by key
     */
    static function &by_key($key){
        return Iquest_key::obj_by_key($key, get_called_class());
    }

    /**
     *  Instantiate solution by id
     */
    static function &by_id($id){
        $objs = self::fetch(array("id"=>$id));
        if (!$objs) {
            $null = null; //reference have to be returned
            return $null;
        }

        $obj = reset($objs);
        return $obj;
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
        if (isset($opt['id']))      $qw[] = "c.".$cc->id." = ".$data->sql_format($opt['id'], "s");
        if (isset($opt['ref_id']))  $qw[] = "c.".$cc->ref_id." = ".$data->sql_format($opt['ref_id'], "s");
        if (isset($opt['key']))     $qw[] = "c.".$cc->key." = ".$data->sql_format($opt['key'], "s");
        if (isset($opt['regexp_key'])) $qw[] = "c.".$cc->regexp_key." = ".$data->sql_format($opt['regexp_key'], "n");
        if (isset($opt['team_id'])){
            $team = Iquest_Team::fetch_by_id($opt['team_id']);
            $team_time_sql = $team->get_time_sql();

            // Generate virtual table we are joining with
            $q_join = "select t.".$ct->show_at.",
                              t.".$ct->solved_at.",
                              t.".$ct->time_shift.",
                              t.".$ct->team_id.",
                              t.".$ct->solution_id."
                       from ".$tt_name." t
                       where t.".$ct->team_id." = ".$data->sql_format($opt['team_id'], "N");

            $join[] = " left join (".$q_join.") tt on c.".$cc->id." = tt.".$ct->solution_id;

            $cols .= ", UNIX_TIMESTAMP(tt.".$ct->show_at.") as ".$ct->show_at;
            $cols .= ", UNIX_TIMESTAMP(tt.".$ct->solved_at.") as ".$ct->solved_at;
            $cols .= ", time_to_sec(tt.".$ct->time_shift.") as ".$ct->time_shift;

            if (!empty($opt['accessible'])){
                $qw[] = "tt.".$ct->show_at." <= $team_time_sql";
                $qw[] = "UNIX_TIMESTAMP(tt.".$ct->show_at.") != 0";
            }

            $order = " order by ".$ct->show_at." desc";
        }


        if (isset($opt['filter']['coin_value']))  $qw[] = $opt['filter']['coin_value']->to_sql_float("c.".$cc->coin_value);
        if (isset($opt['filter']['bomb_value']))  $qw[] = $opt['filter']['bomb_value']->to_sql_float("c.".$cc->bomb_value);


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
                     c.".$cc->regexp_key.",
                     c.".$cc->coin_value.",
                     c.".$cc->bomb_value.
                     $cols."
              from ".$tc_name." c ".implode(" ", $join).
              $qw.$order;

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            if (!isset($row[$ct->show_at])) $row[$ct->show_at] = null;
            if (!isset($row[$ct->solved_at])) $row[$ct->solved_at] = null;
            if (!isset($row[$ct->time_shift])) $row[$ct->time_shift] = null;

            $out[$row[$cc->id]] =  new Iquest_Solution($row[$cc->id],
                                                       $row[$cc->ref_id],
                                                       $row[$cc->filename],
                                                       $row[$cc->content_type],
                                                       $row[$cc->comment],
                                                       $row[$cc->name],
                                                       $row[$cc->timeout],
                                                       $row[$cc->countdown_start],
                                                       $row[$cc->key],
                                                       $row[$cc->regexp_key],
                                                       $row[$cc->coin_value],
                                                       $row[$cc->bomb_value],
                                                       $row[$ct->show_at],
                                                       $row[$ct->solved_at],
                                                       $row[$ct->time_shift]);
        }
        $res->closeCursor();
        return $out;
    }


    /**
     *  Fetch solutions by the clue-group-id that leads to the solution.
     *
     *  If team_id provided the fetched solutions contain correct
     *  'show_at' and 'solved_at' attributes for the team.
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

        // needed for 'solved-at' attribute
        $q3 = "select UNIX_TIMESTAMP(t2.".$ct->solved_at.")
               from ".$tt_name." t2
               where t2.".$ct->team_id." = ".$data->sql_format($team_id, "N")." and
                     t2.".$ct->solution_id."=s.".$cs->id;

        $q = "select s.".$cs->id.",
                     s.".$cs->ref_id.",
                     s.".$cs->filename.",
                     s.".$cs->content_type.",
                     time_to_sec(s.".$cs->timeout.") as ".$cs->timeout.",
                     s.".$cs->countdown_start.",
                     s.".$cs->comment.",
                     s.".$cs->name.",
                     s.".$cs->key.",
                     s.".$cs->regexp_key.",
                     s.".$cs->coin_value.",
                     s.".$cs->bomb_value.",
                     (".$q2.") as ".$ct->show_at.",
                     (".$q3.") as ".$ct->solved_at."
              from ".$ts_name." s
                join ".$tcs_name." cs on cs.".$ccs->solution_id."=s.".$cs->id."
                join ".$tc_name." c on c.".$cc->id."=cs.".$ccs->clue_id.
              $qw;

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$cs->id]] =  new Iquest_Solution($row[$cs->id],
                                                       $row[$cs->ref_id],
                                                       $row[$cs->filename],
                                                       $row[$cs->content_type],
                                                       $row[$cs->comment],
                                                       $row[$cs->name],
                                                       $row[$cs->timeout],
                                                       $row[$cs->countdown_start],
                                                       $row[$cs->key],
                                                       $row[$cs->regexp_key],
                                                       $row[$cs->coin_value],
                                                       $row[$cs->bomb_value],
                                                       $row[$ct->show_at],
                                                       $row[$ct->solved_at]);
        }
        $res->closeCursor();
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

        $team = Iquest_Team::fetch_by_id($team_id);
        $team_time_sql = $team->get_time_sql();

        $qw = array();

        if (is_array($id))  $qw[] = $data->get_sql_in($ct->solution_id, $id, true);
        else                $qw[] = $ct->solution_id." = ".$data->sql_format($id, "s");

        $qw[] = $ct->team_id." = ".$data->sql_format($team_id, "n");
        $qw[] = $ct->show_at." > $team_time_sql";
        $qw = " where ".implode(' and ', $qw);

        $q = "update $tt_name
              set ".$ct->show_at."=FROM_UNIXTIME(0)".$qw;

        $res=$data->db->query($q);

        return true;
    }

    /**
     *  Schedule displaying of solution for team $team_id.
     *  This function do not check whether solution is already scheduled!
     */
    static function schedule($id, $team_id, $timeout, $open_ts = null){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_solution_team->cols;

        $team = Iquest_Team::fetch_by_id($team_id);
        if (!$open_ts) $open_ts = $team->get_time();

        $q="insert into ".$t_name." (
                    ".$c->solution_id.",
                    ".$c->team_id.",
                    ".$c->show_at.",
                    ".$c->solved_at.",
                    ".$c->time_shift.")
            values (".$data->sql_format($id,        "s").",
                    ".$data->sql_format($team_id,   "n").",
                    FROM_UNIXTIME(".$data->sql_format($open_ts + $timeout, "n")."),
                    FROM_UNIXTIME(0),
                    {$team->get_timeshift_sql()})";

        $res=$data->db->query($q);

        return true;
    }

    /**
     *  Update schedule displaying of solution for team $team_id.
     *  This function do not check whether solution is already scheduled!
     */
    public static function schedule_update($id, $team_id, $timeout){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_solution_team->cols;

        $team = Iquest_Team::fetch_by_id($team_id);
        $team_time_sql = $team->get_time_sql();
        $team_time = $team->get_time();
        $team_timeshift_sql = $team->get_timeshift_sql();

        $qw = array();
        $qw[] = $c->solution_id." = ".$data->sql_format($id, "s");
        $qw[] = $c->team_id." = ".$data->sql_format($team_id, "n");
        $qw[] = $c->show_at." > $team_time_sql";
        $qw = " where ".implode(' and ', $qw);

        $q = "update $t_name set
                {$c->show_at}    = FROM_UNIXTIME(".$data->sql_format($team_time + $timeout, "n")."),
                {$c->time_shift} = $team_timeshift_sql
                ".$qw;

        $res=$data->db->query($q);

        return true;
    }

    /**
     * Mark given sulution as solved by the team
     *
     * @param string $id
     * @param int $team_id
     * @return void
     */
    static function mark_solved($id, $team_id){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_solution_team->cols;

        $qw = array();
        $qw[] = $c->solution_id." = ".$data->sql_format($id, "s");
        $qw[] = $c->team_id." = ".$data->sql_format($team_id, "n");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        // Check if a record with given team_id and solution_id exists
        $q = "select count(*) from ".$t_name.$qw;

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_NUM);

        $row=$res->fetch();
        $res->closeCursor();

        $record_exists = (bool)$row[0];

        $team = Iquest_Team::fetch_by_id($team_id);
        $team_time_sql = $team->get_time_sql();
        $team_timeshift_sql = $team->get_timeshift_sql();

        if ($record_exists){
            $q = "update $t_name set
                    {$c->solved_at}  = $team_time_sql,
                    {$c->time_shift} = $team_timeshift_sql
                 $qw";
        }
        else{
            $q="insert into ".$t_name." (
                        ".$c->solution_id.",
                        ".$c->team_id.",
                        ".$c->show_at.",
                        ".$c->solved_at.",
                        ".$c->time_shift.")
                values (".$data->sql_format($id,        "s").",
                        ".$data->sql_format($team_id,   "n").",
                        FROM_UNIXTIME(0),
                        $team_time_sql,
                        $team_timeshift_sql)";
        }

        $res=$data->db->query($q);
    }

    static function get_next_scheduled($team_id){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_solution_team->cols;

        $team = Iquest_Team::fetch_by_id($team_id);
        $team_time_sql = $team->get_time_sql();

        $qw = array();
        $qw[] = $c->show_at." > $team_time_sql";
        $qw[] = $c->team_id." = ".$data->sql_format($team_id, "n");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select UNIX_TIMESTAMP(".$c->show_at.") as ".$c->show_at.",
                     ".$c->solution_id."
              from ".$t_name.$qw;

        $q .= " order by ".$c->show_at;

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = [];
        while ($row=$res->fetch()){
            $out[]=array("show_at"     => $row[$c->show_at],
                         "solution_id" => $row[$c->solution_id]);
        }
        $res->closeCursor();

        return $out;
    }

    public static function get_scheduled_solutions($team_id){
        static $scheduled_solutions = [];

        if (!isset($scheduled_solutions[$team_id])){
            $scheduled_solutions[$team_id] = Iquest_Solution::get_next_scheduled($team_id);
        }

        return $scheduled_solutions[$team_id];
    }


    /**
     * Verify whether the given key can be entered be team. If so, return the
     * solution object with given key. Otherwise null is returned.
     *
     * @param string $key
     * @param int $team_id
     * @return Iquest_Solution|null
     */
    public static function verify_key($key, $team_id, $opts=array()){
        global $lang_str;

        if (empty($key)){
            $err = $lang_str['iquest_err_key_empty'];
            if (!empty($opts['err']['key_empty'])) $err = $opts['err']['key_empty'];

            ErrorHandler::add_error($err);
            self::log_invalid_key($key, $team_id);
            return null;
        }

        $solution = Iquest_Solution::by_key($key);

        if (!$solution){
            $err = $lang_str['iquest_err_key_invalid'];
            if (!empty($opts['err']['key_invalid'])) $err = $opts['err']['key_invalid'];

            ErrorHandler::add_error($err);
            self::log_invalid_key($key, $team_id);
            return null;
        }

        if ($solution->is_solved($team_id)){
            $err = $lang_str['iquest_err_key_dup'];
            if (!empty($opts['err']['key_dup'])) $err = $opts['err']['key_dup'];

            ErrorHandler::add_error($err);
            self::log_invalid_key($key, $team_id);
            return null;
        }

        if (array_key_exists(Iquest_Options::CHECK_KEY_ORDER, $opts)){
            $check_order = $opts[Iquest_Options::CHECK_KEY_ORDER];
        }
        else{
            $check_order = Iquest_Options::get(Iquest_Options::CHECK_KEY_ORDER);
        }

        // Check that the keys are entered in correct order, check that
        // the team has a clue to this key
        if ($check_order and
            !$solution->is_reachable($team_id)){

            $err = $lang_str['iquest_err_key_not_reachable'];
            if (!empty($opts['err']['key_not_reachable'])) $err = $opts['err']['key_not_reachable'];

            ErrorHandler::add_error($err);
            self::log_invalid_key($key, $team_id);
            return null;
        }

        return $solution;
    }


    private static function log_invalid_key($key, $team_id){
        $event_data = array("key" => $key);
        $event_data["diacritics_key"] = remove_diacritics($key);
        $event_data["cannon_key"] = Iquest_Solution::canonicalize_key($key);

        $graph = new Iquest_solution_graph($team_id);
        $event_data["active_solutions"] = $graph->get_active_solutions();
        $event_data["active_clues"] = $graph->get_active_clues();

        Iquest_Events::add(Iquest_Events::KEY,
                            false,
                            $event_data);
    }

    function __construct($id, $ref_id, $filename, $content_type, $comment, $name,
                         $timeout, $countdown_start, $key, $regexp_key, $coin_value, $bomb_value,
                         $show_at=null, $solved_at=null, $time_shift=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);

        $this->name = $name;
        $this->timeout = $timeout;
        $this->countdown_start = $countdown_start;
        $this->key = $key;
        $this->regexp_key = $regexp_key;
        $this->coin_value = $coin_value;
        $this->bomb_value = (float)$bomb_value;
        $this->show_at = $show_at;
        $this->solved_at = $solved_at;
        $this->time_shift = $time_shift;
    }


    /**
     * Set the solution as solved and open new tasks
     *
     * @param int $team_id
     * @param string $key   - just for log purpose
     * @return void
     */
    public function solve($team_id, $key=null){
        // make sure the show_at value is present in the solution object
        $this->get_show_at($team_id);

        Iquest_Events::add(Iquest_Events::KEY,
                           true,
                           array("key" => $key,
                                 "solution" => $this));

        Iquest::solution_found($this, $team_id);
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
     * Return true if the solution is already solved by given team
     *
     * @param string $team_id
     * @return bool
     */
    function is_solved($team_id){
        if (is_null($this->solved_at)) $this->query_team_info($team_id);

        return (bool)$this->solved_at;
    }

    /**
     *  Retrieve the show_at value for given team
     */
    function get_show_at($team_id){

        if (is_null($this->show_at)) $this->query_team_info($team_id);

        return $this->show_at;
    }

    /**
     * Query the iquest_solution_team DB table for show_at and solved_at values
     *
     * @param int $team_id
     * @return void
     */
    private function query_team_info($team_id){
        $opt = array(
            "team_id" =>        $team_id,
            "solution_id" =>    $this->id,
        );

        $res = static::fetch_solution_team($opt);

        if (isset($res[$this->id][$team_id])){
            $row = $res[$this->id][$team_id];
            $this->show_at = $row["show_at"];
            $this->solved_at = $row["solved_at"];
        }
        else{
            $this->show_at = 0;
            $this->solved_at = 0;
        }
    }

    /**
     * Query the iquest_solution_team DB table for show_at and solved_at values
     *
     * @param array $opt
     * @return array  [{show_at: ... , solved_at: ...}, ...]
     */
    public static function fetch_solution_team($opt=array()){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_solution_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_solution_team->cols;

        $qw = array();
        if (isset($opt['team_id']))      $qw[] = $c->team_id." = ".$data->sql_format($opt['team_id'], "n");
        if (isset($opt['solution_id']))  $qw[] = $c->solution_id." = ".$data->sql_format($opt['solution_id'], "s");


        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select $c->team_id,
                     $c->solution_id,
                     UNIX_TIMESTAMP(".$c->show_at.") as ".$c->show_at.",
                     UNIX_TIMESTAMP(".$c->solved_at.") as ".$c->solved_at.",
                     time_to_sec(".$c->time_shift.") as ".$c->time_shift."
              from ".$t_name.$qw;

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$c->solution_id]][$row[$c->team_id]] = array(
                "show_at"   => $row[$c->show_at],
                "solved_at" => $row[$c->solved_at],
                "time_shift" => $row[$c->time_shift],
            );
        }
        $res->closeCursor();
        return $out;
    }


    /**
     * Load values for $this->next_cgrps array from database
     *
     * @return void
     */
    private function load_next_cgrps(){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_solution_next_cgrp->table_name;
        $tc_name = &$config->data_sql->iquest_cgrp->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_solution_next_cgrp->cols;
        $cc      = &$config->data_sql->iquest_cgrp->cols;


        $qw = array();
        $qw[] = "n.".$c->solution_id." = ".$data->sql_format($this->id, "s");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select n.".$c->cgrp_id.",
                     n.".$c->condition."
              from $t_name n left join $tc_name c on n.".$c->cgrp_id." = c.".$cc->id.
              $qw." order by c.".$cc->ordering;

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$c->cgrp_id]] =  new Iquest_Solution_Next_Cgrp($row[$c->cgrp_id],
                                                                     $row[$c->condition]);
        }
        $res->closeCursor();
        $this->next_cgrps = $out;
    }

    /**
     * Get array of IDs of clue groups that are gained by entering key of this solution
     *
     * @return array
     */
    public function get_next_cgrp_ids(){

        $cgrp_ids = array();
        foreach($this->get_next_cgrps() as $next_cgrp) $cgrp_ids[] = $next_cgrp->cgrp_id;

        return $cgrp_ids;
    }

    /**
     * Get array of IDs of clue groups that are gained by entering key of this solution
     * and met their condition
     *
     * @param int $team_id
     * @return array
     */
    public function get_next_cgrp_ids_cond($team_id){

        $cgrp_ids = array();
        foreach($this->get_next_cgrps() as $next_cgrp) {
            if ($next_cgrp->evalueateCondition($this, $team_id)) $cgrp_ids[] = $next_cgrp->cgrp_id;
        }

        return $cgrp_ids;
    }

    /**
     * Get array of Iquest_Solution_Next_Cgrp objects that are gained by entering key of this solution
     *
     * @return array
     */
    public function get_next_cgrps(){
        if (is_null($this->next_cgrps)){
            $this->load_next_cgrps();
        }

        return $this->next_cgrps;
    }

    /**
     * Set next clue groups of the solution
     *
     * @param array $next_cgrps array of Iquest_Solution_Next_Cgrp objects
     * @return void
     */
    public function set_next_cgrps($next_cgrps){

        $this->next_cgrps = array();
        foreach($next_cgrps as $next_cgrp){
            $this->next_cgrps[$next_cgrp->cgrp_id] = $next_cgrp;
        }
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
                    ".$c->regexp_key.",
                    ".$c->coin_value.",
                    ".$c->bomb_value.",
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
                    ".$data->sql_format($this->regexp_key,      "n").",
                    ".$data->sql_format($this->coin_value,      "n").",
                    ".$data->sql_format($this->bomb_value,      "n").",
                    ".$data->sql_format($this->countdown_start, "s").",
                    sec_to_time(".$data->sql_format($this->timeout, "n").")
              )";

        $res=$data->db->query($q);

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
        }

    }

    function to_smarty(){
        $out = parent::to_smarty();
        $out['name'] = $this->name;

        return $out;
    }
}
