<?php

class Traccar_permission{

    public static function add_zone_to_group($server, $zone_id, $group_id){
        $body = [
            "groupId" => $group_id,
            "geofenceId" => $zone_id
        ];

        return $server->query("permissions",
                                [],
                                ['method' => 'POST',
                                 'body' => $body,
                                ]
                               );
    }

}
