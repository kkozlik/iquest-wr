<?php

$_phplib_page_open = array("sess" => "iquest_session");
$_required_modules = array('iquest');
$_required_apu = array('apu_iquest_giveitup');

require dirname(__FILE__)."/prepend.php";

if (Iquest::is_over()){
    $controler->redirect("main.php");
    exit;
}

$apu    = new apu_iquest_giveitup();
$apu->set_opt('team_id', Iquest_auth::get_logged_in_uid());

$apu->set_opt("main_url", "main.php");

$controler->add_apu($apu);
$controler->set_template_name('iquest/giveitup.tpl');
$controler->start();
