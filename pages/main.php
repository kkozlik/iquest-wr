<?php

$_phplib_page_open = array("sess" => "iquest_session",
                           "auth" => "iquest_auth");

$_data_layer_required_methods=array();
$_required_modules = array('iquest', 'auth');
$_required_apu = array('apu_iquest'); 

require dirname(__FILE__)."/prepend.php";


if (!Iquest::is_started()){
    $controler->redirect("waittostart.php");
    exit;
} 

$apu    = new apu_iquest();

//$page_attributes['css_file'][]=$config->style_src_path."get_css.php?css=".RawURLEncode("hello-world/hw.css");

$controler->add_apu($apu);
//$controler->add_reqired_javascript('core/functions.js'); 
$controler->set_template_name('iquest/main.tpl');
$controler->start();


?>
