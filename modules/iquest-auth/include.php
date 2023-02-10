<?php
/**
 *  module init function
 *
 *  Is called when all files are included
 */
function iquest_auth_init(){
    global $config, $_SERWEB, $lang_str;

    /* load lang file for this module */
    load_another_lang('auth');
    // require_once($_SERWEB["configdir"] . "config.iquest-auth.php");
}

require_once( __DIR__."/auth.php" );
require_once( __DIR__."/jwt.php" );

