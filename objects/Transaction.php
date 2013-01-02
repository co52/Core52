<?php

/**
 * Transaction
 *
 * @author "Jonathon Hill" <jhill@companyfiftytwo.com>
 * @package Core52
 * @version 1.0
 *
 * Here's how you run a transaction using the Sage CardVault service
 *
 * $cc = new CreditCard(10003);
 * $cc->cc_exp = sprintf('%04d-01-%02d', $cc->exp_year, $cc->exp_month);
 * $txn = Transaction::factory($cc, 'vault_sale', array('name' => 'fname', 'street' => 'address'));
 * $txn->run(49.95, 'ORD135253'); // returns TRUE if successful
 *
 * If you need to see the response data you can look at $txn->response
 * If you want to do a non-vault transaction:
 *
 * $txn = Transaction::factory($cc, 'sale', array(...));
 *
 * Explanation of parameters:
 *   Transaction::factory(object/id, trans_type, xlate)
 *     object/id:  a Model object or a numeric ID (if numeric, instantiates an object of class Transaction::$user_model_class which should be set when calling Transaction::Initialize())
 *     trans_type: the type of transaction desired (sale, auth, prior_auth, force, credit, void, vault_sale, vault_auth, vault_credit, vault_force)
 *     xlate:      an array indicating which of your object properties map to which fields needed for the transaction
 *
 * The fields needed for the transaction are:
 *   Required fields for non-vault transactions: fname, lname, address, city, state, zip, email, cc_num, cc_exp (in a format readable by strtotime())
 *   Required fields for vault transactions: fname, lname, address, city, state, zip, email, guid
 *   Optional fields: country, cvv
 *
 **/
class Transaction {
	
	# Accepted CC types
	public static $accept = array('VISA', 'MC', 'AMEX', 'DISC');
	
	protected static $transaction_classes = array(
		'sale' 			=> 'Sale_Transaction',
		'auth' 			=> 'Auth_Transaction',
		'prior_auth'	=> 'Prior_Auth_Sale_Transaction',
		'auth_sale'		=> 'Prior_Auth_Sale_Transaction',
		'force' 		=> 'Force_Transaction',
		'credit' 		=> 'Credit_Transaction',
		'void' 			=> 'Void_Transaction',
		'vault_sale'	=> 'Vault_Sale_Transaction',
		'vault_auth'	=> 'Vault_Auth_Transaction',
		'vault_credit'	=> 'Vault_Credit_Transaction',
		'vault_force'	=> 'Vault_Force_Transaction',
		
		'ach_vault_sale'=> 'ACH_Vault_Sale_Transaction',
		'ach_vault_void'=> 'ACH_Vault_Void_Transaction',
		'ach_lookup'	=> 'ACH_Lookup_Transaction',
	);
	
	protected static $supported_gateways = array(
		'sage' => 'Transaction_Providers/sage.php',
		'usa_epay' => 'Transaction_Providers/usa_epay.php',
		'paperless' => 'Transaction_Providers/paperless.php',
		'stripe' => 'Transaction_Providers/stripe.php',
	);
	
	protected static $default_gateway = 'sage';
	protected static $default_connection = 'default_0';
	protected static $connections = array();
	
	
	# Load up the card data
	public static function Initialize($mid = NULL, $mkey = NULL, $user_model_class = NULL, $wsdl = NULL, $gateway = NULL, $debug = FALSE, $test_mode = FALSE) {
		$args = func_get_args();
		
		if(is_array($args[0])) {
			foreach($args as $i => $connection) {
				if(!isset($connection['name'])) {
					$connection['name'] = 'default_'.$i;
				}
				
				if($connection['default']) {
					self::$default_connection = $connection['name'];
				}
				
				# load the selected gateway transaction classes
				$gateway = ifempty($connection['gateway'], self::$default_gateway);
				if(array_key_exists($gateway, self::$supported_gateways)) {
					require_once(PATH_CORE_OBJECTS.self::$supported_gateways[$gateway]);
					$class = '\\'.ucfirst($gateway).'_Transaction\\Base_Transaction';
					call_user_func(array($class, 'Initialize'), $connection);
				} else {
					throw new InvalidArgumentException("Unsupported gateway: $gateway");
				}
				
				self::$connections[$connection['name']] = $connection;
			}
		}
		
		set_time_limit(0);
	}
	
	
	/**
	 * Create a transaction object
	 *
	 * @param unknown_type $user
	 * @param string $trans_type
	 * @param array $xlate
	 * @return Base_Transaction
	 */
	public static function factory($user, $trans_type, $xlate = array(), $connection = NULL) {
		
		if(!$connection) {
			$connection = self::$default_connection;
		}
		
		if(isset(self::$connections[$connection]['gateway'])) {
			$gateway = self::$connections[$connection]['gateway'];
		} else {
			throw new InvalidArgumentException("Unrecognized connection name: $connection");
		}
		
		if(isset(self::$transaction_classes[$trans_type])) {
			$class = '\\'.ucfirst($gateway).'_Transaction\\'.self::$transaction_classes[$trans_type];
			return new $class($user, $xlate);
		} else {
			throw new InvalidArgumentException("Invalid transaction type $trans_type for gateway $gateway");
		}
	}
	
	
	public static function start_transaction($connection, $trans_type, $user, array $xlate = array()) {
		return self::factory($user, $trans_type, $xlate, $connection);
	}
	

	# This function can handle all domestic and int'l CC transactions, including card auth, sale, and credits
	public static function verify(Base_Transaction $txn, $run_auth = FALSE)
	{
		return $txn->verify($run_auth);
	}


	# This function uses the Luhn algorithm to check if a card number is syntactically valid.
	public static function luhn_test($no) {
		
		// Remove non-digits and reverse
		$s = strrev(preg_replace("/[^\d]/", '', $no));
		
		// compute checksum
		$sum = 0;
		for($i=0, $j=strlen($s); $i < $j; $i++) {
			
			// Use even digits as-is
			if(($i % 2) == 0) {
				
				$val = $s[$i];
				
			} else {
				
				// Double odd digits and subtract 9 if greater than 9
				$val = $s[$i]*2;
				if($val > 9) $val -= 9;
			}
			$sum += $val;
		}
		
		// Number is valid if sum is a multiple of 10
		return ($sum % 10 == 0)? true : false;
	}

	
	/**
	 * Mask a credit card number for security
	 *
	 * @param string $cc
	 * @param string $char = 'x'
	 * @param int $leave = 4
	 * @return string
	 */
	public static function mask_cc($cc, $char = '*', $leave = 4) {
		for($i = 0; $i < strlen($cc) - $leave; $i++) {
			if(is_numeric($cc[$i])) {
				$cc[$i] = $char;
			}
		}
		return $cc;
	}
	
	
	/**
	 * Check if a credit card number has been masked for security
	 *
	 * @param string $cc
	 * @return boolean
	 */
	public static function is_masked_cc($cc) {
		$cc = str_replace(array('-', ' '), array('', ''), $cc);
		return !is_numeric($cc);
	}
		

}


class TransactionException extends Exception {}
class VaultTransactionException extends TransactionException {}


