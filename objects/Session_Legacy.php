<?php

/**
 * Session Object.
 *
 * A stripped down version of the original sessions class, this one requires that
 * you have cookies and does not allow for url sids
 *
 * @author David Boskovic
 * @package Core52
 * @version 1.3
 * @copyright Company Fifty Two, 2 April, 2009
 **/

class Session {

	# Variable name for the cookie and for the appended url var
	public static $variable = 'sid';		// should be lowercase text
	
	# Variable name for the token cookie (prevents XSRF)
	const Token_Variable = 'sid_token';
	
	# Permitted time of inactivity (in hours)
	const Inactivity = 24;		// this defines the time of inactivity allowed before the database closes the session
	
	# Cookie Expiration
	const Expiration = false;	// 0: expire on browser close, >=1: number of days to store the cookie for
	
	# The string length of the key
	private static $KeyLen = 32; // default 32 (number of bits in md5)
	const sigTimeOut = 3200;
	
	# Session ID Value  --  changes only after Session::Detect() has been run
	public  static  $sid = false;
	
	# Session data from database
	public  static  $data = false;
	
	# user id if logged in
	private static $user = false;
	private static $class = 'User';
	
	private static $check_xsrf = FALSE;
	private static $stronger_tokens = FALSE;
	
	public static $check_sessions_table = FALSE;

	private static $DDL = "
CREATE TABLE `sessions` (
	`key` varchar(100) NOT NULL ,
	`user` int(11) NULL DEFAULT NULL ,
	`method` set('cookie','uri') NOT NULL ,
	`signature` varchar(100) NOT NULL ,
	`hits` int(10) NOT NULL ,
	`_data` text NOT NULL ,
	`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
	`closed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 ,
	PRIMARY KEY (`key`),
	INDEX `ndx_sessions_signature` USING BTREE (`signature`),
	INDEX `ndx_sessions_users` USING BTREE (`user`),
	INDEX `ndx_sessions_timestamp` USING BTREE (`timestamp`),
	INDEX `ndx_sessions_closed` USING BTREE (`closed`)
) ENGINE=InnoDB;";

	# Passed configuration
	private static $cookie_uri = false;
	private static $alreadyhit = false;
	private static $check_ip = false;
	private static $salt;
	private static $login_controller;
	private static $expiration_threshold;
	private static $secure_cookies = TRUE;
	
	/**
	 * Retrieve session from the database, or create it
	 * @param $cookie_uri
	 * @param $hit
	 * @param $salt
	 * @param $login_controller
	 * @param $expiration_threshold
	 * @param $check_ip
	 * @return boolean
	 */
	public static function Initialize($cookie_uri, $hit = true, $salt = NULL, $login_controller = NULL, $expiration_threshold = '+4 hour', $check_ip = FALSE, $user_class = 'User', $prevent_xsrf = FALSE, $xsrf_token_expire = 3600, $stronger_tokens = FALSE, $secure_cookies = TRUE)
	{
		if(is_array(func_get_arg(0)) && func_num_args() == 1) {
			extract($cookie_uri);
		}
		
		# make the cookie name specific to this domain
		self::$variable = self::$variable . '_' . md5(strtolower(Router::domain('', FALSE)));

		# save the salt
		self::$salt = $salt;
		
		# save the controller
		self::$login_controller = $login_controller;
		self::$class = $user_class;

		# save the cookie uri config
		self::$cookie_uri = $cookie_uri;
		
		# save the cookie security settings
		self::$secure_cookies = (bool) $secure_cookies;
    	
		# save IP check flag
		self::$check_ip = (bool) $check_ip;
		
		self::$stronger_tokens = (bool) $stronger_tokens;
		if(self::$stronger_tokens) {
			self::$KeyLen = 64;
		}

		# save the session expiration threshold
		if(!empty($expiration_threshold) && !is_numeric($expiration_threshold) && strtotime($expiration_threshold) !== FALSE) {
			self::$expiration_threshold = strtotime($expiration_threshold);
		}
		elseif(is_numeric($expiration_threshold)) {
			self::$expiration_threshold = $expiration_threshold;
		}
    	else {
			self::$expiration_threshold = strtotime('+4 hour');
		}

    	# Detect the session ID from either cookie or url
    	self::Detect();
    	
    	# Check if the sessions table exists
    	if(self::$check_sessions_table) {
	    	$table_missing = database()->execute("SHOW TABLES LIKE 'sessions'")->null_set();
	    	if($table_missing) {
	    		database()->execute(self::$DDL);
	    	}
    	}

		# Yes - try to retrieve the session from the DB
		$query = database()->start_query('sessions')
			->where('key', self::$sid)
			->where('closed', 0)
			->raw_where("UNIX_TIMESTAMP(timestamp) < ".self::$expiration_threshold)
			->where('signature', self::get_signature())
			->limit(1)
				->run();
			
		# session found
		if(!$query->null_set()) {
			
				# apply vars to class
				self::$data = $query->row();
				self::$data->_data = (unserialize(self::$data->_data) !== FALSE)? unserialize(self::$data->_data) : array();
				if(!isset(self::$data->_data['vars'])) self::$data->_data['vars'] = array();
		} else {
				
			# bad or missing session ID
			self::Create();
		}

		# record a hit
		if($hit) {
			self::Hit();
		}
	
		if($prevent_xsrf == TRUE) {
			self::$check_xsrf = TRUE;
			self::check_xsrf_token();
			self::set_xsrf_token(FALSE);
		}
		
		return TRUE;

	}
	
