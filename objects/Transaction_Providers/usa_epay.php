<?php

namespace Usa_epay_Transaction;

# USA ePay base class
abstract class Base_Transaction {
	
	public $user_data = array();
	public $response;
	
	protected static $settings;
	
	# Approval Response Codes
	public static $C_APPROVAL = array(
		"A" => "Approved",
		"D" => "Declined",
		"E" => "NOT Approved - Front-end error",
		"V" => "Verification",
	);
	
	# Response Codes
	public static $C_RESULT = array(
		"00001" => "Password/Username Incorrect.",
		"00002" => "Access to page denied.",
		"00003" => "Transaction type [type] not supported.",
		"00004" => "Processing gateway currently offline.",
		"00005" => "Error in verification module [module].",
		"00006" => "Merchant not found.",
		"00007" => "Merchant has been deactivated.",
		"00008" => "Unable to retrieve current batch.",
		"00009" => "Unable To Create Transaction.",
		"00010" => "Unable To Allocate Transaction Slot.",
		"00011" => "Invalid Card Number (1)",
		"00012" => "Invalid Card Number (2)",
		"00013" => "Invalid Card Number (3)",
		"00014" => "Invalid Credit Card Number (1)",
		"00015" => "Invalid expiration date.",
		"00016" => "Invalid expiration date.",
		"00017" => "Credit card has expired.",
		"00018" => "Gateway temporarily offline.",
		"00019" => "Gateway temporarily offline for maintenance.",
		"00020" => "User not configured correctly, please contact support.",
		"00021" => "Invalid username.",
		"00022" => "You do not have access to this page.",
		"00023" => "Specified source key not found.",
		"00024" => "Transaction already voided.",
		"00025" => "Unable to find transaction in batch.",
		"00026" => "The batch has already been closed. Please apply a credit instead.",
		"00027" => "Gateway temporarily offline. Please try again shortly. (2)",
		"00028" => "Unable to verify source.",
		"00029" => "Unable to generate security key.",
		"00030" => "Source has been blocked from processing transactions.",
		"00031" => "Duplicate transaction, wait at least [minutes] minutes before trying again.",
		"00032" => "The maximum order amount is $[amount].",
		"00033" => "The minimum order amount is $[amount].",
		"00034" => "Your billing information does not match your credit card. Please check with your bank.",
		"00035" => "Unable to locate transaction.",
		"00036" => "Gateway temporarily offline for maintenance.",
		"00037" => "Customer Name not submitted.",
		"00038" => "Invalid Routing Number.",
		"00039" => "Invalid Checking Account Number.",
		"00040" => "Merchant does not currently support check transactions.",
		"00041" => "Check processing temporarily offline. Please try again shortly.",
		"00042" => "Temporarily unable to process transaction. Please try again shortly.",
		"00043" => "Transaction Requires Voice Authentication. Please Call-In.",
		"00044" => "Merchant not configured properly (CardAuth)",
		"00045" => "Auth service unavailable.",
		"00046" => "Auth service unavailable (6).",
		"00050" => "Invalid SSN.",
		"00070" => "Transaction exceeds maximum amount.",
		"00071" => "Transaction out of balance.",
		"00080" => "Transaction type not allowed from this source.",
		"02034" => "Your billing address does not match your credit card.",
		"10001" => "Processing Error Please Try Again Error from FDMS Nashville.",
		"10003" => "Merchant does not accept this type of card (1)",
		"10004" => "Merchant does not accept this type of card (2)",
		"10005" => "Invalid Card Expiration Date Error from FDMS Nashville",
		"10006" => "Merchant does not accept this type of card (3) Error from FDMS Nashville.",
		"10007" => "Invalid amount Error from FDMS Nashville",
		"10008" => "Processing Error Please Try Again (08) Error from FDMS Nashville.",
		"10009" => "Processing Error Please Try Again (09) Error from FDMS Nashville",
		"10010" => "Processing Error Please Try Again (10) Error from FDMS Nashville",
		"10011" => "Processing Error Please Try Again (11) Error from FDMS Nashville",
		"10012" => "Processing Error Please Try Again (12) Error from FDMS Nashville",
		"10013" => "Processing Error Please Try Again (13) Error from FDMS Nashville",
		"10014" => "Processing Error Please Try Again (14) Error from FDMS Nashville",
		"10015" => "Processing Error Please Try Again (15) Error from FDMS Nashville",
		"10016" => "Processing Error Please Try Again (16) Error from FDMS Nashville",
		"10017" => "Invalid Invoice Number (17) Error from FDMS Nashville",
		"10018" => "Invalid Transaction Date or Time (18) Error from FDMS Nashville",
		"10019" => "Processing Error Please Try Again (19) Error from FDMS Nashville",
		"10020" => "Processing Error Please Try Again (20) Error from FDMS Nashville",
		"10026" => "Merchant has been deactivated (26) Error from FDMS Nashville",
		"10027" => "Invalid Merchant Account (27) Error from FDMS Nashville.",
		"10030" => "Processing Error Please Try Again (30) Error from FDMS Nashville.",
		"10031" => "Processing Error Please Retry Transaction (31) Error from FDMS Nashville.",
		"10033" => "Processing Error Please Try Again (33) Error from FDMS Nashville.",
		"10043" => "Sequence Error, Please Contact Support (43) Error from FDMS Nashville.",
		"10051" => "Merchant has been deactivated (51) Error from FDMS Nashville.",
		"10054" => "Merchant has not been setup correctly (54) Error from FDMS Nashville.",
		"10057" => "Merchant does not support this card type (57) Error from FDMS Nashville.",
		"10059" => "Processing Error Please Try Again (59) Error from FDMS Nashville.",
		"10060" => "Invalid Account Number (60) Error from FDMS Nashville.",
		"10061" => "Processing Error Please Try Again (61) Error from FDMS Nashville.",
		"10062" => "Processing Error Please Try Again (62) Error from FDMS Nashville.",
		"10080" => "Processing Error Please Try Again (80) Error from FDMS Nashville.",
		"10098" => "Processing Error Please Try Again (98) Error from FDMS Nashville.",
		"10099" => "Session timed out. Please re-login.",
		"10100" => "Your account has been locked for excessive login attempts.",
		"10101" => "Your username has been de-activated due to inactivity for 90 days.",
		"10102" => "Unable to open certificate. Unable to load required certificate.",
		"10103" => "Unable to read certificate. Unable to load required certificate.",
		"10104" => "Error reading certificate. Unable to load required certificate.",
		"10105" => "Unable to find original transaction.",
		"10106" => "You have tried too many card numbers, please contact merchant.",
		"10107" => "Invalid billing zip code.",
		"10108" => "Invalid shipping zip code.",
		"10109" => "Billing state does not match billing zip code.",
		"10110" => "Billing city does not match billing zip code.",
		"10111" => "Billing area code does not match billing zip code.",
		"10112" => "Shipping state does not match shipping zip code.",
		"10113" => "Shipping city does not match shipping zip code.",
		"10114" => "Shipping area code does not match shipping zip code.",
		"10115" => "Merchant does not accept transactions from [country].",
		"10116" => "Unable to verify card ID number.",
		"10117" => "Transaction authentication required.",
		"10118" => "Transaction authentication failed.",
		"10119" => "Unable to parse mag stripe data.",
		"10120" => "Unable to locate valid installation.",
		"10121" => "Wireless key disabled.",
		"10122" => "Wireless key mismatch.",
		"10123" => "Success Operation was successful.",
		"10124" => "Unsupported transaction type.",
		"10125" => "Original transaction not approved.",
		"10126" => "Transactions has already been settled.",
		"10127" => "Card Declined Hard decline from First Data.",
		"10128" => "Processor Error ([response])",
		"10129" => "Invalid transaction data.",
		"10130" => "Library Error.",
		"10131" => "Library Error.",
		"10132" => "Error reading from card processing gateway.",
	);
	
