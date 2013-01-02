<?php

namespace Sage_Transaction;

# Sage Payment Gateway base class
abstract class Base_Transaction {
	
	public $user_data = array();
	public $response;
	
	protected $trans_type;
	protected $method;
	
	protected static $settings;
	
	# Transaction Type Codes
	public static $C_TRANS = array(
		"sale" => "01",
		"auth" => "02",
		"authsale" => "03",
		"void" => "04",
		"credit" => "06",
		"authsaleref" => "11",
	);
	
	# Approval Response Codes
	public static $C_APPROVAL = array(
		"A" => "Approved",
		"E" => "NOT Approved - Front-end error",
		"X" => "NOT Approved - Gateway error",
	);
	
	# Response Codes
	public static $C_RESULT = array(
		"000000" => "Gateway Server Error",
		"000001" => "Approved",
		"900000" => "Invalid Order Number",
		"900001" => "Invalid Name",
		"900002" => "Invalid Address",
		"900003" => "Invalid City",
		"900004" => "Invalid State",
		"900005" => "Invalid Zipcode",
		"900006" => "Invalid Country",
		"900007" => "Invalid Telephone",
		"900008" => "Invalid Fax",
		"900009" => "Invalid Email",
		"900010" => "Invalid Shipping Name",
		"900011" => "Invalid Shipping Address",
		"900012" => "Invalid Shipping City",
		"900013" => "Invalid Shipping State",
		"900014" => "Invalid Shipping Zipcode",
		"900015" => "Invalid Shipping Country",
		"900016" => "Invalid Cardnumber",
		"900017" => "Invalid Card Expiration Date",
		"900018" => "Invalid CVV Code",
		"900019" => "Invalid Transaction Amount",
		"900020" => "Invalid Transaction Code",
		"900021" => "Invalid T_Auth",
		"900022" => "Invalid Transaction Reference Code",
		"900023" => "Invalid Transaction Trackdata",
		"900024" => "Invalid Tracking Number",
		"900025" => "Invalid Customer Number",
		"910000" => "Service Not Allowed",
		"910001" => "Visa Not Allowed",
		"910002" => "MasterCard Not Allowed",
		"910003" => "American Express Not Allowed",
		"910004" => "Discover Not Allowed",
		"910005" => "Card Type Not allowed",
		"911911" => "Security Violation",
		"920000" => "Item Not Found",
		"920001" => "Credit Volume Exceeded",
		"920002" => "AVS Failure",
		"999999" => "Internal Service Error",
	);
	
	# CVV Response Codes
	public static $C_CVV = array(
		"M" => "Match",
		"N" => "No match",
		"P" => "Not processed",
		"S" => "Merchant indicates no CVV present",
		"U" => "Issuer has not provided CVV2",
	);
	
	# AVS Response Codes
	public static $C_AVS = array(
		"X" => "Exact match on address+zip9",
		"Y" => "Match on address+zip5",
		"A" => "Address matches, zip does not",
		"W" => "9 digit zip matches, address does not",
		"Z" => "5 digit zip matches, address does not",
		"N" => "Neither zip or address matches",
		"U" => "Unavailable",
		"R" => "Retry",
		"E" => "Error",
		"S" => "Service not supported",
	);

	# Risk response codes
	public static $C_RISK = array(
		"01" => "Max sale exceeded",
		"02" => "Min sale not met",
		"03" => "1 day volume exceeded",
		"04" => "1 day usage exceeded",
		"05" => "3 day volume exceeded",
		"06" => "3 day usage exceeded",
		"07" => "15 day volume exceeded",
		"08" => "15 day usage exceeded",
		"09" => "30 day volume exceeded",
		"10" => "30 day usage exceeded",
		"11" => "Stolen or lost card",
		"12" => "AVS failure",
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
	
	
	public function run($amount) {
		throw new \Exception('Base_Transaction::run() not implemented');
	}
	
	
	protected function prep_user_data() {
		$this->user_data['T_CODE']		= Base_Transaction::$C_TRANS[$this->trans_type];
		$this->user_data['M_ID'] 		= self::$settings['mid']; 		// merchant id.
		$this->user_data['M_KEY'] 		= self::$settings['mkey']; 		// merchant key;
		$this->user_data['C_NAME']		= "$this->fname $this->lname";
		$this->user_data['C_ADDRESS'] 	= $this->address;
		$this->user_data['C_CITY']		= $this->city;
		$this->user_data['C_STATE'] 	= $this->state;
		$this->user_data['C_ZIP'] 		= $this->zip;
		if(isset($this->country)) $this->user_data['C_COUNTRY'] = $this->country;
		$this->user_data['C_EMAIL'] 	= $this->email; 	// despite documentation, email is not required in testing.  Check production.
		$this->user_data['C_CARDNUMBER']= $this->cc_num;
		$this->user_data['C_EXP'] 		= (is_numeric($this->cc_exp) && strlen($this->cc_exp) == 4)? $this->cc_exp : date('my', strtotime($this->cc_exp));
		if(isset($this->cvv)) $this->user_data['C_CVV'] = $this->cvv;
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
		return ($this->response->APPROVAL_INDICATOR == 'Approved');
	}
}


class Sale_Transaction extends Base_Transaction {
	
