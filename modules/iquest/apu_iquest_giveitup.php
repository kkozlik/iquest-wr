<?php
/**
 * Application unit iquest 
 * 
 * @author    Karel Kozlik
 * @version   $Id: application_layer_cz,v 1.10 2007/09/17 18:56:31 kozlik Exp $
 * @package   serweb
 */ 


/**
 *  Application unit iquest 
 *
 *
 *  This application unit is used for display and edit LB Proxies
 *     
 *  Configuration:
 *  --------------
 *  
 *  'msg_update'                    default: $lang_str['msg_changes_saved_s'] and $lang_str['msg_changes_saved_l']
 *   message which should be showed on attributes update - assoc array with keys 'short' and 'long'
 *                              
 *  'form_name'                     (string) default: ''
 *   name of html form
 *  
 *  'form_submit'               (assoc)
 *   assotiative array describe submit element of form. For details see description 
 *   of method add_submit in class form_ext
 *  
 *  'smarty_form'               name of smarty variable - see below
 *  'smarty_action'                 name of smarty variable - see below
 *  
 *  Exported smarty variables:
 *  --------------------------
 *  opt['smarty_form']              (form)          
 *   phplib html form
 *   
 *  opt['smarty_action']            (action)
 *    tells what should smarty display. Values:
 *    'default' - 
 *    'was_updated' - when user submited form and data was succefully stored
 *  
 */

class apu_iquest_giveitup extends apu_base_class{

    protected $team_id;

    
    /**
     *  constructor 
     *  
     *  initialize internal variables
     */
    function __construct(){
        global $lang_str;
        parent::apu_base_class();


        $this->opt['screen_name'] = "IQUEST-GIVEITUP";
        $this->opt['main_url'] = "";

        /*** names of variables assigned to smarty ***/
        /* form */
        $this->opt['smarty_form'] =         'form';
        /* name of html form */
        $this->opt['form_name'] =           '';

        $this->opt['smarty_main_url'] =         'main_url';
        
        $this->opt['form_submit']['text'] = $lang_str['b_giveitup'];
        $this->opt['form_submit']['class'] = "btn btn-primary";
        
    }

    /**
     *  this metod is called always at begining - initialize variables
     */
    function init(){
        parent::init();

        $this->team_id = $this->user_id->get_uid();

        if (!isset($_SESSION['apu_iquest_giveitup'][$this->opt['instance_id']])){
            $_SESSION['apu_iquest_giveitup'][$this->opt['instance_id']] = array();
        }
        
        $this->session = &$_SESSION['apu_iquest_giveitup'][$this->opt['instance_id']];
    }
    
    
    /**
     *  Method perform action solve
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_giveitup(){

        Iquest_Events::add(Iquest_Events::GIVEITUP,
                           true,
                           array());

        $_SESSION['auth']->activate(false);

        action_log($this->opt['screen_name'], $this->action, " Team deactivated (ID={$this->team_id})");

        $this->controler->change_url_for_reload($this->opt['main_url']);
        $get = array('apu_iquest_giveitup='.RawURLEncode($this->opt['instance_id']));
        return $get;
    }
    

    /**
     *  Method perform action default 
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_default(){

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View give-it-up screen");
        return true;
    }
    
    /**
     *  check _get and _post arrays and determine what we will do 
     */
    function determine_action(){
        if ($this->was_form_submited()){    // Is there data to process?
            $this->action=array('action'=>"giveitup",
                                'validate_form'=>true,
                                'reload'=>true);
        }
        else $this->action=array('action'=>"default",
                                 'validate_form'=>false,
                                 'reload'=>false);
    }

    /**
     *  create html form 
     *
     *  @return null            FALSE on failure
     */
    function create_html_form(){
        parent::create_html_form();

        $this->f->add_element(array("type"=>"text",
                                     "pass"=>true,
                                     "name"=>"passwd",
                                     "value"=>"",
                                     "js_trim_value" => false,
                                     "js_validate" => false, 
                                     "maxlength"=>64));
    }

    function form_invalid(){
        if ($this->action['action'] == "giveitup"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST: Give-it-up failed", false, array("errors"=>$this->controler->errors));

            $event_data = array("passwd" => isset($_POST['passwd']) ? $_POST['passwd'] : null);
            Iquest_Events::add(Iquest_Events::GIVEITUP,
                               false,
                               $event_data);

            if (false === $this->action_default()) return false;
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

        if (Iquest::is_over()){
            ErrorHandler::add_error($lang_str['iquest_err_contest_over']);
            return false; 
        }

        if (empty($_POST['passwd'])){
            ErrorHandler::add_error($lang_str['iquest_err_passw_empty']);
            return false; 
        }

        if (!$_SESSION['auth']->checkpass($_POST['passwd'])){
            ErrorHandler::add_error($lang_str['iquest_err_passw_invalid']);
            return false; 
        }

        return $form_ok;
    }
    
    
    /**
     *  assign variables to smarty 
     */
    function pass_values_to_html(){
        global $smarty;

        $smarty->assign($this->opt['smarty_main_url'], $this->controler->url($this->opt['main_url']));
    }
    
    /**
     *  return info need to assign html form to smarty 
     */
    function pass_form_to_html(){
        return array('smarty_name' => $this->opt['smarty_form'],
                     'form_name'   => $this->opt['form_name'],
                     'after'       => $this->js_after,
                     'before'      => $this->js_before);
    }
}


?>
