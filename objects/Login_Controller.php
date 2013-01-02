<?php

class LoginException extends Exception {}

/**
 * Base Login Controller
 * @author Jonathon Hill
 *
 */
abstract class Login_Controller extends Controller {
	
	/**
	 * URL of home controller
	 * @var string
	 */
	protected $_home_url = '/';
	
	/**
	 * URL of login controller
	 * @var string
	 */
	protected $_login_url = '/login';
	
	/**
	 * Login page view file
	 * @var string
	 */
	protected $_login_form_template = 'user/login';
	
	/**
	 * Login error message
	 * @var string
	 */
	protected $_login_error = 'Login failed';
	
	
	/**
	 * Password reset view file
	 * @var string
	 */
	protected $_password_reset_template = 'user/password_reset';
	
	/**
	 * Password change view file
	 * @var string
	 */
	protected $_password_change_template = 'user/password_change';
	
	/**
	 * Password reset plaintext email view file
	 * @var string
	 */
	protected $_password_email_template = 'user/email_password_reset';
	
	/**
	 * Password reset email subject line
	 * @var string
	 */
	protected $_password_reset_subject = 'Reset Password';
	
	
	/**
	 * User Model class name
	 * @var string
	 */
	protected $_user_model = 'User';
	
	/**
	 * User Model username field
	 * @var string
	 */
	protected $_user_model_username = 'email';
	
	/**
	 * User Model email field
	 * @var string
	 */
	protected $_user_model_email = 'email';
	
	/**
	 * User Model password field
	 * @var string
	 */
	protected $_user_model_password = 'password';
	
	/**
	 * User Model temporary password field
	 * @var string
	 */
	protected $_user_model_temp_password = 'temp_password';
	
	/**
	 * User Model real name field
	 * @var string
	 */
	protected $_user_model_name = 'name';
	
	
	/**
	 * Login form fields
	 * @var array
	 */
	protected $_fields_login = array(
		array(
			'name'	=> 'username',
			'rules'	=> 'required|valid_email|_check_login',
		),
		array(
			'name'	=> 'password',
			'rules'	=> 'required',
			'type'	=> 'password',
		),
	);
	
	
	/**
	 * Password change form fields
	 * @var array
	 */
	protected $_fields_password = array(
		array(
			'name'	=> 'password',
			'rules'	=> 'required|valid_password[8]',
			'type'  => 'password',
		),
		array(
			'name'	=> 'password2',
			'rules'	=> 'required|matches[password]',
			'type'	=> 'password',
			'label' => 'Confirm Password',
		),
	);
	
	
	
