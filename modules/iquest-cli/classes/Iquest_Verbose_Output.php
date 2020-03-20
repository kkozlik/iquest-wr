<?php

class Iquest_Verbose_Output{
    private static $enabled=false;

    public static function enable(){
        self::$enabled=true;
    }

    public static function log($data, $label=""){
        if (!self::$enabled) return;

        if ($label) Console::log("$label: ", Console::LIGHT_GREEN, false, Console::UNDERLINE);
        $str = print_r($data, true);
        Console::log($str, Console::DARK_GRAY);
    }
}
