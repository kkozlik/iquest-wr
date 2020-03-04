<?php

class Traccar_zone{

    public $id;
    public $name;
    public $description;
    public $attributes;

    static $zones = null;

    public static function create($apiObj){
        $o = new Traccar_zone();

        $o->id = $apiObj['id'];
        $o->name = $apiObj['name'];
        $o->description = $apiObj['description'];
        $o->attributes = $apiObj['attributes'];

        return $o;
    }

    public static function fetch($server, $id){

        if (is_null(static::$zones)){
            $resp =  $server->query('geofences');

            $zones = [];
            foreach($resp as $zone){
                $zones[$zone['id']] = static::create($zone);
            }
            static::$zones = $zones;
        }

        if (isset(static::$zones[$id])) return static::$zones[$id];
        return null;
    }
}
