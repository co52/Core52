<?php

namespace Paperless_Transaction;

# Paperless Payment Gateway base class
abstract class Base_Transaction {
	
	public $user_data = array();
	public $response;
	
	public static $wsdl = 'https://svc.paperlesstrans.com:9999/?wsdl';
	protected $method;
	
	protected static $settings;
	
	# Approval Response Codes
	public static $C_APPROVAL = array(
		"0" => "Approved",
		"1" => "Communication error",
		"2" => "System error",
	);
	
	# Response Codes
	public static $C_RESULT = array(

	);
	
	# CVV Response Codes
	public static $C_CVV = array(
		"M" => "Match",
		"N" => "No match",
		"P" => "Not processed",
		"S" => "Merchant indicates no CVV present",
		"U" => "Issuer has not provided CVV2",
		"I" => "Invalid",
		"Y" => "Invalid",
		"" => "Not applicable (non-Visa)"
	);
	
	# AVS Response Codes
	public static $C_AVS = array(
		"1" => "No address supplied",
		"2" => "Bill-to address did not pass Auth Host edit checks",
		"3" => "AVS not performed",
		"4" => "Issuer does not participate in AVS",
		"R" => "Issuer does not participate in AVS",
		"5" => "Edit-error - AVS data is invalid",
		"6" => "System unavailable or time-out",
		"7" => "Address information unavailable",
		"8" => "Transaction Ineligible for AVS",
		"9" => "Zip Match / Zip4 Match / Locale match",
		"A" => "Zip Match / Zip 4 Match / Locale no match",
		"B" => "Zip Match / Zip 4 no Match / Locale match",
		"C" => "Zip Match / Zip 4 no Match / Locale no match",
		"D" => "Zip No Match / Zip 4 Match / Locale match",
		"E" => "Zip No Match / Zip 4 Match / Locale no match",
		"F" => "Zip No Match / Zip 4 No Match / Locale match",
		"G" => "No match at all",
		"H" => "Zip Match / Locale match",
		"J" => "Issuer does not participate in Global AVS",
		"JA" => "International street address and postal match",
		"JB" => "International street address match. Postal code not verified.",
		"JC" => "International street address and postal code not verified.",
		"JD" => "International postal code match. Street address not verified.",
		"M1" => "Cardholder name matches",
		"M2" => "Cardholder name, billing address, and postal code matches",
		"M3" => "Cardholder name and billing code matches",
		"M4" => "Cardholder name and billing address match",
		"M5" => "Cardholder name incorrect, billing address and postal code match",
		"M6" => "Cardholder name incorrect, billing address matches",
		"M7" => "Cardholder name incorrect, billing address matches",
		"M8" => "Cardholder name, billing address and postal code are all incorrect",
		"N3" => "Address matches, ZIP not verified.",
		"N4" => "Address and ZIP code match (International only)",
		"N5" => "Address not verified (International only)",
		"N6" => "Address and ZIP code match (International only)",
		"N7" => "ZIP matches, address not verified",
		"N8" => "Address and ZIP code match (International only)",
		"UK" => "Unknown",
		"X" => "Zip Match / Zip 4 Match / Address Match",
		"Z" => "Zip Match / Locale no match",
		"" => "Not applicable (non-Visa)",
	);

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
	
	protected function prep_user_data() {
		
		# normalize the expiration date to MMYY format
		$cc_exp = (is_numeric($this->cc_exp) && strlen($this->cc_exp) == 4)? $this->cc_exp : format_date($this->cc_exp, 'my');
		
		$this->user_data = array(
			"req" => array(
				"Token" => array(
					"TerminalID" => self::$settings['mid'],
					"TerminalKey" => self::$settings['mkey'],
				),
				"Card" => array(
					"CardNumber" => $this->cc_num,
					"ExpirationMonth" => sprintf('%02d', (int) substr($cc_exp, 0, 2)),
					"ExpirationYear" => sprintf('20%02d', (int) substr($cc_exp, 2)),
					"NameOnAccount" => "$this->fname $this->lname",
					"Address" => array(
						"Street" => $this->address,
						"City" => $this->city,
						"State" => $this->state,
						"Zip" => $this->zip,
						"Country" => $this->country,
					),
				)
			)
		);
		
		# test mode?
		if(self::$settings['test_mode']) {
			$this->user_data['req']['TestMode'] = "True";
		}
		
		# card present?
		if(isset($this->cvv)) {
			$this->user_data['req']['CardPresent'] = 'True';
			$this->user_data['req']['Card']['SecurityCode'] = $this->cvv;
		} else {
			$this->user_data['req']['CardPresent'] = 'False';
		}
		
	}
	
