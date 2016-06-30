<?php


class Iquest_Events{

    const LOGGED = "team_logged";
    const KEY    = "key_entered";
    const LOGOUT = "logout";
    const GIVEITUP = "giveitup";
    const COIN_SPEND = "coin_spend";

    public static $supported_types = array(self::LOGGED,
                                           self::KEY,
                                           self::LOGOUT,
                                           self::GIVEITUP,
                                           self::COIN_SPEND); 

    public $id;
    public $team_id;
    public $timestamp;
    public $type;
    public $success;
    public $data;
    public $team_name;


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
        if (!empty($_SESSION['auth']) and 
            $_SESSION['auth']->is_authenticated()){
            
            $user_id = $_SESSION['auth']->get_logged_user();
            $team_id = $user_id->get_uid();
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
        if ($data->dbIsError($res)) throw new DBException($res);

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

        if (isset($opt['filter']['team_id']))       $qw[] = $opt['filter']['team_id']->to_sql("e.".$c->team_id);
        if (isset($opt['filter']['type']))          $qw[] = $opt['filter']['type']->to_sql("e.".$c->type);
        if (isset($opt['filter']['success']))       $qw[] = $opt['filter']['success']->to_sql("e.".$c->success);

        if (isset($opt['filter']['date_from']))     $qw[] = $opt['filter']['date_from']->to_sql("UNIX_TIMESTAMP(e.".$c->timestamp.")");
        if (isset($opt['filter']['date_to']))       $qw[] = $opt['filter']['date_to']->to_sql("UNIX_TIMESTAMP(e.".$c->timestamp.")");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        if (!empty($opt['use_pager']) or !empty($opt['count_only'])){
            $q="select count(*) from ".$t_name." e
                    join ".$tt_name." t on t.".$ct->id." = e.".$c->team_id.$qw;
            
            $res=$data->db->query($q);
            if (MDB2::isError($res)) throw new DBException($res);
            $row=$res->fetchRow(MDB2_FETCHMODE_ORDERED);
            $data->set_num_rows($row[0]);
            $res->free();

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
                join ".$tt_name." t on t.".$ct->id." = e.".$c->team_id.$qw;


        if ($o_order_by) $q .= " order by ".$c->$o_order_by." ".$o_order_desc;

        $q .= !empty($opt['use_pager']) ? $data->get_sql_limit_phrase() : "";
            
        $res=$data->db->query($q);
        if (MDB2::isError($res)) throw new DBException($res);
    
        $out=array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){

            $out[] =  new Iquest_Events($row[$c->id], 
                                        $row[$c->team_id],
                                        $row[$c->timestamp],
                                        $row[$c->type],
                                        $row[$c->success],
                                        json_decode($row[$c->data], true),
                                        $row[$ct->name]);

        }
        $res->free();
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
                
                if (isset($opt['hint_url'])){
                    $out['solution']['url']  = str_replace("<id>", RawURLEncode($this->data['solution']['ref_id']), $opt['hint_url']);
                }
            }
            
            if (isset($this->data['solution']['coin_value']) and 
                $this->data['solution']['coin_value'] > 0){

                $out['coin gained']['text'] = $this->data['solution']['coin_value'];
            }

            if (isset($this->data['solution']['show_at'])){
                if ($this->data['solution']['show_at'] < $this->timestamp){
                    $out['timeout']['text'] = "expired";
                }
                else{
                    $out['timeout']['text'] = gmdate("H:i:s", $this->data['solution']['show_at'] - $this->timestamp)." till expire";
                }
            }   

            if (isset($this->data['active_solutions'])){
                $active_solutions = array();
                foreach($this->data['active_solutions'] as $solution){
                    $active_solutions[] = $solution['id'];
                }
                $out['active tasks']['text'] = implode(", ", $active_solutions);
            }


            break;
        case self::COIN_SPEND:
            if (isset($this->data['hint']['id']))   $out['hint']['text'] = $this->data['hint']['id'];
            break;
        }

        if (isset($this->data['errors']))           $out['errors']['text'] = implode("; ", $this->data['errors']);
        
        return $out;
    }

    function to_smarty($opt = array()){
        $out = array();
        $out['id'] = $this->id;
        $out['team_id'] = $this->team_id;
        $out['timestamp'] = date("d.m.Y H:i:s", $this->timestamp);
        $out['type'] = $this->type;
        $out['success'] = $this->success;
        $out['data'] = $this->data;
        $out['data_formated'] = print_r($this->data, true);
        $out['data_filtered'] = $this->get_filtered_data($opt);
        $out['team_name'] = $this->team_name;

        return $out;
    }

}

?>
