<?php


// @hack - This lovely hack is to stop error:
//  -- Assigning the return value of new by reference is deprecated --
// on version 1.2.1-dev of simplepie
$cur_error_reporting = ini_get('error_reporting');
ini_set('error_reporting',0);

require_once(PATH_CORE.'3rdparty/simplepie/simplepie.inc');
require_once(PATH_CORE.'3rdparty/simplepie/idn/idna_convert.class.php');

ini_set('error_reporting',$cur_error_reporting);

class Feedreader {
	
	/**
	 * @var SimplePie
	 */
	private static $feedreader = FALSE;
	
	public static function Initialize() {
		self::$feedreader = new SimplePie();
		self::$feedreader->enable_cache(TRUE);
		self::$feedreader->set_cache_location(PATH_CACHE.'feedreader');
		if(!is_dir(PATH_CACHE.'feedreader')) {
			mkdir(PATH_CACHE.'feedreader');
		}
	}


	/**
	 * Returns a preconfigured SimplePie object
	 * @param $url
	 * @return SimplePie
	 */
	public static function factory($url = NULL) {
		if(!self::$feedreader) {
			self::Initialize();
		}
		$obj = clone self::$feedreader;
		if(!is_null($url)) {
			$obj->set_feed_url($url);
		}
		return $obj;
	}	
}