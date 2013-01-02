<?php

require_once(PATH_CORE.'3rdparty/browser/browserDetector.php');


class Browser {
	
	/**
	 * 0	browser_name
	 * 1	version_number
	 * 2	ie_version
	 * 3	dom_browser
	 * 4	safe_browser
	 * 5	os
	 * 6	os_number
	 * 7	s_browser [the browser search string from the browser array]
	 * 8	type
	 * 9	math_version_number
	 * 10	moz_array
	 * 11	webkit_array
	 * 12   mobile_test
	 * 13   mobile_array
	 * 14   true_ie_version
	 * 15   runtime
	 *
	 * Note that the last two are arrays which could contain null data, so always test it first before
	 * assuming the moz/webkit arrays contain any data, ie, if moz or if webkit, then...
	 *
	 * @var array
	 */
	public static $browser = array();
	
	public static $version;
	
	public static function Initialize() {
		self::$browser = browser_detection('full');
		self::$version = (float) self::$browser[9];
	}
	
	public static function is_ie($ver = NULL) {
		$ie_check = (substr(self::$browser[0], 0, 2) == 'ie');
		$ie_ver_check = (is_null($ver))? TRUE : (self::$version == $ver);
		return ($ie_check && $ie_ver_check);
	}
	
	public static function version() {
		return self::$version;
	}
	
	
	public $browser_working;
	public $version_number;
	public $ie_version;
	public $dom;
	public $safe;
	public $os;
	public $os_number;
	public $ua_type;
	public $browser_math_number;
	public $moz_data;
	public $webkit_data;
	public $mobile_test;
	public $mobile_data;
	public $true_ie_number;
	public $run_time;
	
	public function __construct($ua_string = '') {
		$data = browser_detection('full_assoc', '', $ua_string);
		foreach($data as $k => $v) {
			$this->$k = $v;
		}
	}
	
	
}

