<?php
/**
 * Simplified OAuth authenticate endpoint
 */

$_phplib_page_open = array("sess" => "iquest_session");
$_required_modules = array('iquest');
$_required_apu = array('apu_auth_login');

require(__DIR__."/../prepend.php");

$apu = new apu_auth_login();

$apu->set_opt("oauth_enabled", true);
$apu->set_opt("required_capabilities", ['team']);

$apu->set_opt("form_submit", array('type' => 'button',
                                   'class' => "btn btn-primary btn-large",
                                   'text' => $lang_str['auth_b_login']));

$page_attributes['logout']=false;
$smarty->assign("login_screen", true);

$controler->add_apu($apu);
$controler->set_template_name('auth/login.tpl');
$controler->start();
