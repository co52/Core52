<?php

/**
 * Simple wrapper class for mcrypt
 *
 * @author Jonathon Hill
 * @version 1.0
 */
class EncryptionKey {
	
	protected $key;
	protected $cipher;
	protected $mode;
	protected $iv_size;
	protected $iv;
	
	/**
	 * Initialize an encryption key object
	 *
	 * @param string $key
	 * @param integer $cipher = MCRYPT_BLOWFISH
	 * @param integer $mode = MCRYPT_MODE_CBC
	 * @return void
	 */
	public function __construct($key, $cipher = MCRYPT_BLOWFISH, $mode = MCRYPT_MODE_CBC, $iv = MCRYPT_RAND) {
		$this->key = $key;
		$this->cipher = $cipher;
		$this->mode = $mode;
		
		# initialize the randomizer
		srand(microtime(TRUE));
		
		# initialize the initialization vector
		$this->iv_size = mcrypt_get_iv_size($cipher, $mode);
		$this->iv = mcrypt_create_iv($this->iv_size, $iv);
	}
	
	
	/**
	 * Encrypt content
	 *
	 * @param unknown $content
	 * @param boolean $encode
	 * @return string
	 */
	function encrypt($content, $base64_encode = TRUE) {
		if(is_array($content) || is_object($content)) $content = serialize($content);
		$encrypted = mcrypt_encrypt($this->cipher, $this->key, $content, $this->mode, $this->iv);
		return $this->iv.(($base64_encode)? base64_encode($encrypted) : $encrypted);
	}
	
	
	/**
	 * Decrypt content
	 *
	 * @param string $content
	 * @param boolean $decode
	 * @return unknown
	 */
	function decrypt($content, $base64_decode = TRUE) {
		$iv = substr($content, 0, $this->iv_size);
		$content = substr($content, $this->iv_size);
		if($base64_decode) {
			$content = base64_decode($content);
		}
		$decrypted = mcrypt_decrypt($this->cipher, $this->key, $content, $this->mode, $iv);
		return (unserialize($decrypted) !== FALSE)? unserialize($decrypted) : $decrypted;
	}
	
	
}