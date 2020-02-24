<?php

class Iquest_Tracker{
    private $team_id;
    private $team = null;
    private $traccar = null;

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

        // @TODO: make token and address configurable
        $this->traccar = new Traccar([
                'server_addr' => '44.177.215.1'
            ]);

        return $this->traccar;
    }

    public function is_tracking_enabled(){
        // @TODO: implement
        return true;
    }

    public function get_location(){
        global $config, $lang_str;

        $traccar = $this->get_traccar();
        $team = $this->get_team();

        $position = $traccar->get_pos_by_dev($team->tracker_id);

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

}
