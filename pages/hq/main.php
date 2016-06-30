<?php

$_phplib_page_open = array("sess" => "iquest_hq_session",
                           "auth" => "iquest_hq_auth");

$_data_layer_required_methods=array();
$_required_modules = array('iquest', 'auth');
$_required_apu = array('apu_iquest_hq'); 

require dirname(__FILE__)."/prepend.php";

if (!empty($_GET['back_url'])) {
    $controler->change_url_for_reload($_GET['back_url']);
    $controler->set_get_param('back_url');
    $smarty->assign('back_url', $controler->url($_GET['back_url']));
}
else{
    $smarty->assign('back_url', $controler->url($_SERVER['PHP_SELF']));
}

$apu    = new apu_iquest_hq();


$controler->add_apu($apu);
$controler->set_template_name('hq/main.tpl');
$controler->add_required_javascript('jquery.kinetic.min.js');
$controler->add_required_javascript('jquery.floatThead.min.js');
$controler->start();


?>
