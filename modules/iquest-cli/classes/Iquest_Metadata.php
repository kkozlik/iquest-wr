<?php

class Iquest_Metadata{

    const METADATA_FILE = "metadata.ini";
    const SOLUTION_ID_PREFIX = "KEY-";

    const LAST_CLUE = "L";

    /** Name of directives in ini file that set the option */
    public static $option_2_ini_file_directive = array(
        Iquest_Options::INITIAL_CGRP_IDS      => "start",
        Iquest_Options::FINAL_CGRP_ID         => "final",
        Iquest_Options::REVEAL_GOAL_CGRP_ID   => "reveal_goal",
    );

    private $data;
    private $parent_metadata;

    private static $default_cfg = array(
        "clue_pattern"      => "indicie[0-9-]+\\..*",
        "clue_coin_pattern" => "indicie[0-9-]+c\\..*",
        "clue_special_pattern" => "indicie[0-9-]+s\\..*",
        "hint_pattern"      => "napoveda[0-9-]+.*",
        "solution_pattern"  => "reseni.*",
    );

    private static function timeout_to_sec($timeout, $what){

        $timeout = trim($timeout);

        if (!preg_match("/^([0-9]+)([hms]?)$/", $timeout, $matches)){
            throw new Iquest_InvalidConfigException("Invalid timeout value: '$timeout' for $what");
        }

        $value = $matches[1];
        $unit = $matches[2];

        if (!$unit) $unit = "m";

        switch($unit){
        case "s": return (int)$value;
        case "m": return 60*(int)$value;
        case "h": return 3600*(int)$value;
        default:
            throw new Iquest_InvalidConfigException("Invalid unit of timeout: '$timeout' for $what");
        }

    }

    function __construct($dir, $parent_metadata=null){
        $this->data             = $this->get_metadata($dir.self::METADATA_FILE);
        $this->parent_metadata  = $parent_metadata;
    }

    private function get_metadata($file){
        if (!file_exists($file)) throw new Iquest_noMetaDataException();

        $metadata = parse_ini_file($file, true);
        if (false === $metadata){
            throw new Iquest_MetadataOpenException("Cannot open or read metadata file: ".$file);
        }
        return $metadata;
    }

    function get_cfg(){
        // start with default configuration
        if ($this->parent_metadata){
            $cfg = $this->parent_metadata->get_cfg();
        }
        else{
            $cfg = self::$default_cfg;
        }

        // replace all values specified in metadata file
        foreach($cfg as $k => $v){
            if (isset($this->data['cfg'][$k])){
                $cfg[$k] = $this->data['cfg'][$k];
            }
        }

        return $cfg;
    }

    /**
     *  Return charset used in for specific file
     *
     *  Function first look to [$filename] section and if it do not contain
     *  the charset, it look to [general] section.
     */
    function get_charset($filename){

        $filename = basename($filename);
        if (isset($this->data["file:".$filename]['charset'])){
            return $this->data["file:".$filename]['charset'];
        }

        if (isset($this->data['general']['charset'])){
            return $this->data['general']['charset'];
        }

        if ($this->parent_metadata){
            return $this->parent_metadata->get_charset($filename);
        }

        throw new Iquest_InvalidConfigException("Charset not specified in metadata file.");
    }

    /**
     *  Return MIME type used in for specific file
     *
     *  Function first look to [$filename] section and if it do not contain
     *  the MIME type it call Iquest_file::get_mime_type()
     */
    function get_mime_type($filename){

        $filename = basename($filename);
        if (isset($this->data["file:".$filename]['mime_type'])){
            return $this->data["file:".$filename]['mime_type'];
        }

        return Iquest_file::get_mime_type($filename);
    }

    function get_cgrp_id(){
        if (!isset($this->data['general']['id'])){
            throw new Iquest_InvalidConfigException("ID not specified in metadata file.");
        }
        return $this->data['general']['id'];
    }

    function get_cgrp_name(){

        if (!isset($this->data['cgrp']['name'])){
            throw new Iquest_InvalidConfigException("name for clue group is not set.");
        }

        $charset = $this->get_charset(self::METADATA_FILE);
        if ($charset != "UTF-8"){
            return iconv($charset, "UTF-8", $this->data['cgrp']['name']);
        }

        return $this->data['cgrp']['name'];
    }