	public $wsdl = 'https://www.sagepayments.net/web_services/vterm_extensions/transaction_processing.asmx?WSDL';
	
	protected $trans_type = 'sale';
	protected $method = 'BANKCARD_SALE';
	protected $auth_method = 'BANKCARD_AUTHONLY';
	
	public function run($amount, $order_num = NULL) {
		
		$this->user_data['T_AMT'] = $amount;
		$this->user_data['T_ORDERNUM'] = ($order_num === NULL)? rand() : $order_num;

		// run the transaction.
		$this->response_xml = \Soap::call($this->method, $this->user_data, $this->wsdl);

		// parse the response
		$resp = new \SimpleXMLElement($this->response_xml->{$this->method.'Result'}->any);
		$resp = $resp->NewDataSet->Table1;
		$this->response->APPROVAL_INDICATOR 	= Base_Transaction::$C_APPROVAL[(string) $resp->APPROVAL_INDICATOR];
		$this->response->RESULT_CODE		 	= (string) $resp->CODE;
		$this->response->RESULT_MESSAGE			= (isset(Base_Transaction::$C_RESULT[(string) $resp->CODE]))? Base_Transaction::$C_RESULT[(string) $resp->CODE] : (string) $resp->MESSAGE;
		$this->response->CVV_INDICATOR 			= Base_Transaction::$C_CVV[(string) $resp->CVV_INDICATOR];
		$this->response->AVS_INDICATOR 			= Base_Transaction::$C_AVS[(string) $resp->AVS_INDICATOR];
		$this->response->RISK_INDICATOR 		= Base_Transaction::$C_RISK[(string) $resp->RISK_INDICATOR];
		$this->response->REFERENCE 				= (string) $resp->REFERENCE;
		$this->response->ORDER_NUMBER 			= (string) $resp->ORDER_NUMBER;

		// approved?
		return ((string) $resp->APPROVAL_INDICATOR == 'A');
		
	}
		
	public function auth($amount = 0.01) {
		
		$this->user_data['T_AMT'] = $amount;
		
		// run the transaction.
		$this->response_xml = \Soap::call($this->auth_method, $this->user_data, $this->wsdl);

		// parse the response
		$resp = new \SimpleXMLElement($this->response_xml->{$this->auth_method.'Result'}->any);
		$resp = $resp->NewDataSet->Table1;
		$this->response->APPROVAL_INDICATOR 	= Base_Transaction::$C_APPROVAL[(string) $resp->APPROVAL_INDICATOR];
		$this->response->RESULT_CODE		 	= (string) $resp->CODE;
		$this->response->REFERENCE 				= (string) $resp->REFERENCE;
		
		// approved?
		return ((string) $resp->APPROVAL_INDICATOR == 'A');
		
	}
	
}


class Vault_Transaction extends Sale_Transaction {
	
	public $wsdl = 'https://www.sagepayments.net/web_services/wsVault/wsVaultBankcard.asmx?WSDL';
	
