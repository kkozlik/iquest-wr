<?php
/**
 * Application unit login 
 * 
 * @author    Karel Kozlik
 * @version   $Id: apu_login.php,v 1.8 2006/09/08 12:27:33 kozlik Exp $
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
 *  'auth_class'                (string) default: "Auth"
 *   Name of class auth class which is used for validate credentials and which
 *   is created after successfull authentication.
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

    /* return required data layer methods - static class */
    function get_required_data_layer_methods(){
        return array();
    }

    /* return array of strings - requred javascript files */
    function get_required_javascript(){
        return array();
    }
    
    /* constructor */
    function __construct(){
        global $lang_str, $config;
        parent::apu_base_class();

        /* set default values to $this->opt */      

        $this->opt['redirect_on_login'] = null;
        $this->opt['cookie_domain'] = null;
        $this->opt['relogin'] = false;
        $this->opt['auth_class'] = 'iquest_auth';

        /* message on attributes update */
        $this->opt['msg_logout']['long']  = &$lang_str['auth_msg_logout'];
        
        /*** names of variables assigned to smarty ***/

        $this->opt['smarty_relogin'] =      'relogin';

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

    function action_login(&$errors){
        global $lang_str, $config;

        unset($_SESSION['auth']);

        setcookie(self::cookie_user, $_POST['uname'], time()+31536000, null, $this->opt['cookie_domain']); //cookie expires in one year

        $_SESSION['auth'] = new $this->opt['auth_class'];
        $_SESSION['auth']->authenticate_as($this->uid, $this->username, null, null);

        if (is_array($this->perms))
            $_SESSION['auth']->set_perms($this->perms);

        sw_log("User login: redirecting to page: ".$this->opt['redirect_on_login'], PEAR_LOG_DEBUG);

        Iquest_Events::add(Iquest_Events::LOGGED,
                           true,
                           array("uname" => $this->username));

        $this->controler->change_url_for_reload($this->opt['redirect_on_login']);
        return true;
    }
    
    /* check _get and _post arrays and determine what we will do */
    function determine_action(){

        if ($this->opt['relogin']){
            // If relogin screen is displayed do not execute login action
            // The form data should be processed by function 
            // <auth_class>::auth_validatelogin()

            $this->action=array('action'=>"default",
                                'validate_form'=>false,
                                'reload'=>false);
            return;
        }

        if ($this->was_form_submited() or 
            (isset($_GET["redir_id"]) and $_GET["redir_id"] == $this->opt['instance_id'])){ // Is there data to process?

            $this->action=array('action'=>"login",
                                'validate_form'=>true,
                                'reload'=>true,
                                'alone'=>true);
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
        if ($this->opt['relogin'] and isset($_SESSION['auth']->auth['uname'])){
            $cookie_uname=$_SESSION['auth']->auth['uname'];
        }
        
        $this->f->add_element(array("type"=>"text",
                                     "name"=>"uname",
                                     "size"=>20,
                                     "maxlength"=>50,
                                     "value"=>$cookie_uname,
                                     "disabled"=>$this->opt['relogin'],
                                     "skip_load_default"=>$this->opt['relogin'],
                                     "minlength"=>$this->opt['relogin'] ? 0 : 1,
                                     "length_e"=>$lang_str['auth_err_empty_username'],
                                     "extrahtml"=>"autocomplete='off' "));
                                     
        $this->f->add_element(array("type"=>"text",
                                     "name"=>"passw",
                                     "value"=>"",
                                     "size"=>20,
                                     "maxlength"=>25,
                                     "pass"=>1));
        
    }


    function form_invalid(){
        Iquest_Events::add(Iquest_Events::LOGGED,
                           false,
                           array("uname" => isset($_POST['uname']) ? $_POST['uname'] : null,
                                 "passw" => isset($_POST['passw']) ? $_POST['passw'] : null));
    }

    /* validate html form */
    function validate_form(){
        global $config, $lang_str;
        $uid = null;
        $perms = null;

        // don't display logout mesage in case that form was submited
        if (isset($_GET['logout'])) unset($_GET['logout']);

        if (false === parent::validate_form()) return false;

        $this->password = $_POST['passw'];
        $this->username = $_POST['uname'];

        sw_log("User login: values from login form: username: ".
                $this->username.", password: ".$this->password, PEAR_LOG_DEBUG);


        /* validate credentials */
        $opt = array();
        $uid = call_user_func_array(array($this->opt['auth_class'], 'validate_credentials'), 
                                    array($this->username, null, $this->password, &$opt));
        if (false === $uid) return false;


        /* set_permissions */
        $perms = call_user_func_array(array($this->opt['auth_class'], 'find_out_perms'), 
                                      array($uid, array()));

        if (false === $perms) return false;

        $this->uid = $uid;
        $this->perms = $perms;

        sw_log("User login: authentication succeeded, uid: ".$this->uid, PEAR_LOG_DEBUG);

        return true;
    }
    
    
    /* add messages to given array */
    function return_messages(&$msgs){
        global $_GET;
        
        if (isset($_GET['logout'])){
            $msgs[]=&$this->opt['msg_logout'];
            $this->smarty_action="was_logged_out";
        }
    }

    /* assign variables to smarty */
    function pass_values_to_html(){
        global $smarty;
        $smarty->assign($this->opt['smarty_action'], $this->smarty_action);
        $smarty->assign($this->opt['smarty_relogin'], $this->opt['relogin']);
    }
    
}


?>
