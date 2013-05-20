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

?>
