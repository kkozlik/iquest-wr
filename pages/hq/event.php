<?php

$_phplib_page_open = array("sess" => "iquest_hq_session",
                           "auth" => "iquest_hq_auth");

$_data_layer_required_methods=array();
$_required_modules = array('iquest', 'auth', 'widgets');
$_required_apu = array('apu_iquest_event', 'apu_filter'); 

require dirname(__FILE__)."/prepend.php";


$apu    = new apu_iquest_event();
$filter = new apu_filter();

$apu->set_filter($filter);
$filter->set_opt('partial_match', false);

$filter->set_opt('form_submit', array('type' => 'button',
                                      'text' => $lang_str['b_search'],
                                      'class' => 'btn'));
        
$filter->set_opt('form_clear',  array('type' => 'button',
                                      'text' => $lang_str['b_clear_filter'],
                                      'class' => 'btn'));


$controler->add_apu($apu);
$controler->add_apu($filter);
$controler->set_template_name('hq/event.tpl');
$controler->start();


?>
