<?php

namespace Stripe_Transaction;

# Paperless Payment Gateway base class
abstract class Base_Transaction {
	
	public $user_data = array();
	public $response;
	public $response_object;
	
	protected $method;
	
	protected static $settings;
	
	# Approval Response Codes
	public static $C_APPROVAL = array();
	
	# Response Codes
	public static $C_RESULT = array();
	
	# CVV Response Codes
	public static $C_CVV = array();
	
	# AVS Response Codes
	public static $C_AVS = array();

	public static function Initialize(array $settings) {
		self::$settings = $settings;
		core_load_object('Stripe');
	}
	
	public function __construct($userId, $xlate = array()) {
		
		\Stripe::setApiKey(self::$settings['mkey']);
		
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
			'card' => array(
				'number' => $this->cc_num,
				'exp_month' => sprintf('%02d', (int) substr($cc_exp, 0, 2)),
				'exp_year' => sprintf('20%02d', (int) substr($cc_exp, 2)),
				'name' => implode(' ', array($this->fname, $this->lname)),
				'address_line_1' => $this->address,
				'address_city' => $this->city,
				'address_state' => $this->state,
				'address_zip' => $this->zip,
				'address_country' => $this->country,
			),
			'email' => $this->email,
			'description' => implode(' ', array($this->fname, $this->lname)),
		);
		
		if(isset($this->cvv))
			$this->user_data['card']['cvc'] = $this->cvv;
		
	}
	
	public function verify() {
		$m = substr($this->user_data['C_EXP'], 0, 2);
		$y = substr($this->user_data['C_EXP'], 2);
		$exp = mktime(0, 0, 0, $m, 1, $y);
		
		// make sure we have a card on file first
		if(empty($this->user_data['C_CARDNUMBER'])) return false;
		// check for expired card
		elseif($exp < strtotime('+1 month')) return false;
		// check card number syntax
		elseif(!\Transaction::luhn_test($this->user_data['C_CARDNUMBER'])) return false;
		// soft verification passed
		else return TRUE;
	}
	
	public function successful($response_object = NULL) {
		if(!$response_object) {
			$response_object = $this->response_object;
		}
		return (is_object($response_object) && !$response_object instanceof \Exception);
	}

	public function run($call, array $params = NULL) {
		try {
			if(!$params) $params = array('params' => $this->user_data);
			$this->response_object = call_user_func_array($call, $params);
		} catch(\Stripe_Error $e) {
			$this->response_object = $e;
		}
		$this->parse_response($this->response_object);
		return $this->successful($this->response_object);
	}
	
	protected function parse_response($response) {
		$this->response->APPROVAL_INDICATOR = ($this->successful($response))? 'Approved' : 'Declined';
		$this->response->RESULT_CODE		= $this->response->APPROVAL_INDICATOR[0]; // first character
		$this->response->RESULT_MESSAGE		= ($response instanceof \Exception)? $response->getMessage() : '';
		$this->response->CVV_INDICATOR 		= (string) $response->card->cvc_check;
		$this->response->AVS_INDICATOR 		= (string) ifempty($response->card->address_line1_check, $response->card->address_zip_check);
		$this->response->REFERENCE 			= (string) $response->id;
	}
	
}


abstract class Vault_Transaction extends Base_Transaction {
	
	protected function prep_user_data() {
		parent::prep_user_data();
		
		if(!isset($this->guid)) {
			$guid = $this->create_card_profile();
			if($guid !== FALSE) {
				$this->guid = $guid;
				$this->cc_num = \Transaction::mask_cc($this->user_data['card']['number']);
			} else {
				$e = new \VaultTransactionException($this->response->RESULT_MESSAGE);
				$e->transaction = $this;
				throw $e;
			}
		}
		
		$this->user_data['customer'] = $this->guid;
		unset($this->user_data['card']);
	}
	
	public function create_card_profile() {
		if(parent::run(array('\\Stripe_Customer', 'create'))) {
			$guid = (string) $this->response_object->id;
			return ($guid)? $guid : FALSE;
		} else {
			return FALSE;
		}
	}
	
}


class Sale_Transaction extends Base_Transaction {
	
	protected $method = array('\\Stripe_Charge', 'create');
	
	public function run($amount, $description = '') {
		$this->user_data['amount'] = round($amount * 100);
		$this->user_data['currency'] = 'usd';
		$this->user_data['description'] = $description;
		return $this->run($this->method);
	}
		
	public function auth($amount = 0.01) {
		throw new \TransactionException('AUTH transactions are not implemented in the Stripe API');
	}
	
}


class Prior_Auth_Sale_Transaction extends Sale_Transaction {
	
	public function run($amount, $authorization) {
		throw new \TransactionException('AUTH transactions are not implemented in the Stripe API');
	}
	
}


class Vault_Sale_Transaction extends Vault_Transaction {
	
	protected $method = array('\\Stripe_Charge', 'create');
	
	public function run($amount, $description = '') {
		$this->user_data['amount'] = round($amount * 100);
		$this->user_data['currency'] = 'usd';
		$this->user_data['description'] = $description;
		return parent::run($this->method);
	}
		
	public function auth($amount = 0.01) {
		throw new \TransactionException('AUTH transactions are not implemented in the Stripe API');
	}
	
}


class Auth_Transaction extends Base_Transaction {
	
	public function run($amount = 0.01) {
		throw new \TransactionException('AUTH transactions are not implemented in the Stripe API');
	}
	
}


class Credit_Transaction extends Base_Transaction {
	
	protected $method = array('\\Stripe_Charge', 'refund');
	
	public function run($amount, $transaction_id) {
		$this->user_data['amount'] = $amount;
		$this->user_data['id'] = $transaction_id;
		return parent::run($this->method);
	}

}

