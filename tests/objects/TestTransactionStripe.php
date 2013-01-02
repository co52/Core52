<?php

require_once 'PHPUnit/Autoload.php';

define('PATH_CORE', '../../');
require_once PATH_CORE.'helpers/sys-functions.php';

define('PATH_CORE_OBJECTS', PATH_CORE.'objects/');
require_once PATH_CORE_OBJECTS.'Transaction.php';
require_once PATH_CORE_OBJECTS.'Stripe.php';


/**
 *  test case.
 */
class TestTransactionPaperless extends PHPUnit_Framework_TestCase {
	
	protected $user_data = array(
		'fname' => 'John',
		'lname' => 'Public',
		'address' => '1234 Main Street',
		'city' => 'Irving',
		'state' => 'TX',
		'zip' => '99999',
		'cc_num' => '4012888888881881',
		'cc_exp' => '0520',
	);
		
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp();
		
		Transaction::Initialize(array(
			'gateway' => 'stripe',
			'mkey' => '5Q2ItvM8oeqa4f1f0YI5R8bE8crcRSZe',
			'test_mode' => TRUE,
		));
	}
	
	
	public function provider() {
		return array(
			array(
				'sale',
				'Sale_Transaction',
				array('amount' => 20.00),
			),
			array(
				'vault_sale',
				'Vault_Sale_Transaction',
				array('amount' => 20.00),
			),
			#array(
			#	'auth',
			#	'Auth_Transaction',
			#	array('amount' => 20.00),
			#),
		);
	}
	
	
	/**
	 * @dataProvider provider
	 */
	public function testTransaction($type, $class, $params) {
		
		$transaction = Transaction::factory($this->user_data, $type);
		
		$this->assertInstanceOf($class, $transaction);
		
		$result = call_user_func_array(array($transaction, 'run'), $params);
		
		$this->assertTrue($result);
		$this->assertInternalType('object', $transaction->response_object);
		$this->assertInternalType('object', $transaction->response);
	}
	
	/*
	public function testCreditTransaction() {
		
		$transaction = Transaction::factory($this->user_data, 'vault_sale');
		$this->assertInstanceOf('Vault_Sale_Transaction', $transaction);
		
		$result = $transaction->run(100.00);
		$this->assertTrue($result);
		
		$id = $transaction->response_object->id;
		$this->assertFalse(empty($id));
		
		$transaction = Transaction::factory($this->user_data, 'credit');
		$this->assertInstanceOf('Credit_Transaction', $transaction);
		
		$result = $transaction->run(-100.00, $id);
		
		$this->assertTrue($result);
		$this->assertInternalType('object', $transaction->response_object);
		$this->assertInternalType('object', $transaction->response);
	}
	*/
	
}

