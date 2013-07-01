<?php

/* This file can redefine any config value from framework
 */

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


    $config->iquest_data_dir = realpath($_SERWEB["configdir"]."../data")."/";

    $config->enable_logging = true;
    $config->log_file = "/var/log/iquest";
    $config->log_level = "PEAR_LOG_DEBUG";
    
    $config->custom_act_log_function = "iquest_action_log";

    $config->html_doctype = "html";
    
    $config->html_headers[] = "<!--[if IE]><LINK REL=\"StyleSheet\" HREF=\"".htmlspecialchars($config->style_src_path."ie.css")."\" TYPE=\"text/css\" /><![endif]-->\n";
?>
