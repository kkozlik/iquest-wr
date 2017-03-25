<?php

require dirname(__FILE__)."/../prepend.php";

unset($GLOBALS['page_attributes']['giveitup_url']);
$GLOBALS['page_attributes']['events_url'] = $controler->url('event.php');
$GLOBALS['page_attributes']['overview_url'] = $controler->url('main.php');
$GLOBALS['page_attributes']['team_rank_url'] = $controler->url('team_rank.php');
$GLOBALS['page_attributes']['logo_url'] = $controler->url('../logo.php');


$GLOBALS['page_attributes']['css_file'][] = $GLOBALS['config']->style_src_path."bootstrap-datetimepicker.min.css";
$GLOBALS['page_attributes']['required_javascript'][] = "bootstrap-datetimepicker.min.js";


?>
