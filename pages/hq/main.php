<?php

$_phplib_page_open = array("sess" => "iquest_hq_session",
                           "auth" => "iquest_hq_auth");

$_data_layer_required_methods=array();
$_required_modules = array('iquest', 'auth');
$_required_apu = array('apu_iquest_hq'); 

require dirname(__FILE__)."/prepend.php";


$apu    = new apu_iquest_hq();


$controler->add_apu($apu);
$controler->set_template_name('hq/main.tpl');
$controler->start();


?>
