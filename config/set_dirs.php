<?php

// File included early in the script execution. Path to this file should be 
// configured in SERWEB_SET_DIRS environment variable.
//
// This file should configure paths to application directories, so framework 
// knows where to search for them.


global $_SERWEB, $config;

// directory that should be accesible directly from the web
$_SERWEB["pagesdir"] =      $_SERWEB["approotdir"]."/pages/";
// directory containing configuration
$_SERWEB["configdir"] =     $_SERWEB["approotdir"]."/config/";
// directory containing language strings
$_SERWEB["langdir"] =       $_SERWEB["approotdir"]."/lang/";
// directory containing modules
$_SERWEB["modulesdir"] =    $_SERWEB["approotdir"]."/modules/";
// directory containing smarty templates
$_SERWEB["templatesdir"] =  $_SERWEB["approotdir"]."/templates/";


// if you need customize page controler specify here its class name and file
// containing it.
//$_SERWEB['_page_controller_classname'] = "my_page_conroler";
//$_SERWEB['_page_controller_filename']  = $_SERWEB["pagesdir"] . "my_page_controler.php";


// The web path bellow which web accesible directories begin to spread. 
// Don't forget trailing slash.
//
// If you need change $config->img_src_path, $config->js_src_path or
// $config->style_src_path do it rather in config.php file. Otherwise your
// setting will be rewriten by the framework.

$config->root_path=getenv('IQUEST_ROOT_PATH');

?>
