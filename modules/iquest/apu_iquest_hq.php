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

class apu_iquest_hq extends apu_base_class{

    protected $sorter=null;
    protected $ref_id;
    protected $download = false;
    protected $team_id;

    protected $hint;
    protected $clue;
    protected $clue_grp;
    protected $solution;

    protected $smarty_groups;
    protected $smarty_teams;
    protected $smarty_cgrp_team;
    protected $smarty_solution_team;
    protected $smarty_clues;
    protected $smarty_hint;
    protected $smarty_solutions;
    protected $smarty_action = "default";

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
        $this->opt['smarty_solutions'] =    'solutions';
        $this->opt['smarty_teams'] =        'teams';
        $this->opt['smarty_cgrp_team'] =    'cgrp_team';
        $this->opt['smarty_solution_team'] ='solution_team';

        $this->opt['smarty_action'] =       'action';
        $this->opt['smarty_clues'] =        'clues';
        $this->opt['smarty_hint'] =         'hint';

        $this->opt['smarty_get_graph_url'] =    'get_graph_url';
    }

    function set_sorter(&$sorter){
        $this->sorter = &$sorter;
    }

    /**
     *  this metod is called always at begining - initialize variables
     */
    function init(){
        parent::init();

        if (is_a($this->sorter, "apu_base_class")){
            /* register callback called on sorter change */
            //$this->sorter->set_opt('on_change_callback', array(&$this, 'sorter_changed'));
            $this->sorter->set_base_apu($this);
        }
    }

    function get_sorter_columns(){
        return array('name', 'rank');
    }

    function action_get_clue(){
        $this->controler->disable_html_output();
        $this->clue->flush_content($this->download);
        return true;
    }

    function action_get_hint(){
        $this->controler->disable_html_output();
        $this->hint->flush_content($this->download);
        return true;
    }

    function action_get_solution(){
        $this->controler->disable_html_output();
        $this->solution->flush_content($this->download);
        return true;
    }

    function action_get_graph(){
        $this->controler->disable_html_output();

        if (isset($_GET['type']) and $_GET['type'] == "complex"){
            $graph = new Iquest_solution_graph($this->team_id);
        }
        else{
            $cgrp_url = $_SERVER['PHP_SELF'].
                            "?view_grp=<ID>".
                            "&back_url=".rawurlencode($_SERVER['PHP_SELF']."?view_graph=".rawurlencode($this->team_id));

            $graph = new Iquest_contest_graph_simplified($this->team_id);
            $graph->set_cgrp_url($this->controler->url($cgrp_url));
            $graph->link_unknown_cgrps(true);
            $graph->hide_names(false);
        }
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
            // skip hidden clues
            if ($v->type == Iquest_Clue::TYPE_HIDDEN) continue;

            $hints = $clues[$k]->get_all_hints();

            $smarty_clue = $clues[$k]->to_smarty();
            $smarty_clue['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_clue=".RawURLEncode($clues[$k]->ref_id), false);
            $smarty_clue['download_file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_clue=".RawURLEncode($clues[$k]->ref_id)."&download=1", false);

            foreach($smarty_clue['hints'] as $hk => $hv){
                $smarty_clue['hints'][$hk]['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_hint=".RawURLEncode($hv['ref_id']), false);
                $smarty_clue['hints'][$hk]['download_file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_hint=".RawURLEncode($hv['ref_id'])."&download=1", false);
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

        $this->smarty_solutions = $this->solution->to_smarty();
        $this->smarty_solutions['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_solution=".RawURLEncode($this->solution->ref_id), false);
        $this->smarty_solutions['download_file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_solution=".RawURLEncode($this->solution->ref_id)."&download=1", false);

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View solution screen");
        return true;
    }

    /**
     *  Method perform action view_hint
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_view_hint(){

        $this->smarty_hint = $this->hint->to_smarty();
        $this->smarty_hint['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_hint=".RawURLEncode($this->hint->ref_id), false);
        $this->smarty_hint['download_file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_hint=".RawURLEncode($this->hint->ref_id)."&download=1", false);

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View hint screen");
        return true;
    }

    function action_view_graph(){

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View graph");
        return true;
    }

    /**
     *  Method perform action default
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_default(){

        $order_by_rank = false;
        $order_desc    = false;

        $opt = array();
        if (is_a($this->sorter, "apu_base_class")){
            $order_desc = $this->sorter->get_sort_dir();
            if ($this->sorter->get_sort_col() == "name"){
                $opt['order_by']   = $this->sorter->get_sort_col();
                $opt['order_desc'] = $order_desc;
            }
            elseif ($this->sorter->get_sort_col() == "rank"){
                $ranks = Iquest_team_rank::fetch(array("last"=>1));
                $actual_order = reset($ranks)->rank;
                $order_by_rank = true;
            }
        }

        $teams = Iquest_Team::fetch($opt);

        if ($order_by_rank){
            uasort($teams, function($a, $b) use ($actual_order, $order_desc){

                $rank1 = $actual_order[$a->id];
                $rank2 = $actual_order[$b->id];

                if ($rank1 == $rank2) return 0;
                if ($order_desc) return ($rank1 > $rank2) ? -1 : 1;
                else             return ($rank1 < $rank2) ? -1 : 1;
            });

        }


        $clue_groups = Iquest_ClueGrp::fetch(array("orderby"=>"ordering"));
        $open_cgrps = Iquest_ClueGrp::fetch_cgrp_open();
        $solutions = Iquest_Solution::fetch();
        $solution_team = Iquest_Solution::fetch_solution_team();

        $this->smarty_solutions = array();
        foreach($solutions as $solution){
            $this->smarty_solutions[$solution->id] = $solution->to_smarty();
            if ($solution->filename){
                $this->smarty_solutions[$solution->id]['view_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_solution=".RawURLEncode($solution->ref_id));
            }
        }

        $this->smarty_groups = array();
        foreach($clue_groups as $k => $v){
            $cgrp_solutions = Iquest_Solution::fetch_by_opening_cgrp($v->id);
            $solution_ids = array_keys($cgrp_solutions);

            // Sort solutions of each clue group by ordening number of the first
            // clue group they are leading to.
            usort($solution_ids, function($a, $b) use ($clue_groups, $solutions){
                $next_cgrp_ids_a = $solutions[$a]->get_next_cgrp_ids();
                $next_cgrp_ids_b = $solutions[$b]->get_next_cgrp_ids();

                $next_cgrp_id_a = reset($next_cgrp_ids_a);
                $next_cgrp_id_b = reset($next_cgrp_ids_b);

                if (!isset($clue_groups[$next_cgrp_id_a]) and
                    !isset($clue_groups[$next_cgrp_id_b])){

                    return $solutions[$a]->name > $solutions[$b]->name;
                }

                if (!isset($clue_groups[$next_cgrp_id_a])) return 1;
                if (!isset($clue_groups[$next_cgrp_id_b])) return -1;

                return $clue_groups[$next_cgrp_id_a]->ordering -
                       $clue_groups[$next_cgrp_id_b]->ordering;
            });
            $this->smarty_groups[$k] = $v->to_smarty();
            $this->smarty_groups[$k]['view_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_grp=".RawURLEncode($v->ref_id));
            $this->smarty_groups[$k]['solution_ids'] = $solution_ids;
        }

        $this->smarty_teams = array();
        foreach($teams as $k => $v){
            $this->smarty_teams[$k] = $v->to_smarty();
            $this->smarty_teams[$k]['graph_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_graph=".RawURLEncode($v->id));
        }

        $this->smarty_cgrp_team = array();
        foreach($clue_groups as $cgrp){
            $this->smarty_cgrp_team[$cgrp->id] = array();

            foreach($teams as $team){
                $this->smarty_cgrp_team[$cgrp->id][$team->id] = array("gained_at" => "",
                                                                      "gained_at_date" => "",
                                                                      "gained" => false);
                if (!empty($open_cgrps[$cgrp->id][$team->id])){
                    $this->smarty_cgrp_team[$cgrp->id][$team->id] =
                        array("gained_at"       => date("H:i:s", $open_cgrps[$cgrp->id][$team->id]),
                              "gained_at_date"  => date("d.m.Y", $open_cgrps[$cgrp->id][$team->id]),
                              "gained"          => true);
                }
            }
        }

        $this->smarty_solution_team = array();
        foreach($solutions as $solution){
            $this->smarty_solution_team[$solution->id] = array();

            foreach($teams as $team){
                $this->smarty_solution_team[$solution->id][$team->id] = array("solved_at" => "",
                                                                              "solved_at_date" => "",
                                                                              "solved" => false,
                                                                              "showed" => false,
                                                                              "scheduled" => false,
                                                                              "time_to_show" => "",
                                                                        );

                // If the solution is solved, set the solved date
                if (!empty($solution_team[$solution->id][$team->id])){

                    $solved =    (bool)$solution_team[$solution->id][$team->id]['solved_at'];
                    $scheduled = (bool)$solution_team[$solution->id][$team->id]['show_at'];
                    $showed =    false;
                    $time_to_show = "";

                    if ($scheduled){
                        $time_to_show = $solution_team[$solution->id][$team->id]['show_at'] - time(); // TODO: shall this be updated for time shifts??
                        if ($time_to_show < 0) $showed =true;
                        else {
                            $hours = floor($time_to_show / 3600);
                            $mins = floor($time_to_show / 60 % 60);
                            $secs = floor($time_to_show % 60);
                            $time_to_show = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                        }
                    }

                    $this->smarty_solution_team[$solution->id][$team->id] =
                        array("solved_at"       => $solved ? date("H:i:s", $solution_team[$solution->id][$team->id]['solved_at']) : "",
                              "solved_at_date"  => $solved ? date("d.m.Y", $solution_team[$solution->id][$team->id]['solved_at']) : "",
                              "solved"          => $solved,
                              "showed"          => $showed,
                              "scheduled"       => $scheduled,
                              "time_to_show"    => $time_to_show,
                        );
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
        elseif (isset($_GET['view_solution'])){
            $this->smarty_action = 'view_solution';
            $this->ref_id = $_GET['view_solution'];
            $this->action=array('action'=>"view_solution",
                                 'validate_form'=>true,
                                 'reload'=>false);
        }
        elseif (isset($_GET['view_hint'])){
            $this->smarty_action = 'view_hint';
            $this->ref_id = $_GET['view_hint'];
            $this->action=array('action'=>"view_hint",
                                 'validate_form'=>true,
                                 'reload'=>false);
        }
        elseif (isset($_GET['view_graph'])){
            $this->smarty_action = 'view_graph';
            $this->team_id = $_GET['view_graph'];
            $this->action=array('action'=>"view_graph",
                                 'validate_form'=>false,
                                 'reload'=>false);
        }
        elseif (isset($_GET['get_clue'])){
            $this->ref_id = $_GET['get_clue'];
            $this->download = !empty($_GET['download']);
            $this->action=array('action'=>"get_clue",
                                 'validate_form'=>true,
                                 'reload'=>false,
                                 'alone'=>true);
        }
        elseif (isset($_GET['get_hint'])){
            $this->ref_id = $_GET['get_hint'];
            $this->download = !empty($_GET['download']);
            $this->action=array('action'=>"get_hint",
                                 'validate_form'=>true,
                                 'reload'=>false,
                                 'alone'=>true);
        }
        elseif (isset($_GET['get_solution'])){
            $this->ref_id = $_GET['get_solution'];
            $this->download = !empty($_GET['download']);
            $this->action=array('action'=>"get_solution",
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

        /* Check that solution exists before showing it */
        if ($this->action['action'] == "view_solution"){
            $opt = array("ref_id" => $this->ref_id);

            $this->solution = Iquest_Solution::fetch($opt);
            if (!$this->solution){
                ErrorHandler::add_error("Unknown solution!");
                sw_log("Unknown solution: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }
            $this->solution = reset($this->solution);

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


        /* check hint exists */
        if ($this->action['action'] == "get_hint" or
            $this->action['action'] == "view_hint"){

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

        /* check solution exists */
        if ($this->action['action'] == "get_solution"){

            $opt = array("ref_id" => $this->ref_id);
            $solutions = Iquest_Solution::fetch($opt);

            if (!$solutions){
                ErrorHandler::add_error("Unknown solution!");
                sw_log("Unknown solution: '".$this->ref_id."'", PEAR_LOG_INFO);
                return false;
            }

            $this->solution = reset($solutions);
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
        $smarty->assign($this->opt['smarty_solution_team'], $this->smarty_solution_team);
        $smarty->assign($this->opt['smarty_solutions'], $this->smarty_solutions);

        $smarty->assign($this->opt['smarty_action'], $this->smarty_action);
        $smarty->assign($this->opt['smarty_clues'], $this->smarty_clues);
        $smarty->assign($this->opt['smarty_hint'], $this->smarty_hint);

        if ($this->action['action'] == "view_graph"){
            $smarty->assign($this->opt['smarty_get_graph_url'],
                            $this->controler->url($_SERVER['PHP_SELF'].
                                                  "?get_graph=".RawURLEncode($this->team_id)));
        }
    }

}


?>
