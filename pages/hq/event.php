<?php
$_phplib_page_open = array("sess" => "iquest_hq_session");
$_required_modules = array('iquest', 'widgets');
$_required_apu = array('apu_iquest_event', 'apu_filter');

require dirname(__FILE__)."/prepend.php";
Iquest_auth::access_check(['hq']);


$apu    = new apu_iquest_event();
$filter = new apu_filter();

$apu->set_filter($filter);
$filter->set_opt('partial_match', false);

$filter->set_opt('form_submit', array('type' => 'button',
                                      'text' => $lang_str['b_search'],
                                      'class' => 'btn btn-sm btn-primary'));

$filter->set_opt('form_clear',  array('type' => 'button',
                                      'text' => $lang_str['b_clear_filter'],
                                      'class' => 'btn btn-sm btn-secondary'));

$apu->set_opt('event_row_template_name', 'hq/event-row.tpl');

$controler->add_apu($apu);
$controler->add_apu($filter);
$controler->set_template_name('hq/event.tpl');
$controler->add_required_javascript('bootstrap-select.min.js');
$controler->add_required_javascript('moment-with-locales.js');
$controler->add_required_javascript('tempusdominus-bootstrap-4.js');
$controler->start();

