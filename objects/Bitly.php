<?php

class BitlyException extends Exception {}
class BitlyRateLimitException extends BitlyException {}
class BitlyInvalidRequestException extends BitlyException {}
class BitlyUnavailableException extends BitlyException {}


class Bitly {
	
	protected static $login;
	protected static $api_key;
	protected static $api_version = '3.0';
	protected static $api_url = 'http://api.bitly.com/v3/';
	
	
	function Initialize($login, $api_key = NULL, $api_version = '3.0') {
		if(is_array($login)) {
			extract($login);
		}
		
		self::$login = $login;
		self::$api_key = $api_key;
		
		if(!empty($api_version)) {
			self::$api_version = $api_version;
		}
	}
	
	
	function shorten($url) {
		
		# call the API
		$response = CURL::get(self::$api_url.'shorten', array(
			'login'   => self::$login,
			'apiKey'  => self::$api_key,
			'longUrl' => urlencode($url),
			'format'  => 'json',
		), array(), FALSE);
		
		# parse result
		$json = json_decode($response, TRUE);
		
		# evaluate the result
		switch($json['status_code']) {
			
			case 200:
				return $json['data']['url'];
			
			case 403:
				throw new BitlyRateLimitException($json['status_txt']);
				
			case 500:
				if(preg_match('/^(missing|invalid).+/i', $json['status_txt'])) {
					throw new BitlyInvalidRequestException($json['status_txt'].': '.$url);
				} else {
					throw new BitlyUnavailableException($json['status_txt']);
				}
				
			default:
				throw new BitlyException($json['status_txt']);
		}
		
	}
	
}