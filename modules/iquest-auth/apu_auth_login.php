<?php
/**
 * Application unit login
 *
 * @author    Karel Kozlik
 * @package   iquest
 */

/** Application unit login
 *
 *
 *  This application unit is used for login into application. This APU can't
 *  be combined with others APUs on one page.
 *
 *  Configuration:
 *  --------------
 *
 *  'redirect_on_login'         (string) default: 'my_account.php'
 *   name of script ot which is browser redirected after succesfull login
 *
 *  'cookie_domain'             (string) default: null
 *   The domain that the cookie in which is stored username is available
 *
 *  'msg_logout'                default: $lang_str['msg_logout_s'] and $lang_str['msg_logout_l']
 *   message which should be showed on user logout - assoc array with keys 'short' and 'long'
 *
 *  'form_name'                 (string) default: 'login_form'
 *   name of html form
 *
 *  'form_submit'               (assoc)
 *   assotiative array describe submit element of form. For details see description
 *   of method add_submit in class form_ext
 *
 *  'smarty_form'               name of smarty variable - see below
 *  'smarty_action'             name of smarty variable - see below
 *
 *  Exported smarty variables:
 *  --------------------------
 *  opt['smarty_form']          (form)
 *   phplib html form
 *
 *  opt['smarty_action']            (action)
 *    tells what should smarty display. Values:
 *    'default' -
 *    'was_logged_out' - when user was logged out
 *
 */

class apu_auth_login extends apu_base_class{
    var $smarty_action='default';
    var $uid = null;
    var $username = null;
    var $password = null;
    var $perms = null;

    const cookie_user = "iquest_user";

    /* constructor */
    function __construct(){
        global $lang_str, $config;
        parent::apu_base_class();

        /* set default values to $this->opt */

        $this->opt['oauth_enabled'] = false;
        $this->opt['redirect_on_login'] = null;
        $this->opt['cookie_domain'] = null;
        $this->opt['redirect_on_logout'] = null;

        $this->opt['required_capabilities'] = null;

        /* message on attributes update */
        $this->opt['msg_logout']['long']  = &$lang_str['auth_msg_logout'];

        /*** names of variables assigned to smarty ***/

        /* form */
        $this->opt['smarty_form'] =         'form';
        /* smarty action */
        $this->opt['smarty_action'] =       'action';
        /* name of html form */
        $this->opt['form_name'] =           'login_form';

        $this->opt['form_submit']=array('type' => 'button',
                                        'text' => $lang_str['auth_b_login']);

    }