    function get_cgrp_order(){

        if (!isset($this->data['cgrp']['order'])){
            Console::log( "*** WARNING: order for clue group is not set.", Console::YELLOW);
            return 0;
        }

        return $this->data['cgrp']['order'];
    }

    function is_start_cgrp(){
        return !empty($this->data['cgrp']['start']);
    }

    function is_reveal_goal_cgrp(){
        return !empty($this->data['cgrp']['reveal_goal']);
    }

    function is_final_cgrp(){
        return !empty($this->data['cgrp']['final']);
    }

    function get_solution_id(){
        if (isset($this->data['solution']['id'])){
            return $this->data['solution']['id'];
        }

        $next_cgrps = $this->get_solution_next_cgrp();
        if ($next_cgrps) {
            $first = reset($next_cgrps);

            return self::SOLUTION_ID_PREFIX.$first->cgrp_id;
        }

        return $this->get_cgrp_id();
    }

    function get_solution_name(){

        if (!isset($this->data['solution']['name'])){
            return $this->get_cgrp_name();
        }

        $charset = $this->get_charset(self::METADATA_FILE);
        if ($charset != "UTF-8"){
            return iconv($charset, "UTF-8", $this->data['solution']['name']);
        }

        return $this->data['solution']['name'];
    }

    function get_solution_key(){
        if (!isset($this->data['solution']['key'])){
            throw new Iquest_InvalidConfigException("Solution key is not set.");
        }

        $charset = $this->get_charset(self::METADATA_FILE);
        if ($charset != "UTF-8"){
            return iconv($charset, "UTF-8", $this->data['solution']['key']);
        }

        return $this->data['solution']['key'];
    }

    function is_key_set(){
        return !empty($this->data['solution']['key']);
    }

    /**
     *  Get canonicalized key
     */
    function get_solution_key_canon(){
        return Iquest_Solution::canonicalize_key($this->get_solution_key());
    }

    function get_solution_timeout(){
        if (!isset($this->data['solution']['timeout'])){
            return 0;
        }
        return self::timeout_to_sec($this->data['solution']['timeout'], "solution");
    }

    function get_solution_timeout_str(){
        $timeout = $this->get_solution_timeout();
        if (!$timeout) return "------";

        $secs = $timeout % 60; $timeout /= 60;
        $mins = $timeout % 60; $timeout /= 60;
        $hours = $timeout;

        return sprintf("%02d:%02d:%02d", $hours, $mins, $secs);
    }

    function get_solution_countdown_start(){
        if (!isset($this->data['solution']['countdown_start'])){
            return Iquest_Solution::CD_START_ALL;
        }

        if (!in_array($this->data['solution']['countdown_start'],
                      array(Iquest_Solution::CD_START_ALL,
                            Iquest_Solution::CD_START_SINGLE))){

            throw new Iquest_InvalidConfigException("Invalid value '{$this->data['solution']['countdown_start']}' for countdown_start.");
        }

        return $this->data['solution']['countdown_start'];
    }

    function get_solution_coin_value(){
        if (!isset($this->data['solution']['coin_value'])){
            return 0;
        }
        return round($this->data['solution']['coin_value'],2);
    }

    function get_solution_traccar_zones(){
        if (!isset($this->data['solution']['traccar_zones'])){
            return array();
        }
        if (!is_array($this->data['solution']['traccar_zones'])){
            $this->data['solution']['traccar_zones'] = array($this->data['solution']['traccar_zones']);
        }

        return $this->data['solution']['traccar_zones'];
    }

    function get_solution_traccar_condition(){
        if (!isset($this->data['solution']['traccar_condition'])){
            return null;
        }

        return $this->data['solution']['traccar_condition'];
    }


    /**
     * Return next clue group of a solution
     *
     * @return array of Iquest_Solution_Next_Cgrp
     */
    function get_solution_next_cgrp(){
        if (!isset($this->data['solution']['next_cgrp_id'])){
            return array();
        }

        if (!is_array($this->data['solution']['next_cgrp_id'])){
            $this->data['solution']['next_cgrp_id'] = array($this->data['solution']['next_cgrp_id']);
        }

        $output = array();
        foreach($this->data['solution']['next_cgrp_id'] as $next_cgrp_str){
            $output[] = $this->parse_next_cgrp($next_cgrp_str);
        }

        return $output;
    }

