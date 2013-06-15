<?php

/**
 *  Customized function to log user actions
 */ 
function iquest_action_log($screen_name, $action, $msg=null, $success = true, $opt = array()){

    $opt['request_data'] = array_merge($_GET, $_POST, $_REQUEST);

    $log_msg = "[action: ".$screen_name."/".$action['action']."] ";
    if (isset($_SESSION['auth'])){
        $log_msg .= "(team: ".$_SESSION['auth']->get_uid().
                         " '".$_SESSION['auth']->get_team_name()."') ";
    }

    if (is_null($msg)) $log_msg .= "action performed";
    else               $log_msg .= $msg;

    $log_msg .= ($success ? " [successfull] " : " [failed] ");
    $log_msg .= json_encode($opt);
//    $log_msg .= print_r($opt, true);

    sw_log($log_msg, PEAR_LOG_INFO);
}


function remove_diacritics($str){

    $str = Str_Replace(
                Array("á","č","ď","é","ě","í","ľ","ň","ó","ř","š","ť","ú","ů","ý","ž","Á","Č","Ď","É","Ě","Í","Ľ","Ň","Ó","Ř","Š","Ť","Ú","Ů","Ý","Ž"),
                Array("a","c","d","e","e","i","l","n","o","r","s","t","u","u","y","z","A","C","D","E","E","I","L","N","O","R","S","T","U","U","Y","Z"),
                $str);

    return $str;
}

?>
