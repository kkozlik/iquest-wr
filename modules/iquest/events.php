<?php


class Iquest_Events{

    const LOGGED = "team_logged";
    const KEY    = "key_entered";
    const LOGOUT = "logout";
    const GIVEITUP = "giveitup";
    const COIN_SPEND = "coin_spend";
    const BLOW_UP = "blow_up";
    const LOCATION_CHECK = "location_check";
    const TIMESHIFT = "timeshift";

    public static $supported_types = array(self::LOGGED,
                                           self::KEY,
                                           self::LOGOUT,
                                           self::GIVEITUP,
                                           self::COIN_SPEND,
                                           self::BLOW_UP,
                                           self::LOCATION_CHECK,
                                           self::TIMESHIFT,
                                           );

    public $id;
    public $team_id;
    public $timestamp;
    public $type;
    public $success;
    public $data;
    public $team_name;

    private static $extra_data = array();

    public static function set_extra_data($data){
        if (!is_array($data)) throw new UnexpectedValueException("\$data variable is not type of array");
        self::$extra_data = $data;
    }

    static function add_remote_ip(&$data){
        $data['ip'] = $_SERVER["REMOTE_ADDR"];
        if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $data['forwarded_for'] = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
    }

    static function add($type, $success, $event_data){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_event->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_event->cols;

        $team_id = null;
        if (Iquest_auth::has_identity()){
            $team_id = Iquest_auth::get_logged_in_uid();
        }

        if (self::$extra_data) $event_data = array_merge($event_data, self::$extra_data);

        $team = Iquest_auth::get_logged_in_team();
        if ($team){
            $event_data['playtime'] = $team->get_time();
            $event_data['time_shift'] = $team->get_timeshift();
        }

        $eh = &ErrorHandler::singleton();
        $errors = $eh->get_errors_array();
        if ($errors){
            $event_data['errors'] = $errors;
        }

        self::add_remote_ip($event_data);

        $q = "insert into ".$t_name."(
                ".$c->team_id.",
                ".$c->timestamp.",
                ".$c->type.",
                ".$c->success.",
                ".$c->data.")
              values(
                ".$data->sql_format($team_id,                 "N").",
                now(),
                ".$data->sql_format($type,                    "s").",
                ".$data->sql_format($success,                 "n").",
                ".$data->sql_format(json_encode($event_data), "S").")";

