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
        'logout'=>false,
        'prolog'=>"",
        'separator'=>"",
        'epilog'=>"",
        'html_headers' => array(
            '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">'
        ),
        'css_file'=>array(
                    $config->style_src_path."styles.css",
        ),
        'required_javascript' => array(
                    "jquery.min.js",
                    "popper.min.js",
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

Iquest_auth::$login_page_url = "login.php";

$GLOBALS['controler']->attach_listener("pre_form_smarty", function($event){
    OohElCommon::$default_class = "form-control";
    OohElCheckbox::$default_class = "custom-control-input";
    OohElRadio::$default_class = "custom-control-input";
    OohElSelect::$default_class = "custom-select";
    OohElFile::$default_class = "custom-file-input";
    // OohElSubmit::$default_class = "btn btn-primary";
    // OohElReset::$default_class = "btn btn-primary";
    // OohElButton::$default_class = "btn btn-primary";
});

$GLOBALS['controler']->attach_listener("pre_html_output", function($event){

    $GLOBALS['page_attributes']['logout_url']   = $GLOBALS['controler']->url('login.php?logout=1');
    $GLOBALS['page_attributes']['giveitup_url'] = $GLOBALS['controler']->url('giveitup.php');
    $GLOBALS['page_attributes']['logo_url']     = $GLOBALS['controler']->url('logo.php');

    try{
        $GLOBALS['page_attributes']['display_wallet'] = Iquest_Options::get(Iquest_Options::WALLET_ACTIVE);
        $GLOBALS['page_attributes']['game_name'] = Iquest_Options::get(Iquest_Options::GAME_NAME);
    }
    catch(RuntimeException $e){
        $GLOBALS['page_attributes']['display_wallet'] = false;
        $GLOBALS['page_attributes']['game_name'] = "";
    }

    try{
        $GLOBALS['page_attributes']['display_bomb'] = Iquest_Options::get(Iquest_Options::BOMB_ACTIVE);
    }
    catch(RuntimeException $e){
        $GLOBALS['page_attributes']['display_bomb'] = false;
    }

    if (!$GLOBALS['page_attributes']['game_name']) $GLOBALS['page_attributes']['game_name'] = "I.Quest";

    $GLOBALS['page_attributes']['html_title'] = $GLOBALS['page_attributes']['game_name'];

    $team = Iquest_auth::get_logged_in_team();
    if ($team) $GLOBALS['smarty']->assign("team_name", $team->name);

    $GLOBALS['controler']->set_onload_js("set_clock('#current_time', ".time().");");
    $GLOBALS['smarty']->assign("current_time", date("H:i:s"));
});

