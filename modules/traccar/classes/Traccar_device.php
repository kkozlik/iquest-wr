<?php

class Traccar_device{

    public $id;
    public $groupId;
    public $name;
    public $uniqueId;
    public $status;
    public $lastUpdate;
    public $positionId;
    public $geofenceIds;

    public static function create($apiObj){
        $o = new Traccar_device();

        $o->id = $apiObj['id'];
        $o->groupId = $apiObj['groupId'];
        $o->name = $apiObj['name'];
        $o->uniqueId = $apiObj['uniqueId'];
        $o->status = $apiObj['status'];
        $o->lastUpdate = Traccar::parse_time($apiObj['lastUpdate']);
        $o->positionId = $apiObj['positionId'];
        $o->geofenceIds = $apiObj['geofenceIds'];

        return $o;
    }

    public static function fetch($server, $uniqueId){
        try{
            $resp = $server->query('devices', ['uniqueId' => $uniqueId]);
            $resp = reset($resp);
            return static::create($resp);
        }
        catch(Traccar_api_not_found_exception $e) {
            return null;
        }
    }
}