	protected function prep_user_data() {
		parent::prep_user_data();
		
		if(!isset($this->guid)) {
			if(Card_Vault::check_service()) {
				$this->guid = Card_Vault::put_card($this->user_data['C_CARDNUMBER'], $this->user_data['C_EXP']);
				$this->cc_num = \Transaction::mask_cc($this->user_data['C_CARDNUMBER']);
			} else {
				throw new \Exception("Card Vault Service not enabled");
			}
		}
		
		$this->user_data['GUID'] = $this->guid;
		
		unset($this->user_data['C_CARDNUMBER']);
		unset($this->user_data['C_EXP']);
		unset($this->user_data['C_CVV']);
	}
	
}


class Auth_Transaction extends Sale_Transaction {
	protected $method = 'BANKCARD_AUTHONLY';
}


class Credit_Transaction extends Sale_Transaction {
	protected $method = 'BANKCARD_CREDIT';
}


class Prior_Auth_Sale_Transaction extends Sale_Transaction {
	protected $method = 'BANKCARD_PRIOR_AUTH_SALE';
}


class Force_Transaction extends Sale_Transaction {
	protected $method = 'BANKCARD_FORCE';
}


class Void_Transaction extends Sale_Transaction {
	protected $method = 'BANKCARD_VOID';
}


class Vault_Sale_Transaction extends Vault_Transaction {
	protected $method = 'VAULT_BANKCARD_SALE';
}


class Vault_Auth_Transaction extends Vault_Transaction {
	protected $method = 'VAULT_BANKCARD_AUTHONLY';
}


class Vault_Credit_Transaction extends Vault_Transaction {
	protected $method = 'VAULT_BANKCARD_CREDIT';
}


class Vault_Force_Transaction extends Vault_Transaction {
	protected $method = 'VAULT_BANKCARD_FORCE';
}




class Card_Vault {
	
	public static $wsdl = 'https://www.sagepayments.net/web_services/wsVault/wsVault.asmx?WSDL';
	private static $soap = FALSE;
	
	
	private static function soap_call($method, $data) {
		// run the transaction.
		if(!self::$soap) self::$soap = new \Soap(self::$wsdl);
		$response_xml = self::$soap->soap_call($method, $data);
		return (string) $response_xml->{$method.'Result'};
	}
		

	public static function check_service() {
		$data['M_ID'] = self::$settings['mid']; 		// merchant id.
		$data['M_KEY'] = self::$settings['mkey']; 	// merchant key;
		return (self::soap_call('VERIFY_SERVICE', $data) == '1');
	}
	
	
	public static function get_data($guid) {
		$data['M_ID'] = self::$settings['mid']; 		// merchant id.
		$data['M_KEY'] = self::$settings['mkey']; 	// merchant key;
		$data['GUID'] = $guid;
		$result = self::soap_call('SELECT_DATA', $data);
		return (strlen($result) > 0)? explode('|', $result) : FALSE;
	}
	
	
	public static function put_data($val, $guid = NULL) {
		
		$data['M_ID'] = self::$settings['mid']; 		// merchant id.
		$data['M_KEY'] = self::$settings['mkey']; 	// merchant key;
		$data['DATA'] = $val;
		if($guid !== NULL) {
			# update
			$data['GUID'] = $guid;
			$method = 'UPDATE_DATA';
			return (self::soap_call($method, $data) == 1);
		} else {
			# insert
			$method = 'INSERT_DATA';
			$result = self::soap_call($method, $data);
			return (strlen($result) > 0)? $result : FALSE;
		}
		
	}
	
	
	public static function delete_data($guid) {
		$data['M_ID'] = self::$settings['mid']; 		// merchant id.
		$data['M_KEY'] = self::$settings['mkey']; 	// merchant key;
		$data['GUID'] = $guid;
		return (self::soap_call('DELETE_DATA', $data) == 1);
	}
	
	
	public static function put_card($cc, $exp, $guid = NULL) {
		
		if(!self::$soap) self::$soap = new \Soap(self::$wsdl);
		
		$data['M_ID'] = self::$settings['mid']; 		// merchant id.
		$data['M_KEY'] = self::$settings['mkey']; 	// merchant key;
		$data['CARDNUMBER'] = $cc;
		$data['EXPIRATION_DATE'] = (is_numeric($exp) && strlen($exp) == 4)? $exp : date('my', strtotime($exp));
		
		if($guid !== NULL) {
			# update
			$data['GUID'] = $guid;
			$result = self::$soap->soap_call('UPDATE_CREDIT_CARD_DATA', $data);
			$xml = new \SimpleXMLElement($result->UPDATE_CREDIT_CARD_DATAResult->any);
			if($xml->NewDataSet->Table1->MESSAGE != 'SUCCESS') {
				throw new \Exception($xml->NewDataSet->Table1->MESSAGE);
			}
			return ($xml->NewDataSet->Table1->SUCCESS == 'true');
		} else {
			# insert
			$result = self::$soap->soap_call('INSERT_CREDIT_CARD_DATA', $data);
			$xml = new \SimpleXMLElement($result->INSERT_CREDIT_CARD_DATAResult->any);
			return ($xml->NewDataSet->Table1->SUCCESS == 'true')? (string) $xml->NewDataSet->Table1->GUID : FALSE;
		}
		
	}
	
	
}
