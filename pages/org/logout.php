<?php

$_phplib_page_open = array("sess" => "iquest_org_session");
$_required_modules = array('auth', 'iquest');

require dirname(__FILE__)."/prepend.php";

if (!empty($_SESSION['auth'])){

    Iquest_Events::add(Iquest_Events::LOGOUT,
                       true);

    $_SESSION['auth']->logout();
    action_log(null, null, "Logged out");
} 

Header("Location: ".$controler->url("index.php?logout=1"));

?>
