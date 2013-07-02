<?php

/*
 *  Definition of table team
 */                                                             
$config->data_sql->iquest_team = new stdClass();
$config->data_sql->iquest_team->cols = new stdClass();
        
$config->data_sql->iquest_team->table_name =        "team";

$config->data_sql->iquest_team->cols->id =          "team_id";
$config->data_sql->iquest_team->cols->name =        "name";
$config->data_sql->iquest_team->cols->username =    "username";
$config->data_sql->iquest_team->cols->passwd =      "passwd";
$config->data_sql->iquest_team->cols->active =      "active";     



?>
