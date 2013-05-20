<?php
$_data_layer_required_methods=array();
$_phplib_page_open = array("sess" => "iquest_session");
$_required_modules = array('auth');
$_required_apu = array('apu_auth_login'); 

require dirname(__FILE__)."/prepend.php";


$apu = new apu_auth_login();
$apu->set_opt("redirect_on_login", "main.php");

$page_attributes['logout']=false;

$controler->add_apu($apu);

$controler->set_template_name('auth/index.tpl');
$controler->start();


?>
