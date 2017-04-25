<?php

class Iquest_info_msg{

    public static function add_msg($msg, $type = null){
        global $controler;
        
        $info_msg=array();
        $info_msg['long'] = $msg;
        $info_msg['type'] = $type;
        $controler->add_message($info_msg);
    }
}