	# CVV Response Codes
	public static $C_CVV = array(
		"M" => "Match",
		"N" => "No match",
		"P" => "Not processed",
		"S" => "Merchant indicates no CVV present",
		"U" => "Issuer has not provided CVV2",
		"X" => "No response",
		""  => "Not applicable",
	);

	# AVS Response Codes
	public static $C_AVS = array(
		"X"   => "Exact match on address+zip9",
		"YYX" => "Exact match on address+zip9",
		
		"Y"   => "Match on address+zip5",
		"YYY" => "Match on address+zip5",
		"YYA" => "Match on address+zip5",
		"YYD" => "Match on address+zip5",
	
		"A"   => "Address matches, zip does not",
		"YNA" => "Address matches, zip does not",
		"YNY" => "Address matches, zip does not",
	
		"W"   => "9 digit zip matches, address does not",
		"NYW" => "9 digit zip matches, address does not",
	
		"Z"   => "5 digit zip matches, address does not",
		"NYZ" => "5 digit zip matches, address does not",

		"N"   => "Neither zip or address matches",
		"NN"  => "Neither zip or address matches",
		"NNN" => "Neither zip or address matches",
	
		"XXR" => "Unavailable",
		"U"   => "Unavailable",
		"R"   => "Retry",
		"E"   => "Error",
	
		"S"   => "Service not supported",
		"XXS" => "Service not supported",
	
		"XXW" => "Card Number Not On File",
	
		"XXU" => "Address Information not verified for domestic transaction",
	
		"XXE" => "Address Verification Not Allowed For Card Type",
		
		"G"   => "Global Non-AVS participant",
		"C"   => "Global Non-AVS participant",
		"I"   => "Global Non-AVS participant",
		"XXG" => "Global Non-AVS participant",
	
		"B"   => "International Address matches, zip not compatible",
		"M"   => "International Address matches, zip not compatible",
		"YYG" => "International Address matches, zip not compatible",
	
		"D"   => "Exact match on international address",
		"GGG" => "Exact match on international address",
	
		"P"   => "Zip matches, international address does not",
		"YGG" => "Zip matches, international address does not",
	);
	
