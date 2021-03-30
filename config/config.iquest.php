<?php

if (!isset($config->iquest)) $config->iquest = new stdclass();
$config->iquest->graphviz_cmd = "dot";


/**
 *      clue_grp
 */
$config->data_sql->iquest_cgrp = new stdClass();
$config->data_sql->iquest_cgrp->cols = new stdClass();

$config->data_sql->iquest_cgrp->table_name =        "clue_grp";

$config->data_sql->iquest_cgrp->cols->id =          "cgrp_id";
$config->data_sql->iquest_cgrp->cols->ref_id =      "ref_id";
$config->data_sql->iquest_cgrp->cols->name =        "name";
$config->data_sql->iquest_cgrp->cols->ordering =    "ordering";



/**
 *      open_clue_grp_team
 */
$config->data_sql->iquest_cgrp_open = new stdClass();
$config->data_sql->iquest_cgrp_open->cols = new stdClass();

$config->data_sql->iquest_cgrp_open->table_name =      "open_cgrp_team";

$config->data_sql->iquest_cgrp_open->cols->team_id =   "team_id";
$config->data_sql->iquest_cgrp_open->cols->cgrp_id =   "cgrp_id";
$config->data_sql->iquest_cgrp_open->cols->gained_at = "gained_at";
$config->data_sql->iquest_cgrp_open->cols->time_shift ="time_shift";



/**
 *      clue
 */
$config->data_sql->iquest_clue = new stdClass();
$config->data_sql->iquest_clue->cols = new stdClass();

$config->data_sql->iquest_clue->table_name =      "clue";

$config->data_sql->iquest_clue->cols->id =              "clue_id";
$config->data_sql->iquest_clue->cols->ref_id =          "ref_id";
$config->data_sql->iquest_clue->cols->cgrp_id =         "cgrp_id";
$config->data_sql->iquest_clue->cols->filename =        "filename";
$config->data_sql->iquest_clue->cols->content_type =    "content_type";
$config->data_sql->iquest_clue->cols->type =            "type";
$config->data_sql->iquest_clue->cols->comment =         "comment";
$config->data_sql->iquest_clue->cols->ordering =        "ordering";



/**
 *      clue2solution
 */
$config->data_sql->iquest_clue2solution = new stdClass();
$config->data_sql->iquest_clue2solution->cols = new stdClass();

$config->data_sql->iquest_clue2solution->table_name =           "clue_point_to_solution";

$config->data_sql->iquest_clue2solution->cols->clue_id =        "clue_id";
$config->data_sql->iquest_clue2solution->cols->solution_id =    "solution_id";


/**
 *      hint
 */
$config->data_sql->iquest_hint = new stdClass();
$config->data_sql->iquest_hint->cols = new stdClass();

$config->data_sql->iquest_hint->table_name =      "hint";

$config->data_sql->iquest_hint->cols->id =              "hint_id";
$config->data_sql->iquest_hint->cols->ref_id =          "ref_id";
$config->data_sql->iquest_hint->cols->clue_id =         "clue_id";
$config->data_sql->iquest_hint->cols->filename =        "filename";
$config->data_sql->iquest_hint->cols->content_type =    "content_type";
$config->data_sql->iquest_hint->cols->timeout =         "timeout";
$config->data_sql->iquest_hint->cols->price =           "price";
$config->data_sql->iquest_hint->cols->ordering =        "ordering";
$config->data_sql->iquest_hint->cols->comment =         "comment";


/**
 *      hint_team
 */
$config->data_sql->iquest_hint_team = new stdClass();
$config->data_sql->iquest_hint_team->cols = new stdClass();

$config->data_sql->iquest_hint_team->table_name =           "hint_team";

$config->data_sql->iquest_hint_team->cols->team_id =        "team_id";
$config->data_sql->iquest_hint_team->cols->hint_id =        "hint_id";
$config->data_sql->iquest_hint_team->cols->show_at =        "show_at";
$config->data_sql->iquest_hint_team->cols->time_shift =     "time_shift";
$config->data_sql->iquest_hint_team->cols->for_sale =       "for_sale";


/**
 *      solution
 */
$config->data_sql->iquest_solution = new stdClass();
$config->data_sql->iquest_solution->cols = new stdClass();

$config->data_sql->iquest_solution->table_name =            "task_solution";

