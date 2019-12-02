<?php

$_phplib_page_open = array("sess" => "iquest_session");
$_required_modules = array('iquest');
$_required_apu = array('apu_iquest_reveal_goal');

require dirname(__FILE__)."/prepend.php";

Iquest_auth::access_check(['team']);

$apu    = new apu_iquest_reveal_goal();

$apu->set_opt("main_url", "main.php");
$apu->set_opt('team_id', Iquest_auth::get_logged_in_uid());

$controler->add_apu($apu);
$controler->set_template_name('iquest/showtargetlocation.tpl');
$controler->start();