	/**
	 * Set XSRF Token
	 * If there is no token in the session, then generate one.
	 *
	 * @param  bool		$force		generates a token even if check_xsrf is false
	 * @return void
	 * @author Alex King
	 **/
	public static function set_xsrf_token($force = FALSE) {
		
		// Only proceed if check_xsrf is on, or there is a force argument.
		if ($force) {
			self::$check_xsrf = TRUE;
		} elseif(!self::$check_xsrf) {
			return;
		}
				
		// Find the token name
		$token_name = self::Token_Variable;
		
		// Check if there is already a token in the session
		$token = Session::Data($token_name);
		if (empty($token)) {
			
			// We don't already have a token, generate one and save it in the session
			$token = hash('sha256', uniqid(rand(), TRUE));
			self::Data($token_name, $token);
				
		}
		
	}
	
	/**
	 * Check the $_POST for a token
	 *
	 * @return void
	 * @author Alex King
	 **/
	public static function check_xsrf_token() {
		
		# only check POST requests
		if(self::$check_xsrf && !empty($_POST)) {
			
			# verify the token
			Session::check_xsrf_token_string($_POST[self::Token_Variable]);
			
		}
	}
	
	/**
	 * Check a token against the one stored in the session
	 *
	 * @param  string	$token	the token to check
	 * @return void
	 * @author Alex King
	 **/
	public static function check_xsrf_token_string($token = false) {

		// Verify there is a token, and that it matches the one in the session.
		if(!$token || Session::Data(self::Token_Variable) !== $token) {
				
			throw new SecurityException('Token mismatch, possible cross-site request forgery');
		}

	}
	
	public static function get_xsrf_token() {
		if(self::$check_xsrf) {
			return self::Data(self::Token_Variable);
		}
	}
	
	
	public static function debug() {

		$user = print_r(self::$user, TRUE);
		$data = print_r(self::$data, TRUE);
		$sid = self::$sid;
		
		echo <<<DEBUG
		
<pre style="width:70%; border:1px solid black; background:#eee; padding:50px; margin:50px auto; clear:both; position:relative; z-index:100000;">
<b>Session debugging:</b>
SID=$sid

User=$user

Data=$data
</pre>
DEBUG;
		
	}

	
	private static function generate_sid() {
		if(self::$stronger_tokens) {
			return hash('sha256', uniqid(rand(), true));
		} else {
			return md5(uniqid(rand(), true));
		}
	}


	/**
	 * Instantiate the session
	 *
	 * Since no session has been created yet, we need to detect whether or not the user is accepting cookies
	 * so we follow the following steps:
	 *
	 * Step 1 -----------------------------------
	 * 1: Set Cookie or url SID
	 * 2: Redirect to cookie check (or) last page with additional uri sid
	 *
	 * Step 2 ----------------------------------- (cookie only)
	 * 3: If cookie is set, modify session method stored in the database
	 * 4: If cookie isn't set, then set the session method to uri
	 * 5: Redirect to home page or last accessed page, adding the uri sid if using uri method
	 *
	 */
	public static function Create()
	{
		# create unique session identifier
		$key = self::generate_sid();
					
		self::setcookie($key);
		self::Store($key);
		self::$sid = $key;
		
		# Yes - try to retrieve the session from the DB
		$session = database()->start_query('sessions')
			->where(array('key' => self::$sid))
			->limit(1)
				->run()
				->row();
		
		self::$data = (object) $session;
		self::$data->_data = (unserialize(self::$data->_data) !== FALSE)? unserialize(self::$data->_data) : array();
	}
		

