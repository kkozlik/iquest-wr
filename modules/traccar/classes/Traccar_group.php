<?php

class Traccar_group{

    public $id;
    public $name;
    public $attributes;
    public $groupId;

    static $groups = null;

    public static function create($apiObj){
        $o = new Traccar_group();

        $o->id = $apiObj['id'];
        $o->name = $apiObj['name'];
        $o->attributes = $apiObj['attributes'];
        $o->groupId = $apiObj['groupId'];

        return $o;
    }

    public static function load_groups($server){
        if (!is_null(static::$groups)) return;

        $resp =  $server->query('groups');

        $groups = [];
        foreach($resp as $group){
            $groups[$group['id']] = static::create($group);
        }
        static::$groups = $groups;
    }

    public static function fetch($server, $id){

        self::load_groups($server);

        if (isset(static::$groups[$id])) return static::$groups[$id];
        return null;
    }

    public static function fetch_by_name($server, $name){

        self::load_groups($server);

        foreach(static::$groups as $group){
            if ($group->name == $name) return $group;
        }

        return null;
    }

}
