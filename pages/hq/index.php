<?php
$_data_layer_required_methods=array();
$_phplib_page_open = array("sess" => "iquest_hq_session");
$_required_modules = array('auth', 'iquest');
$_required_apu = array('apu_auth_login'); 

require dirname(__FILE__)."/prepend.php";

if (!empty($_SESSION['auth']) and 
    $_SESSION['auth']->is_authenticated()){

    sw_log("Login screen: User already authenticated, redirecting to main screen.", PEAR_LOG_DEBUG);
    
    $controler->change_url_for_reload("main.php");
    $controler->reload(array());
}

$apu = new apu_auth_login();
$apu->set_opt("redirect_on_login", "main.php");
$apu->set_opt("auth_class", "iquest_hq_auth");

$apu->set_opt("form_submit", array('type' => 'button',
                                   'class' => "btn btn-primary btn-large",
                                   'text' => $lang_str['auth_b_login']));


$page_attributes['logout']=false;
$smarty->assign("login_screen", true);


$controler->add_apu($apu);

$controler->set_template_name('auth/index.tpl');
$controler->start();


?>