	public static function Reset() {
		self::_saveData(array('closed' => 1));
		self::$data = FALSE;
		self::$user = FALSE;
		self::Create();
	}


	function setcookie($key) {
		setcookie(self::$variable, $key, self::Expiration, '/', self::$cookie_uri, (Router::protocol() == 'https' && self::$secure_cookies), self::$secure_cookies);
	}


	/**
	 * Session::Store()
	 *
	 * This function saves all the necessary session data into the database.
	 *
	 */

	private static function Store($key) {
	
		# initial data
		$data = array(
			"headers" => $_SERVER,
			"flashdata" => array()
		);
		
		# perform insert
		database()->start_query('sessions', 'INSERT')->set(array(
			"key" => $key,
			"method" => 'cookie',
			"_data" => serialize($data),
			"signature" => self::get_signature(),
		))->run();
	}
	
	/**
	 * Session::Detect()
	 * The purpose of this function is to load up the session id from the cookie or url and
	 * to indicate which method was used.
	 *
	 */

	private static function Detect() {
	
		#print_r($_COOKIE); core_halt();
		
		// check to see if a cookie is set
		$cookie = isset($_COOKIE[self::$variable]) ? strtolower($_COOKIE[self::$variable]) : false;
		if($cookie !== false && strlen($cookie) == self::$KeyLen && ctype_alnum($cookie)) {
			self::$sid = $cookie;
		} else {
			self::$sid = FALSE;
		}
	}
	
	
	public static function Hit() {
		# don't allow scripting to log more than one hit per page
		if(self::$alreadyhit == TRUE) return TRUE;
		
		# increment the hit count in the session table
		database()->start_query('sessions', 'UPDATE')
			->set(array('hits' => self::$data->hits + 1))
			->where(array('key' => self::$sid))
				->run();
		
		try {
			
			# configure the hit data
			$hit = array(
				"user" => self::$data->user,
				"ip" => $_SERVER['REMOTE_ADDR'],
				"session" => self::$sid,
				"_data"		=> serialize(array(
					'URL'          => Router::url(),
					'USER_AGENT'   => $_SERVER['HTTP_USER_AGENT'],
					'REFERER'      => $_SERVER['HTTP_REFERER'],
					'REQUEST_TIME' => $_SERVER['REQUEST_TIME'],
				)),
			);
			
			# create the hit
			database()->start_query('hits', 'INSERT')->set($hit)->run();
			
		} catch(DatabaseException $e) {
			
			// ignore
			
		}
		
		self::$alreadyhit = TRUE;
	}
	
	public static function flashData($var = NULL, $value = NULL) {
		
		# all vars
		if($var === NULL) {
			return self::$data->_data['flashdata'][self::$data->hits];
		}
		elseif(is_array($var) && $value === NULL) {
			# set a bunch of vars
			self::$data->data['flashdata'][self::$data->hits+1] = array_merge((array) self::$data->_data['flashdata'][self::$data->hits], $var);
		}
		elseif($value === NULL) {
			# specific var
			return self::$data->_data['flashdata'][self::$data->hits][$var];
		}
		else {
			# add var
			self::$data->_data['flashdata'][(self::$data->hits+1)][$var] = $value;
		}
		
		self::_saveData();
	}
	
	public static function unset_flashData($var) {
		$val = self::$data->_data['flashdata'][$var];
		unset(self::$data->_data['flashdata'][$var]);
		self::_saveData();
		return $val;
	}
	
		
	public static function Data($var = NULL, $value = NULL) {
		
		# all vars
		if($var === NULL) {
			# return all vars
			return self::$data->_data['vars'];
		}
		elseif(is_array($var) && $value === NULL) {
			# set a bunch of vars
			self::$data->_data['vars'] = array_merge((array) self::$data->_data['vars'], $var);
		}
		elseif($value === NULL) {
			# return a specific var
			return self::$data->_data['vars'][$var];
		}
		else {
			# add a single var
			self::$data->_data['vars'][$var] = $value;
		}
		
		self::_saveData();
	}
	
	public static function unset_Data($var) {
		$val = self::$data->_data['vars'][$var];
		unset(self::$data->_data['vars'][$var]);
		self::_saveData();
		return $val;
	}
	
