<?php

$_phplib_page_open = array("sess" => "iquest_org_session",
                           "auth" => "iquest_org_auth");

$_data_layer_required_methods=array();
$_required_modules = array('iquest', 'auth');
$_required_apu = array('apu_iquest_org'); 

require dirname(__FILE__)."/../prepend.php";


$apu    = new apu_iquest_org();


$controler->add_apu($apu);
$controler->set_template_name('org/main.tpl');
$controler->start();


?>
