<?php
/**
 * Application unit auth login screen
 *
 * @author    Karel Kozlik
 * @package   iquest-auth
 */

require_once (__DIR__."/authn.php");
require_once (__DIR__."/auth_adapter_credentials.php");

load_apu('apu_sbc_base');

/**
 *  Application unit auth login screen
 *
 *  This application unit is used to render login screen
 */
class apu_sbc_auth_login extends apu_sbc_base{

    protected $edit_obj;
    protected $smarty_msgs=array();

    /**
     *  constructor
     *
     *  initialize internal variables
     */
    function __construct(){
        global $lang_str;
        parent::__construct();

        /* set default values to $this->opt */
        $this->opt['screen_name'] = "SBC login";

        $this->opt['script_onlogin'] = null;
        $this->opt['script_onlogout'] = null;

        $this->opt['smarty_msgs'] =              "login_messages";
        $this->opt['smarty_url_ajaxlogin'] =     "url_ajaxlogin";

        $this->opt['form_submit']= array('type' => 'button',
                                         'text' => $lang_str['sbc_auth_b_login']);

        $this->include_dirty_flags_in_json_response = false;
    }


    /**
     *  Method perform action login
     */
    function action_ajaxlogin(){

        $this->controler->disable_html_output();
        header("Content-Type: text/json");

        action_log($this->opt['screen_name'], $this->action, "User {$this->edit_obj->username} logged in");

        $response = array();
        if ($this->opt['script_onlogin']){
            $response['redirect'] = $this->opt['script_onlogin'];
        }

        $this->ajax_response(true, null, $response);
        return true;
    }

    /**
     * Check whether the user session is still valid. This is the handler of
     * queries made by javascript function sbcIsUserLoggedIn().
     */
    function action_ajax_session_check(){

        $this->controler->disable_html_output();
        header("Content-Type: text/json");

        $response = array();

        $authn = Iquest_authN::singleton();
        if ($authn->hasIdentity()){

            if ($authn->sessionExpired()){
                $response['redirect'] = $_SERVER['PHP_SELF']."?session_expired=1";
            }
            else{
                $session_timeout = $authn->getSessionExpireTime()-time();
                if ($session_timeout > 3600) $session_timeout = 3600; // reapeat session check at least every hour. Too large values might get JS confused
                $response['session_timeout'] = $session_timeout;
            }
        }

        echo json_encode($response);
    }

    function action_logout(){

        $auth = Iquest_authN::singleton();
        $user = $auth->getIdentity();

        action_log($this->opt['screen_name'], $this->action, "User '$user' logged out");

        $auth->clearIdentity();

        if ($this->opt['script_onlogout']){
            $this->controler->change_url_for_reload($this->opt['script_onlogout']);
        }
        return true;
    }


    /**
     *  Method perform action default
     */
    function action_default(){
        global $lang_str;

        $auth = Iquest_authN::singleton();

        // get rid of any stored identity
        if ($auth->hasIdentity()) {
            $auth->clearIdentity();
        }

        if (isset($_GET['session_expired'])){
            $this->smarty_msgs[] = $lang_str['sbc_auth_err_sess_expired'];
        }

        $this->default_action_log("View login screen");
        return true;
    }


    /**
     *  check _get and _post arrays and determine what we will do
     */
    function determine_action(){
        if ($this->was_form_submited()){    // Is there data to process?
            $this->set_action("ajaxlogin", true, false, true);
        }
        elseif (isset($_GET['logout'])){
            $this->set_action("logout", false, true);
        }
        elseif (isset($_GET['session_check'])){
            $this->set_action("ajax_session_check", false, false, true);
        }
        else{
            $this->set_action("default");
        }
    }

    /**
     *  create html form
     *
     *  @return null            FALSE on failure
     */
    function create_html_form(){
        parent::create_html_form();

        self::add_form_elements($this->f);
    }

    /**
     * Add form elements to the specified form object
     *
     * @param form_ext $form
     * @return void
     */
    public static function add_form_elements(&$form){
        $form->add_element(array("type"=>"text",
                                 "name"=>"sbc_auth_username",
                                 "value"=>"",
                                 "maxlength"=>128));

        $form->add_element(array("type"=>"text",
                                 "pass"=>1,
                                 "name"=>"sbc_auth_password",
                                 "value"=>"",
                                 "maxlength"=>128));

    }

    function create_edit_obj_from_post_vars(){
        $edit_obj = new stdclass();

        $edit_obj->username =   $_POST['sbc_auth_username'];
        $edit_obj->password =   $_POST['sbc_auth_password'];

        return $edit_obj;
    }


    function form_invalid(){

        if ($this->is_action("ajaxlogin")){
            action_log($this->opt['screen_name'], $this->action, "User login failed", false, array("errors"=>$this->controler->errors,
                                                                                                   "new_value"=>["username"=>$this->edit_obj->username]));
            $this->ajax_response(false);
            exit(0);
        }
    }

    /**
     *  validate html form
     *
     *  @return bool            TRUE if given values of form are OK, FALSE otherwise
     */
    function validate_form(){
        global $lang_str;

        $form_ok = true;
        if (false === parent::validate_form()) $form_ok = false;

        if ($this->is_action("ajaxlogin")){

            $this->edit_obj = $this->create_edit_obj_from_post_vars();


            $adapter = new Iquest_auth_adapter_credentials();
            $adapter->setIdentity($this->edit_obj->username);
            $adapter->setCredential($this->edit_obj->password);

            $authn = Iquest_authN::singleton();
            $result = $authn->authenticate($adapter);

            if (!$result->isValid()){
                foreach($result->getMessages() as $msg) ErrorHandler::add_error($msg);
                return false;
            }

            $authz = Iquest_authZ::singleton();
            if (!$authz->authorize(['gui'])){
                sw_log(__CLASS__." login failed - user is not authorized to access GUI", PEAR_LOG_INFO);

                ErrorHandler::add_error($lang_str['sbc_auth_err_no_gui_access']);
                return false;
            }

        }

        return $form_ok;
    }

    /**
     *  assign variables to smarty
     */
    public function pass_values_to_html(){
        global $smarty;

        $smarty->assign($this->opt['smarty_msgs'],   $this->smarty_msgs);
        $smarty->assign($this->opt['smarty_url_ajaxlogin'],   $this->controler->url($_SERVER['PHP_SELF']."?ajaxlogin=1"));
    }

}

