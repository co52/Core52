<?php

class PaypalException extends Exception {}
class PaypalNVPException extends PaypalException {}
class PaypalNVPInvalidResponseException extends PaypalNVPException {}


class PayPal {
	
	public static $sandbox_mode = FALSE;
	public static $sandbox_email;
	public static $sdk_version = 'PAYPAL_PLATFORM_PHP_SDK_V1.0.0';
	
	public static $api_base_endpoint;
	public static $api_authentication_mode = '3token';
	public static $trust_all_connection;
	
	public static $app_id;
	public static $api_username;
	public static $api_account;
	public static $api_password;
	public static $api_signature;
	
	public static $initialized = FALSE;
	
	public static $logfilename;
	
	
	public static function Initialize($sandbox_mode = FALSE, $app_id = NULL, $api_username = NULL, $api_password = NULL, $api_account = NULL, $api_signature = NULL, $logfilename = NULL, $sandbox_email = NULL) {
		
		# don't call more than once
		if(self::$initialized) {
			throw new Exception('PayPal API already initialized');
		}
		
		if(is_array($sandbox_mode)) {
			extract($sandbox_mode);
		}
		
		# since the SDK conditionally declares classes after calling class_exists(),
		# we have to temporarily disable autoloading.
		AutoClassLoader::Unregister();
		require_once(PATH_CORE.'3rdparty/paypal_sdk/samples/web/lib/AdaptiveAccounts.php');
		require_once(PATH_CORE.'3rdparty/paypal_sdk/samples/web/lib/AdaptivePayments.php');
		AutoClassLoader::Register();
		
		if($sandbox_mode == TRUE) {
			self::set_param('api_base_endpoint', 'https://svcs.sandbox.paypal.com/', TRUE, TRUE);
			self::set_param('sandbox_mode', TRUE);
			self::set_param('sandbox_email', $sandbox_email);
			define('PAYPAL_REDIRECT_URL', 'https://www.sandbox.paypal.com/webscr&cmd=');
			define('PAYPAL_IPN_URL', 'sandbox.paypal.com');
		} else {
			self::set_param('api_base_endpoint', 'https://svcs.paypal.com/', TRUE, TRUE);
			self::set_param('sandbox_mode', FALSE);
			define('PAYPAL_REDIRECT_URL', 'https://www.paypal.com/webscr&cmd=');
			define('PAYPAL_IPN_URL', 'www.paypal.com');
		}
		
		self::set_param('app_id', $app_id, TRUE, TRUE);
		define('X_PAYPAL_APPLICATION_ID', $app_id);
		
		self::set_param('api_username',  $api_username,  TRUE, TRUE);
		self::set_param('api_account',   $api_account,   TRUE, TRUE);
		self::set_param('api_password',  $api_password,  TRUE, TRUE);
		self::set_param('api_signature', $api_signature, TRUE, TRUE);

		if(empty($logfilename)) {
			$logfilename = PATH_APP.'logs/paypal_platform_'.date('Y-m-d').'.log';
		}
		self::set_param('logfilename', $logfilename, TRUE, TRUE);
		
		define('TRUST_ALL_CONNECTION', FALSE);
		define('USE_PROXY',FALSE);
		define('PROXY_HOST', '127.0.0.1');
		define('PROXY_PORT', '808');
		define('X_PAYPAL_REQUEST_DATA_FORMAT','SOAP11');
		define('X_PAYPAL_RESPONSE_DATA_FORMAT','SOAP11');
		define('X_PAYPAL_DEVICE_IPADDRESS', $_SERVER['SERVER_ADDR']);
		define('PAYPAL_DEVELOPER_PORTAL', 'https://developer.paypal.com');
		define('PAYPAL_DEVICE_ID', 'PayPal_Platform_PHP_SDK');
	}
	
	
	private static function set_param($param, $value = NULL, $required = FALSE, $set_const = FALSE) {
		self::${$param} = $value;
		if(is_null($value) && $required == TRUE) {
			throw new InvalidArgumentException("$param is required");
		}
		if($set_const == TRUE) {
			define(strtoupper($param), $value);
		}
	}
	
	
	public static function ClientDetailsFactory() {
		$obj = new ClientDetailsType();
		$obj->applicationId = PayPal::$app_id;
		$obj->deviceId = PAYPAL_DEVICE_ID;
		$obj->ipAddress = X_PAYPAL_DEVICE_IPADDRESS;
		return $obj;
	}
	
	
	public static function PreapprovalRequestFactory(array $data) {
		$obj = new PreapprovalRequest();
		$obj->clientDetails = PayPal::ClientDetailsFactory();
        $obj->requestEnvelope = new RequestEnvelope();
        $obj->requestEnvelope->errorLanguage = "en_US";
        foreach($data as $key => $val) {
			$obj->$key = $val;
		}
        return $obj;
	}
	
	
	public static function PreapprovalDetailsRequestFactory($preapprovalKey) {
		$obj = new PreapprovalDetailsRequest();
		$obj->requestEnvelope = new RequestEnvelope();
		$obj->requestEnvelope->errorLanguage = "en_US";
		$obj->preapprovalKey = $preapprovalKey;
		return $obj;
	}
	
	
	public static function CreateAccountRequestFactory(array $data) {
		$obj = new CreateAccountRequest();
		
		$obj->address = new AddressType();
		foreach($data['address'] as $key => $val) {
			$obj->address->$key = $val;
		}
		unset($data['address']);
        
		$obj->name = new NameType();
		foreach($data['name'] as $key => $val) {
			$obj->name->$key = $val;
		}
		unset($data['name']);
        
		$obj->createAccountWebOptions = new CreateAccountWebOptionsType();
		$obj->createAccountWebOptions->returnUrl = $data['returnUrl'];
		unset($data['returnUrl']);
		$obj->registrationType = 'WEB';
		
		$obj->clientDetails = PayPal::ClientDetailsFactory();
		$obj->requestEnvelope = new RequestEnvelope();
		$obj->requestEnvelope->errorLanguage = "en_US";
		
		foreach($data as $key => $val) {
			$obj->$key = $val;
		}
		
        return $obj;
	}
	
	
	public static function PayRequestFactory(array $data) {
		$obj = new PayRequest();
		$obj->actionType = "PAY";
		
		$obj->receiverList = array();
		foreach((array) $data['recipients'] as $recipient) {
			$receiver = new receiver();
			foreach($recipient as $key => $value) {
				$receiver->$key = $value;
			}
			$obj->receiverList[] = $receiver;
		}
		unset($data['recipients']);
        
		$obj->clientDetails = PayPal::ClientDetailsFactory();
		$obj->requestEnvelope = new RequestEnvelope();
		$obj->requestEnvelope->errorLanguage = "en_US";
		
		foreach($data as $key => $val) {
			$obj->$key = $val;
		}
		
        return $obj;
	}
	
	
	public static function PaymentDetailsRequestFactory($payKey) {
		$obj = new PaymentDetailsRequest();
		$obj->requestEnvelope = new RequestEnvelope();
		$obj->requestEnvelope->errorLanguage = "en_US";
		$obj->payKey = $payKey;
		return $obj;
	}
	
	
	public static function GetTransactionDetails($txn_id) {

		// Call the GetTransactionDetails NVP API method
		$response = self::nvp_post('GetTransactionDetails', "&TRANSACTIONID=".urlencode($txn_id));
		
		if("SUCCESS" == strtoupper($response["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($response["ACK"])) {
			return $response;
		} else  {
			throw new PaypalNVPException('GetTransactionDetails failed: ' . print_r($response, true));
		}

	}
	
	
	/**
	 * Send HTTP POST Request
	 *
	 * @param	string	The API method name
	 * @param	string	The POST Message fields in &name=value pair format
	 * @return	array	Parsed HTTP Response body
	 */
	protected static function nvp_post($methodName_, $nvpStr_) {
		
		$environment = (self::$sandbox_mode)? 'sandbox' : 'live';
	
		// Set up your API credentials, PayPal end point, and API version.
		$API_UserName = urlencode(self::$api_username);
		$API_Password = urlencode(self::$api_password);
		$API_Signature = urlencode(self::$api_signature);
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		if("sandbox" === $environment || "beta-sandbox" === $environment) {
			$API_Endpoint = "https://api-3t.$environment.paypal.com/nvp";
		}
		$version = urlencode('51.0');
	
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
	
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		// Set the API operation, version, and API signature in the request.
		$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
	
		// Set the request as a POST FIELD for curl.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
	
		// Get response from the server.
		$httpResponse = curl_exec($ch);
	
		if(!$httpResponse) {
			exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
		}
	
		// Extract the response details.
		$httpResponseAr = explode("&", $httpResponse);
	
		$httpParsedResponseAr = array();
		foreach ($httpResponseAr as $i => $value) {
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1) {
				$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
			}
		}
	
		if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
			throw new PaypalNVPInvalidResponseException("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
		}
	
		return $httpParsedResponseAr;
	}
	
	
}



class PaypalIpnHttpException extends Exception {}
class PaypalIpnInvalidException extends Exception {}
class PaypalIpnMalformedException extends Exception {}

class PayPalIPN {
	
