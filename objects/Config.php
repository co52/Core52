<?php

class Config {

	private static $data = array();

	public static function get($item, $required = TRUE) {
		if (isset(self::$data[$item])) {
			return self::$data[$item];
		}
		elseif($required === TRUE) {
			throw new Exception("Tried to access configuration variable '$item', which has not been set");
		}
	}

	public static function get_all() {
		return self::$data;
	}

	public static function set($item, $value = NULL) {
		if(!is_array($item)) {
			$item = array($item => $value);
		}

		self::$data = array_merge(self::$data, $item);
	}

	public static function get_val($var) {
		if(strlen($var) % 4 > 0) {
			$var .= str_repeat('=', strlen($var) % 4);
		}
		return base64_decode($var);
	}
	
}


# grab debug settings
function C52_DEV($setting) {
	$s = ($_COOKIE['C52_DEV'])? $_COOKIE['C52_DEV'] : $_REQUEST['C52_DEV'];
	$C52_DEV = explode('|', $s);
	return (in_array($setting, $C52_DEV))? TRUE : FALSE;
}