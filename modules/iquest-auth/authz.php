<?php

class Iquest_authZ{

    protected $storage;
    protected $capabilities = null;

    /**
     * Return a reference to a Iquest_authZ instance, only creating a new instance
     * if no Iquest_authZ instance currently exists.
     */
    public static function singleton(){
        static $instance = null;

        if (is_null($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    protected function __construct(){
        if (!isset($_SESSION['Iquest_authZ'])) $_SESSION['Iquest_authZ'] = array();
        $this->storage = &$_SESSION['Iquest_authZ'];
    }

    public function getCapabilities(){
        if (!is_null($this->capabilities)) return $this->capabilities;

        if (!empty($this->storage['capabilities'])){
            $this->capabilities = $this->storage['capabilities'];
            return $this->capabilities;
        }

        $this->loadCapabilities();
        $this->storage['capabilities'] = $this->capabilities;
        return $this->capabilities;
    }

    public function clearCache(){
        $this->capabilities = null;
        $this->storage['capabilities'] = null;
    }

    protected function loadCapabilities(){
        $auth = Iquest_authN::singleton();
        $groups = $auth->getGroups();

        $this->capabilities = [];
        if ($groups){
            foreach($groups as $group){
                $this->capabilities[$group] = true;
            }
        }
    }

    /**
     * Check whether logged-in user has access to specified capabilities. The
     * format of the $requiredCapabilities argument is this:
     *
     * array(capability1, capability2, ....)
     *
     * If more capabilities are specified this function require that
     * user has access to all of them.
     *
     * @param array $requiredCapabilities
     * @return bool
     */
    function authorize(array $requiredCapabilities){

        foreach($requiredCapabilities as $capability){

            sw_log(__CLASS__." Checking capability '{$capability}' capability.", PEAR_LOG_DEBUG);

            $capabilities = $this->getCapabilities();
            if (!isset($capabilities[$capability])){
                sw_log(__CLASS__." authorization failed. The user is missing '{$capability}' capability.", PEAR_LOG_INFO);
                return false;
            }
        }

        return true;
    }
}