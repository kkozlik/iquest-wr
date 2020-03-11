<?php

class Iquest_Tracker{
    private $team_id;
    private $team = null;
    private $traccar = null;


    // zone priority - in case user is in multiple zones, the zone with higher priority is selected
    const ZONE_ATTR_PRIO = "iq-priority";
    const ZONE_ATTR_KEY  = "iq-key";
    const ZONE_ATTR_MSG  = "iq-msg";

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
        // @TODO: implement conditional
        return Iquest_Options::get(Iquest_Options::TRACCAR_ENABLED);
    }

    public function get_location(){
        global $config, $lang_str;

        $position = null;
        $team = $this->get_team();

        try{
            $traccar = $this->get_traccar();
            $position = $traccar->get_pos_by_dev($team->tracker_id);
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
        ];

        return $out;
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

            foreach($zones as $zone){
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

            Iquest_Events::add(Iquest_Events::LOCATION_CHECK,
                    false,
                    array());

            return $result;
        }

        if (!empty($selectedZone->attributes[self::ZONE_ATTR_MSG])){
            $result['status'] = true;
            Iquest_info_msg::add_msg($selectedZone->attributes[self::ZONE_ATTR_MSG]);
        }

        if (!empty($selectedZone->attributes[self::ZONE_ATTR_KEY])){
            // @TODO: use different error messages (location related) in verify_key()
            $solution = Iquest_Solution::verify_key($selectedZone->attributes[self::ZONE_ATTR_KEY], $this->team_id);

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
