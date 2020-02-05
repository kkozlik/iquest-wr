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

class apu_iquest_event extends apu_base_class{

    protected $smarty_events;
    protected $sorter=null;
    protected $filter=null;
    protected $pager;
    protected $last_id;


    /**
     *  constructor
     *
     *  initialize internal variables
     */
    function __construct(){
        global $lang_str;
        parent::apu_base_class();

        /* set default values to $this->opt */
        $this->opt['screen_name'] = "IQUEST Events";
        $this->opt['event_row_template_name'] = "";


        /*** names of variables assigned to smarty ***/
        /* smarty action */
        $this->opt['smarty_pager'] =        'pager';
        $this->opt['smarty_events'] =       'events';
        $this->opt['smarty_last_id'] =      'last_event_id';
        $this->opt['smarty_row_template'] = 'row_template';
        $this->opt['smarty_my_url'] =       'my_url';
    }

    function set_filter(&$filter){
        $this->filter = &$filter;
    }

    function set_sorter(&$sorter){
        $this->sorter = &$sorter;
    }

    /**
     *  this metod is called always at begining - initialize variables
     */
    function init(){
        parent::init();

        if (!isset($_SESSION['apu_iquest_event'][$this->opt['instance_id']])){
            $_SESSION['apu_iquest_event'][$this->opt['instance_id']] = array();
        }

        $this->session = &$_SESSION['apu_iquest_event'][$this->opt['instance_id']];

        if (is_a($this->sorter, "apu_base_class")){
            /* register callback called on sorter change */
            $this->sorter->set_opt('on_change_callback', array(&$this, 'sorter_changed'));
            $this->sorter->set_base_apu($this);
        }

        if (is_a($this->filter, "apu_base_class")){
            $this->filter->set_base_apu($this);
        }
        else{
            if (!isset($this->session['act_row'])){
                $this->session['act_row'] = 0;
            }

            if (isset($_GET['act_row'])) $this->session['act_row'] = $_GET['act_row'];
        }

    }

    function get_sorter_columns(){
        return array('name', 'values');
    }

    function get_filter_form(){
        global $lang_str;

        $teams = Iquest_Team::fetch(array('order_by' => 'name'));
        $team_options = array();
        foreach($teams as $v){
            $team_options[] = array("value" => $v->id, "label" => $v->name);
        }

        $types = Iquest_Events::$supported_types;
        $type_options = array();
        foreach($types as $v){
            $type_options[] = array("value" => $v, "label" => $v);
        }

        $succes_options = array();
        $succes_options[] = array("value" => "", "label" => $lang_str['iquest_event_all']);
        $succes_options[] = array("value" => "1", "label" => "OK");
        $succes_options[] = array("value" => "0", "label" => "Error");

        $f = array();

        $f[] = array("type"=>"select",
                     "name"=>"team_id",
                     "options"=>$team_options,
                     "multiple"=>1,
                     "size"=>1,
                     "label"=>$lang_str['iquest_event_team']);

        $f[] = array("type"=>"select",
                     "name"=>"type",
                     "multiple"=>1,
                     "size"=>1,
                     "label"=>$lang_str['iquest_event_type'],
                     "options"=>$type_options);

        $f[] = array("type"=>"select",
                     "name"=>"success",
                     "size"=>1,
                     "label"=>$lang_str['iquest_event_success'],
                     "options"=>$succes_options);

        $f[] = array("type"=>"text",
                     "name"=>"date_from",
                     "maxlength"=>32,
                     "label"=>$lang_str['iquest_event_date_from']);

        $f[] = array("type"=>"text",
                     "name"=>"date_to",
                     "maxlength"=>32,
                     "label"=>$lang_str['iquest_event_date_to']);

        $f[] = array("type"=>"checkbox",
                     "name"=>"raw_data",
                     "value"=>1,
                     "label"=>$lang_str['iquest_event_raw_data']);

        return $f;
    }


