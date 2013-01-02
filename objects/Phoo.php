<?php

class Phoo {
	
	protected static $partner_code;
	protected static $secret_code;
	protected static $initialized = FALSE;
	
	
	/**
	 * Initialize Ooyala API credentials, and load up the Phoo library
	 *
	 * @param string $partner_code
	 * @param string $secret_code
	 */
	public static function Initialize($partnerCode = NULL, $secretCode = NULL) {
		
		if(is_array($partnerCode)) extract($partnerCode);
		
		self::$partner_code = $partnerCode;
		self::$secret_code = $secretCode;
		
		if(!self::$initialized) {
			require_once PATH_CORE.'3rdparty/phoo/lib/Phoo/Autoloader.php';
			$autoloader = new \Phoo\Autoloader();
			$autoloader->register();
			self::$initialized = TRUE;
		}
	}
	
	
	/**
	 * Get a Phoo API access object
	 *
	 * @param string $api
	 * @return \Phoo\APIWrapper
	 */
	public static function factory($api) {
		if(!self::$initialized) {
			throw new \Exception('Phoo not initialized (call Phoo::Initialize() in your auth file)');
		}
		$class = "\\Phoo\\".ucfirst($api);
		return new $class(self::$partner_code, self::$secret_code);
	}
	
	
	/**
	 * Shortcut method for ::factory()
	 *
	 * @param string $name
	 * @return \Phoo\APIWrapper
	 */
	public static function __callStatic($name, $args) {
		return self::factory($name);
	}
	
	
}