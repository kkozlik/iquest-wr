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

    protected $team_id;

    protected $smarty_groups;
    protected $smarty_teams;
    protected $smarty_cgrp_team;

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

    function action_get_graph(){
        $this->controler->disable_html_output();

        $graph = new Iquest_solution_graph($this->team_id);
        $graph->image_graph();

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

        $this->smarty_groups = array();
        foreach($clue_groups as $k => $v){
            $this->smarty_groups[$k] = $v->to_smarty();
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
                $this->smarty_cgrp_team[$cgrp->id][$team->id] = "";
                if (!empty($open_cgrps[$cgrp->id][$team->id])){
                    $this->smarty_cgrp_team[$cgrp->id][$team->id] = 
                        date("H:i:s", $open_cgrps[$cgrp->id][$team->id]);
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
        if (isset($_GET['get_graph'])){
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
     *  assign variables to smarty 
     */
    function pass_values_to_html(){
        global $smarty;

        $smarty->assign($this->opt['smarty_teams'], $this->smarty_teams);
        $smarty->assign($this->opt['smarty_groups'], $this->smarty_groups);
        $smarty->assign($this->opt['smarty_cgrp_team'], $this->smarty_cgrp_team);
    }
    
}


?>
