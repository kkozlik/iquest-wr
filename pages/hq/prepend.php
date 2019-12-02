<?php

require dirname(__FILE__)."/../prepend.php";

$GLOBALS['controler']->attach_listener("pre_html_output", function($event){

    unset($GLOBALS['page_attributes']['giveitup_url']);
    $GLOBALS['page_attributes']['events_url']       = $GLOBALS['controler']->url('event.php');
    $GLOBALS['page_attributes']['overview_url']     = $GLOBALS['controler']->url('main.php');
    $GLOBALS['page_attributes']['team_rank_url']    = $GLOBALS['controler']->url('team_rank.php');
    $GLOBALS['page_attributes']['logo_url']         = $GLOBALS['controler']->url('../logo.php');


    $GLOBALS['page_attributes']['css_file'][] = $GLOBALS['config']->style_src_path."bootstrap-datetimepicker.min.css";
    $GLOBALS['page_attributes']['required_javascript'][] = "bootstrap-datetimepicker.min.js";

    $GLOBALS['page_attributes']['html_title'] = $GLOBALS['page_attributes']['game_name']." - HQ";

});
