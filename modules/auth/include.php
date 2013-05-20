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
function auth_init(){
    global $config, $_SERWEB, $lang_str;

    /* load lang file for this module */
    load_another_lang('auth');
    require_once($_SERWEB["configdir"] . "config.auth.php");
}

require_once( dirname(__FILE__)."/classes.php" );

?>