	protected function parse_response($response) {
		// parse the response
		$this->response->APPROVAL_INDICATOR 	= (string) $response->IsApproved;
		$this->response->RESULT_CODE		 	= (string) $response->ResponseCode;
		$this->response->RESULT_MESSAGE			= (string) $response->Message;
		$this->response->REFERENCE 				= (string) $response->Authorization;
		$this->response->TRANSACTION_ID			= (string) $response->TransactionID;
	}
	
	public function verify($run_auth = FALSE) {
		$m = substr($this->user_data['C_EXP'], 0, 2);
		$y = substr($this->user_data['C_EXP'], 2);
		$exp = mktime(0, 0, 0, $m, 1, $y);
		
		// make sure we have a card on file first
		if(empty($this->user_data['C_CARDNUMBER'])) return false;
		// check for expired card
		elseif($exp < strtotime('+1 month')) return false;
		// check card number syntax
		elseif(!\Transaction::luhn_test($this->user_data['C_CARDNUMBER'])) return false;
		// do a hard verification (run an AUTH transaction)
		elseif($run_auth) return $this->auth();
		// soft verification passed
		else return TRUE;
	}
	
	public function successful() {
		return ($this->response->RESULT_CODE == 0 && strtoupper($this->response->APPROVAL_INDICATOR) == 'TRUE');
	}

	public function run($method) {

		// run a transaction
		$this->response_xml = \Soap::call($method, array('parameters' => $this->user_data), self::$wsdl, self::$settings['debug']);

		// parse the response
		$response = $this->response_xml->{$method.'Result'};
		$this->parse_response($response);
		
		return $this->successful();
	}
	
}


abstract class Vault_Transaction extends Base_Transaction {
	
	protected function prep_user_data() {
		parent::prep_user_data();
		
		if(!isset($this->guid)) {
			$guid = $this->create_card_profile();
			if($guid !== FALSE) {
				$this->guid = $guid;
				$this->cc_num = \Transaction::mask_cc($this->user_data['req']['Card']['CardNumber']);
			} else {
				$e = new \VaultTransactionException($this->response->RESULT_MESSAGE);
				$e->transaction = $this;
				throw $e;
			}
		}
		
		$this->user_data['req']['CardPresent'] = 'False';
		$this->user_data['req']['ProfileNumber'] = $this->guid;
		unset($this->user_data['req']['Card']);
	}
	
	public function create_card_profile() {
		parent::run('CreateCardProfile');
		$guid = (string) $this->response_xml->CreateCardProfileResult->ProfileNumber;
		return ($this->response->RESPONSE_CODE == 0 && $guid)? $guid : FALSE;
	}
	
	public static function lookup_profile($profile_number, $method = 'LookupCardProfile') {
		return \Soap::call($method, array('req' =>
			array(
				"token" => array(
					"TerminalID" => self::$settings['mid'],
					"TerminalKey" => self::$settings['mkey'],
				),
				"profileNumber" => $profile_number,
			),
		), self::$wsdl, self::$settings['debug']);
	}
	
}


class Sale_Transaction extends Base_Transaction {
	
	protected $method = 'ProcessCard';
	protected $auth_method = 'AuthorizeCard';
	
	public function run($amount) {
		$this->user_data['req']['Amount'] = $amount;
		$this->user_data['req']['Currency'] = 'USD';
		return parent::run($this->method);
	}
		
	public function auth($amount = 0.01) {
		$this->user_data['req']['Amount'] = $amount;
		$this->user_data['req']['Currency'] = 'USD';
		return parent::run($this->auth_method);
	}
	
}


class Prior_Auth_Sale_Transaction extends Sale_Transaction {
	protected $method = 'SettleCardAuthorization';
	
	public function run($amount, $authorization) {
		
		$this->user_data['req']['Authorization'] = $authorization;
		$this->user_data['req']['CaptureAmount'] = $amount;
		
		unset($this->user_data['req']['CardPresent']);
		unset($this->user_data['req']['Card']);
		unset($this->user_data['req']['Currency']);
		
		return parent::run($this->method);
	}
	
}


class Vault_Sale_Transaction extends Vault_Transaction {
	
	protected $method = 'ProcessCard';
	protected $auth_method = 'AuthorizeCard';
	
	public function run($amount) {
		$this->user_data['req']['Amount'] = $amount;
		$this->user_data['req']['Currency'] = 'USD';
		return parent::run($this->method);
	}
		
	public function auth($amount = 0.01) {
		$this->user_data['req']['Amount'] = $amount;
		$this->user_data['req']['Currency'] = 'USD';
		return parent::run($this->auth_method);
	}
	
}


