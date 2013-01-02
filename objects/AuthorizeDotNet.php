<?php

class AuthorizeDotNet {
	
	protected static $login_id;
	protected static $transaction_key;
	protected static $sandbox = FALSE;
	protected static $log_file = FALSE;
	
	protected static $initialized = FALSE;
	
	
	/**
	 * Initialize Authorize.net API credentials, and load up the library
	 *
	 * @param string $partner_code
	 * @param string $secret_code
	 */
	public static function Initialize($login_id = NULL, $transaction_key = NULL, $sandbox = FALSE, $log_file = FALSE) {
		
		if(self::$initialized) return;
		
		if(is_array($login_id)) extract($login_id);
		
		self::$login_id = $login_id;
		define('AUTHORIZENET_API_LOGIN_ID', $login_id);
		
		self::$transaction_key = $transaction_key;
		define('AUTHORIZENET_TRANSACTION_KEY', $transaction_key);
		
		self::$sandbox = $sandbox;
		define('AUTHORIZENET_SANDBOX', $sandbox);
		
		self::$log_file = $log_file;
		define('AUTHORIZENET_LOG_FILE', $log_file);
		
		require_once PATH_CORE.'3rdparty/authorizedotnet_sdk/AuthorizeNet.php';
		self::$initialized = TRUE;

	}
	
	
	public static function validate_credentials($login_id, $transaction_key) {
		$request = new AuthorizeNetCIM($login_id, $transaction_key);
	    $response = $request->getCustomerProfileIds();
	    if($response->isOk()) {
	    	return TRUE;
	    } else {
	    	throw new AuthorizeNetException($response->getErrorMessage());
	    }
	}
	
	
	/**
	 * Get a customer profile by ID via the CIM API
	 *
	 * @param string $customerProfileID
	 * @param AuthorizeNetCIM $cim
	 * @return AuthorizeNetCIM_Response
	 */
	public static function cim_get_profile($customerProfileID, AuthorizeNetCIM $cim = NULL) {
		if(is_null($cim)) $cim = new AuthorizeNetCIM();
		$response = $cim->getCustomerProfile($customerProfileID);
    	if(!$response->isOk()) {
    		throw new AuthorizeNetException($response->getErrorMessage());
    	} else {
    		return $response;
    	}
	}
	
	
	/**
	 * Get a customer payment profile by ID and payment profile index via the CIM API
	 *
	 * @param string $customerProfileID
	 * @param AuthorizeNetCIM $cim
	 * @return SimpleXMLElement
	 */
	public static function cim_get_payment_profile($customerProfileID, $n = 0, AuthorizeNetCIM $cim = NULL) {
		$response = self::cim_get_profile($customerProfileID, $cim);
		if(!isset($response->xml->profile->paymentProfiles[$n])) {
			throw new InvalidArgumentException("No such customer payment profile index - $n");
		} else {
			return $response->xml->profile->paymentProfiles[$n];
		}
	}
	
}