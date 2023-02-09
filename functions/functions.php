<?php

/**
 *  Customized function to log user actions
 */
function iquest_action_log($screen_name, $action, $msg=null, $success = true, $opt = array()){

    $opt['request_data'] = array_merge($_GET, $_POST, $_REQUEST);

    $log_msg = "[action: ".$screen_name."/".$action['action']."] ";
    if (Iquest_auth::has_identity() and $team = Iquest_auth::get_logged_in_team()){

        $log_msg .= "(team: ".$team->id.
                         " '".$team->name."') ";
    }

    if (is_null($msg)) $log_msg .= "action performed";
    else               $log_msg .= $msg;

    $log_msg .= ($success ? " [successfull] " : " [failed] ");
    $log_msg .= json_encode($opt);
//    $log_msg .= print_r($opt, true);

    sw_log($log_msg, PEAR_LOG_INFO);
}

/**
 * Customized log function
 */
function iquest_log($priority, $message, $file, $line){
    global $serwebLog, $config;

    if (!is_string($message)) $message = json_encode($message);

    if ($config->iquest_log_include_file) $message= $file.":".$line.": ".$message;

    if (class_exists('Iquest_auth')){
        $username = Iquest_auth::get_logged_in_username();
        if ($username) $message = "[User: $username] ".$message;
    }

    if (php_sapi_name() == "cli"){
        $sys_user = posix_getpwuid(posix_geteuid());
        if ($sys_user) $message = "[SysUser: {$sys_user['name']}] ".$message;
    }

    if (class_exists('PHPlib') and PHPlib::$session){
        $message = "[".substr(PHPlib::$session->id(), 0, 8)."] $message";
    }

    return $serwebLog->log($message, $priority);
}

function remove_diacritics($str){
    global $config;

//  Following code is not reliable. Some devices may use characters not listed
//  there.
//
//     $str = Str_Replace(
//                 Array("á","č","ď","é","ě","í","ľ","ň","ó","ř","š","ť","ú","ů","ý","ž","Á","Č","Ď","É","Ě","Í","Ľ","Ň","Ó","Ř","Š","Ť","Ú","Ů","Ý","Ž"),
//                 Array("a","c","d","e","e","i","l","n","o","r","s","t","u","u","y","z","A","C","D","E","E","I","L","N","O","R","S","T","U","U","Y","Z"),
//                 $str);

    setlocale (LC_CTYPE, $config->iquest_locale);
    $str = iconv("UTF-8", "ASCII//TRANSLIT", $str);

    return $str;
}
