<?php

require_once(PATH_CORE.'3rdparty/twitter/EpiCurl.php');
require_once(PATH_CORE.'3rdparty/twitter/EpiOAuth.php');
require_once(PATH_CORE.'3rdparty/twitter/EpiTwitter.php');


class Twitter {
	
	private static $consumerKey = FALSE;
	private static $consumerSecret = FALSE;
	private static $oauthToken;
	private static $oauthSecret;
	
    private static $instance;
	
	
	public static function Initialize($consumerKey, $consumerSecret = NULL, TwitterCredentials $auth = NULL) {
	
		if(is_array($consumerKey)) {
			extract($consumerKey);
		}
		
		self::$consumerKey = $consumerKey;
		self::$consumerSecret = $consumerSecret;
		
		define('TWITTER_CONSUMER_KEY', $consumerKey);
		define('TWITTER_CONSUMER_SECRET', $consumerSecret);
		
		if($auth) {
			return self::factory($auth, TRUE);
		}
	}
	
	
	/**
	 * Create a Twitter API connection object
	 *
	 * @param TwitterCredentials $auth Twitter API authorization credentials
	 * @param boolean $setInstance = FALSE Set this as the default connection instance
	 * @param boolean $cache = TRUE Enable/disable API caching
	 * @return TwitterApi
	 */
	public static function factory(TwitterCredentials $auth = NULL, $setInstance = FALSE, $cache = TRUE) {
		
		$class = ($cache)? 'CachedTwitterApi' : 'TwitterApi';
		
		
		if($auth === NULL && $setInstance === FALSE && self::$instance instanceof $class) {

			return self::$instance;
			
		} else {
		
			if($auth instanceof TwitterCredentialsOAuth) {
				# OAuth authentication
				$instance = new $class(self::$consumerKey, self::$consumerSecret, $auth->token, $auth->secret);
				$instance->auth = $auth;
			} elseif($auth instanceof TwitterCredentialsBasic) {
				# HTTP Basic authentication
				$instance = new $class();
				$instance->auth = $auth;
			} elseif(!empty($auth)) {
				throw new InvalidArgumentException('Invalid $auth parameter');
			} else {
				$instance = new $class(self::$consumerKey, self::$consumerSecret);
			}
			
			if($setInstance) {
				self::$instance = $instance;
			}
			
			return $instance;
		}
		
	}
	
}


class TwitterCacheMissException extends Exception {}
class TwitterCredentialsException extends Exception {}


abstract class TwitterCredentials {
	public $username;
	
	public function encrypt($base64_encode = TRUE) {
		$key = new EncryptionKey(md5(TWITTER_CONSUMER_KEY.TWITTER_CONSUMER_KEY.$this->username));
		return $key->encrypt($this, $base64_encode);
	}
	
	public static function decrypt($username, $data, $base64_decode = TRUE) {
		$key = new EncryptionKey(md5(TWITTER_CONSUMER_KEY.TWITTER_CONSUMER_KEY.$username));
		$obj = $key->decrypt($data, $base64_decode);
		if($obj instanceof self) {
			return $obj;
		} else {
			throw new TwitterCredentialsException("Credential decryption failed");
		}
	}
}


class TwitterCredentialsOAuth extends TwitterCredentials {
	public $token;
	public $secret;
	
	public function __construct($username, $token, $secret) {
		$this->username = $username;
		$this->token = $token;
		$this->secret = $secret;
	}
}


class TwitterCredentialsBasic extends TwitterCredentials {
	public $password;
	
	public function __construct($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}
}


class TwitterApi extends EpiTwitter {
	
	public $requestTokenUrl= 'https://api.twitter.com/oauth/request_token';
	public $accessTokenUrl = 'https://api.twitter.com/oauth/access_token';
	public $authorizeUrl   = 'https://api.twitter.com/oauth/authorize';
	public $authenticateUrl= 'https://api.twitter.com/oauth/authenticate';
	public $apiUrl         = 'https://api.twitter.com';
	public $apiVersionedUrl= 'http://api.twitter.com';
	public $searchUrl      = 'http://search.twitter.com';
	protected $useSSL      = TRUE;
	
	
	public function is_oauth() {
		return ($this->auth instanceof TwitterCredentialsOAuth);
	}
	
	
	public function get_username() {
		return $this->auth->username;
	}
	
	
	public function get($endpoint, $params = NULL) {
		if($this->is_oauth()) {
			return parent::get($endpoint, $params);
		} else {
			return parent::get_basic($endpoint, $params, $this->auth->username, $this->auth->password);
		}
	}
	
	
	public function follow($user, $follower = NULL, $password = NULL) {
		
		if(!empty($follower)) {
			$api = Twitter::factory(new TwitterCredentialsBasic($follower, $password));
		} else {
			$api = $this;
		}
		
		$response = $this->post("/friendships/create.json", array(
			'id' => $user,
		));
		
		return TRUE;
	}
	

