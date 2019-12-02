<?php

class Iquest_authN{

    const ATTR_uid =            'uid';
    const ATTR_user =           'user';
    const ATTR_groups =         'groups';
    const ATTR_ttl =            'ttl';
    const ATTR_sess_expire =    'sess_expire';

    protected $storage;

    protected static $dlConnections = array();

    /**
     * Return a reference to a Iquest_authN instance, only creating a new instance
     * if no Iquest_authN instance currently exists.
     */
    public static function singleton(){
        static $instance = null;

        if (is_null($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    protected function __construct(){
        if (!isset($_SESSION['Iquest_authN'])) $_SESSION['Iquest_authN'] = array();
        $this->storage = &$_SESSION['Iquest_authN'];
    }

    /**
     * Authenticate user with given auth adapter
     */
    public function authenticate(Iquest_auth_adapter_interface $adapter){

        $result = $adapter->authenticate();

        //  Ensure storage has clean state
        if ($this->hasIdentity()) {
            $this->clearIdentity();
        }

        if ($result->isValid()) {
            $this->writeIdentity($adapter);
        }
        else{
            sw_log(__CLASS__."::".__FUNCTION__."() Login failed with result code: ".$result->getCodeAsStr()." remote IP: ".$_SERVER["REMOTE_ADDR"], PEAR_LOG_WARNING);
        }

        return $result;
    }

    public function sessionExpired(){
        if (!$this->storage[self::ATTR_sess_expire]) return false;

        if (time() > $this->storage[self::ATTR_sess_expire]){
            sw_log(__CLASS__."::".__FUNCTION__."(): Session expired for user: ".$this->storage[self::ATTR_user]." at ".date("Y-m-d H:i:s", $this->storage[self::ATTR_sess_expire]), PEAR_LOG_INFO);
            return true;
        }

        return false;
    }

    public function updateSessionExpiration(){
        $timeout = $this->storage[self::ATTR_ttl];
        if ($timeout)   $expire =   time() + $timeout;
        else            $expire = 0;

        sw_log(__CLASS__."::".__FUNCTION__."(): Session expire timeout is $timeout, setting expiration time to: ".date("H:i:s", $expire), PEAR_LOG_DEBUG);

        $this->storage[self::ATTR_sess_expire] = $expire;
    }

    public function getSessionExpireTime(){
        return isset($this->storage[self::ATTR_sess_expire]) ?
                $this->storage[self::ATTR_sess_expire] :
                null;
    }

    public function hasIdentity(){
        return !empty($this->storage[self::ATTR_user]);
    }

    public function getIdentity(){
        if (!$this->hasIdentity()) return false;
        return $this->storage[self::ATTR_user];
    }

    public function getUid(){
        if (!$this->hasIdentity()) return false;
        return $this->storage[self::ATTR_uid];
    }

    /**
     * Return array of group names
     *
     * @return array
     */
    public function getGroups(){
        if (!$this->hasIdentity()) return null;
        return $this->storage[self::ATTR_groups];
    }

    public function clearIdentity(){
        $authz = Iquest_authZ::singleton();
        $authz->clearCache();

        $this->storage[self::ATTR_uid] = null;
        $this->storage[self::ATTR_user] = null;
        $this->storage[self::ATTR_groups] = null;
        $this->storage[self::ATTR_ttl] = null;
        $this->storage[self::ATTR_sess_expire] = null;
    }

    private function writeIdentity(Iquest_auth_adapter_interface $adapter){
        $uid =      $adapter->getUid();
        $user =     $adapter->getIdentity();
        $groups =   $adapter->getGroups();
        $timeout =  $adapter->getTimeout();

        $this->storage[self::ATTR_uid] = $uid;
        $this->storage[self::ATTR_user] = $user;
        $this->storage[self::ATTR_groups] = $groups;
        $this->storage[self::ATTR_ttl] = $timeout;

        $this->updateSessionExpiration();
    }
}
