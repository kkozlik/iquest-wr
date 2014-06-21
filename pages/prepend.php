<?php
/**
 *  File required by all pages. It is used to load all required files
 * 
 */ 


/*
 *  Set page attributes in pre-auth hook so they are available 
 *  if re-login screen is displayed
 */
$GLOBALS["_SERWEB"]["hookpreauth"] = "set_page_attributes";


function set_page_attributes(){
    global $config;
    
    $GLOBALS['page_attributes']=array(
        'title' => null,
        'html_title' => "I.QUEST - contest",
    //  'tab_collection' => $config->admin_tabs,
    //  'path_to_pages' => $config->admin_pages_path,
    //  'run_at_html_body_begin' => '_disable_unneeded_tabs',
        'logout'=>false,
        'prolog'=>"",
        'separator'=>"",
        'epilog'=>"",
        'ie_selects' => true,
        'css_file'=>array(
                    $config->style_src_path."styles.css",
                    $config->style_src_path."bootstrap.css",
                    $config->style_src_path."bootstrap-responsive.css",
        ),
        'required_javascript' => array(
                    "jquery-1.11.1.min.js",
                    "bootstrap.min.js",
                    "jquery.countdown.min.js",
                    "core/functions.js",
                    "functions.js"
        )
    );
}

require_once(realpath(dirname(__FILE__)."/..")."/config/set_env.php");
require_once(dirname(__FILE__)."/../functions/functions.php");
require_once(getenv('SERWEB_DIR')."functions/bootstrap.php");

$GLOBALS['page_attributes']['logout_url'] = $controler->url('logout.php');
$GLOBALS['page_attributes']['giveitup_url'] = $controler->url('giveitup.php');

try{
    $GLOBALS['page_attributes']['display_wallet'] = Iquest_Options::get(Iquest_Options::WALLET_ACTIVE);
}
catch(RuntimeException $e){
    $GLOBALS['page_attributes']['display_wallet'] = false;
}

if (!empty($_SESSION['auth']) and 
    $_SESSION['auth']->is_authenticated()){

    $smarty->assign("team_name", $_SESSION['auth']->get_team_name());
}

$controler->set_onload_js("set_clock('#current_time', ".time().");");
$smarty->assign("current_time", date("H:i:s"));

?>
