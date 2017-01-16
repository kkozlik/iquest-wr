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

$back_url = $controler->url($_SERVER['PHP_SELF']);
if (!empty($_GET["backto"])){
    $back_param = parse_url($_GET["backto"]);

    if (!empty($back_param["path"])){
        $back_url =  $back_param["path"];
        if (!empty($back_param["query"])) $back_url .= "?".$back_param["query"];
    }
}


$apu    = new apu_iquest();

$smarty->assign("contest_over", Iquest::is_over());
$smarty->assign("reveal_url", $controler->url("showtargetlocation.php"));
$smarty->assign("back_url", $back_url);

$controler->add_apu($apu);
$controler->set_template_name('iquest/main.tpl');
$controler->start();


?>
