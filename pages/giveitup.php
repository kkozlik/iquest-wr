<?php

$_phplib_page_open = array("sess" => "iquest_session",
                           "auth" => "iquest_auth");

$_data_layer_required_methods=array();
$_required_modules = array('iquest', 'auth');
$_required_apu = array('apu_iquest_giveitup'); 

require dirname(__FILE__)."/prepend.php";

if (Iquest::is_over()){
    $controler->redirect("main.php");
    exit;
} 

$apu    = new apu_iquest_giveitup();

$apu->set_opt("main_url", "main.php");

$controler->add_apu($apu);
$controler->set_template_name('iquest/giveitup.tpl');
$controler->start();

?>
