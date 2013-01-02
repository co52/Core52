<?php

if(defined('ENABLE_LEGACY_SESSIONS')) {
	
	require_once dirname(__FILE__).'/Session_Legacy.php';
	
} else {

	AutoClassLoader::Unregister();
	
	// forward compatible with PHP 5.4
	if(!interface_exists('SessionHandlerInterface')) {
		interface SessionHandlerInterface {
			public function close();
			public function destroy($sessionid);
			public function gc($maxlifetime);
			public function open($save_path, $sessionid);
			public function read($sessionid);
			public function write($sessionid, $sessiondata);
		}
	}
	
	if(!class_exists('SessionHandler')) {
		class SessionHandler implements SessionHandlerInterface {
			
			protected $sessionid;
			protected $sess_save_path;
			
			public function open($save_path, $sessionid) {
			    $this->sess_save_path = $save_path;
			    $this->sessionid = $sessionid;
			    return TRUE;
			}
			
			public function close() {
			    return TRUE;
			}
			
			public function read($sessionid) {
			    $sess_file = "$this->sess_save_path/sess_$sessionid";
			    return (string) @file_get_contents($sess_file);
			}
			  
			public function write($sessionid, $sess_data) {
			    $sess_file = "$this->sess_save_path/sess_$sessionid";
			    if($fp = @fopen($sess_file, "w")) {
			    	$return = fwrite($fp, $sess_data);
			    	fclose($fp);
			    	return $return;
			    } else {
			    	return FALSE;
			    }
			}
			
			public function destroy($sessionid) {
			    $sess_file = "$this->sess_save_path/sess_$sessionid";
			    return @unlink($sess_file);
			}
			
			public function gc($maxlifetime) {
			    foreach(glob("$this->sess_save_path/sess_*") as $filename) {
			    	if(filemtime($filename) + $maxlifetime < time()) {
			        	@unlink($filename);
			    	}
			    }
			    return TRUE;
			}
		}
	}
	
	AutoClassLoader::Register();
	
	
	/**
	 * PHP session decode function
	 *
	 * @author bmorel@ssi.fr (http://us.php.net/manual/en/function.session-decode.php#56106)
	 * @var string $str
	 * @return array
	 */
	define('PS_DELIMITER', '|');
	define('PS_UNDEF_MARKER', '!');
	function session_string_to_array($str) {

	     $str = (string)$str;

	     $endptr = strlen($str);
	     $p = 0;

	     $serialized = '';
	     $items = 0;
	     $level = 0;

	     while ($p < $endptr) {
	         $q = $p;
	         while ($str[$q] != PS_DELIMITER)
	             if (++$q >= $endptr) break 2;

	         if ($str[$p] == PS_UNDEF_MARKER) {
	             $p++;
	             $has_value = false;
	         } else {
	             $has_value = true;
	         }

	         $name = substr($str, $p, $q - $p);
	         $q++;

	         $serialized .= 's:' . strlen($name) . ':"' . $name . '";';

	         if ($has_value) {
	             for (;;) {
	                 $p = $q;
	                 switch (strtolower($str[$q])) {
	                     case 'n': /* null */
	                     case 'b': /* boolean */
	                     case 'i': /* integer */
	                     case 'd': /* decimal */
	                         do $q++;
	                         while ( ($q < $endptr) && ($str[$q] != ';') );
	                         $q++;
	                         $serialized .= substr($str, $p, $q - $p);
	                         if ($level == 0) break 2;
	                         break;
	                     case 'r': /* reference  */
	                         $q+= 2;
	                         for ($id = ''; ($q < $endptr) && ($str[$q] != ';'); $q++) $id .= $str[$q];
	                         $q++;
	                         $serialized .= 'R:' . ($id + 1) . ';'; /* increment pointer because of outer array */
	                         if ($level == 0) break 2;
	                         break;
	                     case 's': /* string */
	                         $q+=2;
	                         for ($length=''; ($q < $endptr) && ($str[$q] != ':'); $q++) $length .= $str[$q];
	                         $q+=2;
	                         $q+= (int)$length + 2;
	                         $serialized .= substr($str, $p, $q - $p);
	                         if ($level == 0) break 2;
	                         break;
	                     case 'a': /* array */
	                     case 'o': /* object */
	                         do $q++;
	                         while ( ($q < $endptr) && ($str[$q] != '{') );
	                         $q++;
	                         $level++;
	                         $serialized .= substr($str, $p, $q - $p);
	                         break;
	                     case '}': /* end of array|object */
	                         $q++;
	                         $serialized .= substr($str, $p, $q - $p);
	                         if (--$level == 0) break 2;
	                         break;
	                     default:
	                         return false;
	                 }
	             }
	         } else {
	             $serialized .= 'N;';
	             $q+= 2;
	         }
	         $items++;
	         $p = $q;
	     }
	     $unserialized = unserialize( 'a:' . $items . ':{' . $serialized . '}' );
	     if($unserialized !== FALSE) {
	     	return $unserialized;
	     } else {
	     	throw new Exception("Could not decode session data: $str");
	     }
	}
	
	
	function session_array_to_string(array $array) {
		$bk = $_SESSION;
		$_SESSION = $array;
		$string = session_encode();
		$_SESSION = $bk;
		return $string;
	}
	
	
	/**
	 * A MySQL session handler, patterned after the PHP 5.4 session handler class
	 *
	 * @author Jonathon Hill
	 * @package Core52
	 * @version 1.0
	 */
	class MysqlSessionHandler implements SessionHandlerInterface {
		
		protected static $initialized = FALSE;
		protected static $check_sessions_table = TRUE;
		protected static $session_table_create_sql = "
CREATE TABLE `sessions` (
	`key` varchar(100) NOT NULL,
	`user` int(11) NULL DEFAULT NULL,
	`hits` int(10) NOT NULL DEFAULT 1,
	`_data` longtext NOT NULL DEFAULT '',
	`_server` longtext NOT NULL DEFAULT '',
	`ip` varchar(15) NOT NULL,
	`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
	PRIMARY KEY (`key`),
	INDEX `ndx_sessions_users` USING BTREE (`user`),
	INDEX `ndx_sessions_timestamp` USING BTREE (`timestamp`)
) ENGINE=InnoDB;";
		
		/**
		 * @var array Current session database row
		 */
		protected $session;
		
		public function __construct() {
			if(!self::$initialized) {
				self::$initialized = TRUE;
				
				// Create the sessions table if it doesn't exist
				if(self::$check_sessions_table) {
					$this->create_session_table();
				}
			}
		}
		
		public function create_session_table() {
			$table_missing = database()->execute("SHOW TABLES LIKE 'sessions'")->null_set();
		    if($table_missing) {
		    	database()->execute(self::$session_table_create_sql);
		    }
		}
		
		public function close() {
			return TRUE;
		}
		
		public function destroy($sessionid) {
			database()->delete('sessions', array(
				'key' => $sessionid,
			));
			return TRUE;
		}
		
		public function gc($maxlifetime) {
			$timestamp = database()->escape(format_date_mysql(time() - $maxlifetime));
			database()->delete('sessions', 'timestamp <= '.$timestamp);
			return TRUE;
		}
		
		public function open($save_path, $sessionid) {
			return TRUE;
		}
		
		public function read($sessionid) {
			$result = database()->findById('sessions', $sessionid, 'key');
			$this->session = $result->row_array();
			return (string) $this->session['_data'];
		}
		
		public function write($sessionid, $sessiondata) {
			
			// find the hit number for this request
			$this->read($sessionid); // loads the previous copy of the session record into $this->session
			$hit = $this->session['hits'] + 1;
			
			// unserialize the session data so we can read it
			$data = session_string_to_array($sessiondata);
			
			// store the session data
			database()->replace('sessions', array(
				'key'     => $sessionid,
				'_data'   => $sessiondata,
				'_server' => serialize($_SERVER),
				'ip'      => $_SERVER['REMOTE_ADDR'],
				'hits'    => $hit,
				'user'    => $data['user'],
			));
	
			return TRUE;
		}
		
		public function update_id($old_sessionid, $new_sessionid) {
				
			// Delete the new session, if it exists
			$this->destroy($new_sessionid);
			
			// Update the old session ID to the new session ID
			database()->update('sessions', array(
				'key' => $new_sessionid,
			), array(
				'key' => $old_sessionid,
			));
			
			return TRUE;
		}
		
	}
	
	
	/**
	 * Core52 Session class
	 *
	 * @author Jonathon Hill
	 * @package Core52
	 * @version 1.4
	 **/
	class Session {
	
		public static $variable = 'sid'; // session cookie name
		public static $sid; // session id
		
		const Token_Variable = 'sid_token'; // anti-CSRF token name
		protected static $check_xsrf = FALSE;
		protected static $salt;
		protected static $session_storage = 'MysqlSessionHandler';
		
		/**
		 * Login object
		 *
		 * @var Login
		 */
		protected static $login;
		
		/**
		 * SessionObject object
		 *
		 * @var SessionObject
		 */
		protected static $session;
		
		/**
		 * Configure and enable sessions
		 *
		 * @param string $cookie_uri Domain name to restrict the session cookie to
		 * @param string $salt = NULL Secret code to use when salting passwords for hashing
		 * @param string $login_controller = NULL URL of the login page
		 * @param string $expiration_threshold = '+4 hour' How long sessions last before expiring; must be a valid time string recognized by the strtotime() function
		 * @param string $user_class = 'User' User model class for authenticated users
		 * @param boolean $prevent_xsrf = FALSE Whether to prevent XSRF attacks in POST requests via a token (nonce)
		 * @param boolean $stronger_tokens = TRUE Whether to use SHA-256 or MD5 for session IDs
		 * @param boolean $secure_cookies = TRUE Whether to restrict cookies based on the HTTP protocol
		 * @param string $session_handler = 'MysqlSessionHandler' Class name of the session handler to use
		 * @param SessionObject $session_object = NULL Use this SessionObject instead of constructing one
		 * @param Login $login_object = NULL Use this Login object instead of contstructing one
		 * @return SessionObject
		 */
		public static function Initialize($cookie_uri = FALSE, $salt = NULL, $login_controller = '/login', $expiration_threshold = '+4 hour', $user_class = 'User', $prevent_xsrf = FALSE, $stronger_tokens = TRUE, $secure_cookies = TRUE, $session_handler = 'MysqlSessionHandler', SessionObject $session_object = NULL, Login $login_object = NULL) {
			
			// Already initialized?
			if(self::$session) {
				return self::$session;
			}
			
			// Extract array data as individual vars
			if(is_array($cookie_uri)) {
				extract($cookie_uri);
			}
			
			// If 'cookie_uri' wasn't set in the array, then set it to the default value
			if(is_array($cookie_uri)) {
				$cookie_uri = FALSE;
			}
			
			self::$salt = $salt;
			
			// Calculate the session expiration time in seconds
			if(!empty($expiration_threshold) && !is_numeric($expiration_threshold) && strtotime($expiration_threshold) !== FALSE) {
				$lifetime = time() - strtotime($expiration_threshold);
			} elseif(is_numeric($expiration_threshold)) {
				$lifetime = time() - $expiration_threshold;
			} else {
				$lifetime = time() - strtotime('+4 hour');
			}
			
			// Start the session
			if($session_object instanceof SessionObject) {
				self::$session = $session_object;
			} else {
				self::$session = new SessionObject(new $session_handler, array(
					'domain' => empty($cookie_uri)? Router::domain('.', FALSE) : $cookie_uri,
					'lifetime' => $lifetime,
					'stronger_tokens' => (bool) $stronger_tokens,
					'secure_cookies'  => (bool) $secure_cookies,
					'auto_start' => TRUE,
				));
			}
			self::$variable &= self::$session->name;
			self::$sid &= self::$session->sid;
			
			// Load logged in user
			if($login_object instanceof Login) {
				self::$login = $login_object;
			} else {
				self::$login = new Login($user_class);
				self::$login->set_login_controller($login_controller);
			}
			
			if($prevent_xsrf == TRUE) {
				self::$check_xsrf = TRUE;
				self::check_xsrf_token();
				self::set_xsrf_token(FALSE);
			}
			
			return self::$session;
		}
	
		/**
		 * Get the singleton SessionObject instance
		 *
		 * @return SessionObject
		 */
		public static function get($require = TRUE) {
			if(is_object(self::$session)) {
				return self::$session;
			} elseif($require) {
				throw new Exception("Session not initialized yet, call Session::Initialize() before calling this method");
			} else {
				return FALSE;
			}
		}
		
		/**
		 * Set an XSRF token (nonce)
		 *
		 * @param  boolean $force Forces generating a token even if the check_xsrf session setting is false
		 * @author Alex King
		 **/
		public static function set_xsrf_token($force = FALSE) {
			
			// Only proceed if check_xsrf is on, or there is a force argument.
			if($force) {
				self::$check_xsrf = TRUE;
			} elseif(!self::$check_xsrf) {
				return;
			}
					
			// If there is no nonce in the session, generate one
			if(empty($_SESSION[self::Token_Variable])) {
				$_SESSION[self::Token_Variable] = hash('sha256', uniqid(rand(), TRUE));
			}
		}
		
		/**
		 * Check POST requests for a valid XSRF token (nonce)
		 *
		 * @author Alex King
		 **/
		public static function check_xsrf_token() {
			if(self::$check_xsrf && !empty($_POST)) {
				Session::check_xsrf_token_string($_POST[self::Token_Variable]);
			}
		}
		
		/**
		 * Check a token against the one stored in the session
		 *
		 * @param string $token = false The token value to check
		 * @author Alex King
		 **/
		public static function check_xsrf_token_string($token = FALSE) {
			if(!$token || ($_SESSION[self::Token_Variable] && $_SESSION[self::Token_Variable] !== $token)) {
				throw new XSRFSecurityException('Token mismatch, possible cross-site request forgery');
			}
		}
	
		/**
		 * Get the XSRF token (nonce) value
		 *
		 * @author Jonathon Hill
		 * @return string
		 */
		public static function get_xsrf_token() {
			return (string) $_SESSION[self::Token_Variable];
		}
	
		/**
		 * Output session debug information
		 */
		public static function debug() {
	
			$user = print_r(self::user(), TRUE);
			$data = print_r($_SESSION, TRUE);
			$sid = session_id();
			
			echo <<<DEBUG
<pre style="width:70%; border:1px solid black; background:#eee; padding:50px; margin:50px auto; clear:both; position:relative; z-index:100000;">
<b>Session debugging:</b>
SID=$sid

User=$user

Data=$data
</pre>
DEBUG;
			
		}
	
		/**
		 * Destroys the current session and starts a new one
		 */
		public static function reset() {
			if(!self::$session instanceof SessionObject) {
				throw new Exception('Session not initialized, please call Session::Initialize() before calling this method');
			}
			self::$session->reset();
			self::set_xsrf_token(FALSE);
		}
	
		/**
		 * Flashdata getter/setter
		 *
		 * @param string $var
		 * @param unknown_type $value
		 * @return unknown_type
		 */
		public static function flashdata($var = NULL, $value = NULL) {
			if(!self::$session instanceof SessionObject) {
				throw new Exception('Session not initialized, please call Session::Initialize() before calling this method');
			}
			return self::$session->flashdata($var, $value);
		}
		
		/**
		 * Unset a flashdata var
		 *
		 * @param string $var
		 */
		public static function unset_flashdata($var) {
			if(!self::$session instanceof SessionObject) {
				throw new Exception('Session not initialized, please call Session::Initialize() before calling this method');
			}
			return self::$session->unset_flashdata($var);
		}
		
		/**
		 * Session data getter/setter
		 *
		 * @param string $var
		 * @param unknown_type $value
		 * @return unknown_type
		 */
		public static function data($var = NULL, $value = NULL) {
			
			// return all session data
			if($var === NULL) {
				return $_SESSION;
			}
			
			// set multiple session vars
			elseif(is_array($var) && $value === NULL) {
				$_SESSION = array_merge($_SESSION, $var);
			}
			
			// return a specific var
			elseif($value === NULL) {
				return $_SESSION[$var];
			}
			
			// set a single var
			else {
				$_SESSION[$var] = $value;
			}
		}
		
		/**
		 * Unset a session data var
		 *
		 * @param string $var
		 */
		public static function unset_data($var) {
			$val = $_SESSION[$var];
			unset($_SESSION[$var]);
			return $val;
		}
		
		/**
		 * Deprecated; use the Login class instead
		 *
		 * @param unknown_type $id
		 * @param string $class = FALSE
		 * @return Model
		 */
		public static function user($id = FALSE, $class = FALSE) {
			
			if(!self::$login instanceof Login) {
				throw new Exception('Session not initialized, please call Session::Initialize() before calling this method');
			}
			
			if(!$class) $class = self::$login->user_class;
			
			# log out a user
			if($id === 'end') {
				self::$login->logout();
				return TRUE;
			}
			
			# log in a user
			elseif($id && self::$login->login($id, $class)) {
				return self::$login->user();
			}
			
			# no data passed; not logged in
			elseif(!$id && !self::$login->login()) {
				try {
					return new $class;
				} catch(AutoClassLoaderException $e) {
					return (object) array();
				}
			}
			
			# no data passed; logged in
			else {
				return self::$login->login();
			}
		}
		
		/**
		 * Deprecated; use the Login class instead
		 *
		 * @return boolean
		 */
		public static function logged_in() {
			if(!self::$login instanceof Login) {
				return FALSE;
			} else {
				return self::$login->is_logged_in();
			}
		}
		
		/**
		 * Deprecated, use Login::redir() instead
		 *
		 * @param string $url = FALSE
		 * @param string $login_page
		 */
		public static function login_redir($url = FALSE, $login_page = FALSE) {
			Login::redir($url, $login_page);
		}
	
		/**
		 * Change the session ID (use to prevent session fixation)
		 */
		public static function regenerate_sid() {
			if(!self::$session instanceof SessionObject) {
				throw new Exception('Session not initialized, please call Session::Initialize() before calling this method');
			}
			self::$session->regenerate_id();
		}
	
		/**
		 * Deprecated, use Login::check_redir() instead
		 *
		 * @return boolean Returns TRUE if logged in
		 */
		public static function check_login() {
			if(!self::$login instanceof Login) {
				throw new Exception('Session not initialized, please call Session::Initialize() before calling this method');
			}
			self::$login->check_redir();
		}
		
		/**
		 * Deprecated, use Login::post_redir() instead
		 *
		 * @param string $url
		 */
		public static function post_login_redir($url = '/') {
			Login::post_redir($url);
		}
		
		/**
		 * Deprecated, use the Password class instead
		 *
		 * @param string $password
		 * @param boolean $force_stronger_hashing = FALSE
		 * @return string
		 */
		public static function password_hash($password, $force_stronger_tokens = FALSE) {
			if(!self::$session instanceof SessionObject) {
				throw new Exception('Session not initialized, please call Session::Initialize() before calling this method');
			}
			$method = ($force_stronger_tokens || self::$session->stronger_tokens)? 'sha256' : 'md5';
			return Password::hash(self::$salt, (string) $password, $method);
		}
		
		/**
		 * Deprecated, use the Password class instead
		 *
		 * @param integer $length = 8 Password length
		 * @param string $chars = '1234567890qwrypasdfghnz' Characters to use to generate the password (defaults to numbers and unambiguous letters)
		 * @return string
		 */
		public static function random_password($length = 8, $chars = '1234567890qwrypasdfghnz') {
			return (string) new Password($length, $chars);
		}
	
	}
	
	
	/**
	 * Wrapper object for PHP sessions
	 *
	 * @author Jonathon Hill
	 * @package Core52
	 * @version 1.0
	 *
	 */
	class SessionObject {
		
		public $name = 'sid'; // session cookie name
		public $sid = false; // session id
		public $domain = FALSE;
		public $stronger_tokens = TRUE;
		public $secure_cookies = TRUE;
		public $lifetime = 14400; // 4 hours
		
		/**
		 * Session handler object
		 *
		 * @var SessionHandlerInterface
		 */
		protected $handler;
		
		/**
		 * Configure the session
		 *
		 * @param SessionHandlerInterface $handler
		 * @param array $settings = array()
		 * 		domain: Domain name to restrict the session cookie to (defaults to .domain.com)
		 * 		lifetime: How long sessions last before expiring, in seconds (defaults to 4 hours)
		 * 		stronger_tokens: If TRUE, use SHA-256 instead of MD5 for session IDs
		 * 		secure_cookies: If TRUE, sets restrictions on the session cookie based on the HTTP protocol
		 *      auto_start: If TRUE, starts the session immediately (defaults to TRUE)
		 */
		public function __construct(SessionHandlerInterface $handler, array $settings = array()) {
			
			$this->handler = $handler;
			
			// make the session cookie name specific to this domain
			$this->name .= '_'.md5(strtolower(Router::domain('', FALSE)));
			
			if(isset($settings['stronger_tokens'])) {
				$this->stronger_tokens = (bool) $settings['stronger_tokens'];
			}
			
			if(isset($settings['secure_cookies'])) {
				$this->secure_cookies =  (bool) $settings['secure_cookies'];
			}
			
			if(isset($settings['domain']) && !empty($settings['domain'])) {
				$this->domain = $settings['domain'];
			} else {
				$this->domain = Router::domain('.', FALSE);
			}
			
			if(isset($settings['lifetime'])) {
				$this->lifetime = (int) $settings['lifetime'];
			}
			
			// set the session hash generation method
			if($this->stronger_tokens) {
				ini_set('session.hash_function', 'sha256');
			} else {
				ini_set('session.hash_function', 0); // md5
			}
			
			// configure the session cookie expiration and security settings
			session_name($this->name); // cookie name is namespaced to the domain (see above)
			session_set_cookie_params(
				$this->lifetime,       // cookie lifetime in seconds
				'/',                   // cookie path
				$this->domain,         // cookie domain
				(Router::protocol() == 'https' && $this->secure_cookies), // transmit only over https
				(Router::protocol() == 'http' && $this->secure_cookies)   // set the HTTPONLY flag if supported
			);
			
			// start the session
			if(!isset($settings['auto_start']) || $settings['auto_start']) {
				$this->start();
			}
		}
		
		/**
		 * Starts the session
		 */
		public function start() {
			// configure the session handler
			session_set_save_handler(
				array($this->handler, 'open'),
				array($this->handler, 'close'),
				array($this->handler, 'read'),
				array($this->handler, 'write'),
				array($this->handler, 'destroy'),
				array($this->handler, 'gc')
			);
			session_start();
			$this->sid = session_id();
			$this->_update_flashdata();
		}
		
		protected function _update_flashdata() {
			if(is_array($_SESSION['flashdata'])) {
				foreach($_SESSION['flashdata'] as $k => $v) {
					if($v === 'new') {
						// mark current flashdata for removal on the next request
						$_SESSION['flashdata'][$k] = 'old';
					} else {
						// remove old flashdata
						unset($_SESSION[$k]);
						unset($_SESSION['flashdata'][$k]);
					}
				}
			}
		}
		
	
		/**
		 * Output session debug information
		 */
		public function debug() {
	
			$data = print_r($_SESSION, TRUE);
			$sid = session_id();
			
			echo <<<DEBUG
<pre style="width:70%; border:1px solid black; background:#eee; padding:50px; margin:50px auto; clear:both; position:relative; z-index:100000;">
<b>Session debugging:</b>
SID=$sid

Data=$data
</pre>
DEBUG;
			
		}
	
		/**
		 * Destroys the current session and starts a new one
		 */
		public function reset() {
			session_regenerate_id(TRUE); // delete the old session
			$_SESSION = array();
			session_write_close();
			$this->sid = session_id();
		}
	
		/**
		 * Flashdata getter/setter
		 *
		 * @param string $var
		 * @param unknown_type $value
		 * @return unknown_type
		 */
		public function flashdata($var = NULL, $value = NULL) {
			
			// flashdata is stored as normal session data, with metadata about the
			// flashdata status stored in $_SESSION['flashdata']:
			//
			//   'foo' would be stored as follows:
			//     $_SESSION['foo'] = 'bar';
			//     $_SESSION['flashdata']['foo'] = 'status';
			//   where 'status' is either 'new' or 'old'.
			
			// return all flashdata vars
			if($var === NULL) {
				$flashdata = array();
				foreach(array_keys((array) $_SESSION['flashdata']) as $k) {
					$flashdata[$k] = $_SESSION[$k];
				}
				return $flashdata;
			}
			
			// set multiple flashdata vars
			elseif(is_array($var) && $value === NULL) {
				foreach($var as $k => $v) {
					$this->flashdata($k, $v);
				}
			}
	
			// return a specific flashdata var
			elseif($value === NULL) {
				return (isset($_SESSION['flashdata'][$var]))? $_SESSION[$var] : NULL;
			}
			
			// set a single flashdata var
			else {
				// detect collisons
				if(isset($_SESSION[$var]) && !isset($_SESSION['flashdata'][$var])) {
					throw new Exception("Cannot overwrite session var $var with flashdata");
				} else {
					$_SESSION['flashdata'][$var] = 'new';
					$_SESSION[$var] = $value;
				}
			}
		}
		
		/**
		 * Unset a flashdata var
		 *
		 * @param string $var
		 */
		public function unset_flashdata($var) {
			// only unset flashdata
			if(isset($_SESSION['flashdata'][$var])) {
				$val = $_SESSION[$var];
				unset($_SESSION[$var]);
				unset($_SESSION['flashdata'][$var]);
				return $val;
			} else {
				return NULL;
			}
		}
		
		/**
		 * PHP5 getters and setters
		 */
		public function __isset($var) {
			return isset($_SESSION[$var]);
		}
		public function __unset($var) {
			unset($_SESSION[$var]);
		}
		public function __get($var) {
			return $_SESSION[$var];
		}
		public function __set($var, $value) {
			$_SESSION[$var] = $value;
		}
		
		/**
		 * Change the session ID (use to prevent session fixation)
		 */
		public function regenerate_id() {
			if(method_exists($this->handler, 'update_id')) {
				$old_id = session_id();
				session_regenerate_id();
				$new_id = session_id();
				$this->handler->update_id($old_id, $new_id);
			} else {
				session_regenerate_id(TRUE);
			}
			$this->sid = session_id();
		}
	
	}

}