	public function __construct(array $data = NULL) {
		if($data) {
			foreach($data as $key => $val) {
				$this->$key = $val;
			}
		}
	}
	
	
	public function __toString() {
		return get_class($this)."\n-----------------------------------------------------\n".var_export($this, TRUE);
	}
	
	
	/**
	 * Process and verify an IPN
	 *
	 * @return PayPalIPN
	 */
	public static function get() {
		
		if(empty($_POST)) {
			return FALSE;
		}
		
		self::_log("\n\n\n****************************************************\n");
		self::_log("IPN on ".date('Y-m-d H:i:s')."\n");
		self::_log("--- Data: ------------------------------------------\n");
		self::_log(print_r($_POST, TRUE));
		self::_log("--- Response: --------------------------------------\n");
		
		# Prepare IPN verification HTTP request data
		$req = 'cmd=_notify-validate';
		foreach ($_POST as $key => $value) {
			
			# Handle escape characters, which depends on setting of magic quotes
			if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() == 1) {
				$value = urlencode(stripslashes($value));
			} else {
				$value = urlencode($value);
			}
			
			$req .= "&$key=$value";
		}
		
		# Post back to PayPal to validate
		# Note: the IPN flow goes like this:
		#  1) Receive PayPal IPN
		#  2) Echo IPN data back to PayPal
		#  3) PayPal responds either 'VERIFIED' or 'INVALID'
		$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
		$fp = fsockopen(PAYPAL_IPN_URL, 80, $errno, $errstr, 30);
		
		
		# Process validation from PayPal
		if($fp === FALSE) {
			
			# HTTP error
			throw new PaypalIpnHttpException("HTTP error $errno while confirming IPN request: $errstr", $errno);
			
		} else {
			
			# acknowledge PayPal IPN
			fputs($fp, $header . $req);
			$response = '';
			while(!feof($fp)) {
				
				# check PayPal's acknowledgement
				$res = fgets($fp, 1024);
				self::_log($res);
				if(trim($res) === "VERIFIED") {
					
					# VALID
					fclose($fp);
					return self::factory($_POST);
					
				} else {
					
					$response .= $res;
					
				}
				
			}
			
			# BAD REQUEST
			fclose($fp);
			throw new PaypalIpnInvalidException("Fake IPN request (response was: $response)");
						
		}
	
	}
	
	
	private static function _log($msg) {
		error_log($msg, 3, PayPal::$logfilename.'_ipn');
	}
	
	
	private static function factory(array $data) {
		
		switch($data['txn_type']) {
			
			case '--':
				if($data['case_type'] === 'chargeback') {
					return new PayPalChargebackIPN($data);
				}
				break;
				
			case 'adjustment':
				return new PayPalAdjustmentIPN($data);
				
			case 'cart':
				return new PayPalCartIPN($data);
				
			case 'express_checkout':
				return new PayPalAdjustmentIPN($data);
				
			case 'masspay':
				return new PayPalMassPayIPN($data);
				
			case 'merch_pmt':
				return new PayPalMerchantPaymentIPN($data);
				
			case 'new_case':
				return new PayPalNewCaseIPN($data);
				
			case 'recurring_payment':
				return new PayPalRecurringPaymentIPN($data);
				
			case 'recurring_payment_profile_created':
				return new PayPalRecurringPaymentProfileCreatedIPN($data);
				
			case 'send_money':
				return new PayPalSendMoneyIPN($data);
				
			case 'subscr_cancel':
				return new PayPalSubscriptionCancelledIPN($data);
				
			case 'subscr_eot':
				return new PayPalSubscriptionExpiredIPN($data);
				
			case 'subscr_failed':
				return new PayPalSubscriptionFailedIPN($data);
				
			case 'subscr_modify':
				return new PayPalSubscriptionModifiedIPN($data);
				
			case 'subscr_payment':
				return new PayPalSubscriptionPaymentIPN($data);
			 
			case 'subscr_signup':
				return new PayPalSubscriptionSignupIPN($data);
			
			case 'virtual_terminal':
				return new PayPalVirtualTerminalIPN($data);
			
			case 'web_accept':
				return new PayPalWebAcceptIPN($data);
			
		}
		
		
		switch($data['transaction_type']) {
			
			case 'Adaptive Payment PREAPPROVAL':
				return new PayPalAdaptivePaymentPreapprovalIPN($data);
				
		}
		
		
		throw new PaypalIpnInvalidException("Unknown IPN type or txn_type not set: {$data['txn_type']}");
	}
	
}


