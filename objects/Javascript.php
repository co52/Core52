<?php

if(!defined('JAVACRIPT_BASE')) {
	define('JAVASCRIPT_BASE', '/static/js/');
}

class Javascript {
	
    protected static $_code = array();
    protected static $_script = array();
    protected static $_remote = array();
      
    public static function add_inline($data) {
        self::$_code[] = $data;
    }
    public static function add_remote($data) {
        self::$_remote[] = $data;
    }
    public static function add($data) {
        self::$_script[] = $data;
    }
    

    /**
     * Return ALL javascript files as a string for printing
     *
     * @param boolean $include_inline
     * @return string
     */
    public static function get($include_inline = FALSE) {
        $str = "";
        foreach(self::$_script as $file) {
            $str .= '<script type="text/javascript" src="' . JAVASCRIPT_BASE . $file . "\"></script>\n";
        }
        foreach(self::$_remote as $file) {
            $str .= '<script type="text/javascript" src="' . $file . "\"></script>\n";
        }
        if($include_inline) {
        	$str .= self::get_inline();
        }
        return $str;
    }
    
    
    /**
     * Print all javascript files
     *
     * @param boolean $include_inline
     * @return NULL
     */
    public static function publish($include_inline = FALSE) {
    	echo self::get($include_inline);
    }
    
    
    /**
     * Return all inline javascript as a string for printing
     *
     * @return string
     */
    public static function get_inline() {
        $str = "<script type=\"text/javascript\">\n//<![CDATA[\n";
        foreach(self::$_code as $code) {
            $str .= "" . $code . "\n";
        }
        $str .= "//]]>\n</script>\n";
        return $str;
    }
    
    
    /**
     * Print all inline javascript
     *
     * @return NULL
     */
    public static function publish_inline() {
    	echo self::get_inline();
    }
    
    
}
