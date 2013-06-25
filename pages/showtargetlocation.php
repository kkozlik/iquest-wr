<?php

$_phplib_page_open = array("sess" => "iquest_session",
                           "auth" => "iquest_auth");

$_data_layer_required_methods=array();
$_required_modules = array('iquest', 'auth');
$_required_apu = array('apu_iquest_reveal_goal'); 

require dirname(__FILE__)."/prepend.php";


$apu    = new apu_iquest_reveal_goal();

$apu->set_opt("main_url", "main.php");

$controler->add_apu($apu);
$controler->set_template_name('iquest/showtargetlocation.tpl');
$controler->start();

?>
