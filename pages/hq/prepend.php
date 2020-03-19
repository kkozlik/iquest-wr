<?php

require dirname(__FILE__)."/../prepend.php";

$GLOBALS['controler']->attach_listener("pre_html_output", function($event){

    unset($GLOBALS['page_attributes']['giveitup_url']);
    $GLOBALS['page_attributes']['events_url']       = $GLOBALS['controler']->url('event.php');
    $GLOBALS['page_attributes']['overview_url']     = $GLOBALS['controler']->url('main.php');
    $GLOBALS['page_attributes']['team_rank_url']    = $GLOBALS['controler']->url('team_rank.php');
    $GLOBALS['page_attributes']['set_position_url'] = $GLOBALS['controler']->url('set_position.php');
    $GLOBALS['page_attributes']['logo_url']         = $GLOBALS['controler']->url('../logo.php');

    $GLOBALS['page_attributes']['html_title'] = $GLOBALS['page_attributes']['game_name']." - HQ";

});
