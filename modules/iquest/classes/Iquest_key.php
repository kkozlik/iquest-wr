<?php

class Iquest_key{

    /**
     *  Instantiate object of $class by key
     */
    static function &obj_by_key($key, $class){

        $key = self::canonicalize_key($key, $class);

        sw_log(__CLASS__."::".__FUNCTION__."(): Matching key: '$key', class: '$class'", PEAR_LOG_DEBUG);

        $objs = $class::fetch(["key"=>$key, "regexp_key"=>0]);
        if ($objs){
            $obj = reset($objs);
            return $obj;
        }

        $objs = $class::fetch(["regexp_key"=>1]);
        foreach($objs as $obj){
            sw_log(__CLASS__."::".__FUNCTION__."(): Checking regexp key: '$obj->key', class: '$class'", PEAR_LOG_DEBUG);
            if (preg_match(pregize("^".$obj->key."$"), $key)){
                sw_log(__CLASS__."::".__FUNCTION__."(): Regexp matched!", PEAR_LOG_DEBUG);
                return $obj;
            }
        }

        $null = null; //reference have to be returned
        return $null;
    }

    static function canonicalize_key($key, $class){

        // Get common not required prefix of the key and make sure it does not
        // contain non-alphanumeric characters
        $key_prefix = Iquest_Options::get(Iquest_Options::KEY_PREFIX);
        $key_prefix = strtolower($key_prefix);
        $key_prefix = preg_replace("/[^a-z0-9]/", "", $key_prefix);


        // remove diacritics
        $key = remove_diacritics($key);
        // to lowercase
        $key = strtolower($key);
        // remove non-alphanumeric
        $key = preg_replace("/[^a-z0-9]/", "", $key);

        // remove the optional prefix (it was "I.Q:") from the key
        if ($key_prefix) $key = preg_replace("/^$key_prefix/", "", $key);

        return $key;
    }

}
