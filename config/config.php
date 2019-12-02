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


    $config->data_sql->abstraction_layer="MDB2";

    $config->data_sql->type="mysqli";            //type of db host, enter "mysql" for MySQL or "pgsql" for PostgreSQL

    $i=0;
    $config->data_sql->host[$i]['host']= "localhost";   //database host
    $config->data_sql->host[$i]['port']= "";            //database port - leave empty for default
    $config->data_sql->host[$i]['name']= "iquest";      //database name
    $config->data_sql->host[$i]['user']= "iquest";      //database conection user
    $config->data_sql->host[$i]['pass']= "iquest99";    //database conection password

    $config->use_rpc = false;

    $config->force_lang = 'cs-utf-8';

    // Following is needed by function remove_diacritics(). It should be set to
    // some UTF8 charset installed on the system.
    $config->iquest_locale = 'cs_CZ.UTF-8';


    $config->img_src_path =     $config->root_path."img/";
    $config->iquest_data_dir = realpath($_SERWEB["approotdir"]."data")."/";

    // Directory where smarty stores compiled templates
    $config->smarty_compile_dir = realpath($_SERWEB["approotdir"]."smarty")."/";

    $config->enable_logging = true;
    $config->log_file = "/var/log/iquest";
    $config->log_level = "PEAR_LOG_DEBUG";

    $config->custom_act_log_function = "iquest_action_log";

    $config->html_doctype = "html";

    $config->html_headers[] = "<!--[if lt IE 9]><LINK REL=\"StyleSheet\" HREF=\"".htmlspecialchars($config->style_src_path."ie.css")."\" TYPE=\"text/css\" /><![endif]-->\n";


// The 'common' and 'iquest-auth' module shall be always loaded
$config->modules["common"] = true;
$config->modules["iquest-auth"] = true;
