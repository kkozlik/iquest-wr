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

    const   COUNTDOWN_LIMIT = 900; // 15 minutes

    protected $team_id;
    protected $ref_id;
    protected $clue;
    protected $clue_grp;
    protected $hint;
    protected $solution;
    protected $smarty_action = 'default';
    protected $smarty_groups;
    protected $smarty_clues;
    protected $smarty_solutions;
    protected $smarty_next_solution = null;
    protected $smarty_next_hint = null;

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

        $this->opt['msg_solve']['long']  =     &$lang_str['iquest_msg_key_correct'];
        
        /*** names of variables assigned to smarty ***/
        /* form */
        $this->opt['smarty_form'] =         'form';
        /* smarty action */
        $this->opt['smarty_action'] =       'action';
        /* name of html form */
        $this->opt['form_name'] =           '';
        $this->opt['smarty_groups'] =       'clue_groups';
        $this->opt['smarty_clues'] =        'clues';
        $this->opt['smarty_solutions'] =    'solutions';

        $this->opt['smarty_next_solution'] =    'next_solution';
        $this->opt['smarty_next_hint'] =        'next_hint';

        $this->opt['smarty_main_url'] =         'main_url';
        
        $this->opt['form_submit']['text'] = $lang_str['b_ok'];
        $this->opt['form_submit']['class'] = "btn btn-primary";
        
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
        
        if (!isset($this->session['known_cgrps']))      $this->session['known_cgrps'] = array();
        if (!isset($this->session['known_hints']))      $this->session['known_hints'] = array();
        if (!isset($this->session['known_solutions']))  $this->session['known_solutions'] = array();
    }
    
    function get_timeouts(){

        $next_solution = Iquest_Solution::get_next_scheduled($this->team_id);
        $next_hint     = Iquest_Hint::get_next_scheduled($this->team_id);

        if ($next_solution){
            $show_after = $next_solution['show_at'] - time();
            if ($show_after > 0 and 
                $show_after < self::COUNTDOWN_LIMIT){
                
                $this->smarty_next_solution = gmdate("H:i:s", $show_after);
                $this->controler->set_onload_js("enable_countdown('#solution_countdown', $show_after);");
            }
        }

        if ($next_hint){
            $show_after = $next_hint['show_at'] - time();
            if ($show_after > 0 and 
                $show_after < self::COUNTDOWN_LIMIT){
                
                $this->smarty_next_hint = gmdate("H:i:s", $show_after);
                $this->controler->set_onload_js("enable_countdown('#hint_countdown', $show_after);");
            }
        }
    }
    
    /**
     *  Method perform action solve
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_solve(){

        Iquest_Events::add(Iquest_Events::KEY,
                           true,
                           array("key" => isset($_POST['solution_key']) ? $_POST['solution_key'] : null,
                                 "solution" => $this->solution));

        Iquest::solution_found($this->solution, $this->team_id);

        action_log($this->opt['screen_name'], $this->action, " Solved: ".$this->solution->id);

        $get = array('apu_iquest='.RawURLEncode($this->opt['instance_id']));
        return $get;
    }


    function action_get_clue(){
        $this->controler->disable_html_output();
        $this->clue->flush_content();
        return true;
    }

    function action_get_hint(){
        $this->controler->disable_html_output();
        $this->hint->flush_content();
        return true;
    }

    function action_get_solution(){
        $this->controler->disable_html_output();
        $this->solution->flush_content();
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
            $hints = $clues[$k]->get_accessible_hints($this->team_id);
            $smarty_clue = $clues[$k]->to_smarty();
            $smarty_clue['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_clue=".RawURLEncode($clues[$k]->ref_id), false);

            foreach($smarty_clue['hints'] as $hk => $hv){
                $smarty_clue['hints'][$hk]['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_hint=".RawURLEncode($hv['ref_id']), false);
                $smarty_clue['hints'][$hk]['new'] = !isset($this->session['known_hints'][$hv['id']]);
                $this->session['known_hints'][$hv['id']] = true;
            }

            $this->smarty_clues[$k] = $smarty_clue;
        }

        $this->get_timeouts();
        $this->session['known_cgrps'][$this->clue_grp->id] = true;

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View clue group screen");
        return true;
    }

    

    
    /**
     *  Method perform action view_solution 
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_view_solution(){
        
        $this->smarty_solutions = $this->solution->to_smarty();
        $this->smarty_solutions['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_solution=".RawURLEncode($this->solution->ref_id), false);

        $this->get_timeouts();
        $this->session['known_solutions'][$this->solution->id] = true;

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View solution");
        return true;
    }

    /**
     *  Method perform action default 
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_default(){

        $opt = array("team_id" => $this->team_id,
                     "available_only" => true);
        $clue_groups = Iquest_ClueGrp::fetch($opt);

        // if there are no open clue groups
        if (!count($clue_groups)){
            // The team did not started the contest yet, so start it
            Iquest::start($this->team_id);
            // And fetch the clue groups again
            $clue_groups = Iquest_ClueGrp::fetch($opt);
        }

        $this->smarty_groups = array();
        foreach($clue_groups as $k => $v){
            $new_hints = false;
            
            $opt = array("cgrp_id" => $v->id,
                         "team_id" => $this->team_id,
                         "accessible" => true);
            $hints = Iquest_Hint::fetch($opt);

            foreach($hints as $hint){
                if (!isset($this->session['known_hints'][$hint->id])){
                    $new_hints = true;
                    break;
                }
            }

            $smarty_group = $v->to_smarty();
            $smarty_group['detail_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_grp=".RawURLEncode($v->ref_id));
            $smarty_group['new'] = !isset($this->session['known_cgrps'][$v->id]);
            $smarty_group['new_hints'] = $new_hints;
            $this->smarty_groups[$k] = $smarty_group;
        }

        $solutions = Iquest_Solution::fetch_accessible($this->team_id);

        $this->smarty_solutions = array();
        foreach($solutions as $k => $v){
            $smarty_solution = $v->to_smarty();
            $smarty_solution['detail_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_solution=".RawURLEncode($v->ref_id));
            $smarty_solution['new'] = !isset($this->session['known_solutions'][$v->id]);
            $this->smarty_solutions[$k] = $smarty_solution;
        }

        $this->get_timeouts();

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View default screen");
        return true;
    }
    
    /**
     *  check _get and _post arrays and determine what we will do 
     */
    function determine_action(){
        if ($this->was_form_submited()){    // Is there data to process?
            $this->action=array('action'=>"solve",
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
        if ($this->action['action'] == "solve"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: Key entering failed", false, array("errors"=>$this->controler->errors));

            $event_data = array("key" => isset($_POST['solution_key']) ? $_POST['solution_key'] : null);
            if (isset($_POST['solution_key'])){
                $event_data["diacritics_key"] = remove_diacritics($_POST['solution_key']);
                $event_data["cannon_key"] = Iquest_Solution::canonicalize_key($_POST['solution_key']);
            }

            Iquest_Events::add(Iquest_Events::KEY,
                               false,
                               $event_data);
            if (false === $this->action_default()) return false;
        }
        elseif ($this->action['action'] == "view_grp"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: View clue group failed", false, array("errors"=>$this->controler->errors));
        }
        elseif ($this->action['action'] == "view_solution"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: View solution failed", false, array("errors"=>$this->controler->errors));
        }
        elseif ($this->action['action'] == "get_solution"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: Get solution failed", false, array("errors"=>$this->controler->errors));
        }
        elseif ($this->action['action'] == "get_clue"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: Get clue failed", false, array("errors"=>$this->controler->errors));
        }
        elseif ($this->action['action'] == "get_hint"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: Get hint failed", false, array("errors"=>$this->controler->errors));
        }
    }

    /**
     *  validate html form 
     *
     *  @return bool            TRUE if given values of form are OK, FALSE otherwise
     */
    function validate_form(){
        global $lang_str;

        /* Check that clue group is accessible by user before showing it */
        if ($this->action['action'] == "view_grp"){

            $opt = array("ref_id" => $this->ref_id,
                         "team_id" => $this->team_id,
                         "available_only" => true);
        
            $this->clue_grp = Iquest_ClueGrp::fetch($opt);
            if (!$this->clue_grp){
                ErrorHandler::add_error("Unknown clue group!");
                sw_log("Unknown or not accessible clue group: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }
            $this->clue_grp = reset($this->clue_grp);

            return true;
        }


        /* check that solution is accessible to the user */
        if ($this->action['action'] == "view_solution" or 
            $this->action['action'] == "get_solution"){

            $opt = array("ref_id" => $this->ref_id,
                         "team_id" => $this->team_id,
                         "accessible" => true);
            $solutions = Iquest_Solution::fetch($opt);
    
            if (!$solutions){
                ErrorHandler::add_error("Unknown solution!");
                sw_log("Unknown solution: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }
            $this->solution = reset($solutions);

            return true;
        }


        /* Check that clue is accessible by user before showing it */
        if ($this->action['action'] == "get_clue"){

            $this->clue = Iquest_Clue::by_ref_id($this->ref_id);
            if (!$this->clue){
                ErrorHandler::add_error("Unknown clue!");
                sw_log("Unknown clue: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }
            
            if (!Iquest_ClueGrp::is_accessible($this->clue->cgrp_id, $this->team_id)){
                ErrorHandler::add_error("Unknown clue!");
                sw_log("Not accessible clue: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }
            
            return true;
        }


        /* check hint is accessible to the user */
        if ($this->action['action'] == "get_hint"){

            $opt = array("ref_id" => $this->ref_id,
                         "team_id" => $this->team_id,
                         "accessible" => true);
            $hints = Iquest_Hint::fetch($opt);

            if (!$hints){
                ErrorHandler::add_error("Unknown hint!");
                sw_log("Unknown hint: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }

            $this->hint = reset($hints);
            return true;
        }


        $form_ok = true;
        if (false === parent::validate_form()) $form_ok = false;

        if (Iquest::is_over()){
            ErrorHandler::add_error($lang_str['iquest_err_contest_over']);
            return false; 
        }

        if (empty($_POST['solution_key'])){
            ErrorHandler::add_error($lang_str['iquest_err_key_empty']);
            return false; 
        }

        $this->solution = Iquest_Solution::by_key($_POST['solution_key']);

        if (!$this->solution){
            ErrorHandler::add_error($lang_str['iquest_err_key_invalid']);
            return false; 
        }

        if (Iquest_ClueGrp::is_accessible($this->solution->cgrp_id, $this->team_id)){
            ErrorHandler::add_error($lang_str['iquest_err_key_dup']);
            return false; 
        }

        return $form_ok;
    }
    
    
    /**
     *  add messages to given array 
     *
     *  @param array $msgs  array of messages
     */
    function return_messages(&$msgs){
        if (isset($_GET['apu_iquest']) and $_GET['apu_iquest'] == $this->opt['instance_id']){
            $msgs[]=&$this->opt['msg_solve'];
        }
    }

    /**
     *  assign variables to smarty 
     */
    function pass_values_to_html(){
        global $smarty;

        $smarty->assign($this->opt['smarty_action'], $this->smarty_action);
        $smarty->assign($this->opt['smarty_groups'], $this->smarty_groups);
        $smarty->assign($this->opt['smarty_clues'], $this->smarty_clues);
        $smarty->assign($this->opt['smarty_solutions'], $this->smarty_solutions);

        $smarty->assign($this->opt['smarty_next_solution'], $this->smarty_next_solution);
        $smarty->assign($this->opt['smarty_next_hint'], $this->smarty_next_hint);

        $smarty->assign($this->opt['smarty_main_url'], $this->controler->url($_SERVER['PHP_SELF']));
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
