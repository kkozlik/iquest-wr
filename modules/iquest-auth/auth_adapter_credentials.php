<?php

require_once (__DIR__."/auth_adapter_interface.php");
require_once (__DIR__."/auth_adapter_dbtable.php");
require_once (__DIR__."/auth_adapter_options.php");

class Iquest_auth_adapter_credentials implements Iquest_auth_adapter_credential_interface{

    protected $stack = array();
    protected $uid;
    protected $identity;
    protected $timeout;
    protected $groups;

    public function __construct(){
        $this->stack["db"]       = new Iquest_auth_adapter_dbtable();
        $this->stack["options"]  = new Iquest_auth_adapter_options();
    }

    public function setCredential($password){
        foreach($this->stack as &$adapter){
            $adapter->setCredential($password);
        }

        return $this;
    }

    public function setIdentity($user){
        foreach($this->stack as &$adapter){
            $adapter->setIdentity($user);
        }

        $this->identity = $user;
        return $this;
    }

    public function getTimeout() { return $this->timeout; }
    public function getGroups()  { return $this->groups; }
    public function getIdentity(){ return $this->identity; }
    public function getUid()     { return $this->uid; }

    public function authenticate(){
        global $lang_str;

        sw_log(__CLASS__." authenticate entered.", PEAR_LOG_DEBUG);

        foreach($this->stack as &$adapter){
            sw_log(__CLASS__." trying to authenticate user with ".get_class($adapter), PEAR_LOG_INFO);
            $result = $adapter->authenticate();

            if ($result->isValid()){
                $this->timeout  = $adapter->getTimeout();
                $this->groups   = $adapter->getGroups();
                $this->uid      = $adapter->getUid();

                sw_log(__CLASS__." authentication succeeded with ".get_class($adapter), PEAR_LOG_INFO);
                return $result;
            }
        }

        sw_log(__CLASS__." none of the auth adapters suceeded with user authentication.", PEAR_LOG_INFO);
        return new Iquest_auth_result(Iquest_auth_result::FAILURE_CREDENTIAL_INVALID, $lang_str['auth_err_bad_username']);
    }
}