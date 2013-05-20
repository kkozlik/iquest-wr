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

class apu_iquest extends apu_base_class{

    protected $grp_ref_id;
    protected $smarty_action = 'default';
    protected $smarty_groups;
    protected $smarty_clues;

    /** 
     *  return required data layer methods - static class 
     *
     *  @return array   array of required data layer methods
     */
    function get_required_data_layer_methods(){
        return array();
    }

    /**
     *  return array of strings - required javascript files 
     *
     *  @return array   array of required javascript files
     */
    function get_required_javascript(){
        return array();
    }
    
    /**
     *  constructor 
     *  
     *  initialize internal variables
     */
    function __construct(){
        global $lang_str;
        parent::apu_base_class();


        $this->opt['screen_name'] = "IQUEST";


        /* message on attributes update */
        $this->opt['msg_update']['long']  =     &$lang_str['msg_changes_saved'];
        
        /*** names of variables assigned to smarty ***/
        /* form */
        $this->opt['smarty_form'] =         'form';
        /* smarty action */
        $this->opt['smarty_action'] =       'action';
        /* name of html form */
        $this->opt['form_name'] =           '';
        $this->opt['smarty_name'] =         'name';
        $this->opt['smarty_groups'] =       'clue_groups';
        $this->opt['smarty_clues'] =        'clues';
        
        $this->opt['form_submit']['text'] = $lang_str['b_ok'];
        
    }

    /**
     *  this metod is called always at begining - initialize variables
     */
    function init(){
        parent::init();

        if (!isset($_SESSION['apu_iquest'][$this->opt['instance_id']])){
            $_SESSION['apu_iquest'][$this->opt['instance_id']] = array();
        }
        
        $this->session = &$_SESSION['apu_iquest'][$this->opt['instance_id']];
        
        if (!isset($this->session['name'])){
            $this->session['name'] = '';
        }
    }
    
    
    
    /**
     *  Method perform action update
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_update(){

        $this->session['name'] = $_POST['hello_world_name'];

        action_log($this->opt['screen_name'], $this->action, "update name to: ".$this->session['name']);

        $get = array('hello_world_updated='.RawURLEncode($this->opt['instance_id']));
        return $get;
    }

    
    /**
     *  Method perform action view_grp 
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_view_grp(){

        $opt = array("ref_id" => $this->grp_ref_id);

        $clue_grp = Iquest::get_clue_grps_team($this->user_id->get_uid(), $opt);
        if (!$clue_grp){
            ErrorHandler::add_error("Unknown clue group!");
            sw_log("Unknown clue group: '".$this->grp_ref_id."'", PEAR_LOG_ERR);
            return false;
        }
        $clue_grp = reset($clue_grp);
        
        $clues = $clue_grp->get_clues();

        $this->smarty_clues = array();
        foreach($clues as $k => $v){
            $smarty_clue = $v->to_smarty();
            $smarty_clue['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_clue=".RawURLEncode($v->ref_id), false);
            $this->smarty_clues[$k] = $smarty_clue;
        }

        action_log($this->opt['screen_name'], $this->action, "View hello world screen");
        return true;
    }

    
    /**
     *  Method perform action default 
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_default(){

        $clue_groups = Iquest::get_clue_grps_team($this->user_id->get_uid());

        $this->smarty_groups = array();
        foreach($clue_groups as $k => $v){
            $smarty_group = $v->to_smarty();
            $smarty_group['detail_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_grp=".RawURLEncode($v->ref_id));
            $this->smarty_groups[$k] = $smarty_group;
        }


        action_log($this->opt['screen_name'], $this->action, "View hello world screen");
        return true;
    }
    
    /**
     *  check _get and _post arrays and determine what we will do 
     */
    function determine_action(){
        if ($this->was_form_submited()){    // Is there data to process?
            $this->action=array('action'=>"update",
                                'validate_form'=>true,
                                'reload'=>true);
        }
        elseif (isset($_GET['view_grp'])){
            $this->smarty_action = 'view_grp';
            $this->grp_ref_id = $_GET['view_grp'];
            $this->action=array('action'=>"view_grp",
                                 'validate_form'=>true,
                                 'reload'=>false);
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
                                     "name"=>"solution_key",
                                     "value"=>"",
                                     "js_trim_value" => true,
                                     "js_validate" => false, 
                                     "maxlength"=>64));
    }

    function form_invalid(){
        if ($this->action['action'] == "update"){
            action_log($this->opt['screen_name'], $this->action, "Update action failed", false, array("errors"=>$this->controler->errors));
        }
        elseif ($this->action['action'] == "view_grp"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: View clue group failed", false, array("errors"=>$this->controler->errors));
            if (false === $this->action_default($errors)) return false;
        }
    }

    /**
     *  validate html form 
     *
     *  @return bool            TRUE if given values of form are OK, FALSE otherwise
     */
    function validate_form(){
        global $lang_str;

        if ($this->action['action'] == "view_grp"){
            $grp_ok = true;

//@todo: check grp is accessible to the user

            return $grp_ok;
        }


        $form_ok = true;
        if (false === parent::validate_form()) $form_ok = false;

        if (empty($_POST['solution_key'])){
            ErrorHandler::add_error($lang_str['iquest_err_key_empty']);
            $form_ok = false; 
        }

        return $form_ok;
    }
    
    
    /**
     *  add messages to given array 
     *
     *  @param array $msgs  array of messages
     */
    function return_messages(&$msgs){
        if (isset($_GET['hello_world_updated']) and $_GET['hello_world_updated'] == $this->opt['instance_id']){
            $msgs[]=&$this->opt['msg_update'];
        }
    }

    /**
     *  assign variables to smarty 
     */
    function pass_values_to_html(){
        global $smarty;

        $smarty->assign($this->opt['smarty_action'], $this->smarty_action);
        $smarty->assign($this->opt['smarty_name'], $this->session['name']);
        $smarty->assign($this->opt['smarty_groups'], $this->smarty_groups);
        $smarty->assign($this->opt['smarty_clues'], $this->smarty_clues);
        
        
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
