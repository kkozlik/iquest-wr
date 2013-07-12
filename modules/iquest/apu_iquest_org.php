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

class apu_iquest_org extends apu_base_class{

    protected $ref_id;
    protected $team_id;

    protected $hint;
    protected $clue;
    protected $clue_grp;

    protected $smarty_groups;
    protected $smarty_teams;
    protected $smarty_cgrp_team;
    protected $smarty_clues;
    protected $smarty_action;

    /**
     *  constructor 
     *  
     *  initialize internal variables
     */
    function __construct(){
        global $lang_str;
        parent::apu_base_class();


        $this->opt['screen_name'] = "IQUEST admin";

        /*** names of variables assigned to smarty ***/
        /* name of html form */
        $this->opt['smarty_groups'] =       'clue_groups';
        $this->opt['smarty_teams'] =        'teams';
        $this->opt['smarty_cgrp_team'] =    'cgrp_team';

        $this->opt['smarty_action'] =       'action';
        $this->opt['smarty_clues'] =        'clues';

        $this->opt['smarty_main_url'] =         'main_url';
    }

    /**
     *  this metod is called always at begining - initialize variables
     */
    function init(){
        parent::init();

        if (!isset($_SESSION['apu_iquest_org'][$this->opt['instance_id']])){
            $_SESSION['apu_iquest_org'][$this->opt['instance_id']] = array();
        }
        
        $this->session = &$_SESSION['apu_iquest_org'][$this->opt['instance_id']];
        
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

    function action_get_graph(){
        $this->controler->disable_html_output();

        $graph = new Iquest_solution_graph($this->team_id);
        $graph->image_graph();

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
            $hints = $clues[$k]->get_all_hints();

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
     *  Method perform action default 
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_default(){


        $teams = Iquest_Team::fetch();
        $clue_groups = Iquest_ClueGrp::fetch(array("orderby"=>"ordering"));
        $open_cgrps = Iquest_ClueGrp::fetch_cgrp_open();
        $solutions = Iquest_Solution::fetch();

        $cgrp_from_sol = array();
        foreach($solutions as $solution){
            $cgrp_from_sol[$solution->cgrp_id] = $solution->id;
        }

        $this->smarty_groups = array();
        foreach($clue_groups as $k => $v){
            $this->smarty_groups[$k] = $v->to_smarty();
            $this->smarty_groups[$k]['view_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_grp=".RawURLEncode($v->ref_id));
        }

        $this->smarty_teams = array();
        foreach($teams as $k => $v){
            $this->smarty_teams[$k] = $v->to_smarty();
            $this->smarty_teams[$k]['graph_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_graph=".RawURLEncode($v->id));
        }

        $this->smarty_cgrp_team = array();
        foreach($clue_groups as $cgrp){
            $this->smarty_cgrp_team[$cgrp->id] = array();

            foreach($teams as $team){
                $this->smarty_cgrp_team[$cgrp->id][$team->id] = array("gained_at" => "",
                                                                      "solved" => false);
                if (!empty($open_cgrps[$cgrp->id][$team->id])){
                    $this->smarty_cgrp_team[$cgrp->id][$team->id]["gained_at"] = 
                        date("H:i:s", $open_cgrps[$cgrp->id][$team->id]);
                }
                
                // $solutions[$cgrp->id] is tricky!! Solutions are indexed by 
                // solution_id not cgrp_id. However these two are same, at least
                // if the contest is linear.
                // 
                // Maybe it would be better to display list of solutions on 
                // the screen instead of list of clue groups. This is a subject to change
                //
                // @todo: The HQ overview screen may not work correctly if the contest is not linear
                if (isset($solutions[$cgrp->id])){
                    $cgrp_id = $solutions[$cgrp->id]->cgrp_id;
                    $solved = !empty($open_cgrps[$cgrp_id][$team->id]);

                    $this->smarty_cgrp_team[$cgrp->id][$team->id]["solved"] = $solved;
                }

            }
        }


        action_log($this->opt['screen_name'], $this->action, "IQUEST: View default screen");
        return true;
    }
    

    /**
     *  check _get and _post arrays and determine what we will do 
     */
    function determine_action(){
        if (isset($_GET['view_grp'])){
            $this->smarty_action = 'view_grp';
            $this->ref_id = $_GET['view_grp'];
            $this->action=array('action'=>"view_grp",
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
        elseif (isset($_GET['get_graph'])){
            $this->team_id = $_GET['get_graph'];
            $this->action=array('action'=>"get_graph",
                                 'validate_form'=>false,
                                 'reload'=>false,
                                 'alone'=>true);
        }
        else $this->action=array('action'=>"default",
                                 'validate_form'=>false,
                                 'reload'=>false);
    }

    /**
     *  validate html form 
     *
     *  @return bool            TRUE if given values of form are OK, FALSE otherwise
     */
    function validate_form(){
        global $lang_str;

        /* Check that clue group exists before showing it */
        if ($this->action['action'] == "view_grp"){
            $opt = array("ref_id" => $this->ref_id);
        
            $this->clue_grp = Iquest_ClueGrp::fetch($opt);
            if (!$this->clue_grp){
                ErrorHandler::add_error("Unknown clue group!");
                sw_log("Unknown clue group: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }
            $this->clue_grp = reset($this->clue_grp);

            return true;
        }

        /* Check that clue exists before showing it */
        if ($this->action['action'] == "get_clue"){

            $this->clue = Iquest_Clue::by_ref_id($this->ref_id);
            if (!$this->clue){
                ErrorHandler::add_error("Unknown clue!");
                sw_log("Unknown clue: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }
            
            return true;
        }


        /* check hint is exists */
        if ($this->action['action'] == "get_hint"){

            $opt = array("ref_id" => $this->ref_id);
            $hints = Iquest_Hint::fetch($opt);

            if (!$hints){
                ErrorHandler::add_error("Unknown hint!");
                sw_log("Unknown hint: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }

            $this->hint = reset($hints);
            return true;
        }

        return false;
    }    

    /**
     *  assign variables to smarty 
     */
    function pass_values_to_html(){
        global $smarty;

        $smarty->assign($this->opt['smarty_teams'], $this->smarty_teams);
        $smarty->assign($this->opt['smarty_groups'], $this->smarty_groups);
        $smarty->assign($this->opt['smarty_cgrp_team'], $this->smarty_cgrp_team);

        $smarty->assign($this->opt['smarty_action'], $this->smarty_action);
        $smarty->assign($this->opt['smarty_clues'], $this->smarty_clues);

        $smarty->assign($this->opt['smarty_main_url'], $this->controler->url($_SERVER['PHP_SELF']));
    }
    
}


?>
