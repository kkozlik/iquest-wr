<?php
/**
 * Application unit set position
 *
 * @author    Karel Kozlik
 * @package   iquest
 */

/**
 *  Application unit set position
 *
 *  This application unit is used for set location of traccar device
 */

class apu_iquest_set_position extends apu_base_class{

    function get_required_javascript(){
        return array("module:iquest:main.js", "module:iquest:set_position.js");
    }

    /**
     *  constructor
     *
     *  initialize internal variables
     */
    function __construct(){
        parent::__construct();

        /* set default values to $this->opt */
        $this->opt['screen_name'] = "IQUEST Set Position";

        $this->opt['smarty_ajax_set_position_url'] =       'ajax_set_position_url';
    }

    function action_ajax_set_position(){
        global $data, $config;

        $this->controler->disable_html_output();
        header("Content-Type: text/json");

        $ok = true;

        if (empty($_GET['lat'])){
            ErrorHandler::add_error("'lat' is not set"); $ok = false;
        }
        if (empty($_GET['lon'])){
            ErrorHandler::add_error("'lon' is not set"); $ok = false;
        }
        if (empty($_GET['devId'])){
            ErrorHandler::add_error("'devId' is not set"); $ok = false;
        }

        if ($ok){
            $ok = Iquest_Tracker::set_position($_GET['devId'], $_GET['lat'], $_GET['lon']);
        }

        $resp = [];

        if ($ok){
            $lat=round($_GET['lat'], 5);
            $lon=round($_GET['lon'], 5);
            $resp['infomsg'] = ["Poloha nastavena na: N$lat, E$lon"];
        }
        else{
            // Add errors to response
            $eh = ErrorHandler::singleton();
            $errors = $eh->get_errors_array();
            $resp['errors'] = $errors;
        }


        echo json_encode($resp);

        return true;


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


    function determine_action(){
        if (isset($_GET['ajax_set_position'])){
            $this->action=array('action'=>"ajax_set_position",
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

        $smarty->assign($this->opt['smarty_ajax_set_position_url'], $this->controler->url($_SERVER['PHP_SELF']."?ajax_set_position=1"));
    }
}
