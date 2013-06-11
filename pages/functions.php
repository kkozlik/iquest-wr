<?php

/**
 *  Customized function to log user actions
 */ 
function iquest_action_log($screen_name, $action, $msg=null, $success = true, $opt = array()){

    $opt['request_data'] = array_merge($_GET, $_POST, $_REQUEST);

    $log_msg = "[action: ".$screen_name."/".$action['action']."] ";
    if (isset($_SESSION['auth'])){
        //@todo: display team name instead of uid
        $log_msg .= "(team: ".$_SESSION['auth']->get_uid().") ";
    }

    if (is_null($msg)) $log_msg .= "action performed";
    else               $log_msg .= $msg;

    $log_msg .= ($success ? " [successfull] " : " [failed] ");
    $log_msg .= json_encode($opt);
//    $log_msg .= print_r($opt, true);

    sw_log($log_msg, PEAR_LOG_INFO);
}


?>
