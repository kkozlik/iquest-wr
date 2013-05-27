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

    protected $team_id;
    protected $ref_id;
    protected $clue_grp;
    protected $smarty_action = 'default';
    protected $smarty_groups;
    protected $smarty_clues;
    protected $smarty_solutions;

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
        $this->opt['smarty_solutions'] =    'solutions';
        
        $this->opt['form_submit']['text'] = $lang_str['b_ok'];
        
    }

    /**
     *  this metod is called always at begining - initialize variables
     */
    function init(){
        parent::init();

        $this->team_id = $this->user_id->get_uid();

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


    function action_get_clue(){
        $this->controler->disable_html_output();
        $clue = Iquest_Clue::by_ref_id($this->ref_id);
        $clue->flush_content();
        return true;
    }

    function action_get_hint(){
        $this->controler->disable_html_output();
        $hint = Iquest_Hint::by_ref_id($this->ref_id);
        $hint->flush_content();
        return true;
    }

    function action_get_solution(){
        $this->controler->disable_html_output();
        $hint = Iquest_Solution::by_ref_id($this->ref_id);
        $hint->flush_content();
        return true;
    }

    
    /**
     *  Method perform action view_grp 
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_view_grp(){

        $clues = $this->clue_grp->get_clues();

        $this->smarty_clues = array();
        foreach($clues as $k => $v){
            $clues[$k]->get_accessible_hints($this->team_id);
            $smarty_clue = $clues[$k]->to_smarty();
            $smarty_clue['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_clue=".RawURLEncode($clues[$k]->ref_id), false);

            foreach($smarty_clue['hints'] as $hk => $hv){
                $smarty_clue['hints'][$hk]['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_hint=".RawURLEncode($hv['ref_id']), false);
            }

            $this->smarty_clues[$k] = $smarty_clue;
        }

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View clue group screen");
        return true;
    }

    

    
    /**
     *  Method perform action view_solution 
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_view_solution(){

        $opt = array("ref_id" => $this->ref_id);

        $solution = Iquest_Solution::fetch($opt);
        if (!$solution){
            ErrorHandler::add_error("Unknown solution!");
            sw_log("Unknown solution: '".$this->ref_id."'", PEAR_LOG_ERR);
            return false;
        }
        $solution = reset($solution);
        
        $this->smarty_solutions = $solution->to_smarty();
        $this->smarty_solutions['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_solution=".RawURLEncode($solution->ref_id), false);


        action_log($this->opt['screen_name'], $this->action, "IQUEST: View solution");
        return true;
    }
    /**
     *  Method perform action default 
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_default(){

        $clue_groups = Iquest::get_clue_grps_team($this->team_id);

        $this->smarty_groups = array();
        foreach($clue_groups as $k => $v){
            $smarty_group = $v->to_smarty();
            $smarty_group['detail_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_grp=".RawURLEncode($v->ref_id));
            $this->smarty_groups[$k] = $smarty_group;
        }

        $solutions = Iquest::get_accessible_solutions($this->team_id);

        $this->smarty_solutions = array();
        foreach($solutions as $k => $v){
            $smarty_solution = $v->to_smarty();
            $smarty_solution['detail_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_solution=".RawURLEncode($v->ref_id));
            $this->smarty_solutions[$k] = $smarty_solution;
        }


        action_log($this->opt['screen_name'], $this->action, "IQUEST: View default screen");
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
            $this->ref_id = $_GET['view_grp'];
            $this->action=array('action'=>"view_grp",
                                 'validate_form'=>true,
                                 'reload'=>false);
        }
        elseif (isset($_GET['view_solution'])){
            $this->smarty_action = 'view_solution';
            $this->ref_id = $_GET['view_solution'];
            $this->action=array('action'=>"view_solution",
                                 'validate_form'=>true,
                                 'reload'=>false);
        }
        elseif (isset($_GET['get_clue'])){
            $this->ref_id = $_GET['get_clue'];
            $this->action=array('action'=>"get_clue",
                                 'validate_form'=>true,
                                 'reload'=>false,
                                 'alone'=>true);
        }
        elseif (isset($_GET['get_hint'])){
            $this->ref_id = $_GET['get_hint'];
            $this->action=array('action'=>"get_hint",
                                 'validate_form'=>true,
                                 'reload'=>false,
                                 'alone'=>true);
        }
        elseif (isset($_GET['get_solution'])){
            $this->ref_id = $_GET['get_solution'];
            $this->action=array('action'=>"get_solution",
                                 'validate_form'=>true,
                                 'reload'=>false,
                                 'alone'=>true);
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

            $opt = array("ref_id" => $this->ref_id);
        
            $this->clue_grp = Iquest::get_clue_grps_team($this->team_id, $opt);
            if (!$this->clue_grp){
                ErrorHandler::add_error("Unknown clue group!");
                sw_log("Unknown clue group: '".$this->ref_id."'", PEAR_LOG_ERR);
                return false;
            }
            $this->clue_grp = reset($this->clue_grp);

            return $grp_ok;
        }

        if ($this->action['action'] == "view_solution" or 
            $this->action['action'] == "get_solution"){
            $grp_ok = true;

//@todo: check grp is accessible to the user

            return $grp_ok;
        }

        if ($this->action['action'] == "get_clue"){
            $clue_ok = true;

//@todo: check clue is accessible to the user

            return $clue_ok;
        }

        if ($this->action['action'] == "get_hint"){
            $clue_ok = true;

//@todo: check clue is accessible to the user

            return $clue_ok;
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
        $smarty->assign($this->opt['smarty_solutions'], $this->smarty_solutions);
        
        
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
