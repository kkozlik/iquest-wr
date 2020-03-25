<?php

class Iquest_Tracker{
    private $team_id;
    private $team = null;
    private $traccar = null;


    // zone priority - in case user is in multiple zones, the zone with higher priority is selected
    const ZONE_ATTR_PRIO = "iq-priority";
    const ZONE_ATTR_KEY  = "iq-key";
    const ZONE_ATTR_MSG  = "iq-msg";
    const ZONE_ATTR_COND = "iq-condition";

    public function __construct($team_id){
        $this->team_id = $team_id;
    }

    private function get_team(){
        if ($this->team) return $this->team;
        $team = Iquest_Team::fetch_by_id($this->team_id);

        if (!$team){
            ErrorHandler::add_error("Invalid team ID");
            return false;
        }

        $this->team = $team;
        return $this->team;
    }

    private function get_traccar(){
        if ($this->traccar) return $this->traccar;

        $this->traccar = new Traccar([
                'auth_token' => Iquest_Options::get(Iquest_Options::TRACCAR_AUTH_TOKEN),
                'server_addr' => Iquest_Options::get(Iquest_Options::TRACCAR_ADDR)
            ]);

        return $this->traccar;
    }

    public function is_tracking_enabled(){
        $enabled = Iquest_Options::get(Iquest_Options::TRACCAR_ENABLED);

        if (is_numeric($enabled)) return (bool)$enabled;

        return Iquest_Condition::evalueateCondition($enabled, ['team_id' => $this->team_id]);
    }

    /**
     * Set position of given device to specified position in traccar
     *
     * @param string $devId
     * @param float $lat
     * @param float $lon
     * @return bool
     */
    public static function set_position($devId, $lat, $lon){
        global $lang_str;

        $server_addr = Iquest_Options::get(Iquest_Options::TRACCAR_ADDR);

        $devId = rawurlencode($devId);
        $lat   = rawurlencode($lat);
        $lon   = rawurlencode($lon);

        $url = "http://$server_addr:5055/?id=$devId&lat=$lat&lon=$lon";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        sw_log(__CLASS__."::".__FUNCTION__.": Executing Traccar OSMAnd query: url:'$url'", PEAR_LOG_DEBUG);

        if (PHPlib::$session) PHPlib::$session->close_session();
        $output = curl_exec($curl);
        if (PHPlib::$session) PHPlib::$session->reopen_session();

        if (false === $output){
            $err = $lang_str['traccar_err_api_call_error'].curl_error($curl);
            curl_close($curl);

            ErrorHandler::add_error($err);
            sw_log(__CLASS__.": Failed to query url: ".$url, PEAR_LOG_ERR);
            sw_log(__CLASS__.": ".$err, PEAR_LOG_ERR);
            return false;
        }

        curl_close($curl);
        return true;
    }

    public function get_location_of_device($devId){
        global $config, $lang_str;

        $position = null;

        try{
            $traccar = $this->get_traccar();
            $position = $traccar->get_pos_by_dev($devId);
        }
        catch(Traccar_api_query_exception $e){
            sw_log_exception($e);
            ErrorHandler::add_error($lang_str['iquest_err_internal_error']);
            return [];
        }

        if (!$position){
            ErrorHandler::add_error($lang_str['iquest_err_tracker_get_location']);
            return [];
        }

        $now = new DateTime('now');
        $interval = $now->diff($position->time);

        $position->time->setTimezone(new DateTimeZone($config->timezone));


        if ($interval->d > 0 )     {$lastupdate = $interval->format('%a dny'); $lastupdate_ts = $position->time->format("Y-m-d H:i:s");}
        elseif ($interval->h > 0 ) {$lastupdate = $interval->format('%h hod'); $lastupdate_ts = $position->time->format("H:i:s");}
        elseif ($interval->i > 0 ) {$lastupdate = $interval->format('%i min'); $lastupdate_ts = $position->time->format("H:i:s");}
        else                       {$lastupdate = $interval->format('%s sec'); $lastupdate_ts = $position->time->format("H:i:s");}

        $out = [
            "lat"           => $position->latitude,
            "lon"           => $position->longitude,
            "lastupdate"    => $lastupdate,
            "lastupdate_ts" => $lastupdate_ts,
            "age"           => $now->getTimestamp() - $position->time->getTimestamp(),
        ];

        return $out;
    }