    private function parse_next_cgrp($val){
        $parts = explode(",", $val, 2);
        if (empty($parts[1])) $condition = null;
        else                  $condition = trim($parts[1]);

        $next_cgrp_id = trim($parts[0]);

        return new Iquest_Solution_Next_Cgrp($next_cgrp_id, $condition);
    }


    /**
     *  Return number of hidden clues
     */
    function get_clue_hidden_nr(){
        if (!isset($this->data['cgrp']['hidden_clues'])) return 0;

        return (int)$this->data['cgrp']['hidden_clues'];
    }

    function get_clue_point_to($clue_nr){
        // If no point_to is specifed for the clue, assume it point to
        // solution specified in this directory
        if (!isset($this->data['clue']['point_to'][$clue_nr])){
            return array($this->get_solution_id());
        }

        $point_to = $this->data['clue']['point_to'][$clue_nr];

        // If point_to is "-" or "" it point to no solution
        if ($point_to == "-" or $point_to == "") return array();

        // Otherwise create array from the comma separated list
        $point_to_values = explode(",", $point_to);
        $point_to_values = array_map("trim", $point_to_values);
        return $point_to_values;
    }


    function get_clue_type($clue_nr, $filename){
        $cfg = $this->get_cfg();

        $clue_type = Iquest_Clue::TYPE_REGULAR;
        if (!$filename){
            $clue_type = Iquest_Clue::TYPE_HIDDEN;
        }
        elseif (preg_match("/".$cfg["clue_coin_pattern"]."/i", basename($filename))){
            $clue_type = Iquest_Clue::TYPE_COIN;
        }
        elseif (preg_match("/".$cfg["clue_special_pattern"]."/i", basename($filename))){
            $clue_type = Iquest_Clue::TYPE_SPECIAL;
        }

        return $clue_type;
    }


    /**
     *  Retrieve the clue ID for given hint number
     *  Return null if clue ids are not specified
     *  Return self::LAST_CLUE as a special value if last defined clue shall be used
     */
    function get_hint_clueid($hint_nr){
        if (!isset($this->data['hint']['clue_IDs']) or
            trim($this->data['hint']['clue_IDs']) == ""){

            return null;
        }

        $clue_IDs = explode(",", $this->data['hint']['clue_IDs']);

        if (trim($clue_IDs[0]) == ""){
            return null;
        }

        if (!isset($clue_IDs[$hint_nr])){
            $clue_ID = (int)end($clue_IDs);
            $clue_ID = $this->get_cgrp_id()."-".$clue_ID;
        }
        else{
            if ($clue_IDs[$hint_nr] == 'L') return self::LAST_CLUE;

            $clue_ID = (int)$clue_IDs[$hint_nr];
            $clue_ID = $this->get_cgrp_id()."-".$clue_ID;
        }

        return $clue_ID;
    }

    function get_hint_timeout($hint_nr){
        if (!isset($this->data['hint']['timeouts']) or
            trim($this->data['hint']['timeouts']) == ""){

            $this->data['hint']['timeouts'] = "";
        }

        $timeouts = explode(",", $this->data['hint']['timeouts']);


        if (!isset($timeouts[$hint_nr])){
            $timeout = end($timeouts);
            if (!$timeout) $timeout=0; // for the case the array is empty
        }
        else{
            $timeout = $timeouts[$hint_nr];
        }

        if ($timeout == "") $timeout = 0;

        return self::timeout_to_sec($timeout, "hint $hint_nr");
    }

    function get_hint_price($hint_nr){
        if (!isset($this->data['hint']['prices']) or
            trim($this->data['hint']['prices']) == ""){

            $this->data['hint']['prices'] = "";
        }

        $prices = explode(",", $this->data['hint']['prices']);

        if (!isset($prices[$hint_nr])){
            $price = 0;
        }
        else{
            $price = $prices[$hint_nr];
        }

        if ($price == "") $price = 0;

        if (!is_numeric($price)){
            throw new Iquest_InvalidConfigException("Invalid price value: '$price' for hint $hint_nr");
        }

        return round($price, 2);
    }


