<?php

/**
 *  Class to access iquest options
 */ 
class Iquest_Options{

    const START_TIME      = "start_time";
    const END_TIME        = "end_time";
    const INITIAL_CGRP_ID = "initial_cgrp_id";
    const FINAL_TASK_ID   = "final_task_id";

    /** Options cache */
    private static $options = null;

    /**
     *  Load options from the DB
     */         
    private static function load(){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_option->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_option->cols;


        $q = "select ".$c->name.",
                     ".$c->value."
              from ".$t_name;

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        self::$options = array();
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            self::$options[$row[$c->name]] = $row[$c->value];
        }
        $res->free();

        return true;
    }


    /**
     *  Return option with given name
     */         
    public static function get($option_name){
        // if options are not loaded yet, load them
        if (is_null(self::$options)) self::load();
        
        // check if the option name is valid
        if (!array_key_exists($option_name, self::$options)){
            throw new RuntimeException("Unknown option: '$option_name'");
        }

        // time options convert to unix timestamp
        if ($option_name == self::START_TIME or
            $option_name == self::END_TIME) {
            
            $time = strtotime(self::$options[$option_name]);
            
            if (false === $time){
                throw new RuntimeException("Option '$option_name' do not contain valid datetime but: '".self::$options[$option_name]."'");
            }
            
            return $time;
        }
        
        return self::$options[$option_name];
    }

}

?>