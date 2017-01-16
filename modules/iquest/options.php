<?php

/**
 *  Class to access iquest options
 */ 
class Iquest_Options{

    const START_TIME      = "start_time";
    const END_TIME        = "end_time";
    const INITIAL_CGRP_ID = "initial_cgrp_id";
    const FINAL_CGRP_ID   = "final_cgrp_id";
    const REVEAL_GOAL_CGRP_ID = "reveal_goal_cgrp_id";
    const WALLET_ACTIVE   = "wallet_active";
    const CHECK_KEY_ORDER = "check_key_order";

    /** Show position of the team among others */
    const SHOW_PLACE      = "show_place";

    /** Show contest graph to teams */
    const SHOW_GRAPH            = "show_graph";
    /** Show names of unknown clue groups in contest graph */
    const SHOW_GRAPH_CGRP_NAMES = "show_graph_cgrp_names";



    const COUNTDOWN_LIMIT_HINT     = "countdown_limit_hint";
    const COUNTDOWN_LIMIT_SOLUTION = "countdown_limit_solution";

    public static $supported_options = array(self::START_TIME,
                                             self::END_TIME,
                                             self::INITIAL_CGRP_ID,
                                             self::FINAL_CGRP_ID,
                                             self::REVEAL_GOAL_CGRP_ID,
                                             self::WALLET_ACTIVE,
                                             self::CHECK_KEY_ORDER,
                                             self::COUNTDOWN_LIMIT_HINT,
                                             self::COUNTDOWN_LIMIT_SOLUTION,
                                             self::SHOW_PLACE,
                                             self::SHOW_GRAPH,
                                             self::SHOW_GRAPH_CGRP_NAMES,
                                             ); 

    public static $set_in_global_ini = array(self::START_TIME,
                                             self::END_TIME,
                                             self::CHECK_KEY_ORDER,
                                             self::COUNTDOWN_LIMIT_HINT,
                                             self::COUNTDOWN_LIMIT_SOLUTION,
                                             self::SHOW_PLACE,
                                             self::SHOW_GRAPH,
                                             self::SHOW_GRAPH_CGRP_NAMES,
                                            );
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

    public static function set($option_name, $option_value){
        global $data, $config;


        // verify time options contain valid datetime
        if ($option_name == self::START_TIME or
            $option_name == self::END_TIME) {
            
            $time = strtotime($option_value);
            
            if (false === $time){
                throw new RuntimeException("Option '$option_name' do not contain valid datetime but: '".$option_value."'");
            }
        }


        /* table's name */
        $t_name = &$config->data_sql->iquest_option->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_option->cols;

        if (!in_array($option_name, self::$supported_options)){
            throw new UnexpectedValueException("Unknown option '$option_name'");
        }

        $q = "delete from ".$t_name."
              where ".$c->name." = ".$data->sql_format($option_name, "s");

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);


        $q = "insert into ".$t_name."(
                ".$c->name.",
                ".$c->value.")
              values(
                ".$data->sql_format($option_name,  "s").",
                ".$data->sql_format($option_value, "s").")";

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        if (!is_null(self::$options)) self::$options[$option_name] = $option_value; 
        
    }
}

?>