    function save_options(){
        if (!isset($this->data['options'])) return;
        if (!is_array($this->data['options'])) return;

        foreach($this->data['options'] as $name=>$value){
            if ($name == Iquest_Options::COUNTDOWN_LIMIT_HINT or
                $name == Iquest_Options::COUNTDOWN_LIMIT_SOLUTION or
                $name == Iquest_Options::HIDE_PLACE_TIMEOUT){

                $value = self::timeout_to_sec($value, "option $name");
            }

            try{
                Iquest_Options::set($name, $value);
            }
            catch(RuntimeException $e){
                throw new Iquest_InvalidConfigException("Error while processing 'options' from top metadata file: ".$e->getMessage());
            }
        }
    }

    function save_hq_login(){
        if (!isset($this->data['HQ']['credentials']) or
            !is_array($this->data['HQ']['credentials']) or
            !count($this->data['HQ']['credentials'])) {

            Console::log("*** WARNING: There is no information for login into HQ specified in top metadata file.", Console::YELLOW);
            return;
        }

        $hq_logins = array();
        foreach($this->data['HQ']['credentials'] as $username => $password){
            if (substr($password, 0, 1) == '$') $password = substr($password, 1);
            else                                $password = md5($password);

            $hq_logins[$username] = $password;
        }

        Iquest_Options::set(Iquest_Options::HQ_LOGIN, $hq_logins);
    }

    function get_traccar_zones(){
        $zones = [];

        if (isset($this->data['traccar_zones']['message'])){
            foreach($this->data['traccar_zones']['message'] as $zone_name => $message){
                $zones[$zone_name][Iquest_Tracker::ZONE_ATTR_MSG] = $message;
            }
        }

        if (isset($this->data['traccar_zones']['condition'])){
            foreach($this->data['traccar_zones']['condition'] as $zone_name => $condition){
                $zones[$zone_name][Iquest_Tracker::ZONE_ATTR_COND] = $condition;
            }
        }

        if (isset($this->data['traccar_zones']['key'])){
            foreach($this->data['traccar_zones']['key'] as $zone_name => $key){
                $zones[$zone_name][Iquest_Tracker::ZONE_ATTR_KEY] = $key;
            }
        }

        if (isset($this->data['traccar_zones']['priority'])){
            foreach($this->data['traccar_zones']['priority'] as $zone_name => $priority){
                $zones[$zone_name][Iquest_Tracker::ZONE_ATTR_PRIO] = $priority;
            }
        }

        return $zones;
    }

    function to_string(){
        $cfg = $this->get_cfg();

        $str = "\n";

        $str .= "** FOLDER CFG:\n";
        foreach($cfg as $key=>$val){
            $key = strtoupper($key);
            $str .= "   $key: $val\n";
        }

        try                                     {$cgrp_id=$this->get_cgrp_id();}
        catch(Iquest_InvalidConfigException $e) {$cgrp_id = "---";}

        try                                     {$cgrp_name=$this->get_cgrp_name();}
        catch(Iquest_InvalidConfigException $e) {$cgrp_name = "---";}

        $next_cgrps = $this->get_solution_next_cgrp();
        $next_cgrps_str = array();

        foreach($next_cgrps as $next_cgrp){
            if ($next_cgrp->isConditional()) $next_cgrps_str[] = "*{$next_cgrp->cgrp_id}";
            else                             $next_cgrps_str[] = $next_cgrp->cgrp_id;
        }
        $next_cgrps_str = implode(", ", $next_cgrps_str);


        $str .= "** CLUE GROUP:\n";
        $str .= "   ID: ".$cgrp_id.
                  " NAME: ".$cgrp_name.
                  " ORDER: ".$this->get_cgrp_order()."\n";
        $str .= "   START: ".$this->is_start_cgrp().
                  " FINAL: ".$this->is_final_cgrp().
                  " GIVE IT UP: ".$this->is_reveal_goal_cgrp()."\n";

        $str .= "** SOLUTION:\n";
        $str .= "   ID: ".$this->get_solution_id().
                  " NAME: ".$this->get_solution_name().
                  ($this->is_key_set()?" KEY: ".$this->get_solution_key():"")."\n";
        $str .= "   TIMEOUT: ".$this->get_solution_timeout().
                  " COUNTDOWN START: ".$this->get_solution_countdown_start().
                  " COIN VALUE: ".$this->get_solution_coin_value().
                  " NEXT CGRP IDS: ".$next_cgrps_str."\n";

        $str .= "** CLUES:\n";
        $str .= "   NR HIDDEN CLUES: ".$this->get_clue_hidden_nr()."\n";

        return $str;
    }
}
