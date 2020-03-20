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
function iquest_cli_init(){
    global $config, $_SERWEB, $lang_str, $data;

    $data->connect_to_db();

    /* load lang file for this module */
    // load_another_lang('iquest');
    // require_once($_SERWEB["configdir"] . "config.iquest.php");
}

include_module('iquest');

require_once( "classes/Exceptions.php" );
require_once( "classes/Chroust.php" );
require_once( "classes/Iquest_Metadata.php" );
require_once( "classes/Iquest_Verbose_Output.php" );
