<?php

class Chroust{

    static $summary = array(
                        'keys'  => array(),
                        'hints' => array(),
                      );
    static $unknown_files = array();

    static $id2ref_id = array();

    static $parsed_data = array(
        "clues" => array(),
        "hints" => array(),
        "solutions" => array(),
        "traccar_zones" => array()
    );

    static function prune_data_dir(){
        global $config;

        if ($config->iquest_data_dir and $config->iquest_data_dir != "/"){
            Console::log("Erasing content of directory: ".$config->iquest_data_dir, Console::YELLOW);

            // make sure the destination directory is empty
            rm($config->iquest_data_dir."*");
        }
        else {
            throw new Exception("Iquest datadir is not set in config file.");
        }
    }

    static function prune_db($option){
        global $data, $config;

        $tables = array(
            $config->data_sql->iquest_cgrp->table_name,
            $config->data_sql->iquest_clue->table_name,
            $config->data_sql->iquest_clue2solution->table_name,
            $config->data_sql->iquest_hint->table_name,
            $config->data_sql->iquest_solution->table_name,
            $config->data_sql->iquest_solution_next_cgrp->table_name,
            $config->data_sql->iquest_option->table_name,
        );

        if (empty($option['preserve-user-data'])){
            $tables[] = $config->data_sql->iquest_cgrp_open->table_name;
            $tables[] = $config->data_sql->iquest_hint_team->table_name;
            $tables[] = $config->data_sql->iquest_solution_team->table_name;
            $tables[] = $config->data_sql->iquest_event->table_name;
            $tables[] = $config->data_sql->iquest_team_rank->table_name;
        }

        foreach($tables as $table){
            Console::log("Erasing DB table: ".$table, Console::YELLOW);
            $res=$data->db->query("delete from ".$table);
        }
    }