$config->data_sql->iquest_solution->cols->id =              "solution_id";
$config->data_sql->iquest_solution->cols->ref_id =          "ref_id";
$config->data_sql->iquest_solution->cols->name =            "name";
$config->data_sql->iquest_solution->cols->key =             "solution_key";
$config->data_sql->iquest_solution->cols->filename =        "filename";
$config->data_sql->iquest_solution->cols->content_type =    "content_type";
$config->data_sql->iquest_solution->cols->timeout =         "timeout";
$config->data_sql->iquest_solution->cols->countdown_start = "countdown_start";
$config->data_sql->iquest_solution->cols->comment =         "comment";
$config->data_sql->iquest_solution->cols->coin_value =      "coin_value";
$config->data_sql->iquest_solution->cols->bomb_value =      "bomb_value";


/**
 *      solution_next_cgrp
 */
$config->data_sql->iquest_solution_next_cgrp = new stdClass();
$config->data_sql->iquest_solution_next_cgrp->cols = new stdClass();

$config->data_sql->iquest_solution_next_cgrp->table_name =            "task_solution_nextcgrp";

$config->data_sql->iquest_solution_next_cgrp->cols->solution_id =     "solution_id";
$config->data_sql->iquest_solution_next_cgrp->cols->cgrp_id =         "cgrp_id";
$config->data_sql->iquest_solution_next_cgrp->cols->condition =       "cond";


/**
 *      solution_team
 */
$config->data_sql->iquest_solution_team = new stdClass();
$config->data_sql->iquest_solution_team->cols = new stdClass();

$config->data_sql->iquest_solution_team->table_name =           "task_solution_team";

$config->data_sql->iquest_solution_team->cols->team_id =        "team_id";
$config->data_sql->iquest_solution_team->cols->solution_id =    "solution_id";
$config->data_sql->iquest_solution_team->cols->show_at =        "show_at";
$config->data_sql->iquest_solution_team->cols->solved_at =      "solved_at";
$config->data_sql->iquest_solution_team->cols->time_shift =     "time_shift";



/**
 *      option
 */
$config->data_sql->iquest_option = new stdClass();
$config->data_sql->iquest_option->cols = new stdClass();

$config->data_sql->iquest_option->table_name =          "options";

$config->data_sql->iquest_option->cols->name =          "name";
$config->data_sql->iquest_option->cols->value =         "value";


/**
 *      event
 */
$config->data_sql->iquest_event = new stdClass();
$config->data_sql->iquest_event->cols = new stdClass();

$config->data_sql->iquest_event->table_name =           "event";

$config->data_sql->iquest_event->cols->id =             "event_id";
$config->data_sql->iquest_event->cols->team_id =        "team_id";
$config->data_sql->iquest_event->cols->timestamp =      "timestamp";
$config->data_sql->iquest_event->cols->type =           "type";
$config->data_sql->iquest_event->cols->success =        "success";
$config->data_sql->iquest_event->cols->data =           "data";


/**
 *      team rank
 */
$config->data_sql->iquest_team_rank = new stdClass();
$config->data_sql->iquest_team_rank->cols = new stdClass();

$config->data_sql->iquest_team_rank->table_name =       "team_rank";

$config->data_sql->iquest_team_rank->cols->timestamp =  "timestamp";
$config->data_sql->iquest_team_rank->cols->distance =   "distance";
$config->data_sql->iquest_team_rank->cols->rank =       "rank";
$config->data_sql->iquest_team_rank->cols->team_id =    "team_id";


/**
 *      team finish distance
 */
$config->data_sql->iquest_team_finish_distance = new stdClass();
$config->data_sql->iquest_team_finish_distance->cols = new stdClass();

$config->data_sql->iquest_team_finish_distance->table_name =       "team_finish_distance";

$config->data_sql->iquest_team_finish_distance->cols->team_id =    "team_id";
$config->data_sql->iquest_team_finish_distance->cols->timestamp =  "timestamp";
$config->data_sql->iquest_team_finish_distance->cols->distance =   "distance";


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
$config->data_sql->iquest_team->cols->wallet =      "wallet";
$config->data_sql->iquest_team->cols->bomb =        "bomb";
$config->data_sql->iquest_team->cols->tracker_id =  "tracker_id";
$config->data_sql->iquest_team->cols->time_shift =  "time_shift";