class Auth_Transaction extends Base_Transaction {
	protected $method = 'AuthorizeCard';
	
	public function run($amount = 0.01) {
		$this->user_data['req']['Amount'] = $amount;
		$this->user_data['req']['Currency'] = 'USD';
		return parent::run($this->method);
	}
	
}


class Credit_Transaction extends Base_Transaction {
	protected $method = 'RefundCardTransaction';
	
	public function run($amount, $transaction_id) {
		
		$this->user_data['req']['TransactionID'] = $transaction_id;
		$this->user_data['req']['CreditAmount'] = $amount;
		
		unset($this->user_data['req']['CardPresent']);
		unset($this->user_data['req']['CardProfile']);
		unset($this->user_data['req']['Card']);
		
		return parent::run($this->method);
	}
	
	public function successful() {
		return ($this->response->RESULT_CODE == 0);
	}

}

/* ACH */
abstract class ACH_Vault_Transaction extends Base_Transaction {
	
	protected function prep_user_data() {

		$this->user_data = array(
			"req" => array(
				"Token" => array(
					"TerminalID" => self::$settings['mid'],
					"TerminalKey" => self::$settings['mkey'],
				),
				"Check" => array(
					"NameOnAccount" => "$this->fname $this->lname",
					"RoutingNumber" => $this->routing_number,
					"AccountNumber" => $this->account_number,
					"Address" => array(
						"Street" 	=> $this->address,
						"City"	 	=> $this->city,
						"State"	 	=> $this->state,
						"Zip"	 	=> $this->zip,
						"Country" 	=> "US",
					),
				),
				"Memo" => 'Problem?',
				"ListingName" => "$this->fname $this->lname",
				"CheckNumber" => $this->check_number,
			)
		);
		
		# test mode?
		if(self::$settings['test_mode']) {
			$this->user_data['req']['TestMode'] = "True";
		}
		
		if(!isset($this->guid)) {
			$guid = $this->create_profile();
			if($guid !== FALSE) {
				$this->guid = $guid;
				$this->routing_number = \Transaction::mask_cc($this->user_data['req']['Check']['RoutingNumber']);
				$this->account_number = \Transaction::mask_cc($this->user_data['req']['Check']['AccountNumber']);
				
			} else {
				$e = new \VaultTransactionException($this->response->RESULT_MESSAGE);
				$e->transaction = $this;
				throw $e;
			}
		}
		
		$this->user_data['req']['ProfileNumber'] = $this->guid;
		unset($this->user_data['req']['Check']);

	}
	
	protected function parse_response($response) {
		// parse the response
		$this->response->APPROVAL_INDICATOR 	= (string) $response->IsAccepted;
		$this->response->RESULT_CODE		 	= (string) $response->ResponseCode;
		$this->response->RESULT_MESSAGE			= (string) $response->Message;
		$this->response->REFERENCE 				= (string) $response->Authorization;
		$this->response->TRANSACTION_ID			= (string) $response->TransactionID;
	}
	
	public function create_profile() {
		parent::run('CreateACHProfile');
		$guid = (string) $this->response_xml->CreateACHProfileResult->ProfileNumber;

		return ($this->response->RESPONSE_CODE == 0 && $guid) ? $guid : FALSE;
	}
	
}

class ACH_Vault_Sale_Transaction extends ACH_Vault_Transaction {
	
	protected $method = 'ProcessACH';
	
	public function run($amount) {
		$this->user_data['req']['Amount'] = (string) $amount;
		$this->user_data['req']['Currency'] = 'USD';
		return parent::run($this->method);
	}
		
}

class ACH_Vault_Void_Transaction extends ACH_Vault_Transaction {
	protected $method = 'VoidACHTransaction';
	
	public function run($transaction_id) {
		
		$this->user_data['req']['TransactionID'] = $transaction_id;
		
		unset($this->user_data['req']['Check']);
		unset($this->user_data['req']['ListingName']);
		unset($this->user_data['req']['CheckNumber']);
		
		return parent::run($this->method);
	}
	
	public function successful() {
		return ($this->response->RESULT_CODE == 0);
	}

}


class ACH_Lookup_Transaction extends ACH_Vault_Transaction {
	
	protected $method = 'GetCheckProcessing';
	
	protected function prep_user_data() {
		$this->user_data = array(
			"req" => array(
				"token" => array(
					"TerminalID" => self::$settings['mid'],
					"TerminalKey" => self::$settings['mkey'],
				),
			)
		);
	}
	
	public function run($transaction_id) {
		$this->user_data['req']['transactionID'] = $transaction_id;
		$this->response_xml = \Soap::call($this->method, $this->user_data, self::$wsdl, self::$settings['debug']);
	}
	
}
