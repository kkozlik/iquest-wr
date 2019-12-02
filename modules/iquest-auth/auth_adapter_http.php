<?php

require_once (__DIR__."/auth_adapter_interface.php");
require_once (__DIR__."/auth_adapter_credentials.php");
require_once (__DIR__."/auth_result.php");


class Iquest_auth_adapter_http implements Iquest_auth_adapter_interface{

    protected $identity;
    protected $credential;
    protected $groups;
    protected $uid;
    protected $timeout;
    protected $authenticated = false;

    protected $providedIdentity;

    protected $supportedSchemes = ['basic'];

    public function getTimeout(){
        if (!$this->authenticated) return null;
        return $this->timeout;
    }

    public function getIdentity(){
        if (!$this->authenticated) return null;
        return $this->identity;
    }

    public function getGroups(){
        if (!$this->authenticated) return null;
        return $this->groups;
    }

    public function getUid(){
        if (!$this->authenticated) return null;
        return $this->uid;
    }

    public function getProvidedIdentity(){
        return $this->providedIdentity;
    }

    public function authenticate(){

        $getHeader = 'HTTP_AUTHORIZATION';
        if (empty($_SERVER[$getHeader])) {
            sw_log(__CLASS__." Authorization header is not set", PEAR_LOG_DEBUG);
            return $this->challengeClient(Iquest_auth_result::FAILURE_CREDENTIAL_NOT_PROVIDED);
        }

        $authHeader = $_SERVER[$getHeader];

        list($clientScheme) = explode(' ', $authHeader);
        $clientScheme = strtolower($clientScheme);

        if (!in_array($clientScheme, $this->supportedSchemes)){
            sw_log(__CLASS__." Client requested an incorrect or unsupported authentication scheme: $clientScheme", PEAR_LOG_INFO);

            $this->setHttpResponse(400, 'Bad request');

            return new Iquest_auth_result(Iquest_auth_result::FAILURE_UNCATEGORIZED,
                                      'Client requested an incorrect or unsupported authentication scheme');
        }


        if (empty($authHeader)) {
            sw_log(__CLASS__." Value of Authorization header is not set", PEAR_LOG_DEBUG);

            return new Iquest_auth_result(Iquest_auth_result::FAILURE_UNCATEGORIZED,
                                      'The value of the client Authorization header is required');
        }

        // Decode the Authorization header
        $auth = substr($authHeader, strlen('Basic '));
        $auth = base64_decode($auth);
        if (!$auth) {
            sw_log(__CLASS__." Invalid value of Authorization header: '$authHeader'", PEAR_LOG_DEBUG);

            return new Iquest_auth_result(Iquest_auth_result::FAILURE_UNCATEGORIZED,
                                      'Unable to base64_decode Authorization header value');
        }

        $creds = array_filter(explode(':', $auth));
        if (count($creds) != 2) {
            sw_log(__CLASS__." Authorization header does not contain two fields: ".json_encode($auth), PEAR_LOG_DEBUG);
            return $this->challengeClient(Iquest_auth_result::FAILURE_UNCATEGORIZED);
        }

        $adapter = new Iquest_auth_adapter_credentials();
        $adapter->setIdentity($creds[0]);
        $adapter->setCredential($creds[1]);

        $this->providedIdentity = $creds[0];

        $result = $adapter->authenticate();

        if ($result->isValid()) {
            sw_log(__CLASS__." Credential check succeeded", PEAR_LOG_DEBUG);

            $this->authenticated = true;

            $this->identity =   $adapter->getIdentity();
            $this->groups =     $adapter->getGroups();
            $this->uid =        $adapter->getUid();
            $this->timeout =    $adapter->getTimeout();

            return $result;
        }

        sw_log(__CLASS__." Credential check failed", PEAR_LOG_DEBUG);
        return $this->challengeClient(Iquest_auth_result::FAILURE_CREDENTIAL_INVALID);
    }

    protected function challengeClient($result_code=Iquest_auth_result::FAILURE_CREDENTIAL_INVALID){

        $this->setHttpResponse(401, 'Unauthorized');
        header('WWW-Authenticate: Basic');

        return new Iquest_auth_result($result_code, 'Invalid or absent credentials; challenging client');
    }

    protected function setHttpResponse($code, $message){
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol." $code $message", true, $code);
    }
}