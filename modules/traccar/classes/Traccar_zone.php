<?php

class Traccar_zone{

    public $id;
    public $name;
    public $description;
    public $attributes;
    public $calendarId;
    public $area;

    static $zones = null;

    public static function create($apiObj){
        $o = new Traccar_zone();

        $o->id = $apiObj['id'];
        $o->name = $apiObj['name'];
        $o->description = $apiObj['description'];
        $o->attributes = $apiObj['attributes'];
        $o->calendarId = $apiObj['calendarId'];
        $o->area = $apiObj['area'];

        return $o;
    }

    public function to_api_obj(){
        $apiObj = [];

        $apiObj['id'] = $this->id;
        $apiObj['name'] = $this->name;
        $apiObj['description'] = $this->description;
        $apiObj['attributes'] = $this->attributes;
        $apiObj['calendarId'] = $this->calendarId;
        $apiObj['area'] = $this->area;

        return $apiObj;
    }

    public function update($server){
        return $server->query("geofences/{$this->id}",
                                [],
                                ['method' => 'PUT',
                                 'body' => $this->to_api_obj(),
                                ]
                               );
    }

    public static function load_zones($server){
        if (!is_null(static::$zones)) return;

        $resp =  $server->query('geofences');

        $zones = [];
        foreach($resp as $zone){
            $zones[$zone['id']] = static::create($zone);
        }
        static::$zones = $zones;
    }

    public static function fetch($server, $id){

        self::load_zones($server);

        if (isset(static::$zones[$id])) return static::$zones[$id];
        return null;
    }

    public static function fetch_by_name($server, $name){

        self::load_zones($server);

        foreach(static::$zones as $zone){
            if ($zone->name == $name) return $zone;
        }

        return null;
    }

}
