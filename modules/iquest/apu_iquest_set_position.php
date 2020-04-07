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
        $this->opt['smarty_ajax_get_position_url'] =       'ajax_get_position_url';
    }

    function action_ajax_get_position(){
        if (PHPlib::$session) PHPlib::$session->close_session();

        $this->controler->disable_html_output();
        header("Content-Type: text/json");

        $ok = true;
        $resp = [];

        if (empty($_GET['devId'])){
            ErrorHandler::add_error("'devId' is not set"); $ok = false;
        }

        if ($ok){
            $tracker = new Iquest_Tracker(null);
            $resp = $tracker->get_location_of_device($_GET['devId']);
        }

        // Add errors to response
        $eh = ErrorHandler::singleton();
        $errors = $eh->get_errors_array();
        $resp['errors'] = $errors;

        echo json_encode($resp);

        return true;
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
    }


    function determine_action(){
        if (isset($_GET['ajax_set_position'])){
            $this->action=array('action'=>"ajax_set_position",
                                'validate_form'=>false,
                                'reload'=>false,
                                'alone'=>true);
        }
        elseif (isset($_GET['ajax_get_position'])){
            $this->action=array('action'=>"ajax_get_position",
                                'validate_form'=>false,
                                'reload'=>false,
                                'alone'=>true);
        }
        else $this->action=array('action'=>"default",
                                 'validate_form'=>false,
                                 'reload'=>false);
    }

    function create_html_form(){
        parent::create_html_form();

        $teams = Iquest_Team::fetch(array('order_by' => 'name'));
        $team_options = array();
        $team_options[] = array("value" => "",
                                "label" => "-- žádný --");

        foreach($teams as $v){
            $team_options[] = array("value" => $v->id,
                                    "label" => $v->name,
                                    "extrahtml" => "data-tracker-id='".htmlspecialchars($v->tracker_id)."'");
        }

        $this->f->add_element(array("type"=>"select",
                                    "name"=>"team_id",
                                    "options"=>$team_options,
                                    "value"=>"",
                                    "size"=>1,
                              ));
    }

    /**
     *  assign variables to smarty
     */
    function pass_values_to_html(){
        global $smarty;

        $smarty->assign($this->opt['smarty_ajax_set_position_url'], $this->controler->url($_SERVER['PHP_SELF']."?ajax_set_position=1"));
        $smarty->assign($this->opt['smarty_ajax_get_position_url'], $this->controler->url($_SERVER['PHP_SELF']."?ajax_get_position=1"));
    }
}
