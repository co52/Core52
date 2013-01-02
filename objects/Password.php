<?php

/**
 * Password class for generating and manipulating passwords
 *
 * @author Jonathon Hill <jhill@company52.com>
 * @package Core52
 * @version 1.0
 *
 */
class Password {
	
	/**
	 * A password string generated in the constructor
	 *
	 * @var $password string
	 */
	protected $password;
	
	/**
	 * PHP5 magic method to cast a Password object in string format
	 *
	 * @return string
	 */
	public function __toString() {
		return (string) $this->password;
	}
	
	/**
	 * Generates a random password
	 *
	 * @param integer $length = 8 Password length
	 * @param string $chars = '1234567890qwrypasdfghnz' Characters to use to generate the password (defaults to numbers and unambiguous letters)
	 */
	public function __construct($length = 8, $chars = '1234567890qwrypasdfghnz') {
		$this->password = $this->generate($length, $chars);
	}
	
	/**
	 * Generates a random password
	 *
	 * @param integer $length = 8 Password length
	 * @param string $chars = '1234567890qwrypasdfghnz' Characters to use to generate the password (defaults to numbers and unambiguous letters)
	 * @return string
	 */
	public static function generate($length = 8, $chars = '1234567890qwrypasdfghnz') {
		
		$range_start = 0;
		$range_end = strlen($chars) - 1;
		
		$password = array();
		for($i = 0; $i < $length; $i++) {
			$c = rand($range_start, $range_end);
			$password[$i] = $chars[$c];
		}
		
		return implode('', $password);
	}
	
	/**
	 * Hashes a password with a salt for secure storage
	 *
	 * @param string|boolean $salt = FALSE Salt string, uses Config::get('salt') if omitted
	 * @param string|boolean $password = FALSE Password string, uses the password generated in Password::__construct() if omitted
	 * @param string $method = 'sha256' Hashing method, must be a valid PHP hashing algorithm name
	 * @return string
	 */
	public function hash($salt = FALSE, $password = FALSE, $method = 'sha256') {
		
		if($salt === FALSE) {
			$salt = Config::get('salt');
		}
		
		if($password === FALSE) {
			$password = $this->password;
		}
		
		return hash($method, $salt.$password);
	}
	
}


