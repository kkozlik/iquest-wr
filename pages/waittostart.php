<?php

$_phplib_page_open = array("sess" => "iquest_session");
$_required_modules = array('iquest');

require dirname(__FILE__)."/prepend.php";

Iquest_auth::access_check(['team']);

if (Iquest::is_started()){
    $controler->redirect("main.php");
    exit;
}

$start_time = Iquest_Options::get(Iquest_Options::START_TIME);
$sec_remaining = $start_time - time();

$smarty->assign("start_time", $start_time);
$smarty->assign("time_remaining", gmdate("H:i:s", $sec_remaining));

$controler->set_onload_js("enable_countdown('#start_coundown', $sec_remaining);");

$controler->set_template_name('iquest/waittostart.tpl');
$controler->start();

