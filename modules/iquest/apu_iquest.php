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
    protected $download = false;
    protected $clue;
    protected $clue_grp;
    protected $hint;
    protected $solution;
    protected $tracker;
    protected $smarty_action = 'default';
    protected $smarty_groups;
    protected $smarty_cgrp;
    protected $smarty_solutions;
    protected $smarty_next_solution = null;
    protected $smarty_next_hint = null;
    protected $smarty_team = null;
    protected $smarty_team_place = null;
    protected $smarty_show_place = false;


    const SESS_URL_TOKEN = "url_token";
    const GET_URL_TOKEN = "token";

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
        return array("module:iquest:main.js");
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
        $this->opt['team_id'] =     null;

        $this->opt['msg_solve']['long']  =     &$lang_str['iquest_msg_key_correct'];

        /*** names of variables assigned to smarty ***/
        /* form */
        $this->opt['smarty_form'] =         'form';
        /* smarty action */
        $this->opt['smarty_action'] =       'action';
        /* name of html form */
        $this->opt['form_name'] =           '';
        $this->opt['smarty_groups'] =       'clue_groups';
        $this->opt['smarty_cgrp'] =         'clue_grp';
        $this->opt['smarty_solutions'] =    'solutions';
        $this->opt['smarty_team'] =         'team';

        $this->opt['smarty_next_solution'] =    'next_solution';
        $this->opt['smarty_next_hint'] =        'next_hint';

        $this->opt['smarty_team_place'] =       'team_place';
        $this->opt['smarty_show_place'] =       'show_place';

        $this->opt['smarty_graph_enabled'] =    'graph_enabled';
        $this->opt['smarty_tracker_enabled'] =  'tracker_enabled';

        $this->opt['smarty_main_url'] =         'main_url';
        $this->opt['smarty_get_graph_url'] =    'get_graph_url';
        $this->opt['smarty_view_graph_url'] =   'view_graph_url';
        $this->opt['smarty_all_in_1_url'] =     'all_in_1_url';
        $this->opt['smarty_get_location_url'] = 'get_location_url';
        $this->opt['smarty_check_location_url'] = 'check_location_url';

        $this->opt['form_submit']['text'] = $lang_str['b_ok'];
        $this->opt['form_submit']['class'] = "btn btn-primary";

    }

    /**
     *  this metod is called always at begining - initialize variables
     */
    function init(){
        parent::init();

        if (is_null($this->opt['team_id'])) throw new Exception("team_id is not set");
        $this->team_id = $this->opt['team_id'];

        if (!isset($_SESSION['apu_iquest'][$this->opt['instance_id']])){
            $_SESSION['apu_iquest'][$this->opt['instance_id']] = array();
        }

        $this->session = &$_SESSION['apu_iquest'][$this->opt['instance_id']];

        if (!isset($this->session['known_cgrps']))      $this->session['known_cgrps'] = array();
        if (!isset($this->session['known_hints']))      $this->session['known_hints'] = array();
        if (!isset($this->session['known_solutions']))  $this->session['known_solutions'] = array();
        if (!isset($this->session['hidden_ref_ids']))   $this->session['hidden_ref_ids'] = array();
        if (!isset($this->session['view_all']))         $this->session['view_all'] = false;

        $this->tracker = new Iquest_Tracker($this->team_id);
    }

    function get_timeouts(){

        $next_solution = Iquest_Solution::get_next_scheduled($this->team_id);
        $next_hint     = Iquest_Hint::get_next_scheduled($this->team_id);

        $countdown_limit_hint = Iquest_Options::get(Iquest_Options::COUNTDOWN_LIMIT_HINT);
        $countdown_limit_solution = Iquest_Options::get(Iquest_Options::COUNTDOWN_LIMIT_SOLUTION);

        if ($next_solution){
            $show_after = $next_solution['show_at'] - time();
            if ($show_after > 0 and
                ($show_after < $countdown_limit_solution or
                 $countdown_limit_solution == 0)){

                $this->smarty_next_solution = gmdate("H:i:s", $show_after);
                $this->controler->set_onload_js("enable_countdown('#solution_countdown', $show_after);");
            }
        }

        if ($next_hint){
            $show_after = $next_hint['show_at'] - time();
            if ($show_after > 0 and
                ($show_after < $countdown_limit_hint or
                 $countdown_limit_hint == 0)){

                $this->smarty_next_hint = gmdate("H:i:s", $show_after);
                $this->controler->set_onload_js("enable_countdown('#hint_countdown', $show_after);");
            }
        }
    }

    function get_team_info(){
        $team = Iquest_Team::fetch_by_id($this->team_id);
        $this->smarty_team = $team->to_smarty();

        if (Iquest_Options::get(Iquest_Options::SHOW_PLACE)){

            $hide_timeout = Iquest_Options::get(Iquest_Options::HIDE_PLACE_TIMEOUT);
            $hide_time = 0;

            if ($hide_timeout){
                $end_time = Iquest_Options::get(Iquest_Options::END_TIME);
                $hide_time = $end_time - $hide_timeout;
            }

            if ($hide_time and time() > $hide_time){
                $this->smarty_team_place = "??";
            }
            else{
                $ranks = Iquest_team_rank::fetch(array("last"=>1));
                $actual_order = reset($ranks)->rank;
                $this->smarty_team_place = $actual_order[$this->team_id];
            }

            $this->smarty_show_place = true;
        }
    }


    /**
     *  Get token for URL that is used to detect doubled requests
     */
    function get_url_token(){

        if (empty($this->session[self::SESS_URL_TOKEN])){
            // The token in not set yet. Generate new one
            $this->session[self::SESS_URL_TOKEN] = rfc4122_uuid();
        }

        return self::GET_URL_TOKEN."=".RawURLEncode($this->session[self::SESS_URL_TOKEN]);
    }

    function cgrp_to_smarty(&$clue_grp){
        global $lang_str;

        $hint_for_sale = $clue_grp->get_next_hint_for_sale($this->team_id);

        $smarty_cgrp = $clue_grp->to_smarty();
        $smarty_cgrp['hints_for_sale'] = !empty($hint_for_sale);
        $smarty_cgrp['new'] = !isset($this->session['known_cgrps'][$clue_grp->id]);

        if ($smarty_cgrp['hints_for_sale']){
            $smarty_cgrp['buy_url'] = $this->controler->url($_SERVER['PHP_SELF'].
                                                            "?buy_hint=".RawURLEncode($clue_grp->ref_id).
                                                            "&".$this->get_url_token().
                                                            ($this->action['action'] == "view_grp" ? "&view_grp_detail=1" : ""));
            $smarty_cgrp['buy_confirmation'] = str_replace("<price>",
                                                           $hint_for_sale->price,
                                                           $lang_str['iquest_conf_buy_hint']);
            $smarty_cgrp['hint_price'] = str_replace("<price>",
                                                     $hint_for_sale->price,
                                                     $lang_str['iquest_btn_buy_hint_price']);
        }


        $clues = $clue_grp->get_clues();
        $smarty_cgrp['clues'] = array();

        foreach($clues as $k => $v){
            // skip hidden clues
            if ($v->type == Iquest_Clue::TYPE_HIDDEN) continue;

            $hints = $clues[$k]->get_accessible_hints($this->team_id);
            $smarty_clue = $clues[$k]->to_smarty();
            $smarty_clue['hidden'] = isset($this->session['hidden_ref_ids'][$v->ref_id]);
            $smarty_clue['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_clue=".RawURLEncode($clues[$k]->ref_id), false);
            $smarty_clue['download_file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_clue=".RawURLEncode($clues[$k]->ref_id)."&download=1", false);
            $smarty_clue['hide_url'] = $this->controler->url($_SERVER['PHP_SELF']."?hide=".RawURLEncode($clues[$k]->ref_id));
            $smarty_clue['unhide_url'] = $this->controler->url($_SERVER['PHP_SELF']."?unhide=".RawURLEncode($clues[$k]->ref_id));

            foreach($smarty_clue['hints'] as $hk => $hv){
                $smarty_clue['hints'][$hk]['file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_hint=".RawURLEncode($hv['ref_id']), false);
                $smarty_clue['hints'][$hk]['download_file_url'] = $this->controler->url($_SERVER['PHP_SELF']."?get_hint=".RawURLEncode($hv['ref_id'])."&download=1", false);
                $smarty_clue['hints'][$hk]['hide_url'] = $this->controler->url($_SERVER['PHP_SELF']."?hide=".RawURLEncode($hv['ref_id']));
                $smarty_clue['hints'][$hk]['unhide_url'] = $this->controler->url($_SERVER['PHP_SELF']."?unhide=".RawURLEncode($hv['ref_id']));
                $smarty_clue['hints'][$hk]['new'] = !isset($this->session['known_hints'][$hv['id']]);
                $smarty_clue['hints'][$hk]['hidden'] = isset($this->session['hidden_ref_ids'][$hv['ref_id']]);
                $this->session['known_hints'][$hv['id']] = true;
            }

            $smarty_cgrp['clues'][$k] = $smarty_clue;
        }

        $this->session['known_cgrps'][$clue_grp->id] = true;

        return $smarty_cgrp;
    }


    function action_ajax_hide(){
        $this->controler->disable_html_output();
        header("Content-Type: text/plain");

        $this->session['hidden_ref_ids'][$this->ref_id] = true;

        return true;
    }

    function action_ajax_unhide(){
        $this->controler->disable_html_output();
        header("Content-Type: text/plain");

        unset($this->session['hidden_ref_ids'][$this->ref_id]);

        return true;
    }

    public function action_ajax_get_location(){
        $this->controler->disable_html_output();
        header("Content-Type: text/json");

        if (!$this->tracker->is_tracking_enabled()){
            $resp = [];
            ErrorHandler::add_error("Tracking is not enabled");
        }
        else{
            $resp = $this->tracker->get_location();
        }

        // Add errors to response
        $eh = ErrorHandler::singleton();
        $errors = $eh->get_errors_array();
        $resp['errors'] = $errors;

        echo json_encode($resp);

        return true;
    }

    public function action_check_location(){
        global $lang_str;

        // Solve the solution and open another tasks
        if ($this->solution){
            $this->solution->solve($this->team_id);

            action_log($this->opt['screen_name'], $this->action, " Solved: ".$this->solution->id);

            Iquest_info_msg::add_msg($lang_str['iquest_msg_location_correct']);
        }

        $get = array('apu_iquest='.RawURLEncode($this->opt['instance_id']));
        return $get;
    }


    /**
     *  Method perform action solve
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_solve(){

        // Solve the solution and open another tasks
        $this->solution->solve($this->team_id);

        action_log($this->opt['screen_name'], $this->action, " Solved: ".$this->solution->id);

        Iquest_info_msg::add_msg($this->opt['msg_solve']['long']);

        $get = array('apu_iquest='.RawURLEncode($this->opt['instance_id']));
        return $get;
    }

    /**
     *  Method perform action buy_hint
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_buy_hint(){

        Iquest_Events::add(Iquest_Events::COIN_SPEND,
                           true,
                           array("price" => $this->hint->price,
                                 "hint" => $this->hint));

        Iquest::buy_hint($this->hint, $this->team_id);
        // Coin is spent, reset the URL token so new one will be generated
        $this->session[self::SESS_URL_TOKEN] = "";

        action_log($this->opt['screen_name'], $this->action, " Hint bought: ".$this->hint->id);

        $get = array('apu_iquest='.RawURLEncode($this->opt['instance_id']));

        if (!empty($_GET['view_grp_detail'])){
            $get[] = "view_grp=".RawURLEncode($this->ref_id);
        }

        return $get;
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

        $cgrp_url = $_SERVER['PHP_SELF'].
                        "?view_grp=<ID>".
                        "&backto=".rawurlencode($_SERVER['PHP_SELF']."?view_graph=1");

        $graph = new Iquest_contest_graph_simplified($this->team_id);
        $graph->set_cgrp_url($this->controler->url($cgrp_url));
        $graph->link_unknown_cgrps(false);
        $graph->hide_names(!Iquest_Options::get(Iquest_Options::SHOW_GRAPH_CGRP_NAMES));
        $graph->image_graph();

        return true;
    }


    /**
     *  Method perform action view_grp
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */
    function action_view_grp(){
        global $lang_str;

        $hint_for_sale = $this->clue_grp->get_next_hint_for_sale($this->team_id);

        $this->smarty_cgrp = $this->cgrp_to_smarty($this->clue_grp);

        $this->get_timeouts();
        $this->get_team_info();

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

        $this->get_timeouts();
        $this->get_team_info();
        $this->session['known_solutions'][$this->solution->id] = true;

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View solution");
        return true;
    }

    function action_view_all(){
        global $lang_str;

        $opt = array("team_id" => $this->team_id,
                     "available_only" => true);
        $clue_groups = Iquest_ClueGrp::fetch($opt);


        $this->get_timeouts();
        $this->get_team_info();
//        $this->session['known_cgrps'][$this->clue_grp->id] = true;

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View all clue groups screen");
        return true;
    }

    function action_view_graph(){

        $this->get_timeouts();
        $this->get_team_info();

        action_log($this->opt['screen_name'], $this->action, "IQUEST: View graph");
        return true;
    }

    /**
     *  Method perform action default
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_default(){
        global $lang_str;

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

        if (isset($_GET['view_all'])){
            $this->session['view_all'] = (bool)$_GET['view_all'];
        }


        $this->smarty_groups = array();

        if ($this->session['view_all']){
            $this->smarty_action = 'view_all';

            foreach($clue_groups as &$cgrp){
                $this->smarty_groups[$cgrp->id] = $this->cgrp_to_smarty($cgrp);
            }
        }
        else{
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

                $hint_for_sale = $clue_groups[$k]->get_next_hint_for_sale($this->team_id);

                $smarty_group = $v->to_smarty();
                $smarty_group['detail_url'] = $this->controler->url($_SERVER['PHP_SELF']."?view_grp=".RawURLEncode($v->ref_id));
                $smarty_group['new'] = !isset($this->session['known_cgrps'][$v->id]);
                $smarty_group['new_hints'] = $new_hints;
                $smarty_group['hints_for_sale'] = !empty($hint_for_sale);
                if ($smarty_group['hints_for_sale']){
                    $smarty_group['buy_url'] = $this->controler->url($_SERVER['PHP_SELF'].
                                                                        "?buy_hint=".RawURLEncode($v->ref_id).
                                                                        "&".$this->get_url_token()
                                                                    );
                    $smarty_group['buy_confirmation'] = str_replace("<price>",
                                                                    $hint_for_sale->price,
                                                                    $lang_str['iquest_conf_buy_hint']);
                    $smarty_group['hint_price'] = str_replace("<price>",
                                                              $hint_for_sale->price,
                                                              $lang_str['iquest_btn_buy_hint_price']);
                }
                $this->smarty_groups[$k] = $smarty_group;
            }
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
        $this->get_team_info();

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
        elseif (isset($_GET['check_location'])){
            $this->action=array('action'=>"check_location",
                                 'validate_form'=>true,
                                 'reload'=>true);
        }
        elseif (isset($_GET['hide'])){
            $this->ref_id = $_GET['hide'];
            $this->action=array('action'=>"ajax_hide",
                                 'validate_form'=>false,
                                 'reload'=>false,
                                 'alone'=>true);
        }
        elseif (isset($_GET['unhide'])){
            $this->ref_id = $_GET['unhide'];
            $this->action=array('action'=>"ajax_unhide",
                                 'validate_form'=>false,
                                 'reload'=>false,
                                 'alone'=>true);
        }
        elseif (isset($_GET['get_location'])){
            $this->action=array('action'=>"ajax_get_location",
                                 'validate_form'=>false,
                                 'reload'=>false,
                                 'alone'=>true);
        }
        elseif (isset($_GET['buy_hint'])){
            $this->ref_id = $_GET['buy_hint'];
            $this->action=array('action'=>"buy_hint",
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
        elseif (isset($_GET['view_graph']) and Iquest_Options::get(Iquest_Options::SHOW_GRAPH)){
            $this->smarty_action = 'view_graph';
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
        elseif (isset($_GET['get_graph']) and Iquest_Options::get(Iquest_Options::SHOW_GRAPH)){
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
            if (false === $this->action_default()) return false;
        }
        elseif ($this->action['action'] == "check_location"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: Location check failed", false, array("errors"=>$this->controler->errors));
            if (false === $this->action_default()) return false;
        }
        elseif ($this->action['action'] == "buy_hint"){
            action_log($this->opt['screen_name'], $this->action, "IQUEST MAIN: Buy hint failed", false, array("errors"=>$this->controler->errors));

            if (!empty($_GET['view_grp_detail']) and
                $this->clue_grp){

                $this->smarty_action = 'view_grp';
                if (false === $this->action_view_grp()) return false;
            }
            else{
                if (false === $this->action_default()) return false;
            }
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

            if ($this->clue->type == Iquest_Clue::TYPE_HIDDEN){
                ErrorHandler::add_error("Unknown clue!");
                sw_log("Hidden clue: '".$this->ref_id."'", PEAR_LOG_INFO);
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

        if ($this->action['action'] == "buy_hint"){

            // Check that the URL token match to one stored in session
            if ($this->session[self::SESS_URL_TOKEN] != $_GET[self::GET_URL_TOKEN]){
                ErrorHandler::add_error("Double request to buy hint!");
                sw_log("Doubled request to buy hint. Actual valid token: '".$this->session[self::SESS_URL_TOKEN]."'".
                       ". URL token: '".$_GET[self::GET_URL_TOKEN]."'", PEAR_LOG_INFO);
                return false;
            }
            else{
                sw_log("Buy hint URL token: '".$_GET[self::GET_URL_TOKEN]."'", PEAR_LOG_INFO);
            }

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

            $this->hint = $this->clue_grp->get_next_hint_for_sale($this->team_id);
            if (!$this->hint){
                ErrorHandler::add_error($lang_str['iquest_err_no_hint_for_sale']);
                return false;
            }

            $team = Iquest_Team::fetch_by_id($this->team_id);

            if ($team->wallet < $this->hint->price){
                ErrorHandler::add_error(str_replace("<price>",
                                                    $this->hint->price,
                                                    $lang_str['iquest_err_hint_no_money']));
                return false;
            }

            return true;
        }

        if ($this->action['action'] == "check_location"){
            if (Iquest::is_over()){
                ErrorHandler::add_error($lang_str['iquest_err_contest_over']);
                return false;
            }

            $result = $this->tracker->check_location($this->controler);

            if (!$result['status']) return false;
            if ($result['solution']) $this->solution = $result['solution'];

            return true;
        }

        // Action: solve
        $form_ok = true;
        if (false === parent::validate_form()) $form_ok = false;

        if (Iquest::is_over()){
            ErrorHandler::add_error($lang_str['iquest_err_contest_over']);
            return false;
        }

        $key = null;
        if (isset($_POST['solution_key'])) $key = $_POST['solution_key'];

        $this->solution = Iquest_Solution::verify_key($key, $this->team_id);
        if (!$this->solution){
            return false;
        }

        return $form_ok;
    }


    /**
     *  assign variables to smarty
     */
    function pass_values_to_html(){
        global $smarty;

        $smarty->assign($this->opt['smarty_action'], $this->smarty_action);
        $smarty->assign($this->opt['smarty_groups'], $this->smarty_groups);
        $smarty->assign($this->opt['smarty_cgrp'], $this->smarty_cgrp);
        $smarty->assign($this->opt['smarty_solutions'], $this->smarty_solutions);

        $smarty->assign($this->opt['smarty_team'], $this->smarty_team);

        $smarty->assign($this->opt['smarty_next_solution'], $this->smarty_next_solution);
        $smarty->assign($this->opt['smarty_next_hint'], $this->smarty_next_hint);

        $smarty->assign($this->opt['smarty_team_place'], $this->smarty_team_place);
        $smarty->assign($this->opt['smarty_show_place'], $this->smarty_show_place);

        $smarty->assign($this->opt['smarty_graph_enabled'], Iquest_Options::get(Iquest_Options::SHOW_GRAPH));
        $smarty->assign($this->opt['smarty_tracker_enabled'], true); // @TODO: set correct value

        $smarty->assign($this->opt['smarty_main_url'], $this->controler->url($_SERVER['PHP_SELF']));
        $smarty->assign($this->opt['smarty_get_graph_url'], $this->controler->url($_SERVER['PHP_SELF']."?get_graph=1"));
        $smarty->assign($this->opt['smarty_view_graph_url'], $this->controler->url($_SERVER['PHP_SELF']."?view_graph=1"));
        $smarty->assign($this->opt['smarty_all_in_1_url'],
                        $this->controler->url($_SERVER['PHP_SELF'].
                            "?view_all=".($this->session['view_all']?"0":"1")));

        $smarty->assign($this->opt['smarty_get_location_url'], $this->controler->url($_SERVER['PHP_SELF']."?get_location=1"));
        $smarty->assign($this->opt['smarty_check_location_url'], $this->controler->url($_SERVER['PHP_SELF']."?check_location=1"));
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