    static function initialize_user_wallets($option){
        global $data, $config;

        $wallet_initial_value = (float)Iquest_Options::get(Iquest_Options::WALLET_INITIAL_VALUE);
        $bomb_initial_value = (float)Iquest_Options::get(Iquest_Options::BOMB_INITIAL_VALUE);

        if (empty($option['preserve-user-data'])){
            Console::log("Wiping user wallets and bombs", Console::YELLOW);
            $res=$data->db->query(
                "update {$config->data_sql->iquest_team->table_name}
                 set {$config->data_sql->iquest_team->cols->wallet}=$wallet_initial_value,
                     {$config->data_sql->iquest_team->cols->bomb}=$bomb_initial_value");
        }
    }

    static function save_ref_ids($option){

        if (empty($option['preserve-user-data'])) return;

        Iquest_Verbose_Output::log("Saving ref_ids from DB tables");

        $cgrps = Iquest_ClueGrp::fetch();
        $clues = Iquest_Clue::fetch();
        $hints = Iquest_Hint::fetch();
        $solutions = Iquest_Solution::fetch();

        foreach($cgrps as $cgrp)            self::$id2ref_id['cgrp'][$cgrp->id]         = $cgrp->ref_id;
        foreach($clues as $clue)            self::$id2ref_id['clue'][$clue->id]         = $clue->ref_id;
        foreach($hints as $hint)            self::$id2ref_id['hint'][$hint->id]         = $hint->ref_id;
        foreach($solutions as $solution)    self::$id2ref_id['solution'][$solution->id] = $solution->ref_id;
    }


    /**
     *  Set default values for options
     */
    static function set_defaults(){
        Iquest_Options::set(Iquest_Options::WALLET_INITIAL_VALUE, 0);
        Iquest_Options::set(Iquest_Options::BOMB_INITIAL_VALUE, 0);
        Iquest_Options::set(Iquest_Options::CHECK_KEY_ORDER, 0);
        Iquest_Options::set(Iquest_Options::SHOW_PLACE, 1);
        Iquest_Options::set(Iquest_Options::HIDE_PLACE_TIMEOUT, 0);
        Iquest_Options::set(Iquest_Options::SHOW_GRAPH, 1);
        Iquest_Options::set(Iquest_Options::SHOW_GRAPH_CGRP_NAMES, 0);
        Iquest_Options::set(Iquest_Options::SHOW_GRAPH_MARK_SOLVED, 1);
        Iquest_Options::set(Iquest_Options::KEY_PREFIX, "");
        Iquest_Options::set(Iquest_Options::LOGO, "");
        Iquest_Options::set(Iquest_Options::GAME_NAME, "");
        Iquest_Options::set(Iquest_Options::HQ_LOGIN, array());
        Iquest_Options::set(Iquest_Options::TRACCAR_ENABLED, 0);
        Iquest_Options::set(Iquest_Options::TRACCAR_ADDR, "");
        Iquest_Options::set(Iquest_Options::TRACCAR_AUTH_TOKEN, "");
        Iquest_Options::set(Iquest_Options::TRACCAR_GROUP, "");
    }

    /**
     *  Create initial record for team ranking.
     */
    static function init_team_rank($option){

        $ranks = Iquest_team_rank::fetch();

        // Do it only when user data are not preserved or team_rank DB table is empty
        if (!empty($option['preserve-user-data']) and count($ranks)){
            return;
        }

        Iquest_team_rank::init_db_table();
    }

    static function insert_cgrp($metadata){
        $cgrp_id = $metadata->get_cgrp_id();
        $cgrp_name = $metadata->get_cgrp_name();
        $cgrp_order = $metadata->get_cgrp_order();

        if (isset(self::$id2ref_id['cgrp'][$cgrp_id])) $ref_id = self::$id2ref_id['cgrp'][$cgrp_id];
        else                                           $ref_id = rfc4122_uuid();

        $id_dup_cgrp = Iquest_ClueGrp::fetch(array("id"=>$cgrp_id));
        if ($id_dup_cgrp){
            throw new Iquest_InvalidConfigException("There are two clue groups sharing same ID: '$cgrp_id'.");
        }

        $cgrp = new Iquest_ClueGrp($cgrp_id, $ref_id, $cgrp_name, $cgrp_order);
        $cgrp->insert();
        return $cgrp;
    }

    static function insert_clue($filename, $clue_nr, $metadata){
        $cgrp_id = $metadata->get_cgrp_id();
        $clue_id = $cgrp_id."-".$clue_nr;

        if (isset(self::$id2ref_id['clue'][$clue_id])) $ref_id = self::$id2ref_id['clue'][$clue_id];
        else                                           $ref_id = rfc4122_uuid();

        $content_type = $metadata->get_mime_type($filename);
        $point_to = $metadata->get_clue_point_to($clue_nr);
        $clue_type = $metadata->get_clue_type($clue_nr, $filename);

        $clue = new Iquest_Clue($clue_id, $ref_id,
                $filename, $content_type, $clue_type,
                null, $cgrp_id, $clue_nr, $point_to);

        $clue->insert();
        return $clue;
    }

    static function insert_hint($filename, $hint_nr, $metadata, $clues){
        $cgrp_id = $metadata->get_cgrp_id();
        $hint_id = $cgrp_id."-".$hint_nr;

        if (isset(self::$id2ref_id['hint'][$hint_id])) $ref_id = self::$id2ref_id['hint'][$hint_id];
        else                                           $ref_id = rfc4122_uuid();

        $content_type = $metadata->get_mime_type($filename);
        $clue_id = $metadata->get_hint_clueid($hint_nr);
        $timeout = $metadata->get_hint_timeout($hint_nr);
        $price = $metadata->get_hint_price($hint_nr);

        if (!$clues){
            throw new Iquest_InvalidConfigException("Cannot insert hint. There are no clues defined in clue grp '$cgrp_id'.");
        }

        // if clue ID is not specified, use the ID of last clue
        if (is_null($clue_id)){
            $last_clue = end($clues);
            $clue_id = $last_clue->id;

            Console::log("*** WARNING: clue_IDs are not specified for hint '$hint_nr'. Useing the last clue: '$clue_id'", Console::YELLOW);
        }

        // if last clue shall be used
        if ($clue_id == Iquest_Metadata::LAST_CLUE){
            $last_clue = end($clues);
            $clue_id = $last_clue->id;
        }

        // Check whether the clue ID is valid
        if (!isset($clues[$clue_id])){
            throw new Iquest_InvalidConfigException("Invalid clue_ID '$clue_id' specified for hint $hint_nr.");
        }

        if (!$timeout and !$price){
            throw new Iquest_InvalidConfigException("Neither timeout nor price is specified for hint '$hint_nr'. This hint will never be shown.");
        }

        self::$summary['hints'][] = array("cgrp_name" => $metadata->get_cgrp_name(),
                                          "ordering"  => $metadata->get_cgrp_order(false),
                                          "clue_id"   => $clue_id,
                                          "hint_nr"   => $hint_nr,
                                          "price"     => $price,
                                          "timeout"   => $timeout);


        $hint = new Iquest_Hint($hint_id, $ref_id,
                $filename, $content_type, null, $clue_id, $timeout, $price, $hint_nr);

        $hint->insert();
        return $hint;
    }

    static function insert_solution($filename, $solution_nr, $metadata){

        if ($solution_nr > 0){
            Console::log("*** WARNING: Only one solution file allowed in a directory. Ignoring the another one: '$filename'", Console::YELLOW);
            return null;
        }

        $solution_id = $metadata->get_solution_id();

        if (isset(self::$id2ref_id['solution'][$solution_id])) $ref_id = self::$id2ref_id['solution'][$solution_id];
        else                                                   $ref_id = rfc4122_uuid();

        $content_type = $metadata->get_mime_type($filename);

        $name = $metadata->get_solution_name();
        $key = $metadata->get_solution_key_canon();
        $timeout = $metadata->get_solution_timeout();
        $countdown_start = $metadata->get_solution_countdown_start();
        $next_cgrps = $metadata->get_solution_next_cgrp();
        $coin_value = $metadata->get_solution_coin_value();
        $bomb_value = $metadata->get_solution_bomb_value();

        $traccar_zones = $metadata->get_solution_traccar_zones();
        $traccar_condition = $metadata->get_solution_traccar_condition();

        if ($coin_value == 0 and $bomb_value == 0 and ! $next_cgrps){
            Console::log("*** WARNING: Neither coin_value nor bomb_value nor next_cgrp_id is set for solution. This looks to be a dead end.", Console::YELLOW);
        }

        if (!$filename and $timeout > 0){
            throw new Iquest_InvalidConfigException("No solution file exists, but timeout (for displaying it) is set.");
        }

        $id_dup_solution = Iquest_Solution::by_id($solution_id);
        if ($id_dup_solution){
            throw new Iquest_InvalidConfigException("There are two solutions sharing same ID: '$solution_id'.");
        }

        $key_dup_solution = Iquest_Solution::by_key($key);
        if ($key_dup_solution){
            throw new Iquest_InvalidConfigException("Solution '{$key_dup_solution->id}' and '$solution_id' have same key configured ($key).");
        }

        $next_cgrps_str = array();
        foreach($next_cgrps as $next_cgrp){
            if ($next_cgrp->isConditional()) $next_cgrps_str[] = "*{$next_cgrp->cgrp_id}";
            else                             $next_cgrps_str[] = $next_cgrp->cgrp_id;

            if ($next_cgrp->isConditional()){
                try{
                    Iquest_Condition::parseCondition($next_cgrp->condition);
                }
                catch(Exception $e){
                    throw new Iquest_VerifyFailedException(
                                "next_cgrp_id of solution '$solution_id' contain invalid condition '{$next_cgrp->condition}'. ".$e->getMessage()
                    );
                }
            }
        }
        $next_cgrps_str = implode(", ", $next_cgrps_str);

        self::$summary['keys'][] = array("name" => $name,
                                         "key"  => $metadata->get_solution_key(),
                                         "ordering" => $metadata->get_cgrp_order(false),
                                         "coin_value" => $coin_value,
                                         "bomb_value" => $bomb_value,
                                         "timeout" =>    $timeout,
                                         "next_cgrp_id" => $next_cgrps ? $next_cgrps_str :  null);

        $solution = new Iquest_Solution($solution_id, $ref_id,
                $filename, $content_type, null, $name, $timeout,
                $countdown_start, $key, $coin_value, $bomb_value);

        $solution->set_next_cgrps($next_cgrps);

        $solution->aditional_data = new stdClass();
        $solution->aditional_data->dir = $metadata->dir;
        $solution->aditional_data->traccar_zones = $traccar_zones;
        $solution->aditional_data->traccar_condition = $traccar_condition;

        $solution->insert();
        return $solution;
    }


    static function process_top_metadata($dir){

        try{
            $metadata = new Iquest_Metadata($dir);
        }
        catch (Iquest_noMetaDataException $e){
            throw new Iquest_InvalidConfigException("Cannot find top metadata file in directory: ".$dir, 0 ,$e);
        }

        try{
            $metadata->save_options();
            $metadata->save_hq_login();
        }
        catch(Iquest_MetadataOpenException $e){
            throw new Iquest_InvalidConfigException("Problem reading metadata of directory: ".$dir, 0 ,$e);
        }

        self::process_logo($dir);

        return $metadata;
    }

    static function process_logo($src_dir){
        global $config;

        $logo_file = Iquest_Options::get(Iquest_Options::LOGO);
        if ($logo_file){

            $dest_file = self::canonicalize_name(basename($logo_file));

            if (!file_exists($src_dir.$logo_file)){
                throw new Iquest_InvalidConfigException("File for logo does not exists: ".$src_dir.$logo_file);
            }

            if (!copy($src_dir.$logo_file, $config->iquest_data_dir.$dest_file)){
                throw new Exception("Copy of file ".$src_dir.$logo_file." failed");
            }

            Iquest_Options::set(Iquest_Options::LOGO, $logo_file);
        }
    }

    static function process_data_dir($src_dir, $top_metadata){

        $dir = scandir($src_dir, SCANDIR_SORT_ASCENDING);
        if ($dir === false) throw new Iquest_ConfigDirOpenException("Cannot open directory: ".$src_dir);

        foreach($dir as $entry){
            //skip entries that are not directories
            if (!is_dir($src_dir.$entry)) continue;
            if (substr($entry, 0, 1) == ".") continue;

            try{
                $dest_dir = self::canonicalize_name($entry);
                self::process_task_dir($src_dir.$entry.DIRECTORY_SEPARATOR, $dest_dir, $top_metadata);
            }
            catch(Iquest_MetadataOpenException $e){
                throw new Iquest_InvalidConfigException("Problem during processing directory: ".$src_dir.$entry.DIRECTORY_SEPARATOR, 0, $e);
            }
        }

    }

    static private function filename_replacement(&$line, &$files, $filename){
        while (preg_match("/<file-(?P<type>[a-z])(?P<nr>[0-9])+>/i", $line, $matches)){

            if ($matches['type']=='c') $type='clues';
            elseif ($matches['type']=='h') $type='hints';
            elseif ($matches['type']=='s') $type='solutions';
            else{
                throw new Iquest_InvalidConfigException("Invalid file replacement '{$matches[0]}' in file '$filename'");
            }

            $nr = $matches['nr'] - 1;
            $filenames = array_keys($files[$type]);
            if (!isset($filenames[$nr])){
                throw new Iquest_InvalidConfigException("Cannot find file number '{$matches['nr']}' for file replacement '{$matches[0]}' in file '$filename'");
            }

            $line = str_replace($matches[0], $filenames[$nr], $line);
        }
    }

    static function copy_file($from, $to, $file_obj, $metadata, $files){
        global $config;

        if (substr($file_obj->content_type, 0, 5) == "text/"){
            $charset = $metadata->get_charset($file_obj->filename);

            $in =  fopen($from, "r");
            $out = fopen($config->iquest_data_dir.$to, "w");

            while($line = fgets($in)) {
                if ($charset != "UTF-8"){
                    $line = iconv($charset, "UTF-8", $line);
                }

                if (is_a($file_obj, "Iquest_Solution")){
                    $line = str_replace("<key>", $metadata->get_solution_key(), $line);
                }

                self::filename_replacement($line, $files, basename($from));

                if (preg_match("/<hint-cnt>/i", $line, $matches)){
                    $line = str_replace($matches[0], count($files['hints']), $line);
                }

                if (preg_match("/<timeout>/i", $line, $matches)){
                    $line = str_replace($matches[0], $metadata->get_solution_timeout_str(), $line);
                }

                if (false === fwrite($out, $line)){
                    throw new Exception("Copy of file $from failed");
                }
            }

            if (!feof($in)) {
                throw new Exception("Copy of file $from failed");
            }


            fclose($in);
            fclose($out);
            return;
        }

        if (!copy($from, $config->iquest_data_dir.$to)){
            throw new Exception("Copy of file $from failed");
        }
    }

    static function process_task_dir($task_dir, $dest_dir, $top_metadata){
        global $config;

        Console::log("Reading directory: $task_dir", Console::GREEN);

        try{
            $metadata = new Iquest_Metadata($task_dir, $top_metadata);
        }
        catch (Iquest_noMetaDataException $e){
            Console::log("*** No metadata. Skipping!!!", Console::YELLOW);
            return;
        }

        Iquest_Verbose_Output::log($metadata->to_string(), "Metadata");

        // re-create the dest. dir
        RecursiveMkdir($config->iquest_data_dir.$dest_dir);

        $dir = scandir($task_dir, SCANDIR_SORT_ASCENDING);
        if ($dir === false) throw new Iquest_ConfigDirOpenException("Cannot open directory: ".$task_dir);

        $cfg = $metadata->get_cfg();
        $entries=array();
        $entries['clues']=array();
        $entries['hints']=array();
        $entries['solutions']=array();

        foreach($dir as $entry){
            //skip entries that are not files
            if (!is_file($task_dir.$entry)) continue;
            // skip metadata file
            if ($entry == Iquest_Metadata::METADATA_FILE) continue;

            $dest_file = self::canonicalize_name($entry);

            if (preg_match("/".$cfg["clue_pattern"]."/i", $dest_file) or
                preg_match("/".$cfg["clue_coin_pattern"]."/i", $dest_file) or
                preg_match("/".$cfg["clue_special_pattern"]."/i", $dest_file)){
                $entries['clues'][$entry] = $dest_file;
            }
            elseif (preg_match("/".$cfg["hint_pattern"]."/i", $dest_file)){
                $entries['hints'][$entry] = $dest_file;
            }
            elseif (preg_match("/".$cfg["solution_pattern"]."/i", $dest_file)){
                $entries['solutions'][$entry] = $dest_file;
            }
            else{
                self::$unknown_files[] = $task_dir.$entry;
            }
        }

        Iquest_Verbose_Output::log($entries, "Read data");

        asort($entries['clues']);
        asort($entries['hints']);
        asort($entries['solutions']);

        //Create clue group only if it contain at least one clue
        if ($entries['clues']) {
            self::insert_cgrp($metadata);

            if ($metadata->is_start_cgrp()){
                try{
                    $initial_cgrps = Iquest_Options::get(Iquest_Options::INITIAL_CGRP_IDS);
                }
                catch(RuntimeException $e){
                    $initial_cgrps = array();
                }

                $initial_cgrps[] = $metadata->get_cgrp_id();
                Iquest_Options::set(Iquest_Options::INITIAL_CGRP_IDS, $initial_cgrps);
            }

            if ($metadata->is_reveal_goal_cgrp()){
                Iquest_Options::set(Iquest_Options::REVEAL_GOAL_CGRP_ID, $metadata->get_cgrp_id());
            }

            if ($metadata->is_final_cgrp()){
                Iquest_Options::set(Iquest_Options::FINAL_CGRP_ID, $metadata->get_cgrp_id());
            }
        }


        $clue_nr = $hint_nr = $solution_nr = 0;

        $clues = array();
        foreach($entries['clues'] as $from_file=>$to_file){
            $clue = self::insert_clue($dest_dir.DIRECTORY_SEPARATOR.$to_file, $clue_nr, $metadata);
            if (!$clue) continue;

            self::copy_file($task_dir.$from_file, $dest_dir.DIRECTORY_SEPARATOR.$to_file, $clue, $metadata, $entries);

            $clues[$clue->id] = $clue;
            $clue_nr++;
        }

        // generate hidden clues
        $hidden_clues = $metadata->get_clue_hidden_nr();
        for($i=0; $i<$hidden_clues; $i++){
            $clue = self::insert_clue(null, $clue_nr, $metadata);
            if (!$clue) continue;

            $clues[$clue->id] = $clue;
            $clue_nr++;
        }

        $hints = array();
        foreach($entries['hints'] as $from_file=>$to_file){
            $hint = self::insert_hint($dest_dir.DIRECTORY_SEPARATOR.$to_file, $hint_nr, $metadata, $clues);
            if (!$hint) continue;

            self::copy_file($task_dir.$from_file, $dest_dir.DIRECTORY_SEPARATOR.$to_file, $hint, $metadata, $entries);

            $hints[$hint->id] = $hint;
            $hint_nr++;
        }

        $solutions = array();
        foreach($entries['solutions'] as $from_file=>$to_file){
            $solution = self::insert_solution($dest_dir.DIRECTORY_SEPARATOR.$to_file, $solution_nr, $metadata);
            if (!$solution) continue;

            self::copy_file($task_dir.$from_file, $dest_dir.DIRECTORY_SEPARATOR.$to_file, $solution, $metadata, $entries);

            $solutions[$solution->id] = $solution;
            $solution_nr++;
        }

        // If there is no solution file in the directory, but a key is set in metadata...
        if (!$entries['solutions'] and $metadata->is_key_set()){
            // Create the solution record
            $solution = self::insert_solution(null, $solution_nr, $metadata);
            if ($solution){
                $solutions[$solution->id] = $solution;
            }
        }

        $zones = $metadata->get_traccar_zones();
        foreach($zones as $zone_name => $zone){
            if (isset($zones[$zone_name][Iquest_Tracker::ZONE_ATTR_PRIO]))  static::$parsed_data["traccar_zones"][$zone_name][Iquest_Tracker::ZONE_ATTR_PRIO] = $zones[$zone_name][Iquest_Tracker::ZONE_ATTR_PRIO];
            if (isset($zones[$zone_name][Iquest_Tracker::ZONE_ATTR_KEY]))   static::$parsed_data["traccar_zones"][$zone_name][Iquest_Tracker::ZONE_ATTR_KEY]  = $zones[$zone_name][Iquest_Tracker::ZONE_ATTR_KEY];
            if (isset($zones[$zone_name][Iquest_Tracker::ZONE_ATTR_MSG]))   static::$parsed_data["traccar_zones"][$zone_name][Iquest_Tracker::ZONE_ATTR_MSG]  = $zones[$zone_name][Iquest_Tracker::ZONE_ATTR_MSG];
            if (isset($zones[$zone_name][Iquest_Tracker::ZONE_ATTR_COND]))  static::$parsed_data["traccar_zones"][$zone_name][Iquest_Tracker::ZONE_ATTR_COND] = $zones[$zone_name][Iquest_Tracker::ZONE_ATTR_COND];
            static::$parsed_data["traccar_zones"][$zone_name]['referenced_by'][] = "metadata (traccar_zones): ".basename($task_dir);
        }

        static::$parsed_data["clues"]     = array_merge(static::$parsed_data["clues"],     $clues);
        static::$parsed_data["hints"]     = array_merge(static::$parsed_data["hints"],     $hints);
        static::$parsed_data["solutions"] = array_merge(static::$parsed_data["solutions"], $solutions);
    }

    /**
     *  Set value of WALLET_ACTIVE option in dependency whether
     *  there are any hints that could be bought
     */
    static function set_wallet_active(){
        // Check whether the WALLET_ACTIVE is already set from metadata file. If so
        // just return. Otherwise catch the exception thrown and set the WALLET_ACTIVE flag.
        try{
            $option_value = Iquest_Options::get(Iquest_Options::WALLET_ACTIVE);
            return;
        }
        catch(RuntimeException $e){}

        $coin_value = 0;

        foreach(self::$summary['hints'] as $row){
            $coin_value += abs($row['price']);
        }

        Iquest_Options::set(Iquest_Options::WALLET_ACTIVE, ($coin_value>0) ? 1 : 0);
    }

    /**
     *  Set value of BOMB_ACTIVE option in dependency whether
     *  there are any hints that could be bought
     */
    static function set_bomb_active(){
        // Check whether the BOMB_ACTIVE is already set from metadata file. If so
        // just return. Otherwise catch the exception thrown and set the BOMB_ACTIVE flag.
        try{
            $option_value = Iquest_Options::get(Iquest_Options::BOMB_ACTIVE);
            return;
        }
        catch(RuntimeException $e){}

        $bomb_value = 0;

        foreach(self::$summary['keys'] as $row){
            $bomb_value += abs($row['bomb_value']);
        }

        Iquest_Options::set(Iquest_Options::BOMB_ACTIVE, ($bomb_value>0) ? 1 : 0);
    }

    static function verify(){
        global $data, $config;

        $cgrps = Iquest_ClueGrp::fetch();
        $clues = Iquest_Clue::fetch();
        $solutions = Iquest_Solution::fetch();

        // verify that next_cgrp_id for each solution is valid (exists)
        foreach($solutions as $solution){
            foreach($solution->get_next_cgrp_ids() as $next_cgrp_id){
                if (!isset($cgrps[$next_cgrp_id])){
                    throw new Iquest_VerifyFailedException(
                                "Task solution '{$solution->id}' reference non existing clue group '{$next_cgrp_id}'. Make sure that the respective directory of clue group contain a clue file.",
                                array(Iquest_VerifyFailedException::CLUE_GRP_IDS)
                    );
                }
            }

            // verify the conditions of solutions
            foreach($solution->get_next_cgrps() as $next_cgrp){
                if (!$next_cgrp->isConditional()) continue;

                try{
                    Iquest_Condition::verifyCondition($next_cgrp->condition);
                }
                catch(Exception $e){
                    throw new Iquest_VerifyFailedException(
                                "next_cgrp_id of solution '{$solution->id}' contain invalid condition '{$next_cgrp->condition}'. ".$e->getMessage()
                    );
                }
            }
        }



        // verify that clue2soultion point to existing solution

        /* table's name */
        $t_name = &$config->data_sql->iquest_clue2solution->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_clue2solution->cols;

        // fetch the whole clue2solution DB table
        $q = "select ".$c->clue_id.",
                     ".$c->solution_id."
              from ".$t_name;

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        // walk through the rows
        while ($row=$res->fetch()){
            if (!isset($solutions[$row[$c->solution_id]])){
                throw new Iquest_VerifyFailedException(
                            "Clue2solution '{$row[$c->clue_id]}, {$row[$c->solution_id]}' reference non existing solution '{$row[$c->solution_id]}'.",
                            array(Iquest_VerifyFailedException::SOLUTION_IDS)
                );
            }

            if (!isset($clues[$row[$c->clue_id]])){
                throw new Iquest_VerifyFailedException(
                            "Clue2solution '{$row[$c->clue_id]}, {$row[$c->solution_id]}' reference non existing clue '{$row[$c->clue_id]}'.",
                            array(Iquest_VerifyFailedException::CLUE_IDS)
                );
            }

        }
        $res->closeCursor();


        //check that all Iquest_Options are set
        foreach(Iquest_Options::$supported_options as $option_name){
            try{
                $option_value = Iquest_Options::get($option_name);
            }
            catch(RuntimeException $e){
                echo "\n";
                Console::log("\n*** ERROR: Option $option_name is not set.", Console::RED);

                if (in_array($option_name, Iquest_Options::$set_in_global_ini)){
                    echo "It should be specified in [options] section of metadata in top directory.\n";
                }
                else{
                    echo "It should be specified in [cgrp] section of metadata in the appropriate clue-grp directory.\n";
                }

                if (isset(Iquest_Metadata::$option_2_ini_file_directive[$option_name])){
                    echo "You should set the directive '".Iquest_Metadata::$option_2_ini_file_directive[$option_name]."'.\n";
                }

                throw new Iquest_InvalidConfigException($e->getMessage());
            }
        }

        if (Iquest_Options::get(Iquest_Options::TRACCAR_ENABLED)){
            if (!Iquest_Options::get(Iquest_Options::TRACCAR_ADDR)){
                throw new Iquest_InvalidConfigException('Tracking is enabled but address of traccar server is not set.');
            }
            if (!Iquest_Options::get(Iquest_Options::TRACCAR_AUTH_TOKEN)){
                throw new Iquest_InvalidConfigException('Tracking is enabled but authentication token for traccar server is not set.');
            }
        }

        // Verify that the graph of clues and solutions is continuous.
        // Use any fake number as team_id. We do not need to have graph for any team.
        $graph = new Iquest_solution_graph(9999);
        $graph_errors = $graph->check_graph_continuous();
        if ($graph_errors){
            Console::log("\n".str_repeat("*", 80), Console::LIGHT_PURPLE);
            Console::log("The graph of clues and solutions is not continuous.", Console::LIGHT_PURPLE);
            Console::log("Some nodes are not accessible from initial clue group.", Console::LIGHT_PURPLE);
            Console::log("It seems your configuration is not correct.", Console::LIGHT_PURPLE);
            Console::log("You can check the graph in the HQ interface by click on name of any team.\n", Console::LIGHT_PURPLE);
            Console::log($graph_errors, Console::LIGHT_PURPLE);
            Console::log(str_repeat("*", 80), Console::LIGHT_PURPLE);
        }
    }

    static function traccar_update(){

        $traccar = new Traccar([
            'auth_token' => Iquest_Options::get(Iquest_Options::TRACCAR_AUTH_TOKEN),
            'server_addr' => Iquest_Options::get(Iquest_Options::TRACCAR_ADDR)
        ]);

        // read zone attributes from parsed solutions
        foreach(static::$parsed_data["solutions"] as $solution){

            if ($solution->aditional_data->traccar_zones){
                $opening_cgrps = Iquest_ClueGrp::fetch_by_pointing_to_solution($solution->id);
                $opening_cgrp_ids = [];
                foreach($opening_cgrps as $cgrp) $opening_cgrp_ids[] = $cgrp->id;

                foreach($solution->aditional_data->traccar_zones as $zone_name){

                    static::$parsed_data["traccar_zones"][$zone_name]['referenced_by'][] = "metadata (solution): ".basename($solution->aditional_data->dir);
                    static::$parsed_data["traccar_zones"][$zone_name][Iquest_Tracker::ZONE_ATTR_KEY] = $solution->key;

                    if (is_null($solution->aditional_data->traccar_condition)){
                        if (!isset(static::$parsed_data["traccar_zones"][$zone_name][Iquest_Tracker::ZONE_ATTR_COND])){
                            static::$parsed_data["traccar_zones"][$zone_name][Iquest_Tracker::ZONE_ATTR_COND] = "OPENED_ANY(".implode(", ", $opening_cgrp_ids).")";
                        }
                    }
                    else{
                        static::$parsed_data["traccar_zones"][$zone_name][Iquest_Tracker::ZONE_ATTR_COND] = $solution->aditional_data->traccar_condition;
                    }
                }
            }
        }

        // update zones in traccar
        $traccar_zones = [];
        foreach(static::$parsed_data["traccar_zones"] as $zone_name => $zone_attrs){
            Console::log("Updating traccar zone: $zone_name", Console::CYAN);
            Iquest_Verbose_Output::log("Data collected from: ".implode(", ", $zone_attrs['referenced_by']));

            $zone = $traccar->get_zone_by_name($zone_name);
            if (!$zone) throw new Iquest_InvalidConfigException("Cannot find traccar zone: '$zone_name' referenced by: ".implode(", ", $zone_attrs['referenced_by']).".");

            foreach([Iquest_Tracker::ZONE_ATTR_PRIO, Iquest_Tracker::ZONE_ATTR_KEY, Iquest_Tracker::ZONE_ATTR_MSG] as $attr){
                unset($zone->attributes[$attr]);
                if (isset($zone_attrs[$attr])) $zone->attributes[$attr] = $zone_attrs[$attr];
            }

            unset($zone->attributes[Iquest_Tracker::ZONE_ATTR_COND]);
            if (!empty($zone_attrs[Iquest_Tracker::ZONE_ATTR_COND])) $zone->attributes[Iquest_Tracker::ZONE_ATTR_COND] = $zone_attrs[Iquest_Tracker::ZONE_ATTR_COND];

            $traccar_zones[] = $zone;
            $traccar->update_zone($zone);
        }

        // If traccar group name is set, assign zones to that group
        $traccar_group_name = Iquest_Options::get(Iquest_Options::TRACCAR_GROUP);
        if ($traccar_group_name){

            $traccar_group = $traccar->get_group_by_name($traccar_group_name);
            if (!$traccar_group){
                throw new Iquest_InvalidConfigException("Traccar group '$traccar_group_name' does not exists on the server.");
            }

            foreach($traccar_zones as $zone){
                Console::log("Adding traccar zone: {$zone->name} to group: {$traccar_group->name}", Console::CYAN);
                $traccar->add_zone_to_group($zone->id, $traccar_group->id);
            }
        }
    }

    static function canonicalize_name($str){
        $str = remove_diacritics($str);
        $str = strtolower($str);
        $str = str_replace(" ", "_", $str);
        $str = preg_replace("/[^-_a-z0-9.]/", "", $str);
        return $str;
    }

    static function utf_8_sprintf ($format) {
        $args = func_get_args();

        for ($i = 1; $i < count($args); $i++) {
            $args [$i] = iconv('UTF-8', 'ISO-8859-2', $args [$i]);
        }

        return iconv('ISO-8859-2', 'UTF-8', call_user_func_array('sprintf', $args));
    }

    static function format_time($t){
        return sprintf("%02d%s%02d%s%02d",
                            floor($t/3600), ':',
                            ($t/60)%60,     ':',
                            $t%60);
    }

    static function usage(){
        echo "Usage: ".$_SERVER['argv'][0]." [--verbose|-v] [--traccar-update|-t] [--preserve-user-data|--clear-user-data] <datadir> \n\n";
    }

    static function main(){

        if (posix_getuid() == 0){
            fwrite(STDERR, "Execution as root is not allowed\n");
            exit(1);
        }

        if ($_SERVER['argc'] < 2){
            self::usage();
            exit;
        }

        $option = array('preserve-user-data' => true,
                        'traccar-updade' => false);
        $src_dir = null;

        for ($i = 1; $i < $_SERVER["argc"]; $i++){
            switch($_SERVER["argv"][$i]){
            case "--preserve-user-data":
                $option['preserve-user-data'] = true;
                break;

            case "--clear-user-data":
                $option['preserve-user-data'] = false;
                break;

            case "-t":
            case "--traccar-update":
                $option['traccar-updade'] = true;
                break;

            case "-v":
            case "--verbose":
                Iquest_Verbose_Output::enable();
                break;

            default:
                if ($src_dir){  // src dir is already set - it acnnot be set twice
                    self::usage();
                    exit;
                }
                $src_dir = $_SERVER["argv"][$i];
            }
        }

        if (!$src_dir){  // src dir is not set, exit
            self::usage();
            fwrite(STDERR, "The src_dir is not set.\n");
            exit;
        }

        if (!is_dir($src_dir)){
            fwrite(STDERR, "The directory does not exists ($src_dir)\n");
            exit(1);
        }

        if (substr($src_dir, -1) != DIRECTORY_SEPARATOR){
            $src_dir.=DIRECTORY_SEPARATOR;
        }

        umask(002);

        try{
            self::save_ref_ids($option);
            self::prune_data_dir();
            self::prune_db($option);
            self::set_defaults();

            $top_metadata = self::process_top_metadata($src_dir);

            $zones = $top_metadata->get_traccar_zones();
            foreach($zones as &$zone) $zone['referenced_by'][] = "Top metadata";
            static::$parsed_data["traccar_zones"] = $zones;

            self::initialize_user_wallets($option);

            self::process_data_dir($src_dir, $top_metadata);

            if (self::$unknown_files){
                echo "\n";
                Console::log("\nNot recognized files:\n", Console::YELLOW);
                foreach(self::$unknown_files as $file){
                    Console::log("  * $file", Console::NORMAL);
                }
            }

            self::set_wallet_active();
            self::set_bomb_active();

            self::verify();

            self::init_team_rank($option);


            if ($option['traccar-updade']){
                self::traccar_update();

                self::zone_check();

                // Do not print zone summary if traccar has not been updated.
                // The self::traccar_update() function set most of the data in
                // static::$parsed_data["traccar_zones"] array
                self::print_zone_summary();
            }

            echo "\n";
            Console::log("Hint summary:", Console::LIGHT_GREEN, false, Console::UNDERLINE);
            echo "\n";

            // sort the keys summary by 'ordering' attribute
            usort(self::$summary['hints'], create_function('$a,$b','
                if ($a["ordering"] == $b["ordering"]) {
                    if ($a["clue_id"] == $b["clue_id"]) {
                        if ($a["hint_nr"] == $b["hint_nr"]) {return 0;}
                        return ($a["hint_nr"] < $b["hint_nr"]) ? -1 : 1;
                    }
                    return ($a["clue_id"] < $b["clue_id"]) ? -1 : 1;
                }
                return ($a["ordering"] < $b["ordering"]) ? -1 : 1;
            '));

            $fields_headers = array('cgrp_name' => "Cgrp Name ",
                                    'clue_id'   => "Clue ID ",
                                    'hint_nr'   => "Hint Nr ",
                                    'price'     => "Price ",
                                    'timeout'   => "Timeout ",
                                    );
            $fields_widths = self::get_field_widths(self::$summary['hints'], $fields_headers);

            self::print_table_heading($fields_headers, $fields_widths);
            self::print_table_separator($fields_widths);

            $fstring = self::get_formating_string($fields_widths, "|");

            $total_hint_price = 0;
            foreach(self::$summary['hints'] as $row){
                $total_hint_price += $row['price'];
                echo self::utf_8_sprintf($fstring,
                            $row['cgrp_name'],
                            $row['clue_id'],
                            $row['hint_nr'],
                            $row['price']>0?$row['price']:"---",
                            $row['timeout']>0?gmdate("H:i:s", $row['timeout']):"---");
            }

            self::print_table_separator($fields_widths);

            echo self::utf_8_sprintf($fstring,
                        "Total:",
                        "",
                        "",
                        $total_hint_price,
                        "");





            echo "\n";
            Console::log("Keys summary:", Console::LIGHT_GREEN, false, Console::UNDERLINE);
            echo "\n";

            // sort the keys summary by 'ordering' attribute
            usort(self::$summary['keys'], create_function('$a,$b','
                if ($a["ordering"] == $b["ordering"]) {return 0;}
                return ($a["ordering"] < $b["ordering"]) ? -1 : 1;
            '));


            $fields_headers = array('name'          => "Name ",
                                    'key'           => "Key ",
                                    'coin_value'    => "Coin ",
                                    'bomb_value'    => "Bomb ",
                                    'timeout'       => "Timeout ",
                                    'next_cgrp_id'  => "NextCgrps ",
                                    );
            $fields_widths = self::get_field_widths(self::$summary['keys'], $fields_headers);

            // Hack: make sure the last 'Total:' line fits into column widths
            if ($fields_widths['name'] < iconv_strlen("Total:", "UTF-8")) $fields_widths['name'] = iconv_strlen("Total:", "UTF-8");

            self::print_table_heading($fields_headers, $fields_widths);
            self::print_table_separator($fields_widths);

            $fstring = self::get_formating_string($fields_widths, "|");
            $total_bombs = $total_coins = $total_timeout = 0;
            foreach(self::$summary['keys'] as $row){
                $total_coins += $row['coin_value'];
                $total_bombs += $row['bomb_value'];
                $total_timeout += $row['timeout'];
                echo self::utf_8_sprintf($fstring,
                            $row['name'],
                            $row['key'],
                            $row['coin_value']!=0?$row['coin_value']:"---",
                            $row['bomb_value']!=0?$row['bomb_value']:"---",
                            $row['timeout']>0?gmdate("H:i:s", $row['timeout']):"---",
                            $row['next_cgrp_id']?$row['next_cgrp_id']:"---");
            }

            self::print_table_separator($fields_widths);
            echo self::utf_8_sprintf($fstring,
                        "Total:",
                        "",
                        $total_coins,
                        $total_bombs,
                        self::format_time($total_timeout),
                        "");


            echo "\n";

        }
        catch (Iquest_VerifyFailedException $e){
            fwrite(STDERR, "\nSORRY VOLE ERROR:\n");
            Console_Cli::print_exception_error($e);
            fwrite(STDERR, "\n");
            fwrite(STDERR, $e->get_info());
            exit(1);
        }
        catch (Iquest_InvalidConfigException $e){
            fwrite(STDERR, "\nSORRY VOLE ERROR:\n");
            Console_Cli::print_exception_error($e);
            exit(1);
        }
        catch(Traccar_api_query_exception $e){
            fwrite(STDERR, "\nSORRY VOLE ERROR:\n");
            Console_Cli::print_exception_error($e);
            exit(1);
        }
        catch(PDOException $e){
            $message = "DB query failed";
            if ($e->query){
                $message .= ":\n{$e->query}";
            }

            fwrite(STDERR, "\n$message\n");
            Console_Cli::print_exception_error($e);
            throw $e;
        }
        catch (exception $e){
            fwrite(STDERR, "\nUnexpected exception. See PHP error log for details:\n");
            Console_Cli::print_exception_error($e);
            throw $e;
        }
    }

    static function zone_check(){
        foreach(static::$parsed_data["traccar_zones"] as $zone => $zone_attrs){
            if (empty($zone_attrs[Iquest_Tracker::ZONE_ATTR_KEY]) and
                empty($zone_attrs[Iquest_Tracker::ZONE_ATTR_MSG])){

                Console::log("*** WARNING: There is neither a KEY nor a MESSAGE specified for zone: $zone", Console::YELLOW);
            }

            if (!empty($zone_attrs[Iquest_Tracker::ZONE_ATTR_COND])){
                // verify the zone condition
                try{
                    Iquest_Condition::verifyCondition($zone_attrs[Iquest_Tracker::ZONE_ATTR_COND]);
                }
                catch(Exception $e){
                    throw new Iquest_VerifyFailedException(
                                "Condition of zone '{$zone}' is invalid: '{$zone_attrs[Iquest_Tracker::ZONE_ATTR_COND]}'. ".$e->getMessage()
                    );
                }
            }

        }
    }

    static function print_zone_summary(){

        echo "\n";
        Console::log("Traccar zone summary:", Console::LIGHT_GREEN, true, Console::UNDERLINE);

        ksort(static::$parsed_data["traccar_zones"]);

        foreach(static::$parsed_data["traccar_zones"] as $zone => $zone_attrs){
            Console::log("");
            Console::log("  Zone: $zone", Console::LIGHT_BLUE);

            if (isset($zone_attrs[Iquest_Tracker::ZONE_ATTR_PRIO])){
                Console::log("    Priority: ", Console::YELLOW, false);
                Console::log($zone_attrs[Iquest_Tracker::ZONE_ATTR_PRIO]);
            }
            if (isset($zone_attrs[Iquest_Tracker::ZONE_ATTR_KEY])){
                Console::log("    Key: ", Console::YELLOW, false);
                Console::log($zone_attrs[Iquest_Tracker::ZONE_ATTR_KEY]);
            }
            if (isset($zone_attrs[Iquest_Tracker::ZONE_ATTR_MSG])){
                Console::log("    Message: ", Console::YELLOW, false);
                Console::log($zone_attrs[Iquest_Tracker::ZONE_ATTR_MSG]);
            }
            if (isset($zone_attrs[Iquest_Tracker::ZONE_ATTR_COND])){
                Console::log("    Condition: ", Console::YELLOW, false);
                Console::log($zone_attrs[Iquest_Tracker::ZONE_ATTR_COND]);
            }

            if (isset($zone_attrs['referenced_by'])){
                foreach($zone_attrs['referenced_by'] as $ref){
                    Console::log("    Set in: ", Console::YELLOW, false);
                    Console::log($ref);
                }
            }
        }
    }

    static function get_field_widths($fields, $fields_headers){
        $field_widths = array();
        foreach($fields_headers as $key => $val){
            $field_widths[$key] = iconv_strlen($val, "UTF-8");

            foreach($fields as $field){
                $width = iconv_strlen($field[$key], "UTF-8");
                if ($field_widths[$key] < $width) $field_widths[$key] = $width;
            }
        }

        return $field_widths;
    }

    static function get_formating_string($fields_widths, $separator){
        $fstr = $separator;

        foreach($fields_widths as $width){
            $fstr .= "%-".$width."s".$separator;
        }
        return $fstr."\n";
    }

    static function print_table_heading($fields_headers, $fields_widths){
        $format_str = self::get_formating_string($fields_widths, "^");
        $args = array_merge(array($format_str), $fields_headers);

        echo call_user_func_array(array('self', 'utf_8_sprintf'), $args);
    }

    static function print_table_separator($fields_widths){
        $len = 1;
        foreach($fields_widths as $width){
            $len += $width + 1;
        }

        for($i=0; $i<$len; $i++){
            echo "-";
        }
        echo "\n";
    }

}