        $res=$data->db->query($q);

    }

    static function fetch($opt=array()){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_event->table_name;
        $tt_name = &$config->data_sql->iquest_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_event->cols;
        $ct      = &$config->data_sql->iquest_team->cols;

        $o_order_by = (!empty($opt['order_by'])) ? $opt['order_by'] : "timestamp";
        $o_order_desc = (!empty($opt['order_desc'])) ? "desc" : "";

        $qw = array();
        if (isset($opt['id']))                      $qw[] = "e.".$c->id." = ".$data->sql_format($opt['id'], "n");

        if (isset($opt['filter']['id']))            $qw[] = $opt['filter']['id']->to_sql("e.".$c->id, true);
        if (isset($opt['filter']['team_id']))       $qw[] = $opt['filter']['team_id']->to_sql("e.".$c->team_id, true);
        if (isset($opt['filter']['type']))          $qw[] = $opt['filter']['type']->to_sql("e.".$c->type);
        if (isset($opt['filter']['success']))       $qw[] = $opt['filter']['success']->to_sql("e.".$c->success, true);

        if (isset($opt['filter']['date_from']))     $qw[] = $opt['filter']['date_from']->to_sql("UNIX_TIMESTAMP(e.".$c->timestamp.")", true);
        if (isset($opt['filter']['date_to']))       $qw[] = $opt['filter']['date_to']->to_sql("UNIX_TIMESTAMP(e.".$c->timestamp.")", true);

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        if (!empty($opt['use_pager']) or !empty($opt['count_only'])){
            $q="select count(*) from ".$t_name." e
                    left join ".$tt_name." t on t.".$ct->id." = e.".$c->team_id.$qw;

            $res=$data->db->query($q);
            $res->setFetchMode(PDO::FETCH_NUM);

            $row=$res->fetch();
            $data->set_num_rows($row[0]);
            $res->closeCursor();

            if (!empty($opt['count_only'])) return $row[0];

            /* if act_row is bigger then num_rows, correct it */
            $data->correct_act_row();
        }


        $q="select e.".$c->id.",
                   e.".$c->team_id.",
                   UNIX_TIMESTAMP(e.".$c->timestamp.") as ".$c->timestamp.",
                   e.".$c->type.",
                   e.".$c->success.",
                   e.".$c->data.",
                   t.".$ct->name."
            from ".$t_name." e
                left join ".$tt_name." t on t.".$ct->id." = e.".$c->team_id.$qw;


        if ($o_order_by) $q .= " order by ".$c->$o_order_by." ".$o_order_desc.", {$c->id} $o_order_desc";

        $q .= !empty($opt['use_pager']) ? $data->get_sql_limit_phrase() : "";

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out=array();
        while ($row=$res->fetch()){

            $out[] =  new Iquest_Events($row[$c->id],
                                        $row[$c->team_id],
                                        $row[$c->timestamp],
                                        $row[$c->type],
                                        $row[$c->success],
                                        json_decode($row[$c->data], true),
                                        $row[$ct->name]);

        }
        $res->closeCursor();
        return $out;
    }

    function __construct($id, $team_id, $timestamp, $type, $success, $data, $team_name){
        $this->id =         $id;
        $this->team_id =    $team_id;
        $this->timestamp =  $timestamp;
        $this->type =       $type;
        $this->success =    $success;
        $this->data =       $data;
        $this->team_name =  $team_name;
    }

    private function get_filtered_data($opt = array()){

        $out = array();

        switch($this->type){
        case self::KEY:
            if (isset($this->data['key']))              $out['key']['text'] = $this->data['key'];
            if (isset($this->data['solution']['id'])){
                $out['solution']['text'] = $this->data['solution']['id'];

                if (isset($opt['solution_url']) and !empty($this->data['solution']['filename'])){
                    $out['solution']['url']  = str_replace("<id>", RawURLEncode($this->data['solution']['ref_id']), $opt['solution_url']);
                }
            }

            if (isset($this->data['solution']['coin_value']) and
                $this->data['solution']['coin_value'] != 0){

                $out['coin gained']['text'] = $this->data['solution']['coin_value'];
            }

            if (isset($this->data['solution']['bomb_value']) and
                $this->data['solution']['bomb_value'] != 0){

                $out['bomb gained']['text'] = $this->data['solution']['bomb_value'];
            }

            if (isset($this->data['solution']['show_at']) and $this->data['solution']['show_at'] != 0){
                if ($this->data['solution']['show_at'] < $this->timestamp){
                    $out['timeout']['text'] = "expired";
                }
                else{
                    $out['timeout']['text'] = gmdate("H:i:s", $this->data['solution']['show_at'] - $this->timestamp)." till expire";
                }
            }

            break;
        case self::COIN_SPEND:
            if (isset($this->data['hint']['id']))   {
                $out['hint']['text'] = $this->data['hint']['id'];

                if (isset($opt['hint_url'])){
                    $out['hint']['url']  = str_replace("<id>", RawURLEncode($this->data['hint']['ref_id']), $opt['hint_url']);
                }
            }
            break;
        case self::BLOW_UP:
            if (isset($this->data['solution']['id']))   {
                $out['solution']['text'] = $this->data['solution']['id'];

                if (isset($opt['solution_url']) and !empty($this->data['solution']['filename'])){
                    $out['solution']['url']  = str_replace("<id>", RawURLEncode($this->data['solution']['ref_id']), $opt['solution_url']);
                }
            }
            break;
        case self::LOCATION_CHECK:
            if (isset($this->data['selected_zone']))    $out['selected_zone']['text'] = $this->data['selected_zone'];
            if (!empty($this->data['zones']))           $out['zones']['text'] = implode(", ", $this->data['zones']);
            if (isset($this->data['lat']) and isset($this->data['lon'])){
                $out['lokace']['text'] = sprintf("N%2.5f, E%2.5f", $this->data['lat'], $this->data['lon']);
            }
            break;
        case self::TIMESHIFT:
            $out['timeshift_incremented']['text'] = Iquest_Utils::sec2time($this->data['timeshift_incremented']);
            break;
        case self::LOGGED:
        case self::LOGOUT:
            if (isset($this->data['uname']))        $out['username']['text'] = $this->data['uname'];
            if (isset($this->data['perms']) and in_array('hq', $this->data['perms'])) $out['login to']['text'] = "HQ interface";
            break;
        }

        if (isset($this->data['note']))             $out['note']['text'] = $this->data['note'];
        if (isset($this->data['errors']))           $out['errors']['text'] = implode("; ", $this->data['errors']);
        if (isset($this->data['time_shift']) and $this->data['time_shift']){
            $out['timeshift']['text'] = Iquest_Utils::sec2time($this->data['time_shift']);
        }

        switch($this->type){
        case self::KEY:
            if (isset($this->data['active_clues'])){
                $active_cgrps = array();
                foreach($this->data['active_clues'] as $clue){
                    $active_cgrps[$clue['cgrp_id']] = $clue['cgrp_id'];
                }

                $active_tasks = array();
                foreach($active_cgrps as $cgrp_id){
                    $clue_grp = &Iquest_ClueGrp::by_id($cgrp_id);

                    $active_task = array();
                    if ($clue_grp){
                        $active_task['text'] = $clue_grp->name;
                        if (isset($opt['cgrp_url'])){
                            $active_task['url']  = str_replace("<id>", RawURLEncode($clue_grp->ref_id), $opt['cgrp_url']);
                        }
                    }
                    else{
                        $active_task['text'] = $cgrp_id;
                    }
                    $active_tasks[] = $active_task;
                }

                $out['active tasks']['values'] = $active_tasks;
            }

            if (isset($this->data['active_solutions'])){
                $active_solutions = array();

                foreach($this->data['active_solutions'] as $solution){
                    $active_solution = array();
                    $active_solution['text'] = "{$solution['id']} ({$solution['key']})";
                    if (isset($opt['solution_url']) and !empty($solution['filename'])){
                        $active_solution['url']  = str_replace("<id>", RawURLEncode($solution['ref_id']), $opt['solution_url']);
                    }
                    $active_solutions[] = $active_solution;
                }
                $out['active keys']['values'] = $active_solutions;
            }

            break;
        }


        return $out;
    }

    function to_smarty($opt = array()){
        $out = array();
        $out['id'] = $this->id;
        $out['team_id'] = $this->team_id;
        $out['timestamp'] = date("d.m.Y H:i:s", $this->timestamp);
        $out['playtime'] = isset($this->data['playtime']) ? date("d.m.Y H:i:s", $this->data['playtime']) : null;
        $out['time_shift'] = isset($this->data['time_shift']) ? $this->data['time_shift'] : null;
        $out['type'] = $this->type;
        $out['success'] = $this->success;
        $out['data'] = $this->data;
        $out['data_formated'] = print_r($this->data, true);
        $out['data_filtered'] = $this->get_filtered_data($opt);
        $out['team_name'] = $this->team_name;

        return $out;
    }

}
