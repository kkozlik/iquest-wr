<?php
$_phplib_page_open = array("sess" => "iquest_hq_session");
$_required_modules = array('iquest');
$_required_apu = array('apu_auth_login');

require dirname(__FILE__)."/prepend.php";

$apu = new apu_auth_login();
$apu->set_opt("required_capabilities", ['hq']);
$apu->set_opt("redirect_on_login", "main.php");

$apu->set_opt("form_submit", array('type' => 'button',
                                   'class' => "btn btn-primary btn-large",
                                   'text' => $lang_str['auth_b_login']));

$page_attributes['logout']=false;
$smarty->assign("login_screen", true);

$controler->add_apu($apu);
$controler->set_template_name('auth/login.tpl');
$controler->start();
