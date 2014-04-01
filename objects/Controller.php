<?php

/**
 * Core52 Controller Class
 *
 * Develop for added functionality to controllers.
 * "Someday we will figure out what else to add to this." - Jake A. Smith
 *
 * @author "Jonathon Hill" <jhill@companyfiftytwo.com>
 * @package Core52
 * @version 0.1
 *
 **/
abstract class Controller {
	
	/**
	 * Form object
	 * @var Form
	 */
	public $form;
	
	/**
	 * View tpe
	 *
	 * @var string
	 **/
	protected $_view_type = 'ViewObject';
	
	/**
	 * Require login to access this controller
	 *
	 * @var boolean
	 */
	protected $_require_auth = FALSE;
	
	/**
	 * Array of allowed user IDs
	 *
	 * @var array
	 */
	protected $_allowed_users = NULL;
	
	/**
	 * Array of allowed user role IDs
	 *
	 * @var array
	 */
	protected $_allowed_roles = NULL;
	
	/**
	 * Enable auto XSS filtering of $_GET, $_POST, and $_REQUEST
	 *
	 * @var boolean
	 */
	protected $_sanitize_data = TRUE;

	/**
	 * Set to a string to force accessing this controller using the specified protocol (http or https)
	 * @var string
	 */
	protected $_force_protocol = FALSE;
	
	/**
	 * The user that is logged in
	 *
	 * @var User
	 */
	public $user = NULL;
	protected $_user_class = 'User';
	
	/**
	 * Array of DatabaseConnection objects
	 * @var array
	 */
	protected $db_objects = array();
	
	/**
	 * Page title
	 * @var $_page_title string
	 */
	protected $_page_title = '';
	
	
	/**
	 * Controller initialization
	 * @return void
	 */
	public function __construct() {
		
		$this->_load_viewparser();
		
		if($this->_sanitize_data) {
			$this->_sanitize_input();
		}
		
		if ($this->_require_auth) {
			$this->_require_user();
		} else {
			$this->_load_user();
		}
		
		if ($this->_force_protocol) {
			$this->_force_protocol();
		}
		
		if($this->_fields && is_array($this->_fields) && is_null($this->form)) {
			$this->_load_form($this->_fields);
		}
		
	}
	
	
	
	/**
	 * Sanitize $_POST/$_GET/$_REQUEST against XSS
	 * @return void
	 */
	protected function _sanitize_input() {
		
		$form = new Form('post', NULL, 'xss');
		$_POST = $form->input;
		
		$form = new Form('get', NULL, 'xss');
		$_GET = $form->input;
		
		$form = new Form('request', NULL, 'xss');
		$_REQUEST = $form->input;
	}
	
	
	/**
	 * Load form object
	 * @return void
	 */
	protected function _load_form(array $fields = array(), $class = 'Form', $set = TRUE, $data = NULL) {
		switch($this->_sanitize_data) {
			case TRUE:
				$sanitize = 'xss';
				break;
			case FALSE:
				$sanitize = FALSE;
				break;
			default:
				$sanitize = $this->_sanitize_data;
				break;
		}
		$form = new $class('post', $data, $sanitize);
		if(! $form instanceof Form) {
			throw new InvalidArgumentException("$class must extend Form");
		}
		$form->add_fields($fields);
		$form->controller =& $this;
		
		if($set) {
			$this->form = $form;
			return $this->form;
		} else {
			return $form;
		}
	}
	
	
	/**
	 * Load view object
	 * @return void
	 */
	protected function _load_viewparser() {
		
		$this->view = new $this->_view_type();
	}
	
	
	/**
	 * Load user, verify authentication and permissions
	 * @return User
	 */
	protected function _require_user($msg = NULL) {
		
		if(! ($this->user instanceof $this->_user_class && $this->user->exists())) {
			if(!$this->_load_user()) {
				if(!is_null($msg)) Session::flashData('msg', $msg);
				Session::login_redir();
			}
		}
		
		# Prevent caching of authenticated pages
		if(!headers_sent()) {
			header('Cache-Control: no-store, no-cache');
			header('Pragma: no-cache');
			header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');	# date in the past
		}
		
		return $this->user;
	}
	
	
	/**
	 * Load user, if logged in
	 *
	 * @return User
	 */
	protected function _load_user() {
		try {
			if(Session::logged_in()) {
				$this->user = Session::User();
				
				# make sure user has permission to access this page
				if(
					($this->_allowed_users === NULL || in_array($this->user->pk(), (array) $this->_allowed_users)) &&
					($this->_allowed_roles === NULL || in_array($this->user->role()->pk(), (array) $this->_allowed_roles) || count(array_intersect($this->user->roles(), (array) $this->_allowed_roles)) > 0)
				) {
					# user has permission, proceed
				} else {
					throw new AccessDeniedException();
				}
				
				return $this->user;
			} else {
				return FALSE;
			}
		} catch(AutoClassLoaderException $e) {
			return FALSE;
		}
	}

	
	protected function _force_protocol($protocol = FALSE) {
		if($protocol === FALSE) $protocol = $this->_force_protocol;
		$protocol = strtolower($protocol);
	
		# force protocol if specified, and not running in dev mode
		if($protocol == 'http' || $protocol == 'https') {
    		if(Router::protocol() != $protocol && !(defined('DEV_MODE') && DEV_MODE)) {
    			Router::redirect(Router::url($protocol));
    		}
		} elseif(!empty($protocol)) {
			throw new Exception("Invalid protocol: $protocol");
		}
	}
	
	/**
	 * Returns a database connection
	 * @return DatabaseConnection
	 */
	protected function db($db = NULL) {
		if(!isset($this->db_objects[$db]) || !$this->db_objects[$db] instanceof DatabaseConnection) {
			$this->db_objects[$db] =& DatabaseConnection::factory($db);
		}
		return $this->db_objects[$db];
	}
	
	
	/**
	 * Publish the template
	 * @param boolean $return
	 * @return string
	 */
	protected function output($return = FALSE) {
		if($this->view instanceof ViewObject) {
			$this->view->parse();
		}
		if($this->form instanceof Form) {
			$this->view->Data('form_instance', $this->form);
			$this->view->Data('form', $this->form->render_fields());
		}
		if($this->_page_title) {
			$this->view->Global_Data('title', $this->_page_title);
		}
		return $this->view->publish($return);
	}
	
	
	/**
	 * Publish output as JSON
	 *
	 * @param array|object $data
	 * @return NULL
	 */
	protected function output_json($data) {
		json_send($data);
    	core_halt();
	}
	
	
	/**
	 * Call a method in another controller
	 * @param string $controller
	 * @param string $method
	 * @param array $args
	 */
	protected function call() {
	
		list($controller, $method, $args) = func_get_args();
	
		if(is_object($controller)) {
			
			if(! $controller instanceof Controller) {
				throw new InvalidArgumentException("Not a Controller instance");
			} else {
				$obj = $controller;
			}
			
		} else {
			
			# load the controller
			$class = core_load_controller($controller);
			
			# validate controller class
			if(!is_subclass_of($class, 'Controller')) {
				throw new InvalidArgumentException("$class must extend Controller");
			} elseif(!method_exists($class, $method)) {
				throw new InvalidArgumentException("$class::$method does not exist");
			} elseif(!is_callable(array($class, $method))) {
				throw new InvalidArgumentException("$class::$method is not callable");
			} elseif($obj === NULL) {
				$obj = new $class;
			}
			
		}
	
		# call the controller
		call_user_func_array(array($obj, $method), (array) $args);
	}

}