	private static function _saveData(array $data = NULL) {
		
		$query = database()->start_query('sessions', 'UPDATE')
			->set(array("_data" => serialize(self::$data->_data)))
			->where(array('key' => self::$sid));
			
		if($data) {
			$query->set($data);
		}
		
		$query->run();
	}
	
	/**
	 * Authentication function
	 *
	 * @return bool
	 * @param $id
	 * @param $pass
	 * @param $id_column
	 * @param $pass_column
	 * @param $class
	 * @param $table
	 * @author Jake A. Smith
	 **/
	public static function authenticate($id, $pass, $id_column = "email", $pass_column = "password", $class = "User", $table = "users") {
		
		// Query $table for row with $id and hashed $pass
		$user = database()->start_query($table)
			->where(array(
				$id_column => $id,
				$pass_column => self::password_hash($pass),
				'status' => 'confirmed',
			))
				->run()
				->object($class);
		
		if($user && $user->exists()) {
			self::User($user);
			self::regenerate_sid();
			return true;
		}
		
		else {
			return false;
		}
	}
	
	
	/**
	 * User login/logout function
	 *
	 * @param $id
	 * @param $class
	 * @return User
	 */
	public static function User($id = FALSE, $class = FALSE) {

		if(!$class) $class = self::$class;
		
		
		# log out a user
		if($id === 'end') {
			database()->start_query('sessions', 'UPDATE')
				->set(array("user" => NULL))
				->where(array('key' => self::$sid))
					->run();
			self::$user = FALSE;
			return TRUE;
		}
		
		# log in a user by ID
		elseif($id && is_numeric($id)) {
			database()->start_query('sessions', 'UPDATE')
				->set(array("user" => $id))
				->where(array('key' => self::$sid))
					->run();
			self::$data->user = $id;
			self::$user = new $class($id);
			return self::$user;
		}
		
		# log in a user by class
		elseif($id && is_object($id) && get_class($id) == $class) {
			database()->start_query('sessions', 'UPDATE')
				->set(array("user" => $id->id))
				->where(array('key' => self::$sid))
					->run();
			self::$data->user = $id->id;
			return self::$user;
		}
		
		# load logged-in user from session
		elseif(is_numeric(self::$data->user) && !self::$user) {
			self::$user = new $class(self::$data->user);
			return self::$user;
		}
		
		# no data passed; not logged in
		elseif(!self::$user) {
			return new $class;
		}
		
		# no data passed; logged in
		else {
			return self::$user;
		}
	}
	
	public static function logged_in() {
		self::user();
		return (self::$user instanceof Model && self::$user->exists());
	}
	
	
	public static function login_redir($url = FALSE, $login_page = FALSE) {
		$url = ($url !== FALSE)? $url : Router::url();
		self::Data('login_redir', $url);
		Router::Redirect($login_page ? $login_page : self::$login_controller);
	}

	
	public static function regenerate_sid() {
		// Change the session ID to prevent session fixation
		$key = self::generate_sid();
		database()->start_query('sessions', 'UPDATE')
			->set(array('key' => $key))
			->where(array('key' => self::$sid))
				->run();
		self::setcookie($key);
	}


	public static function check_login() {
		self::User();
		if(!Session::logged_in() && rtrim(Router::$path, '/') != rtrim(self::$login_controller, '/')) {
			#die('login_redir => '.Router::current_url());
			self::Data('login_redir', Router::url());
			Router::Redirect(self::$login_controller);
		} else {
			return TRUE;
		}
	}
	

	public static function post_login_redir($url = '/') {
		$url = (strlen(self::Data('login_redir')) > 0 && self::Data('login_redir') != self::$login_controller)? self::Data('login_redir') : $url;
		self::unset_Data('login_redir');
		Router::Redirect($url);
	}
	
	public static function password_hash($password, $sha = FALSE) {
		if($sha || self::$stronger_tokens) {
			return hash('sha256', self::$salt.$password);
		} else {
			# maintain backward compatibility
			return md5(self::$salt.$password);
		}
	}
	
	
	public static function random_password($length = 8, $chars = '1234567890qwrypasdfghnz') {
		
		$range_start = 0;
		$range_end = strlen($chars) - 1;
		
		$password = array();
		for($i = 0; $i < $length; $i++) {
			$c = rand($range_start, $range_end);
			$password[$i] = $chars[$c];
		}
		
		return implode('', $password);
	}
	

	public static function get_signature() {
		$string = (self::$check_ip)? $_SERVER['REMOTE_ADDR'] : '';
		$string .= $_SERVER['HTTP_USER_AGENT'];
		return self::password_hash($string);
	}


}