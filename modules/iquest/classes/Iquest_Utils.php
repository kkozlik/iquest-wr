<?php

class Iquest_Utils{

    public static function sec2time($secs){
        $hours = floor($secs / 3600);
        $mins  = floor($secs / 60 % 60);
        $secs  = floor($secs % 60);

        return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
    }

    public static function timeFormatByDate($ts){
        if (date("d.m.Y") == date("d.m.Y", $ts)) return "H:i:s";
        else return "d.m.Y H:i:s";
    }
}