	# Transaction Status Codes
	public static $C_STATUS = array(
		"N" => "Queued",
		"P" => "Pending",
		"B" => "Submitted",
		"F" => "Funded",
		"S" => "Settled",
		"E" => "Error",
		"V" => "Voided",
		"R" => "Returned",
		"T" => "Timed out",
		"M" => "Manager Approval Required",
	);
	
	protected $token;
	protected $trans_type;
	protected $method;
	
	public static function Initialize(array $settings) {
		self::$settings = $settings;
	}
	
	
	public function __construct($userId, $xlate = array()) {
		
		if(is_array($userId)) {
			$user = (object) $userId;
		}
		elseif(is_object($userId)) {
			$user = ($userId instanceof \Model)? $userId->toArray() : $userId;
		}
		else {
			$class = self::$settings['user_model_class'];
			$user = new $class($userId);
		}
		
		foreach((array) $user as $k => $v) {
			if(array_key_exists($k, $xlate)) {
				$l = $xlate[$k];
				$this->$l = $v;
			} else {
				$this->$k = $v;
			}
		}
		
		$this->prep_user_data();
		
	}
	
	
	protected function security_token() {
		
		if(!$this->token) {
			// make PIN hash
			$seed = mktime() . rand();
			$hash = sha1(self::$settings['mkey'] . $seed . self::$settings['mid']);
	 
			// assembly ueSecurityToken as an array
			$this->token = array(
				'SourceKey' => self::$settings['mkey'],
				'PinHash'=>array(
					'Type' => 'sha1',
					'Seed' => $seed,
					'HashValue' => $hash,
				),
				'ClientIP' => $_SERVER['REMOTE_ADDR'],
			);
		}
		
		return $this->token;
	}
	

