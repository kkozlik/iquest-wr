<?php

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

        /* table's name */
        $t_name  = &$config->data_sql->iquest_team_rank->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_team_rank->cols;

        $qw = array();
        //  if (isset($opt['ref_id']))  $qw[] = "c.".$cc->ref_id." = ".$data->sql_format($opt['ref_id'], "s");

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
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$c->timestamp]] =  new Iquest_team_rank($row[$c->timestamp],
                                                              json_decode($row[$c->distance], true),
                                                              json_decode($row[$c->rank], true),
                                                              $row[$c->team_id]);
        }
        $res->closeCursor();
        return $out;
    }

    // TODO: make sure this function is executed also when contest is finished - there might be unprocessed entries in iquest_team_finish_distance table
    public static function update_ranks(){
        global $data, $config;

        $t_name = $config->data_sql->iquest_team_finish_distance->table_name;
        $c      = $config->data_sql->iquest_team_finish_distance->cols;

        // This function might behave weird if executed multiple times in parallel.
        // Use semaphore to prevent multiple execution
        $sem = new Shm_Semaphore(__FILE__, "i", 1, 0666);
        if (!$sem->acquire()){
            sw_log(__CLASS__."::".__FUNCTION__."() - Cannot acquire semaphore", PEAR_LOG_ALERT);
            return;
        }

        try{
            $last_rank_obj = self::fetch(array("last"=>true));

            if ($last_rank_obj){
                $last_rank_obj = reset($last_rank_obj);
                $last_update_ts = $last_rank_obj->timestamp;
            }
            else{
                $last_update_ts = time();
            }

            $now = microtime(true); // TODO: do not allow $now > contest end time

            $q = "select ".$c->team_id.",
                        UNIX_TIMESTAMP(".$c->timestamp.") as ".$c->timestamp.",
                        ".$c->distance."
                from ".$t_name."
                where {$c->timestamp} >  FROM_UNIXTIME(".$data->sql_format($last_update_ts, "n").") and
                        {$c->timestamp} <= FROM_UNIXTIME(".$data->sql_format($now,            "n").")
                order by ".$c->timestamp;

            $res=$data->db->query($q);
            $res->setFetchMode(PDO::FETCH_ASSOC);

            while ($row=$res->fetch()){
                self::update_rank(
                    $row[$c->timestamp],
                    $row[$c->team_id],
                    $row[$c->distance]
                );
            }
            $res->closeCursor();
        }
        finally{
            $sem->release();
        }

    }

    private static function update_rank($timestamp, $team_id, $distance){

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

        $last_rank_obj->timestamp = $timestamp;
        $last_rank_obj->team_id = $team_id;
        $last_rank_obj->insert();
    }

    public static function init_db_table(){
        $teams = Iquest_Team::fetch();

        $team_nr = count($teams);
        $distances = array();
        $rank = array();

        foreach($teams as $team){
            $distances[$team->id] = "999999";
            $rank[$team->id] = $team_nr;
        }

        $start_time = Iquest_Options::get(Iquest_Options::START_TIME);

        $team_rank = new Iquest_team_rank($start_time, $distances, $rank, null);
        $team_rank->insert();
    }

    public static function clear_db_table(){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_team_rank->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_team_rank->cols;

        $q = "delete from ".$t_name;

        $res=$data->db->query($q);
    }

    static function add_finish_distance($team_id, $distance){
        global $data, $config;

        /* table's name */
        $t_name = $config->data_sql->iquest_team_finish_distance->table_name;
        /* col names */
        $c      = $config->data_sql->iquest_team_finish_distance->cols;

        $team = Iquest_Team::fetch_by_id($team_id);

        $q = "insert into ".$t_name."(
                    ".$c->team_id.",
                    ".$c->timestamp.",
                    ".$c->distance."
              )
              values(
                    ".$data->sql_format($team_id,  "n").",
                    ".$team->get_utime_sql().",
                    ".$data->sql_format($distance, "n")."
              )";

        $res=$data->db->query($q);
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

        // TODO: add team id to the primary key
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
    }

}
