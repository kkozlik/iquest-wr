<?php
/**
 * JSON api endpoint that returns JWT token for given OAuth code
 */

$_phplib_page_open = array();
$_data_layer_required_methods=array();
$_required_modules = array('iquest-auth');
$_required_apu = array();

require(__DIR__."/../prepend.php");

header("Content-Type: text/json");

function error_response($response){
    http_response_code(400);

    echo json_encode($response);
    exit(0);
}

$code = isset($_POST['code']) ? $_POST['code'] : "";

if (!$code){
    sw_log("Missing 'code' parameter", PEAR_LOG_DEBUG);
    error_response([
        'error' => 'invalid_request',
        'error_description' => 'Missing "code" parameter'
    ]);
}

$token = Iquest_auth_oauth_token::by_code($code);

if (!$token){
    sw_log("Token code value: '$code' not found", PEAR_LOG_DEBUG);
    error_response([
        'error' => 'invalid_request',
        'error_description' => 'Invalid "code" value'
    ]);
}

// The $code can be used only once
$token->delete();

if (!$token->is_valid()){
    sw_log("Token code value: '$code' expired", PEAR_LOG_DEBUG);
    error_response([
        'error' => 'invalid_request',
        'error_description' => 'Invalid "code" value'
    ]);
}

echo json_encode(['jwt' => $token->get_jwt()]);
