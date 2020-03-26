<?php

require_once( __DIR__."/authn.php" );
require_once( __DIR__."/authz.php" );
require_once( __DIR__."/auth_adapter_http.php" );

class Iquest_auth{

    const REASON_SESS_EXPIRED = "session_expired";

    public static $login_page_url;
    protected static $async_request = false;

    protected static $team_cache = [];

    public static function set_assync($val){
        static::$async_request = (bool)$val;
    }

    /**
     * Check whether a user is logged-in and if so whether has access to
     * specified capabilities. The format of the $required_capabilities
     * argument is this:
     *
     * array(capability1, capability2, ....)
     *
     * If more capabilities are specified this function require that
     * user has access to all of them.
     *
     * If no user is logged in it is either redirected to login screen or
     * 401 http response is returned. If the user does not fulfill the required
     * capabilities 403 http response is returned.
     *
     * Example of use of this function:
     *
     * Iquest_auth::access_check(['team']);
     *
     * @param array $required_capabilities
     * @return null
     */
    public static function access_check($required_capabilities=array()){

        $authn = Iquest_authN::singleton();

        if (!$authn->hasIdentity()){
            $adapter = new Iquest_auth_adapter_http();
            $result = $authn->authenticate($adapter);

            // $username = $adapter->getProvidedIdentity();

            if (!$result->isValid()){
                foreach($result->getMessages() as $msg) ErrorHandler::add_error($msg);

                static::unauthorized();
            }
        }

        if ($authn->sessionExpired()){
            static::unauthorized(self::REASON_SESS_EXPIRED);
        }

        $authn->updateSessionExpiration();

        static::authorize($required_capabilities);
    }


    /**
     * Check whether logged in user has access to the $required_capabilities.
     * Return 403 HTTP response if does not
     *
     * Check function access_check() for the format of $required_capabilities param
     *
     * @param array $required_capabilities
     */
    public static function authorize(array $required_capabilities=array()){
        $authz = Iquest_authZ::singleton();
        if (!$authz->authorize($required_capabilities)){
            static::forbidden();
        }
    }



    /**
     * Set perm_* options of APU to according to permissions of logged in user.
     * Also set the perms in 'iquest_perms' smarty variable.
     *
     * Available options:
     *  - 'required_perms'  (array)     Permissions they are checked.
     *                                  This option use same format as perms accepted by access_check() method
     *  - 'apu_opts'        (array)     Options of the APU that are set to FALSE if permissions are not met.
     *                                  Default: ['perm_insert', 'perm_edit', 'perm_delete']
     *  - 'set_apu_opts'    (bool)      Indicate whether this function shall set the APU opts
     *                                  Default: TRUE
     *  - 'set_smarty_vars' (bool)      Indicate whether this function shall set smarty vars
     *                                  Default: TRUE
     *
     * @param apu_base_class $apu
     * @param array          $ops
     */
    public static function set_perms_to_apu($apu, array $opts=array()){
        global $smarty;

        $set_apu_opts    = true;
        $set_smarty_vars = true;

        if (isset($opts['set_apu_opts']))       $set_apu_opts       = $opts['set_apu_opts'];
        if (isset($opts['set_smarty_vars']))    $set_smarty_vars    = $opts['set_smarty_vars'];

        $required_perms = $opts['required_perms'];

        $apu_opts = array('perm_insert', 'perm_edit', 'perm_delete', 'perm_modify');
        if (isset($opts['apu_opts'])) $apu_opts = $opts['apu_opts'];


        $authz = Iquest_authZ::singleton();
        $allow = $authz->authorize($required_perms);

        if (!$allow){
            sw_log(__CLASS__."::".__FUNCTION__."Perms denied by Iquest_authZ::authorize: ".json_encode($apu_opts), PEAR_LOG_DEBUG);
        }

        $smarty_perms = array();
        foreach($apu_opts as $apu_opt) {
            if ($set_apu_opts)      $apu->set_opt($apu_opt, $allow);
            if ($set_smarty_vars)   $smarty_perms[$apu_opt] = $allow;
        }

        $smarty->append("iquest_perms", $smarty_perms, true);
    }

    /**
     * Get username of logged-in user
     *
     * @return string|null
     */
    public static function get_logged_in_username(){
        $authn = Iquest_authN::singleton();
        if (!$authn->hasIdentity()) return null;
        return $authn->getIdentity();
    }
    public static function get_logged_in_uid(){
        $authn = Iquest_authN::singleton();
        if (!$authn->hasIdentity()) return null;
        return $authn->getUid();
    }
    public static function get_logged_in_team(){

        // if the logged in user is not team but HQ, return null
        $authz = Iquest_authZ::singleton();
        if (!$authz->authorize(['team'])) return null;

        $uid = Iquest_auth::get_logged_in_uid();
        if (isset(static::$team_cache[$uid])) return static::$team_cache[$uid];

        static::$team_cache[$uid] = Iquest_Team::fetch_by_id($uid);
        return static::$team_cache[$uid];
    }
    public static function has_identity(){
        $authn = Iquest_authN::singleton();
        return $authn->hasIdentity();
    }

    private static function unauthorized($reason = null){
        global $config;

        if (static::$async_request){
            http_response_code(401);
            echo "<h1>401 Unauthorized</h1>\n";

            $errors = MsgCollector::get_errors();
            if ($errors){
                echo "<ul>\n";
                foreach($errors as $error) echo "<li>".htmlspecialchars($error)."</li>\n";
                echo "</ul>\n";
            }
        }
        else{
            $url = static::$login_page_url;

            if (!$url) throw new Exception('URL of login screen is not set.');

            if ($reason == self::REASON_SESS_EXPIRED) {
                $url .= "?session_expired=1";
            }

            if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(200);
                header("Content-Type: text/json");
                echo json_encode(["redirect" => $url]);
            }
            else{
                http_response_code(401);
                header("Location: ".$url);
            }
        }

        // Log the error messages
        $eh = &ErrorHandler::singleton();
        foreach($eh->get_errors_array() as $error) sw_log("Error: $error", PEAR_LOG_INFO);

        exit();
    }


    private static function forbidden(){
        http_response_code(403);
        echo "<h1>403 Forbidden</h1>";
        exit();
    }
}