<?php

$_phplib_page_open = array("sess" => "iquest_session",
                           "auth" => "iquest_auth");

$_data_layer_required_methods=array();
$_required_modules = array('iquest', 'auth');
$_required_apu = array('apu_iquest'); 

require dirname(__FILE__)."/prepend.php";


if (Iquest::is_started()){
    $controler->redirect("main.php");
    exit;
} 

$start_time = Iquest_Options::get(Iquest_Options::START_TIME);
$sec_remaining = $start_time - time();

$smarty->assign("start_time", $start_time);
$smarty->assign("time_remaining", gmdate("H:i:s", $sec_remaining));

$controler->set_template_name('iquest/waittostart.tpl');
$controler->start();

?>
