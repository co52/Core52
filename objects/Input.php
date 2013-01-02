<?php

/**
 * Input
 * 
 * Basically, as of Aug 31st, 2009, no idea what this is, who built it
 * or what it was built for, but it's here.
 *
 * @package Core52
 * @todo Evaluate class for deletion or document purpose and use.
 * 
 **/

class Input {
	
	public static $vars = array();
	public static $errors = array();
	
	public static function Init($array = null) {
		self::$vars = $array;
	}
	public static function Register($array) {
		if(!is_array($array)) { return false; }
		else {
			foreach($array as $rpkey => $key) {
				if(isset($_REQUEST[$key])) {
					
					if(is_numeric($rpkey)) $rpkey = $key;
					self::$vars[$rpkey] = $_REQUEST[$key];
					
				}
				else {
					self::$errors[] = "Could not load key: ".$key;
				}
			}
		}
	}
	public static function Add($array) {
		if(!is_array($array)) { return false; }
		else {
			foreach($array as $rpkey => $key) {
				if(is_numeric($rpkey)) { $rpkey = $key; $key = ''; }
				self::$vars[$rpkey] = $key;
			}
		}
	}
	public static function Remove($array) {
		if($array == 'all') {
			self::$vars = array(); return true;
		}
		if(!is_array($array)) {		
			if(is_numeric($array)) { return false; }
			elseif(strlen($array) > 2) {
				unset(self::$vars[$array]);
			}
		}
		else {
			foreach($array as $key) {
				unset(self::$vars[$rpkey]);
			}
		}
	}
	public static function Clean($threat = 'sql', $data = null) {
		$dirty = (!is_null($data))? $data : self::$vars;
		$clean = array();
		
		switch($threat)
		{
			case 'sql':
				if(is_array($dirty)) {
					foreach($dirty as $key => $value) {
						$clean[$key] = addslashes(stripslashes($value));
					}
				}
				else {
					return addslashes(stripslashes($dirty));
				}
				break;
				
			case 'xss':
				if(is_array($dirty)) {
					foreach($dirty as $key => $value) {
						$clean[$key] = htmlentities(stripslashes($value));
					}
				}
				else {
					return htmlentities($dirty);
				}
				break;
			
			default:
				return false;
		}
		
		if(! is_array($data)) self::$vars = $clean;
		
		return $clean;
	}
}