	public function run($amount) {
		throw new \Exception('Base_Transaction::run() not implemented');
	}
	
	
	protected function prep_user_data() {
		
		$this->user_data = array(
			'AccountHolder' => "$this->fname $this->lname",
			'CreditCardData' => array(
				'AvsStreet' => $this->address,
				'AvsZip' => $this->zip,
				'CardNumber' => $this->cc_num,
				'CardExpiration' => (is_numeric($this->cc_exp) && strlen($this->cc_exp) == 4)? $this->cc_exp : format_date($this->cc_exp, 'my'),
			),
			'BillingAddress' => array(
				'FirstName' => $this->fname,
				'LastName' => $this->lname,
				'Street' => $this->address,
				'City' => $this->city,
				'State' => $this->state,
				'Zip' => $this->zip,
				'Country' => $this->country,
				'Email' => $this->email,
			),
		);
			
		if(isset($this->cvv))
			$this->user_data['CreditCardData']['CardCode'] = $this->cvv;
	}
	
	
	protected function parse_response($response) {
		// parse the response
		$this->response->APPROVAL_INDICATOR = Base_Transaction::$C_APPROVAL[(string) $response->ResultCode];
		$this->response->RESULT_CODE		= (string) $response->ResultCode;
		$this->response->RESULT_MESSAGE		= (isset(Base_Transaction::$C_RESULT[(string) $response->ErrorCode]))? Base_Transaction::$C_RESULT[(string) $response->ErrorCode] : (string) $response->Error;
		$this->response->CVV_INDICATOR 		= Base_Transaction::$C_CVV[(string) $response->CardCodeResultCode];
		$this->response->AVS_INDICATOR 		= Base_Transaction::$C_AVS[(string) $response->AvsResultCode];
		$this->response->REFERENCE 			= (string) $response->RefNum;
	}
	

	public function verify($run_auth = FALSE) {
		$m = substr($this->user_data['CreditCardData']['CardExpiration'], 0, 2);
		$y = substr($this->user_data['CreditCardData']['CardExpiration'], 2);
		$exp = mktime(0, 0, 0, $m, 1, $y);
		
		// make sure we have a card on file first
		if(empty($this->user_data['CreditCardData']['CardNumber'])) return false;
		// check for expired card
		elseif($exp < strtotime('+1 month')) return false;
		// check card number syntax
		elseif(!\Transaction::luhn_test($this->user_data['CreditCardData']['CardNumber'])) return false;
		// do a hard verification (run an AUTH transaction)
		elseif($run_auth) return $this->auth();
		// soft verification passed
		else return TRUE;
	}
	

	public function successful() {
		return ($this->response->RESULT_CODE == 'A');
	}
}


class Sale_Transaction extends Base_Transaction {
	
	protected $method = 'runSale';
	protected $auth_method = 'runAuthOnly';
	
	public function run($amount, $order_num = NULL) {
		
		$this->user_data['Details']['Invoice']  = ($order_num === NULL)? rand() : $order_num;
		$this->user_data['Details']['Amount']   = $amount;
		
		$params = array(
			'Token' => $this->security_token(),
			'Params' => $this->user_data
		);

		// run the transaction.
		$this->response_xml = \Soap::call($this->method, $params, self::$settings['wsdl'], self::$settings['dbg']);
		$this->parse_response($this->response_xml);
		
		// approved?
		return ($this->response->RESULT_CODE == 'A');
		
	}
	
		
	public function auth($amount = 0.01) {
		
		$this->user_data['TransactionDetail'] = array(
			'Amount'  => $amount,
			'Currency' => 'USD',
		);
		
		$params = array(
			'Token' => $this->security_token(),
			'Params' => $this->user_data
		);

		// run the transaction.
		$this->response_xml = \Soap::call($this->auth_method, $params, self::$settings['wsdl']);
		$this->parse_response($this->response_xml);
		
		// approved?
		return ($this->response->RESULT_CODE == 'A');
		
	}
	

}


class Auth_Transaction extends Sale_Transaction {
	protected $method = 'runAuthOnly';
}


class Credit_Transaction extends Sale_Transaction {
	protected $method = 'runCredit';
}


class Prior_Auth_Sale_Transaction extends Sale_Transaction {
	protected $method = 'captureTransaction';
	
	public function run($amount, $ref) {
		
		$params = array(
			'Token' => $this->security_token(),
			'RefNum' => $ref,
			'Amount' => $amount,
		);

		// run the transaction.
		$this->response_xml = \Soap::call($this->method, $params, self::$settings['wsdl']);
		$this->parse_response($this->response_xml);
		
		// approved?
		return ($this->response->RESULT_CODE == 'A');
	}
	
}


class Void_Transaction extends Sale_Transaction {
	protected $method = 'voidTransaction';
	
	public function run($ref) {
		
		$params = array(
			'Token' => $this->security_token(),
			'RefNum' => $ref,
		);

		// run the transaction.
		$this->response_xml = \Soap::call($this->method, $params, self::$settings['wsdl']);
		$this->parse_response($this->response_xml);
		
		// approved?
		return ($this->response->RESULT_CODE == 'A');
	}
	
}

