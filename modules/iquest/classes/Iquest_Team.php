<?php

class Iquest_Team{
    public $id;
    public $name;
    public $username;
    public $active;
    public $wallet;
    public $bomb;
    public $tracker_id;
    public $time_shift;

    static function fetch_by_id($id){
        static $team_cache = [];

        if (isset($team_cache[$id])) return $team_cache[$id];

        $records = static::fetch(array("id" => $id));
        $team_cache[$id] = reset($records);

        return $team_cache[$id];
    }

    static function fetch($opt=array()){
        global $data, $config;

        /* table's name */
        $tt_name = &$config->data_sql->iquest_team->table_name;
        /* col names */
        $ct      = &$config->data_sql->iquest_team->cols;

        $o_order_by = (isset($opt['order_by'])) ? $opt['order_by'] : "";
        $o_order_desc = (!empty($opt['order_desc'])) ? "desc" : "";

        $qw = array();
        if (isset($opt['id']))          $qw[] = "t.".$ct->id." = ".$data->sql_format($opt['id'], "n");
        if (isset($opt['username']))    $qw[] = "t.".$ct->username." = ".$data->sql_format($opt['username'], "s");
        if (isset($opt['name']))        $qw[] = "t.".$ct->name." = ".$data->sql_format($opt['name'], "s");
        if (isset($opt['password']))    $qw[] = "t.".$ct->passwd." = ".$data->sql_format($opt['password'], "s");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select t.".$ct->id.",
                     t.".$ct->username.",
                     t.".$ct->name.",
                     t.".$ct->active.",
                     t.".$ct->wallet.",
                     t.".$ct->bomb.",
                     t.".$ct->tracker_id.",
                     time_to_sec(t.".$ct->time_shift.") as time_shift
              from ".$tt_name." t ".
              $qw;

        if ($o_order_by) $q .= " order by ".$ct->$o_order_by." ".$o_order_desc;


        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$ct->id]] =  new Iquest_Team($row[$ct->id],
                                                   $row[$ct->username],
                                                   $row[$ct->name],
                                                   $row[$ct->active],
                                                   $row[$ct->wallet],
                                                   $row[$ct->bomb],
                                                   $row[$ct->tracker_id],
                                                   $row['time_shift']);
        }
        $res->closeCursor();
        return $out;
    }

    /**
     *  Check password of logged team
     */
    public static function checkpass($username, $password){

        $opt = [
            'username' => $username,
            'password' => $password
        ];

        $team = self::fetch($opt);
        if (!$team) return null;

        $team = reset($team);
        return $team;
    }

    function __construct($id, $username, $name, $active, $wallet, $bomb, $tracker_id, $time_shift){
        $this->id =         $id;
        $this->username =   $username;
        $this->name =       $name;
        $this->active =     (int)$active;
        $this->wallet =     (float)$wallet;
        $this->bomb =       (float)$bomb;
        $this->tracker_id = $tracker_id;
        $this->time_shift = (int)$time_shift;
    }

    public function is_active(){
        return $this->active;
    }

    public function activate($val){
        $this->active = (bool)$val;
        $this->update();
    }

    function wallet_add_money($value){
        $this->wallet += $value;
        $this->update();
    }

    function wallet_spend_money($value){

        if ($this->wallet < $value) {
            throw new UnderflowException("Cannot spend $value, not enought money in the wallet. Wallet value: {$this->wallet}");
        }

        $this->wallet -= $value;
        $this->update();
    }

    function add_bomb($value){
        $this->bomb += $value;
        $this->update();
    }

    function remove_bomb($value){

        if ($this->bomb < $value) {
            throw new UnderflowException("Cannot remove bomb ($value), not enought bombs available. Bomb value: {$this->bomb}");
        }

        $this->bomb -= $value;
        $this->update();
    }

    public function shift_time($secs){
        $this->time_shift += $secs;
        $this->update();
    }

    public function get_time(){
        return time() + $this->time_shift;
    }

    private function update(){
        global $data, $config;

        /* table's name */
        $tt_name = &$config->data_sql->iquest_team->table_name;
        /* col names */
        $ct      = &$config->data_sql->iquest_team->cols;

        $q = "update ".$tt_name." set
                ".$ct->active."     = ".$data->sql_format($this->active, "n").",
                ".$ct->wallet."     = ".$data->sql_format($this->wallet, "n").",
                ".$ct->bomb."       = ".$data->sql_format($this->bomb, "n").",
                ".$ct->time_shift." = sec_to_time(".$data->sql_format($this->time_shift, "n").")
              where ".$ct->id." = ".$data->sql_format($this->id, "n");

        $res=$data->db->query($q);
    }

    function to_smarty(){
        $out = array();
        $out['id'] = $this->id;
        $out['name'] = $this->name;
        $out['active'] = $this->active;
        $out['wallet'] = $this->wallet;
        $out['bomb']   = $this->bomb;
        $out['tracker_id'] = $this->tracker_id;
        $out['time_shift'] = $this->time_shift;
        return $out;
    }

}
