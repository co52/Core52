<?php

define('PATH_CORE', dirname(dirname(dirname(__FILE__))));
require_once PATH_CORE.'/objects/Password.php';

/**
 * Password test case.
 */
class PasswordTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * @var Password
	 */
	private $Password;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		$this->Password = new Password(12, '1234567890qwrypasdfghnz');
	}
	
	/**
	 * Tests Password->__toString()
	 */
	public function test__toString() {
		$password = $this->Password->__toString();
		$this->assertRegExp('/[1234567890qwrypasdfghnz]+/', $password);
		$this->assertEquals(12, strlen($password));
	}
	
	/**
	 * Tests Password::generate()
	 */
	public function testGenerate() {
		$password = Password::generate(12, '1234567890qwrypasdfghnz');
		$this->assertRegExp('/[1234567890qwrypasdfghnz]+/', $password);
		$this->assertEquals(12, strlen($password));
	}
	
	/**
	 * Tests Password->hash()
	 * @depends test__toString
	 */
	public function testHash() {
		$this->assertEquals(hash('sha256', 'asdf1234'), Password::hash('asdf', '1234', 'sha256'));
		$this->assertEquals(hash('md5', 'asdf1234'), Password::hash('asdf', '1234', 'md5'));
		$this->assertEquals(hash('sha256', 'asdf'.$this->Password->__toString()), $this->Password->hash('asdf'));
	}

}

