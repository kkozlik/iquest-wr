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
        'css_file'=>array($config->style_src_path."styles.css")
    );
}

require_once(dirname(__FILE__)."/functions.php");
require_once(getenv('SERWEB_DIR')."functions/bootstrap.php");

?>
