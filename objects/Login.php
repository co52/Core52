<?php

class Login {
	
	protected $session_user_id_key;
	public $user_class;
	protected $user_object = FALSE;
	
	protected static $login_controller = '/login';
	
	/**
	 * Set up a Login object
	 *
	 * @param string $user_class Model class for user objects
	 * @param string $session_user_id_key Session variable name to use for the loggin in user ID
	 */
	public function __construct($user_class = 'User', $session_user_id_key = 'user') {
		$this->user_class = $user_class;
		$this->session_user_id_key = $session_user_id_key;
		
		// load logged in user from the session, if any
		$this->login();
	}
	
	/**
	 * Log in a user
	 *
	 * @param numeric|Model $id User ID or User model object
	 * @param string $class = FALSE User model class name
	 * @return Model
	 */
	public function login($id = FALSE, $class = FALSE) {

		if(!$class) $class = $this->user_class;
		
		if($id && $id instanceof $class) {
			
			// log in a user by object
			$this->user_object = $id;
			$_SESSION[$this->session_user_id_key] = $this->user_object->pk();
			Session::regenerate_sid(); // prevent session fixation
			
		} elseif($id && is_numeric($id)) {
			
			// log in a user by ID
			$this->user_object = new $class($id);
			$_SESSION[$this->session_user_id_key] = $this->user_object->pk();
			Session::regenerate_sid(); // prevent session fixation
			
		} elseif(isset($_SESSION[$this->session_user_id_key]) && !$this->user_object) {
			
			// load logged-in user from session
			$this->user_object = new $class($_SESSION[$this->session_user_id_key]);
			
		} elseif(!$this->user_object) {
			
			return FALSE;
			
		}
		
		
		if(is_object($this->user_object) && ! $this->user_object instanceof Model) {
			throw new Exception('User object must be a Model class');
		} else {
			return $this->user_object;
		}
	}
	
	/**
	 * Log out a user
	 */
	public function logout() {
		Session::reset(); // prevent session fixation
		$this->user_object = FALSE;
	}
	
	/**
	 * Gets the logged in user object
	 *
	 * @return boolean|Model Returns FALSE if not logged in
	 */
	public function user() {
		return $this->user_object;
	}
	
	/**
	 * Check if a user is logged in or not
	 *
	 * @return boolean
	 */
	public function is_logged_in() {
		return is_object($this->login());
	}
	
	/**
	 * Set the default login controller
	 *
	 * @param string $uri
	 */
	public static function set_login_controller($uri) {
		self::$login_controller = $uri;
	}
	
	/**
	 * Save the current URL in the session and redirect to the login page
	 *
	 * @param string $url = FALSE URL to redirect to after logging in; defaults to the current URL
	 * @param string $login_page URL of the login page; defaults to the login_controller Session setting
	 */
	public static function redir($url = FALSE, $login_page = FALSE) {
		$url = ($url !== FALSE)? $url : Router::url();
		$_SESSION['login_redir'] = $url;
		Router::Redirect($login_page ? $login_page : self::$login_controller);
	}
	
	/**
	 * Redirect to the original page after a login operation
	 *
	 * @param string $url = '/' URL to redirect to if no login_redir URL exists in the session
	 */
	public static function post_redir($url = '/') {
		$url = (strlen($_SESSION['login_redir']) > 0 && $_SESSION['login_redir'] != self::$login_controller)? $_SESSION['login_redir'] : $url;
		unset($_SESSION['login_redir']);
		Router::Redirect($url);
	}
	
	/**
	 * Check if a user is logged in, and redirect to the login controller if not
	 *
	 * @return boolean Returns TRUE if logged in
	 */
	public function check_redir() {
		if(!$this->is_logged_in() && rtrim(Router::$path, '/') != rtrim(self::$login_controller, '/')) {
			self::redir();
		} else {
			return TRUE;
		}
	}
	
}