	public function following($user, $follower = NULL) {
		if(empty($follower)) {
			$follower = $this->get_username();
		}
		$response = $this->get('/friendships/exists.json', array('user_a' => $follower, 'user_b' => $user));
		return (trim(strtoupper($response->responseText)) === 'TRUE');
	}
	
	
    public function is_followed_by($target) {
    	
    	if(is_numeric($target)) {
    		$params = array('target' => $target);
    	} else {
    		$params = array('target_screen_name' => ltrim($target, '@'));
    	}
    	
    	# http://bit.ly/pWPIwS
    	$response = $this->get('/friendships/show.json', $params);
    	
    	$json = json_decode($response->responseText);
    	if($json === FALSE) {
    		throw new EpiTwitterException("Invalid JSON response to /friendships/show.json: $response->responseText");
    	}
    	
    	return ($json->relationship->target->following == TRUE);
    }
	
	
	public function dm($msg, $to, $from = NULL) {
		
		if($from instanceof TwitterCredentials) {
			$from_api = Twitter::factory($from);
			$from = $from->username;
		} else {
			$from_api = $this;
			$from = $this->get_username();
		}
		
		if($to instanceof TwitterCredentials) {
			$to_api = Twitter::factory($to);
			$to = $to->username;
		}
		
		# auto-follow
		if(!$from_api->following($to)) {
			$from_api->follow($to);
		}
		if($to_api && !$to_api->following($from)) {
			$to_api->follow($from);
		}
		
		return $from_api->post('/direct_messages/new.json', array('user' => $to, 'text' => $msg));
	}

}



class CachedTwitterApi extends TwitterApi {
	
	protected $timeout = 30;	     // time in minutes
	protected $stale_threshold = 1;  // time in days
	
	
	public function request() {
		$params = func_get_args();
		return $this->_request_cached('request', $params);
	}
	
	
	public function request_basic() {
		$params = func_get_args();
		return $this->_request_cached('request_basic', $params);
	}
	
	
	protected function _request_cached($func, $params) {
		
		# only cache if GET method
		if($params['method'] !== 'GET') {
			return call_user_func_array(array(parent, $func), $params);
		}
		
		
		# attempt to load from cache
		try {
			
			$hash = md5($func.serialize($params));
			return $this->_hit_cache($hash, "$this->timeout MINUTE");
			
		} catch(TwitterCacheMissException $ecm1) {
			
			# cache miss, call the API
			try {
				
				$result = call_user_func_array(array(parent, $func), $params);
				$this->_store_cache($hash, $result);
				return $result;
				
			} catch(EpiTwitterException $epx) {
				
				# API call failed, check for a stale copy in the cache
				try {
			
					return $this->_hit_cache($hash, "$this->stale_threshold DAY");
				
				} catch(TwitterCacheMissException $ecm2) {
					
					# blubber...
					throw $epx;
				}
			}
		}
		
	}
	
	
	protected function _hit_cache($hash, $interval) {
		
		$result = database()->start_query('api_cache')
			->where('key', $hash)
			->raw_where("CURRENT_TIMESTAMP - time_stamp < INTERVAL $interval")
				->run();
		
		if(!$result->null_set()) {
			
			# cache hit
			$row = $result->row();
			return unserialize($row->contents);
			
		} else {
			
			throw new TwitterCacheMissException;
			
		}
	}
	
	
	protected function _store_cache($hash, $data) {
		
		$result = database()->start_query('api_cache', 'REPLACE')
			->set('key', $hash)
			->set('contents', serialize($data))
			->set('time_stamp', format_date_mysql())
				->run();
		
	}
	
	
}








