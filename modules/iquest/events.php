<?php


class Iquest_Events{

    const LOGGED = "team_logged";
    const KEY    = "key_entered";
    const LOGOUT = "logout";
    const GIVEITUP = "giveitup";

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
}

?>
