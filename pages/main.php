<?php

$_phplib_page_open = array("sess" => "iquest_session");
$_required_modules = array('iquest');
$_required_apu = array('apu_iquest');

require dirname(__FILE__)."/prepend.php";

Iquest_auth::access_check(['team']);

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
$apu->set_opt('team_id', Iquest_auth::get_logged_in_uid());

$smarty->assign("contest_over", Iquest::is_over());
$smarty->assign("reveal_url", $controler->url("showtargetlocation.php"));
$smarty->assign("back_url", $back_url);

$controler->add_apu($apu);
$controler->add_required_javascript('leaflet.js');
$controler->set_template_name('iquest/main.tpl');
$controler->start();
