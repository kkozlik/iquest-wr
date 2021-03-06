<?php

/* This file can redefine any config value from framework
 */

    if (!isset($config->iquest)) $config->iquest = new stdclass();


    $config->iquest->notifications = array();
    /**
     *  Configure send email notifications when team find a solution
     *
     *  $config->iquest->notifications['solution-id'] = array("foo@bar.cz",
     *                                                        "bagr@bagr.com");
     */


    /**
     *  Default timezone:
     *
     *  List of supported timezones:
     *  http://www.php.net/manual/en/timezones.php
     */
    $config->timezone = "Europe/Prague";


    $config->data_sql->abstraction_layer="PDO";

    $config->data_sql->type="mysql";            //type of db host, enter "mysql" for MySQL or "pgsql" for PostgreSQL

    $i=0;
    $config->data_sql->host[$i]['dsn']=  "mysql:dbname=iquest";    // database service name
    // $config->data_sql->host[$i]['host']= "localhost";   //database host
    // $config->data_sql->host[$i]['port']= "";            //database port - leave empty for default
    // $config->data_sql->host[$i]['name']= "iquest";      //database name
    $config->data_sql->host[$i]['user']= "iquest";      //database conection user
    $config->data_sql->host[$i]['pass']= "iquest99";    //database conection password

    $config->use_rpc = false;

    $config->force_lang = 'cs-utf-8';

    // Following is needed by function remove_diacritics(). It should be set to
    // some UTF8 charset installed on the system.
    $config->iquest_locale = 'cs_CZ.UTF-8';


    $config->img_src_path =     $config->root_path."img/";
    $config->iquest_data_dir = realpath($_SERWEB["approotdir"]."data")."/";
    $config->traccar_cookie_file = "/var/lib/iquest/traccar-cookie.txt";

    // Directory where smarty stores compiled templates
    $config->smarty_compile_dir = realpath($_SERWEB["approotdir"]."smarty")."/";

    $config->enable_logging = true;
    $config->log_file = "/var/log/iquest";
    $config->log_level = "PEAR_LOG_DEBUG";

    $config->custom_act_log_function = "iquest_action_log";
    $config->custom_log_function = "iquest_log";

    // Whether include file and line identification in the log messages
    $config->iquest_log_include_file = false;

    $config->html_doctype = "html";


// The 'common' and 'iquest-auth' module shall be always loaded
$config->modules["common"] = true;
$config->modules["iquest-auth"] = true;