    /* this metod is called always at begining */
    function init(){
        parent::init();

        $this->controler->set_onload_js("
            if (document.forms['".$this->opt['form_name']."']['uname'].value != '') {
                document.forms['".$this->opt['form_name']."']['passw'].focus();
            } else {
                document.forms['".$this->opt['form_name']."']['uname'].focus();
            }
        ");
    }

    function action_login(){

        serwebSetCookie(
            self::cookie_user,
            $this->username,
            [
                'expires' => time()+31536000,   //cookie expires in one year
                'domain' =>  $this->opt['cookie_domain'],
            ]);


        sw_log("User login: redirecting to page: ".$this->opt['redirect_on_login'], PEAR_LOG_DEBUG);

        Iquest_Events::add(Iquest_Events::LOGGED,
                           true,
                           array("uname" => $this->username,
                                 "perms" => $this->opt['required_capabilities']));

        $get=[];
        if ($this->opt['oauth_enabled']){

            $this->opt['redirect_on_login'] = $_POST['redirect_uri'];

            try{
                $code = $this->get_oauth_redirect_token();
                $get[] = "code=".RawUrlEncode($code);

                sw_log("Generated code: $code", PEAR_LOG_DEBUG);
            }
            catch(Iquest_auth_jwt_exception $e){
                ErrorHandler::add_error($e->getMessage());
            }
        }

        $this->controler->change_url_for_reload($this->opt['redirect_on_login']);
        return $get;
    }

    function action_logout(){

        $auth = Iquest_authN::singleton();
        $user = $auth->getIdentity();

        Iquest_Events::add(Iquest_Events::LOGOUT,
                        true,
                        array("uname" => Iquest_auth::get_logged_in_username(),
                              "perms" => $this->opt['required_capabilities']));

        action_log(null, null, "User '$user' logged out");

        $auth->clearIdentity();
        if (PHPlib::$session) PHPlib::$session->delete();

        if ($this->opt['redirect_on_logout']){
            $this->controler->change_url_for_reload($this->opt['redirect_on_logout']);
        }
        return 'logged_out=1';
    }

    function action_default(){
        if ($this->opt['oauth_enabled']){
            if (empty($_GET['redirect_uri'])){
                throw new Exception('redirect_uri not present in GET params or is empty');
            }

            $this->opt['redirect_on_login'] = $_GET['redirect_uri'];

            $auth = Iquest_authN::singleton();
            if ($auth->hasIdentity()) {
                try{
                    $code = $this->get_oauth_redirect_token();
                    $url_param = "code=".$code;

                    sw_log("Generated code: $code", PEAR_LOG_DEBUG);
                    $this->controler->redirect($_GET['redirect_uri'], [ $url_param ]);
                }
                catch(Iquest_auth_jwt_exception $e){
                    ErrorHandler::add_error($e->getMessage());
                }
            }
        }
    }

    /**
     * Generate OAuth token, store it in DB and return the 'code' string of the token
     *
     * @return string
     * @throws Iquest_auth_jwt_exception
     */
    private function get_oauth_redirect_token() : string{
        $token = new Iquest_auth_oauth_token();
        $token->init();

        Iquest_auth_oauth_token::gc();
        $token->insert();

        return $token->get_code();
    }

    /* check _get and _post arrays and determine what we will do */
    function determine_action(){

        if ($this->was_form_submited()){

            $this->action=array('action'=>"login",
                                'validate_form'=>true,
                                'reload'=>true,
                                'alone'=>true);
        }
        elseif (isset($_GET['logout'])){
             $this->action=array('action'=>"logout",
                                 'validate_form'=>false,
                                 'reload'=>true);
        }
        else $this->action=array('action'=>"default",
                                 'validate_form'=>false,
                                 'reload'=>false);
    }

    /* create html form */
    function create_html_form(){
        global $lang_str;
        parent::create_html_form();

        $cookie_uname="";
        if (isset($_COOKIE[self::cookie_user])) $cookie_uname=$_COOKIE[self::cookie_user];

        $this->f->add_element(array("type"=>"text",
                                     "name"=>"uname",
                                     "size"=>20,
                                     "maxlength"=>50,
                                     "value"=>$cookie_uname,
                                     "minlength"=> 1,
                                     "length_e"=>$lang_str['auth_err_empty_username'],
                                     "extrahtml"=>"autocomplete='off' "));

        $this->f->add_element(array("type"=>"text",
                                     "name"=>"passw",
                                     "value"=>"",
                                     "size"=>20,
                                     "maxlength"=>25,
                                     "pass"=>1));

        if ($this->opt['oauth_enabled']){
            $this->f->add_element(array("type"=>"hidden",
                                        "name"=>"redirect_uri",
                                        "value"=>!empty($_GET['redirect_uri']) ? $_GET['redirect_uri'] : ""));
        }
    }


    function form_invalid(){
        Iquest_Events::add(Iquest_Events::LOGGED,
                           false,
                           array("uname" => isset($_POST['uname']) ? $_POST['uname'] : null,
                                 "passw" => isset($_POST['passw']) ? $_POST['passw'] : null,
                                 "perms" => $this->opt['required_capabilities']));
    }

    /* validate html form */
    function validate_form(){
        global $lang_str;

        // don't display logout mesage in case that form was submited
        if (isset($_GET['logged_out'])) unset($_GET['logged_out']);

        if (false === parent::validate_form()) return false;

        if ($this->opt['oauth_enabled']){
            if (empty($_POST['redirect_uri'])){
                ErrorHandler::add_error('redirect_uri not present in POST params or is empty');
                return false;
            }
        }

        $this->username = $_POST['uname'];
        $this->password = $_POST['passw'];

        sw_log("User login: values from login form: username: ".
                $this->username.", password: ".$this->password, PEAR_LOG_DEBUG);

        $adapter = new Iquest_auth_adapter_credentials();
        $adapter->setIdentity($this->username);
        $adapter->setCredential($this->password);

        $authn = Iquest_authN::singleton();
        $result = $authn->authenticate($adapter);

        if (!$result->isValid()){
            foreach($result->getMessages() as $msg) ErrorHandler::add_error($msg);
            return false;
        }

        $authz = Iquest_authZ::singleton();
        if ($this->opt['required_capabilities'] and !$authz->authorize($this->opt['required_capabilities'])){
            sw_log(__CLASS__." login failed - not authorized ", PEAR_LOG_INFO);

            ErrorHandler::add_error($lang_str['auth_err_bad_username']);
            return false;
        }

        sw_log("User login: authentication succeeded, uid: ".$this->uid, PEAR_LOG_DEBUG);

        return true;
    }


    /* add messages to given array */
    function return_messages(&$msgs){
        global $_GET;

        if (isset($_GET['logged_out'])){
            $msgs[]=&$this->opt['msg_logout'];
            $this->smarty_action="was_logged_out";
        }
    }

    /* assign variables to smarty */
    function pass_values_to_html(){
        global $smarty;
        $smarty->assign($this->opt['smarty_action'], $this->smarty_action);
    }

}
