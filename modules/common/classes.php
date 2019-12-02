<?php

global $_SERWEB;
require_once($_SERWEB["phplibdir"] . "session4.1.php");

/**
 *  main session class
 *
 *  @package   PHPLib
 */

class iquest_session extends Session {
    var $classname = "iquest_session";

    var $trans_id_enabled = false;
    var $cookiename     = "";                ## defaults to classname
    var $mode           = "cookie";          ## We propagate session IDs with cookies
    var $fallback_mode  = "get";
    var $allowcache     = "no";              ## "public", "private", or "no"
    var $lifetime       = 2880;              ## 0 = do session cookies, else minutes
}


/**
 *  Session for admin interface - just to have different session name
 */
class iquest_hq_session extends iquest_session {
    var $classname = "iquest_hq_session";
}