class PayPalCartIPN extends PayPalIPN {}
class PayPalExpressCheckoutIPN extends PayPalIPN {}
class PayPalWebAcceptIPN extends PayPalIPN {}

class PayPalSendMoneyIPN extends PayPalIPN {}
class PayPalChargebackIPN extends PayPalIPN {}
class PayPalMassPayIPN extends PayPalIPN {}
class PayPalMerchantPaymentIPN extends PayPalIPN {}
class PayPalVirtualTerminalIPN extends PayPalIPN {}

class PayPalRecurringPaymentIPN extends PayPalIPN {}
class PayPalRecurringPaymentProfileCreatedIPN extends PayPalIPN {}

class PayPalSubscriptionCancelledIPN extends PayPalIPN {}
class PayPalSubscriptionExpiredIPN extends PayPalIPN {}
class PayPalSubscriptionFailedIPN extends PayPalIPN {}
class PayPalSubscriptionModifiedIPN extends PayPalIPN {}
class PayPalSubscriptionPaymentIPN extends PayPalIPN {}
class PayPalSubscriptionSignupIPN extends PayPalIPN {}

class PayPalNewCaseIPN extends PayPalIPN {}
class PayPalAdjustmentIPN extends PayPalIPN {}

class PayPalAdaptivePaymentPreapprovalIPN extends PayPalIPN {}


