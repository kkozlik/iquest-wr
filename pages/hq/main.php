<?php

$_phplib_page_open = array("sess" => "iquest_hq_session");
$_required_modules = array('iquest', 'widgets');
$_required_apu = array('apu_iquest_hq', 'apu_sorter');

require dirname(__FILE__)."/prepend.php";

Iquest_auth::access_check(['hq']);

if (!empty($_GET['back_url'])) {
    $controler->change_url_for_reload($_GET['back_url']);
    $controler->set_get_param('back_url');
    $smarty->assign('back_url', $controler->url($_GET['back_url']));
}
else{
    $smarty->assign('back_url', $controler->url($_SERVER['PHP_SELF']));
}

$smarty->assign('main_url', $_SERVER['PHP_SELF']);


$apu    = new apu_iquest_hq();
$sorter = new apu_sorter();

$apu->set_sorter($sorter);


$controler->add_apu($apu);
$controler->add_apu($sorter);
$controler->set_template_name('hq/main.tpl');
$controler->add_required_javascript('jquery.kinetic.min.js');
$controler->add_required_javascript('jquery.floatThead.min.js');
$controler->start();