    /**
     *  Parse the date/time from 'date_from' and 'date_to' filters
     *  and convert it to timestamp
     */
    private function alter_filter_timestamp(&$filter){
        global $lang_str;

        if (isset($filter['date_from'])){
            $filter['date_from']->op = ">=";
            $timestamp = strtotime($filter['date_from']->value);

            if (false === $timestamp){
                ErrorHandler::add_error(
                    str_replace("<value>",
                                $filter['date_from']->value,
                                $lang_str['iquest_event_err_inv_datetime']));
                unset($filter['date_from']);
            }
            else{
                $filter['date_from']->value = $timestamp;
            }
        }

        if (isset($filter['date_to'])){
            $filter['date_to']->op = "<=";
            $timestamp = strtotime($filter['date_to']->value);

            if (false === $timestamp){
                ErrorHandler::add_error(
                    str_replace("<value>",
                                $filter['date_to']->value,
                                $lang_str['iquest_event_err_inv_datetime']));
                unset($filter['date_to']);
            }
            else{
                $filter['date_to']->value = $timestamp;
            }
        }

        if (isset($filter['date_from']) and
            isset($filter['date_to']) and
            $filter['date_from']->value > $filter['date_to']->value){

            ErrorHandler::add_error($lang_str['iquest_event_err_datetime_intersect']);
        }

    }

    function events_to_smarty($events){
        $opt = array(
                    "cgrp_url" => "main.php?view_grp=<id>&back_url=".RawURLEncode($_SERVER['PHP_SELF']),
                    "hint_url" => "main.php?view_hint=<id>&back_url=".RawURLEncode($_SERVER['PHP_SELF']),
                    "solution_url" => "main.php?view_solution=<id>&back_url=".RawURLEncode($_SERVER['PHP_SELF']),
                );
        $this->smarty_events = array();
        foreach ($events as $k => $v){
            $smarty_event = $v->to_smarty($opt);
            $this->smarty_events[] = $smarty_event;
        }
    }


    function action_ajax_latest_events(){
        global $data, $config;

        $this->controler->disable_html_output();
        header("Content-Type: text/html");

        $raw_data = false;
        if (is_a($this->filter, "apu_base_class")){
            $opt['filter'] = $this->filter->get_filter();
            $this->alter_filter_timestamp($opt['filter']);
            $filter_values = $this->filter->get_filter_values();
            if (!empty($filter_values['raw_data'])) $raw_data = true;
        }
        $opt['filter']['id'] = new Filter("id", $this->last_id, ">");

        $events = Iquest_Events::fetch($opt);
        $this->events_to_smarty($events);

        $last_id = $this->last_id;
        $last_event = end($events);
        if ($last_event) $last_id = $last_event->id;

        $html_rows = array();
        $sm = new Smarty_Serweb();
        $sm->assign("raw_data", $raw_data);

        foreach($this->smarty_events as $event){
            $sm->assign("event", $event);
            $html_rows[] = $sm->fetch($this->opt['event_row_template_name']);
        }


        $response = array(
                        "rows" => $html_rows,
                        "last_id" => $last_id,
                    );
        echo json_encode($response);
    }


    /**
     *  Method perform action default
     *
     *  @return array           return array of $_GET params fo redirect or FALSE on failure
     */

    function action_default(){
        global $data, $config;


        $opt = array('use_pager' => true);

        if (is_a($this->filter, "apu_base_class")){
            $opt['filter'] = $this->filter->get_filter();
            $this->alter_filter_timestamp($opt['filter']);
            $data->set_act_row($this->filter->get_act_row());
        }
        else{
            $data->set_act_row($this->session['act_row']);
        }
        if (is_a($this->sorter, "apu_base_class")){
            $opt['order_by']   = $this->sorter->get_sort_col();
            $opt['order_desc'] = $this->sorter->get_sort_dir();
        }
        else{
            $opt['order_desc'] = true;
        }


        $data->set_showed_rows(50);

        $events = Iquest_Events::fetch($opt);
        $this->pager=$data->get_pager();
        $this->events_to_smarty($events);

        if ($events){
            $last_event = reset($events);
            $this->last_id = $last_event->id;
        }

        action_log($this->opt['screen_name'], $this->action, "View events");
    }

    /**
     *  check _get and _post arrays and determine what we will do
     */
    function determine_action(){
        if (isset($_GET['last_id_ajax'])){
            $this->last_id = $_GET['last_id_ajax'];
            $this->action=array('action'=>"ajax_latest_events",
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
        $smarty->assign($this->opt['smarty_events'], $this->smarty_events);
        $smarty->assign($this->opt['smarty_pager'], $this->pager);
        $smarty->assign($this->opt['smarty_row_template'], $this->opt['event_row_template_name']);
        $smarty->assign($this->opt['smarty_last_id'], $this->last_id);
        $smarty->assign($this->opt['smarty_my_url'], $this->controler->url($_SERVER['PHP_SELF']));
    }
}


?>
