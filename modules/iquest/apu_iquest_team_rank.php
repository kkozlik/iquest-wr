<?php
/**
 * Application unit events
 *
 * @author    Karel Kozlik
 * @package   serweb
 */

/**
 *  Application unit events
 *
 *
 *  This application unit is used for display iquest events
 *
 *  Configuration:
 *  --------------
 *
 *  'msg_update'                    default: $lang_str['msg_changes_saved_s'] and $lang_str['msg_changes_saved_l']
 *   message which should be showed on attributes update - assoc array with keys 'short' and 'long'
 *
 *  'form_name'                 (string) default: ''
 *   name of html form
 *
 *  'form_submit'               (assoc)
 *   assotiative array describe submit element of form. For details see description
 *   of method add_submit in class form_ext
 *
 *  'smarty_form'               name of smarty variable - see below
 *
 *  Exported smarty variables:
 *  --------------------------
 *  opt['smarty_form']          (form)
 *   phplib html form
 *
 *
 *  opt['smarty_pager']             (pager)
 *   associative array containing size of result and which page is returned
 */

class apu_iquest_team_rank extends apu_base_class{

    protected $smarty_ranks;
    protected $smarty_actual_order;


    /**
     *  constructor
     *
     *  initialize internal variables
     */
    function __construct(){
        global $lang_str;
        parent::apu_base_class();

        /* set default values to $this->opt */
        $this->opt['screen_name'] = "IQUEST Team Rank";


        /*** names of variables assigned to smarty ***/
        /* smarty action */
        $this->opt['smarty_ranks'] =       'ranks';

        $this->opt['smarty_actual_order'] =       'actual_order';
    }

    /**
     *  Method perform action default
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_default(){

        $ranks = Iquest_team_rank::fetch();
        $teams = Iquest_team::fetch();

        $this->smarty_ranks = array();
        foreach($teams as $team){
            $this->smarty_ranks[$team->id]['name'] = $team->name;
        }

        foreach($ranks as $rank){
            foreach($rank->rank as $team_id => $team_rank){
                $this->smarty_ranks[$team_id]['data'][] =
                            array("timestamp"   => $rank->timestamp,
                                  "rank"        => $team_rank,
                                  "origin"      => ($team_id == $rank->team_id));
            }
        }


        $this->smarty_actual_order = array();
        if ($ranks){
            $actual_order = end($ranks)->rank;
            $actual_order_team_name = array();

            foreach($actual_order as $team_id => $team_rank){
                $team_name = $this->smarty_ranks[$team_id]['name'];
                $actual_order_team_name[$team_name] = $team_rank;
            }

            asort($actual_order_team_name);
            $this->smarty_actual_order = $actual_order_team_name;
        }

        action_log($this->opt['screen_name'], $this->action, "View ranks");
    }


    /**
     *  assign variables to smarty
     */
    function pass_values_to_html(){
        global $smarty;
        $smarty->assign($this->opt['smarty_ranks'], $this->smarty_ranks);
        $smarty->assign($this->opt['smarty_actual_order'], $this->smarty_actual_order);
    }
}


?>
