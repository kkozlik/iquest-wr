<?php

global $_SERWEB;
require_once($_SERWEB["phplibdir"] . "session4.1.php");
require_once($_SERWEB["phplibdir"] . "auth4.1.php");

class iquest_auth extends auth{

    /**
     *  Name of class holding info about user. 
     */         
    static $user_class = "Iquest_user";

    /**
     *  Max allowed idle time before reauthentication is necessary.
	 *  If set to 0, auth never expires.
     */         
    public $lifetime       = 0;

    private $team_name = null;

    /**
     *  Function is called when user is not authenticated
     *
     *  If user is logged in and authentication expired, this function
     *  display relogin page. Otherwise if user is not logged in yet, is 
     *  redirected to login page
     */
    function auth_loginform() {
        global $sess;
        global $_SERWEB;

        $this->auth['adm_domains'] = null;
        
        //user is not logged in, forward to login screen
        if (!isset($this->auth["uid"]) or is_null($this->auth["uid"])){
            Header("Location: ".$sess->url("index.php"));
            exit;
        }

        //else display relogin form

        // Bootstrap process did not finished yet. So do the last steps manualy
        // in order we have access to controler and apu_auth_login

        $GLOBALS['_required_apu'] = array('apu_auth_login'); 
        require_once ($_SERWEB["corefunctionsdir"] . "load_apu.php");
        $GLOBALS['controler']->add_required_javascript('core/phplib.js');
        init_modules();

        // Instantiate the apu_auth_login and run the controler
        $apu = new apu_auth_login();
        $apu->set_opt("relogin", true);

        $GLOBALS['controler']->add_apu($apu);
        $GLOBALS['controler']->set_template_name('auth/index.tpl');
        $GLOBALS['controler']->start();
    }


    /**
     *  Function validate password obtained from re-login form
     *
     *  If password is valid, function authenticate user again and return true,
     *  otherwise return false.
     *
     *  @return     bool
     */
    function auth_validatelogin() {

        // At this stage the 'auth' module is not initialized yet
        // So do it manualy otherwise some variables needed by function
        // validate_credentials() are not set.
        init_module('auth');
    
        $password = "";
        if (isset($_POST['passw'])) $password = $_POST['passw'];

        $opt = array();
        if (false === $this->validate_credentials($this->auth['uname'], null, $password, $opt)){
            return false;
        }

        $this->authenticate();

        $perms = $this->find_out_perms($this->auth['uid'], array());
        if (false === $perms) return false;
        $this->set_perms($perms);

        return true;
    }


    /**
     *  Validate given credentials and return UID if they are valid
     *
     *  @static
     *  @param  string  $username   
     *  @param  string  $did        
     *  @param  string  $password   
     *  @param  array   $optionals      
     *  @return string              UID if credentials are valid, false otherwise
     */
    static function validate_credentials($username, $did, $password, &$optionals){
        global $lang_str, $data, $config;

        $data->connect_to_db();

        // chceck credentials
        sw_log("validate_credentials: checking credentials for username: ".$username, PEAR_LOG_DEBUG);

        /* table's name */
        $t_name = &$config->data_sql->iquest_team->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_team->cols;

        $q = "select t.".$c->id." 
              from ". $t_name." t 
              where t.".$c->username."=".$data->sql_format($username, "s")." and 
                    t.".$c->passwd."=".$data->sql_format($password, "s");

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        if (!$res->numRows()){
            sw_log("validate_credentials: authentication failed: bad username or password ", PEAR_LOG_INFO);
            ErrorHandler::add_error($lang_str['auth_err_bad_username']);
            return false;
        }

		$row = $res->fetchRow(MDB2_FETCHMODE_ASSOC);
		$res->free();

		return $row[$c->id];
    }


    /**
     *  Get name of logged team
     */         
    public function get_team_name(){
        global $data, $config;

        // check the cache
        if (!is_null($this->team_name)) return $this->team_name;

        /* table's name */
        $t_name = &$config->data_sql->iquest_team->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_team->cols;

        $team_id = $this->get_uid();

        $q = "select t.".$c->name." 
              from ". $t_name." t 
              where t.".$c->id."=".$data->sql_format($team_id, "n");

        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        if (!$res->numRows()){
            sw_log("get_team_name: Team name for team ID=$team_id not found ", PEAR_LOG_ERROR);
            return null;
        }

		$row = $res->fetchRow(MDB2_FETCHMODE_ASSOC);
        $this->team_name = $row[$c->name];
		$res->free();
        
        return $this->team_name;
    }
}


/**
 *  Override SerwebUser class with own version
 */ 
class Iquest_user extends SerwebUser{
    function get_domainname(){
        // Domains are not used
        return null;
    }
}


/**
 *	main session class
 *	
 *	@package   PHPLib
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

?>
