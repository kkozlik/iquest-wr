<?php
/**
 *  File automaticaly included by the framework when module is loaded
 * 
 *  @author     Karel Kozlik
 *  @package    iquest
 */ 

/**
 *  module init function
 *
 *  Is called when all files are included
 */
function iquest_init(){
    global $config, $_SERWEB, $lang_str, $data;

    $data->connect_to_db();

    /* load lang file for this module */
    load_another_lang('iquest');
    require_once($_SERWEB["configdir"] . "config.iquest.php");
}

require_once( dirname(__FILE__)."/classes.php" );
require_once( dirname(__FILE__)."/options.php" );
require_once( dirname(__FILE__)."/events.php" );

?>
