<?php

require_once (__DIR__."/auth_adapter_interface.php");
require_once (__DIR__."/auth_result.php");

class Iquest_auth_adapter_options implements Iquest_auth_adapter_credential_interface{

    protected $identity;
    protected $credential;
    protected $groups;
    protected $uid;
    protected $authenticated = false;

    public function setCredential($credential){
        $this->credential = $credential;
        return $this;
    }

    public function setIdentity($identity){
        $this->identity = $identity;
        return $this;
    }

    public function getTimeout(){
        return 60*60*24; // auth expiration one day
    }

    public function getIdentity(){
        if (!$this->authenticated) return null;
        return $this->identity;
    }

    /**
     * Return array of group names
     * @return array
     */
    public function getGroups(){
        if (!$this->authenticated) return null;
        return $this->groups;
    }

    public function getUid(){
        if (!$this->authenticated) return null;
        return $this->uid;
    }

    /**
     * Authenticate the credentials set with setIdentity() and setCredential() methods
     * against DB table.
     *
     * @return Iquest_auth_result
     */
    public function authenticate(){
        global $lang_str;

        $credentials = Iquest_Options::get(Iquest_Options::HQ_LOGIN);

        if ($credentials){
            // Authenticate by credentials from metadata.ini file
            if (isset($credentials[$this->identity]) and
                $credentials[$this->identity] == md5($this->credential)){


                $this->groups = ['hq'];
                $this->uid = 'HQ:'.$this->identity;
                $this->authenticated = true;

                sw_log(__CLASS__." authenticate HQ user '{$this->identity}' succeeded.", PEAR_LOG_INFO);
                return new Iquest_auth_result(Iquest_auth_result::SUCCESS);
            }
        }

        sw_log(__CLASS__." authenticate user '{$this->identity}' failed - user not found.", PEAR_LOG_INFO);
        return new Iquest_auth_result(Iquest_auth_result::FAILURE_IDENTITY_NOT_FOUND, $lang_str['auth_err_bad_username']);
    }
}