    public function get_team_location(){
        $team = $this->get_team();
        return $this->get_location_of_device($team->tracker_id);
    }


    public function check_location(){
        global $lang_str;

        $selectedZone = null;
        $team = $this->get_team();

        $result = [
            'status' => false,
            'solution' => null
        ];

        try{
            $traccar = $this->get_traccar();
            $zones = $traccar->get_zone_by_dev($team->tracker_id);
            $device = $traccar->get_device($team->tracker_id);

            $now = new DateTime('now');
            $data_age = $now->getTimestamp() - $device->lastUpdate->getTimestamp();


            foreach($zones as $zone){
                if (!empty($zone->attributes[self::ZONE_ATTR_COND])){
                    // skip zone if condition evalueate to false
                    if (!Iquest_Condition::evalueateCondition($zone->attributes[self::ZONE_ATTR_COND], ['team_id' => $this->team_id])) {
                        sw_log("check_location - skipping zone {$zone->name}. Condition not met: {$zone->attributes[self::ZONE_ATTR_COND]}", PEAR_LOG_INFO);
                        continue;
                    }
                }

                if (!$selectedZone) { $selectedZone = $zone; continue; }

                if (isset($selectedZone->attributes[self::ZONE_ATTR_PRIO])){
                    if ($zone->attributes[self::ZONE_ATTR_PRIO] and
                        (int)$zone->attributes[self::ZONE_ATTR_PRIO] > (int)$selectedZone->attributes[self::ZONE_ATTR_PRIO]) $selectedZone = $zone;
                }
                else{
                    if (isset($zone->attributes[self::ZONE_ATTR_PRIO])) $selectedZone = $zone;
                    elseif($zone->attributes[self::ZONE_ATTR_KEY] and !isset($selectedZone->attributes[self::ZONE_ATTR_KEY])) $selectedZone = $zone;
                }
            }
        }
        catch(Traccar_api_query_exception $e){
            sw_log_exception($e);
            ErrorHandler::add_error($lang_str['iquest_err_internal_error']);
            return $result;
        }

        if (!$selectedZone){
            ErrorHandler::add_error($lang_str['iquest_err_tracker_wrong_location']);

            if ($data_age > 3*60){
                ErrorHandler::add_error(str_replace('<age>', '3 min', $lang_str['iquest_err_tracker_old_location']));
            }

            Iquest_Events::add(Iquest_Events::LOCATION_CHECK,
                    false,
                    array());

            return $result;
        }

        sw_log("check_location - selected zone {$selectedZone->name}.", PEAR_LOG_INFO);

        if (!empty($selectedZone->attributes[self::ZONE_ATTR_MSG])){
            $result['status'] = true;
            Iquest_info_msg::add_msg($selectedZone->attributes[self::ZONE_ATTR_MSG]);
        }

        if (!empty($selectedZone->attributes[self::ZONE_ATTR_KEY])){
            // Use different (location related) error messages in verify_key()
            $opt = [ 'err' => [
                        'key_dup' => $lang_str['iquest_err_tracker_location_dup'],
                        'key_not_reachable' => $lang_str['iquest_err_tracker_location_not_reachable'],
                    ]];
            $solution = Iquest_Solution::verify_key($selectedZone->attributes[self::ZONE_ATTR_KEY], $this->team_id, $opt);

            if ($solution){
                $result['status'] = true;
                $result['solution'] = $solution;
            }
        }

        Iquest_Events::add(Iquest_Events::LOCATION_CHECK,
                true,
                array("zone" => $selectedZone->name));

        return $result;
    }

}
