<?php
/**
 * PHP Colored CLI
 * Used to log strings with custom colors to console using php
 * 
 * Copyright (C) 2013 Sallar Kaboli <sallar.kaboli@gmail.com>
 * MIT Liencesed
 * http://opensource.org/licenses/MIT
 *
 * Original colored CLI output script:
 * (C) Jesse Donat https://github.com/donatj
 */
class Console {
 
    const NORMAL  =         'normal';
    const BOLD  =           'bold';

    const BLACK  =          'black';
    const BLUE  =           'blue';
    const GREEN  =          'green';
    const CYAN  =           'cyan';
    const RED  =            'red';
    const PURPLE  =         'purple';
    const BROWN  =          'brown';
    const LIGHT_GRAY  =     'light_gray';

    const DIM  =            'dim';
    const DARK_GRAY  =      'dark_gray';
    const LIGHT_BLUE  =     'light_blue';
    const LIGHT_GREEN  =    'light_green';
    const LIGHT_CYAN  =     'light_cyan';
    const LIGHT_RED  =      'light_red';
    const LIGHT_PURPLE  =   'light_purple';
    const YELLOW  =         'yellow';
    const WHITE  =          'white';
    const MAGENTA  =        'magenta';
 
    const UNDERLINE  =      'underline';
    const REVERSE  =        'reverse';
    const BLINK  =          'blink';
    const HIDDEN  =         'hidden';
 
 
    static $foreground_colors = array(
        self::BOLD         => '1',    self::DIM          => '2',
        self::BLACK        => '0;30', self::DARK_GRAY    => '1;30',
        self::BLUE         => '0;34', self::LIGHT_BLUE   => '1;34',
        self::GREEN        => '0;32', self::LIGHT_GREEN  => '1;32',
        self::CYAN         => '0;36', self::LIGHT_CYAN   => '1;36',
        self::RED          => '0;31', self::LIGHT_RED    => '1;31',
        self::PURPLE       => '0;35', self::LIGHT_PURPLE => '1;35',
        self::BROWN        => '0;33', self::YELLOW       => '1;33',
        self::LIGHT_GRAY   => '0;37', self::WHITE        => '1;37',
        self::NORMAL       => '0;39',
    );
    
    static $background_colors = array(
        self::BLACK        => '40',   self::RED          => '41',
        self::GREEN        => '42',   self::YELLOW       => '43',
        self::BLUE         => '44',   self::MAGENTA      => '45',
        self::CYAN         => '46',   self::LIGHT_GRAY   => '47',
    );
 
    static $options = array(
        self::UNDERLINE    => '4',    self::BLINK         => '5', 
        self::REVERSE      => '7',    self::HIDDEN        => '8',
    );

    static $EOF = "\n";

    static $colors_supported = null;

    /**
     * Logs a string to console.
     * @param  string  $str        Input String
     * @param  string  $color      Text Color
     * @param  boolean $newline    Append EOF?
     * @param  [type]  $background Background Color
     * @return [type]              Formatted output
     */
    public static function log($str = '', $color = self::NORMAL, $newline = true, $background_color = null)
    {
        if( is_bool($color) )
        {
            $newline = $color;
            $color   = self::NORMAL;
        }
        elseif( is_string($color) && is_string($newline) )
        {
            $background_color = $newline;
            $newline          = true;
        }
        $str = $newline ? $str . self::$EOF : $str;

        if (is_null(self::$colors_supported)) self::check_color_support();
        if (self::$colors_supported)        echo self::color($str, $color, $background_color);
        else                                echo $str;
    }

    /**
     * Check if colors are supported by current terminal
     * @return bool
     */
    public static function check_color_support()
    {
        if (!posix_isatty(STDOUT)) {
            self::$colors_supported = false;
            return self::$colors_supported;
        }
    
        $out = exec("tput colors");
        if (!is_numeric($out)) {
            self::$colors_supported = false;
            return self::$colors_supported;
        }
        if ((int)$out <= 0) {
            self::$colors_supported = false;
            return self::$colors_supported;
        }
        
        self::$colors_supported = true;
        return self::$colors_supported;
    }

    /**
     * Check if colors are supported by current terminal
     * @return bool
     */
    public static function is_color_supported()
    {
        if (is_null(self::$colors_supported)) self::check_color_support();
        if (self::$colors_supported)        return true;
        else                                return false;
    }


    /**
     * Add coloring escape characters to string
     * @param  string  $str        Input String
     * @param  string  $color      Text Color
     * @param  boolean $newline    Append EOF?
     * @param  [type]  $background Background Color
     * @return [type]              Formatted output
     */
    public static function color($str = '', $color = self::NORMAL, $background_color = null)
    {
        return self::$color($str, $background_color);
    }
    
    /**
     * Anything below this point (and its related variables):
     * Colored CLI Output is: (C) Jesse Donat
     * https://gist.github.com/donatj/1315354
     * -------------------------------------------------------------
     */
    
    /**
     * Catches static calls (Wildcard)
     * @param  string $foreground_color Text Color
     * @param  array  $args             Options
     * @return string                   Colored string
     */
    public static function __callStatic($foreground_color, $args)
    {
        $string         = $args[0];
        $colored_string = "";
  
        // Check if given foreground color found
        if( isset(self::$foreground_colors[$foreground_color]) ) {
            $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        }
        else{
            die( $foreground_color . ' not a valid color');
        }
        
        array_shift($args);

        foreach( $args as $option ){
            // Check if given background color found
            if(isset(self::$background_colors[$option])) {
                $colored_string .= "\033[" . self::$background_colors[$option] . "m";
            }
            elseif(isset(self::$options[$option])) {
                $colored_string .= "\033[" . self::$options[$option] . "m";
            }
        }
        
        // Add string and end coloring
        $colored_string .= $string . "\033[0m";
        
        return $colored_string;
        
    }
 
    /**
     * Plays a bell sound in console (if available)
     * @param  integer $count Bell play count
     * @return string         Bell play string
     */
    public static function bell($count = 1) {
        echo str_repeat("\007", $count);
    }
 
}
