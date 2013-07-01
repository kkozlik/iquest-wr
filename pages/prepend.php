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

/*
 *  Save the application root directory into $_SERWEB variable.
 *  This can't be done later in set_dirs.php file because the config directory
 *  (where the file is located) could be located outside the application tree
 *  e.g. in /etc dir. Hence the __FILE__ variable return wrong path.     
 */ 
$GLOBALS["_SERWEB"]["approotdir"] = realpath(dirname(__FILE__)."/..")."/";

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
                    "jquery-1.10.1.min.js",
                    "bootstrap.min.js",
                    "jquery.countdown.min.js",
                    "functions.js"
        )
    );
}

require_once($GLOBALS["_SERWEB"]["approotdir"]."config/set_env.php");
require_once(dirname(__FILE__)."/functions.php");
require_once(getenv('SERWEB_DIR')."functions/bootstrap.php");

$GLOBALS['page_attributes']['logout_url'] = $controler->url('logout.php');

?>
