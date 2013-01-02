<?php

require_once 'PHPUnit/Autoload.php';

define('PATH_CORE', '../../');
require_once PATH_CORE.'helpers/sys-functions.php';

define('PATH_CORE_OBJECTS', PATH_CORE.'objects/');
require_once PATH_CORE_OBJECTS.'Transaction.php';
require_once PATH_CORE_OBJECTS.'Soap.php';


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
			'gateway' => 'paperless',
			'mid' => '28ffcd0e-c3a8-46d2-befb-1cbe4f47e649',
			'mkey' => '997378435',
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
			array(
				'auth',
				'Auth_Transaction',
				array('amount' => 20.00),
			),
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
		$this->assertInternalType('object', $transaction->response_xml);
		$this->assertInternalType('object', $transaction->response);
	}
	
	
	public function testPriorAuthSaleTransaction() {
		
		$transaction = Transaction::factory($this->user_data, 'auth_sale');
		$this->assertInstanceOf('Prior_Auth_Sale_Transaction', $transaction);
		
		$result = $transaction->auth(100.00);
		$this->assertTrue($result);
		
		$authorization = $transaction->response_xml->AuthorizeCardResult->AuthorizationNumber;
		$this->assertFalse(empty($authorization));
		
		$result = $transaction->run(100.00, $authorization);
		
		$this->assertTrue($result);
		$this->assertInternalType('object', $transaction->response_xml);
		$this->assertInternalType('object', $transaction->response);
	}
	
	
	public function testCreditTransaction() {
		
		$transaction = Transaction::factory($this->user_data, 'vault_sale');
		$this->assertInstanceOf('Vault_Sale_Transaction', $transaction);
		
		$result = $transaction->run(100.00);
		$this->assertTrue($result);
		
		$id = $transaction->response_xml->ProcessCardResult->TransactionID;
		$this->assertFalse(empty($id));
		
		$transaction = Transaction::factory($this->user_data, 'credit');
		$this->assertInstanceOf('Credit_Transaction', $transaction);
		
		$result = $transaction->run(-100.00, $id);
		
		$this->assertTrue($result);
		$this->assertInternalType('object', $transaction->response_xml);
		$this->assertInternalType('object', $transaction->response);
	}
	
	
}

