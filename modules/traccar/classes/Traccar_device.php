<?php

class Traccar_device{

    public $id=-1;
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

    public function to_api_obj(){
        $apiObj = [];

        $apiObj['id'] = $this->id;
        $apiObj['name'] = $this->name;
        $apiObj['uniqueId'] = $this->uniqueId;
        $apiObj['groupId'] = $this->groupId;

        return $apiObj;
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

    public function insert($server){
        return $server->query("devices",
                                [],
                                ['method' => 'POST',
                                 'body' => $this->to_api_obj(),
                                ]
                               );
    }

    public function update($server){
        return $server->query("devices/{$this->id}",
                                [],
                                ['method' => 'PUT',
                                 'body' => $this->to_api_obj(),
                                ]
                               );
    }

}

