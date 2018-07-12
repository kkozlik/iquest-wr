<?php

class Iquest_Team{
    public $id;
    public $name;
    public $active;
    public $wallet;

    static function fetch_by_id($id){
        $records = static::fetch(array("id" => $id));
        return reset($records);
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

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";


        $q = "select t.".$ct->id.",
                     t.".$ct->name.",
                     t.".$ct->active.",
                     t.".$ct->wallet."
              from ".$tt_name." t ".
              $qw;

        if ($o_order_by) $q .= " order by ".$ct->$o_order_by." ".$o_order_desc;


        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $out = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $out[$row[$ct->id]] =  new Iquest_Team($row[$ct->id], 
                                                   $row[$ct->name],
                                                   $row[$ct->active],
                                                   $row[$ct->wallet]);
        }
        $res->free();
        return $out;
    }

    function __construct($id, $name, $active, $wallet){
        $this->id =         $id;
        $this->name =       $name;
        $this->active =     $active;
        $this->wallet =     $wallet;
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
    
    private function update(){
        global $data, $config;

        /* table's name */
        $tt_name = &$config->data_sql->iquest_team->table_name;
        /* col names */
        $ct      = &$config->data_sql->iquest_team->cols;
        
        $q = "update ".$tt_name." set 
                ".$ct->wallet." = ".$data->sql_format($this->wallet, "n")."
              where ".$ct->id." = ".$data->sql_format($this->id, "n");
        
        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);
    }

    function to_smarty(){
        $out = array();
        $out['id'] = $this->id;
        $out['name'] = $this->name;
        $out['active'] = $this->active;
        $out['wallet'] = $this->wallet;
        return $out;
    }

}
