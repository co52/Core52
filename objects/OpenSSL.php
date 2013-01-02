<?php

class OpenSSLException extends Exception {}
class OpenSSLEncryptException extends OpenSSLException {}
class OpenSSLDecryptException extends OpenSSLException {}
class OpenSSLKeyException extends OpenSSLException {}

class OpenSSL {
	
	protected $public_key;
	protected $private_key;
	
	public static function generate_key_pair($passphrase, $bits = 2048, $type = OPENSSL_KEYTYPE_RSA) {
		
		// generate a 1024 bit rsa private key, returns a php resource, save to file
		$key = openssl_pkey_new(array(
			'private_key_bits' => $bits,
			'private_key_type' => $type,
		));
		if($key === FALSE) {
			// try again
			$key = openssl_pkey_new(array(
				'private_key_bits' => $bits,
				'private_key_type' => $type,
				'config' => Config::get('openssl_conf'),
			));
			
			if($key === FALSE) {
				throw new Exception('Could not generate an OpenSSL key pair');
			}
		}
		
		// export the private key as a string
		if(!openssl_pkey_export($key, $private_key_string, $passphrase)) {
			if(!openssl_pkey_export($key, $private_key_string, $passphrase, array(
				'config' => Config::get('openssl_conf'),
			))) {
				throw new Exception('Could not export the private key');
			}
		}
		 
		// export the public key as a string
		$keyDetails = openssl_pkey_get_details($key);
		$public_key_string = $keyDetails['key'];
		
		return array(
			'public_key' => $public_key_string,
			'private_key' => $private_key_string,
		);
	}
	
	public function __construct($public_key = NULL, $private_key = NULL, $passphrase = 'passphrase') {
		if($public_key) {
			$this->load_public_key($public_key);
		}
		if($private_key) {
			$this->load_private_key($private_key, $passphrase);
		}
	}
	
	public function load_public_key($public_key) {
		$this->public_key = openssl_pkey_get_public($public_key);
		if($this->public_key === FALSE) {
			throw new OpenSSLKeyException(openssl_error_string());
		}
	}
	
	public function load_private_key($private_key, $passphrase) {
		$this->private_key = openssl_pkey_get_private($private_key, $passphrase);
		if($this->private_key === FALSE) {
			throw new OpenSSLKeyException(openssl_error_string());
		}
	}
	
	public function encrypt($content, $base64_encode = TRUE) {
		if(openssl_public_encrypt($content, $encrypted, $this->public_key)) {
			return ($base64_encode)? base64_encode($encrypted) : $encrypted;
		} else {
			throw new OpenSSLEncryptException(openssl_error_string());
		}
	}
	
	public function decrypt($content, $base64_decode = TRUE) {
		if($base64_decode) $content = base64_decode($content);
		if(openssl_private_decrypt($content, $decrypted, $this->private_key)) {
			return $decrypted;
		} else {
			throw new OpenSSLDecryptException(openssl_error_string());
		}
	}
	
}