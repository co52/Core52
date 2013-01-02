<?php

/**
 * Core52 CURL Class
 *
 * Origin and stability unknown.
 *
 * @author unknown
 * @package Core52
 * @version 1.0
 * @todo Check stability of class
 *
 **/

class CURL {
	
	public static function post($url, $post, array $params = array()) {
	
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
		
		foreach($params as $option => $value) {
			curl_setopt($c, $option, $value);
		}
		
		$response = curl_exec($c);
		if(!$response) {
			return FALSE;
		}
		
		curl_close($c);
		return $response;
	}
	
	
	public static function get($url, $get = null, array $params = array(), $urlencode = TRUE) {
	
		if(is_array($get)) $url .= '?'.self::build_query_string($get, $urlencode);
		
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
		
		foreach($params as $option => $value) {
			curl_setopt($c, $option, $value);
		}
		
		$response = curl_exec($c);
		if(!$response) {
			return FALSE;
		}
		
		curl_close($c);
		return $response;
	}
	
	
	public static function build_query_string(array $data, $urlencode = TRUE) {
		$string = array();
		foreach($data as $key => $val) {
			$string[] = $urlencode ?
				urlencode($key).'='.urlencode($val) :
				"$key=$val";
		}
		return implode('&', $string);
	}
	
}