	/**
	 * User Model ID (pk)
	 * @var integer
	 */
	protected $_user_id = NULL;
	
	
	/**
	 * Login page
	 */
	public function _default() {
		
		# redir to home page if already logged in
		if(Session::logged_in()) {
			Session::post_login_redir($this->_home_url);
		}
		
		$this->view->Load($this->_login_form_template);
		$this->_load_form($this->_fields_login); // load Form object, $this->form

		
		if($this->form->validate()) {
			
			# user exists?
			# $this->_user_id should be set by the _check_login validation callback
			$user = new $this->_user_model($this->_user_id);
			if(!$user->exists()) {
				
				# something went wrong and $this->_user_id is invalid or not set.
				# if you're getting this, be sure that $this->_check_login() is
				# setting $this->_user_id when the check succeeds
				throw new LoginException($this->_login_error);
				
			} else {
				
				# log in
				$this->_login_user($user);
				
			}
			
		} else {
			
			$this->view->Data('errors', $this->form->errors);
			
		}
		
		$this->output();
	}
	
	
	/**
	 * "Forgot my password" page
	 */
	public function reset() {
		
		# redir to home page if already logged in
		if(Session::logged_in()) {
			Session::post_login_redir($this->_home_url);
		}
		
		$this->view->Load($this->_password_reset_template);
		$this->_load_form($this->_fields_login);
		
		# @HACK to remove the password field on the initial reset form
		unset($this->form->rules['password']);
		unset($this->form->types['password']);
		
		# @HACK change the username validation rules
		$this->form->rules['username'] = 'required|valid_email';

		
		if($this->form->validate()) {
			
			try { // prevent username discovery
			
				$users = Model::find($this->_user_model, array($this->_user_model_username => $this->form->field('username')));
				
				if(count($users) === 1) {
					# set a temp password and email it to the user
					$user = array_pop($users);
					$success = $this->_reset_password($user);
				}
				
			} catch(Exception $e) {
				# log this error if desired, but don't indicate
				# anything was amiss to the end user for security reasons
				throw $e;
			}
			
			$this->view->Data('reset', TRUE);
			
		} else {
			
			$this->view->Data('errors', $this->form->errors);
			
		}
		
		$this->output();
	}
	
	
	/**
	 * Change password page
	 */
	public function password() {
	
		# user must log in before changing the password
		$this->_require_user();
		
		
		$this->_load_form($this->_fields_password);
		$this->view->load($this->_password_change_template);

		
		if($this->form->validate()) {
			
			# change the password and go home
			$this->user->save(array(
				$this->_user_model_password => Session::password_hash($this->form->field('password')),
				$this->_user_model_temp_password => NULL, // clear temp password
			));
			
			Router::redirect($this->_home_url);
			
		} elseif($this->form->submitted()) {
			
			$this->view->data('errors', $this->form->errors);
			
		}

		
		if(!empty($this->user->temp_password)) {
			$this->view->data('temp_password', TRUE);
		}
		
		$this->view->data('form', $this->form->render_fields());
		$this->output();
	}
	
	
	/**
	 * Log out page
	 */
	public function end_session() {
		Session::reset(); // close the old session and open a new one
		Router::redirect($this->_login_url);
	}
	
	
	/**
	 * Set a random temp password and email it to the user
	 * @param Model $user
	 * @return boolean E-mail send successful
	 */
	protected function _reset_password($user) {
		
		# set a random temporary password
		$password = Session::random_password();
		$user->save(array(
			$this->_user_model_temp_password => Session::password_hash($password),
		));
		
		$email = $user->{$this->_user_model_email};
		$username = (method_exists($user, 'name'))? $user->name() : $user->{$this->_user_model_name};
		
		# email password reset email
		$mailer = Mailer::factory();
		$mailer->AddAddress($email);//, $username);
		$mailer->Subject = $this->_password_reset_subject;
		
		$view = new FastViewObject();
		$view->load($this->_password_email_template);
		$view->data('user', $user);
		$view->data('name', $username);
		$view->data('password', $password);
		$view->data('url', Router::domain(Router::protocol().'://', FALSE).$this->_login_url);
		$mailer->Body = $view->Publish(TRUE);
		
		# return success indicator (boolean)
		return $mailer->send();
	}
	
	
	/**
	 * Log in a user
	 * @param Model $user
	 */
	protected function _login_user($user) {
		
		# log in
		Session::User($this->_user_id, $this->_user_model);
		Session::regenerate_sid(); // prevent session fixation attacks
		
		# temp password set?
		if(!empty($user->{$this->_user_model_temp_password})) {
			
			# yes - redir to password change page
			Router::redirect("$this->_login_url/password");
			
		} else {
			
			# go to home page
			Session::post_login_redir($this->_home_url);
			
		}
	}
	
	
	/**
	 * Form validation callback - _check_login
	 *
	 * @param string $str
	 * @param string $parameters
	 * @param string $field
	 */
	public function _check_login($str, $parameters, $field) {
		
		# lookup the user based on login credentials
		# match: username + (password OR temp_password)
		$obj = new $this->_user_model;
		$users = $obj->db()->start_query($obj->table())
			->where($this->_user_model_username, $this->form->field('username'))
				->run()
				->objects($this->_user_model);

		
		# exactly one user found - valid
		if(count($users) === 1) {
			
			# reload the model normally in case there's a custom_query in use
			$user_id = $users[0]->pk();
			$user = new $this->_user_model($user_id);
			
			if(!$this->_check_user_password($user)) {
				$this->form->set_error_message('_check_login', $this->_login_error);
				$this->form->set_error($field, '_check_login');
				return FALSE;
			} else {
				return TRUE;
			}
			
		} elseif(empty($str)) {
			
			# don't check empty username
			return TRUE;
			
		} else {
			
			$this->form->set_error_message('_check_login', $this->_login_error);
			$this->form->set_error($field, '_check_login');
			return FALSE;
			
		}
	}
	
	
	protected function _check_user_password(Model $user) {
		
		$pwd = Session::password_hash($this->form->field('password'));
		$user_pwd = $user->{$this->_user_model_password};
		$user_pwd_tmp = $user->{$this->_user_model_temp_password};
		
		if(
			# user blank? (could be blank if user role check failed during construction)
			!$user->exists() ||
		
			# password or temporary password mismatch?
			!($user_pwd === $pwd || (!empty($user_pwd_tmp) && $user_pwd_tmp === $pwd)) ||
			
			# user account inactive?
			(method_exists($user, 'active') && !$user->active())
		) {
			
			return FALSE;
			
		} else {
			
			$this->_user_id = $user->pk();
			return TRUE;
			
		}
		
	}
	
	
}

