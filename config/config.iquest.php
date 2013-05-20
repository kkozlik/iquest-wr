<?php

$config->data_sql->iquest_cgrp = new stdClass();
$config->data_sql->iquest_cgrp->cols = new stdClass();
        
$config->data_sql->iquest_cgrp->table_name =        "clue_grp";

$config->data_sql->iquest_cgrp->cols->id =          "cgrp_id";
$config->data_sql->iquest_cgrp->cols->ref_id =      "ref_id";
$config->data_sql->iquest_cgrp->cols->name =        "name";



$config->data_sql->iquest_cgrp_open = new stdClass();
$config->data_sql->iquest_cgrp_open->cols = new stdClass();
        
$config->data_sql->iquest_cgrp_open->table_name =      "open_cgrp_team";

$config->data_sql->iquest_cgrp_open->cols->team_id =   "team_id";
$config->data_sql->iquest_cgrp_open->cols->cgrp_id =   "cgrp_id";
$config->data_sql->iquest_cgrp_open->cols->gained_at = "gained_at";



$config->data_sql->iquest_clue = new stdClass();
$config->data_sql->iquest_clue->cols = new stdClass();
        
$config->data_sql->iquest_clue->table_name =      "clue";

$config->data_sql->iquest_clue->cols->id =              "clue_id";
$config->data_sql->iquest_clue->cols->ref_id =          "ref_id";
$config->data_sql->iquest_clue->cols->cgrp_id =         "cgrp_id";
$config->data_sql->iquest_clue->cols->filename =        "filename";
$config->data_sql->iquest_clue->cols->content_type =    "content_type";
$config->data_sql->iquest_clue->cols->comment =         "comment";



$config->data_sql->iquest_clue2solution = new stdClass();
$config->data_sql->iquest_clue2solution->cols = new stdClass();
        
$config->data_sql->iquest_clue2solution->table_name =           "clue_point_to_solution";

$config->data_sql->iquest_clue2solution->cols->clue_id =        "clue_id";
$config->data_sql->iquest_clue2solution->cols->solution_id =    "solution_id";

?>
