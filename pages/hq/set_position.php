<?php

$_phplib_page_open = array("sess" => "iquest_hq_session");
$_required_modules = array('iquest');
$_required_apu = array('apu_iquest_set_position');

require dirname(__FILE__)."/prepend.php";

Iquest_auth::access_check(['hq']);

$apu    = new apu_iquest_set_position();


$controler->add_apu($apu);
$controler->add_required_javascript('leaflet.js');
$controler->set_template_name('hq/set_position.tpl');
$controler->start();
