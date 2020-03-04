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

class apu_iquest_reveal_goal extends apu_base_class{

    protected $team_id;
    protected $ref_id;
    protected $download = false;
    protected $clue;
    protected $clue_grp;
    protected $smarty_clues;

    /**
     *  constructor
     *
     *  initialize internal variables
     */
    function __construct(){
        global $lang_str;
        parent::apu_base_class();


        $this->opt['screen_name'] = "IQUEST-REVEAL";
        $this->opt['team_id'] =     null;

        $this->opt['main_url'] = "";


        /*** names of variables assigned to smarty ***/
        $this->opt['smarty_clues'] =        'clues';
        $this->opt['smarty_main_url'] =     'main_url';

    }

    /**
     *  this metod is called always at begining - initialize variables
     */
    function init(){
        parent::init();

        if (is_null($this->opt['team_id'])) throw new Exception("team_id is not set");
        $this->team_id = $this->opt['team_id'];


        // Verify that the goal could be revealed, exit otherwise

        if (!Iquest::is_over()){
            // If it is not time to show target location yet, return back to main screen
            $this->controler->redirect($this->opt['main_url']);
            exit;
        }


        // Get ID of the clue group revealing the goal
        $cgrp_id = Iquest_Options::get(Iquest_Options::REVEAL_GOAL_CGRP_ID);

        // And fetch the clue group
        $opt = array("id" => $cgrp_id);
        $this->clue_grp = Iquest_ClueGrp::fetch($opt);

        if (!$this->clue_grp){
            throw new Exception("Unknown clue group (ID=$cgrp_id)");
        }

        $this->clue_grp = reset($this->clue_grp);

    }


    function action_get_clue(){
        $this->controler->disable_html_output();
        $this->clue->flush_content($this->download);
        return true;
    }


    /**
     *  Method perform action defaul
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_default(){

        $clues = $this->clue_grp->get_clues();

        $this->smarty_clues = array();
        foreach($clues as $k => $v){
            $smarty_clue = $clues[$k]->to_smarty();
            $smarty_clue['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_clue=".RawURLEncode($clues[$k]->ref_id), false);
            $smarty_clue['download_file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_clue=".RawURLEncode($clues[$k]->ref_id)."&download=1", false);
            $this->smarty_clues[$k] = $smarty_clue;
        }

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View contest goal screen");
        return true;
    }





    /**
     *  check _get and _post arrays and determine what we will do
     */
    function determine_action(){
        if (isset($_GET['get_clue'])){
            $this->ref_id = $_GET['get_clue'];
            $this->download = !empty($_GET['download']);
            $this->action=array('action'=>"get_clue",
                                 'validate_form'=>true,
                                 'reload'=>false,
                                 'alone'=>true);
        }
        else $this->action=array('action'=>"default",
                                 'validate_form'=>false,
                                 'reload'=>false);
    }

    function form_invalid(){
        if ($this->action['action'] == "get_clue"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: Get clue failed", false, array("errors"=>$this->controler->errors));
        }
    }

    /**
     *  validate html form
     *
     *  @return bool            TRUE if given values of form are OK, FALSE otherwise
     */
    function validate_form(){
        global $lang_str;

        /* Check that clue is accessible by user before showing it */
        if ($this->action['action'] == "get_clue"){

            $this->clue = Iquest_Clue::by_ref_id($this->ref_id);
            if (!$this->clue){
                ErrorHandler::add_error("Unknown clue!");
                sw_log("Unknown clue: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }

            if ($this->clue->cgrp_id != $this->clue_grp->id){
                ErrorHandler::add_error("Unknown clue!");
                sw_log("Not accessible clue: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }

            return true;
        }

        return true;
    }


    /**
     *  assign variables to smarty
     */
    function pass_values_to_html(){
        global $smarty;

        $smarty->assign($this->opt['smarty_clues'], $this->smarty_clues);
        $smarty->assign($this->opt['smarty_main_url'], $this->controler->url($this->opt['main_url']));
    }

}
