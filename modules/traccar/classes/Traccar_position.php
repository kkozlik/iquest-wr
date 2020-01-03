<?php

class Traccar_position{

    public $id;
    public $deviceId;
    public $serverTime;
    public $outdated;
    public $valid;
    public $latitude;
    public $longitude;
    public $altitude;
    public $speed;
    public $course;
    public $accuracy;

    public static function create($apiObj){
        $o = new Traccar_position();

        $o->id          = $apiObj['id'];
        $o->deviceId    = $apiObj['deviceId'];
        $o->time        = Traccar::parse_time($apiObj['serverTime']);
        $o->outdated    = (bool)$apiObj['outdated'];
        $o->valid       = (bool)$apiObj['valid'];
        $o->latitude    = (float)$apiObj['latitude'];
        $o->longitude   = (float)$apiObj['longitude'];
        $o->altitude    = (float)$apiObj['altitude'];
        $o->speed       = (float)$apiObj['speed'];
        $o->course      = $apiObj['course'];
        $o->accuracy    = $apiObj['accuracy'];

        return $o;
    }

    public static function fetch($server, $id){
        try{
            $resp = $server->query('positions', ['id' => $id]);
            $resp = reset($resp);
            return static::create($resp);
        }
        catch(Traccar_api_not_found_exception $e) {
            return null;
        }
    }
}

