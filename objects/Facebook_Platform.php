<?

require_once(PATH_CORE.'3rdparty/facebook_platform/php/facebook.php');
require_once(PATH_CORE.'3rdparty/facebook_platform/php/facebookapi_php5_restlib.php');

class Facebook_Platform {
	
	private static $api_key;
	private static $secret;
	private static $session_key;
	private static $apis = array();
	
	
	public static function api_key() {
		return self::$api_key;
	}
	
	
	public static function Initialize($api_key, $secret = NULL, $session_key = NULL) {
		if(is_array($api_key)) {
			extract($api_key);
		}
		
		self::$api_key = $api_key;
		self::$secret = $secret;
		self::$session_key = $session_key;
		
		self::$apis['standard'] = new Facebook(self::$api_key, self::$secret);
		self::$apis['rest'] = new FacebookRestClient(self::$api_key, self::$secret, self::$session_key);
	}
	
	
	/**
	 * Get an instance of the Facebook API
	 * @return Facebook
	 */
	public static function api_instance() {
		
		if(empty(self::$api_key) || empty(self::$secret)) {
			throw new Exception('Facebook platform not initialized, please call Facebook_Platform::Initialize() in your auth file');
		}
		
		return self::$apis['standard'];
	}
	
	
	/**
	 * Get an instance of the Facebook REST Client
	 * @return FacebookRestClient
	 */
	public static function rest_api_instance() {
				
		if(empty(self::$api_key) || empty(self::$secret)) {
			throw new Exception('Facebook platform not initialized, please call Facebook_Platform::Initialize() in your auth file');
		}
		
		return self::$apis['rest'];
	}
	
	
}