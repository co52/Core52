<?php

require_once(PATH_CORE.'3rdparty/akismet/Akismet.class.php');

/**
 *
 * Usage example:
 *
 *     Akismet_API::Initialize($api_key);
 *     $akismet = Akismet_API::factory();
 *     $akismet->setCommentAuthor($name);
 *     $akismet->setCommentAuthorEmail($email);
 *     $akismet->setCommentAuthorURL($url);
 *     $akismet->setCommentContent($comment);
 *     $akismet->setPermalink('http://www.example.com/blog/alex/someurl/');
 *     if($akismet->isCommentSpam()) {
 *       // store the comment but mark it as spam (in case of a mis-diagnosis)
 *     } else {
 *       // store the comment normally
 *     }
 *
 *
 */


class Akismet_API {
	
	private static $api_key;
	private static $url;
	private static $instance;
	
	
	/**
	 * Get or set the Akismet API key
	 *
	 * @param string $api_key
	 */
	public static function api_key($api_key = NULL) {
		if(!empty($api_key)) {
			self::Initialize($api_key, self::$url);
		} else {
			return self::$api_key;
		}
	}
	
	
	/**
	 * Initialize the Akismet API
	 *
	 * @param string $api_key
	 * @param string $url
	 */
	public static function Initialize($api_key, $url = NULL, $check_key = FALSE) {
		
		if(is_array($api_key)) {
			extract($api_key);
		}
		
		self::$api_key = $api_key;
		self::$url = empty($url)? Router::domain('http://') : $url;
		
		self::$instance = new Akismet(self::$url, self::$api_key);
		
		if($check_key && !self::$instance->isKeyValid()) {
			throw new Exception('Invalid Akismet API key');
		}
	}
	
	
	/**
	 * Get an instance of the Akismet API
	 *
	 * @return Akismet
	 */
	public static function factory() {
		return clone self::$instance;
	}
	
	
}
