<?php
/**
 *  File automaticaly included by the framework when module is loaded
 *
 *  @author     Karel Kozlik
 *  @package    traccar
 */

/**
 *  module init function
 *
 *  Is called when all files are included
 */
function traccar_init(){
    global $config, $_SERWEB, $lang_str, $data;

    /* load lang file for this module */
    load_another_lang('traccar');
    // require_once($_SERWEB["configdir"] . "config.iquest.php");
}

require_once("classes/Traccar.php");
require_once("classes/Traccar_exceptions.php");
require_once("classes/Traccar_device.php");
require_once("classes/Traccar_group.php");
require_once("classes/Traccar_permission.php");
require_once("classes/Traccar_position.php");
require_once("classes/Traccar_zone.php");
