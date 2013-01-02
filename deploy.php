<?php

ini_set('show_errors', 'On');
error_reporting(E_ALL ^ E_NOTICE);


/**
 * Core52 Deploy Script
 */

class Deploy {
	
	public $name = 'deploy.php';
	public $description = 'Core52 deploy script';
	
	private $args = array(
	
		'[app-name]' => array(
			'required' => TRUE,
			'description' => 'Application name (letters, numbers, and underscore only)',
		),
		
		'--username' => array(
			'required' => TRUE,
			'description' => 'SVN username',
		),
		/*'-U' => array(
			'alias' => '--username',
		),*/
		
		'--password' => array(
			'required' => TRUE,
			'description' => 'SVN password',
		),
		/*'-P' => array(
			'alias' => '--password',
		),*/
		
		'--app' => array(
			'required' => TRUE,
			'description' => 'URL of application repository',
		),
		
		'--core' => array(
			'required' => FALSE,
			'description' => 'URL of Core52 repository',
			'default' => 'https://svn.company52.com/core52/trunk'
		),
		
		'--db' => array(
			'required' => FALSE,
			'description' => 'Database configuration string [user:password@host/db]',
		),
		
		'--svn' => array(
			'required' => TRUE,
			'description' => 'Path to the svn command',
			'default' => 'svn',
		),
		
		'--dir' => array(
			'required' => TRUE,
			'description' => 'Application directory (should be outside your web server document root for security)',
			'default' => '',
		),
		
		'--authdir' => array(
			'required' => FALSE,
			'description' => 'Authentication file directory',
			'default' => '/fiftytwo/auth',
		),
		
	);
	
	private $_line_ending = "\n";
	private $_stdin;
	private $_args = array();
	
	
	
	public function __construct() {
		$this->_stdin = fopen("php://stdin", 'r');
		
		# get the CLI args
		array_shift($_SERVER['argv']);
		$this->_args = (array) $_SERVER['argv'];
	}
	
	
	public function __destruct() {
		fclose($this->_stdin);
	}
	
	
	private function input($prompt, $trim = TRUE) {
		$this->output($prompt, ': ');
		$input = fgets($this->_stdin, 1024);
		return ($trim)? trim($input) : $input;
	}
	
	
	private function output($str, $line_ending = NULL) {
		$le = ($line_ending === NULL)? $this->_line_ending : $line_ending;
		echo $str.$le;
	}
	
	
	private function parse_args($check_missing = TRUE) {
		
		# check each argument
		foreach($this->_args as $i => $arg) {
			
			# separate the parameter from the value
			list($arg, $value) = explode('=', $arg);
			
			# store the arg value
			if($this->set_arg_value($arg, $value)) {
				unset($this->_args[$i]);
			}
		}
		
		# check for anonymous arguments
		foreach($this->args as $arg => $settings) {
			
			if($arg === '['.trim($arg, '[]').']') {
				$this->set_arg_value($arg, array_shift($this->_args));
			}
			
		}
		
		# check for invalid arguments
		if(count($this->_args) > 0) {
			$this->error(new InvalidArgumentException("Invalid Arguments: ".implode(', ', $this->_args)), TRUE);
		}
		
		# check for missing required arguments
		if($check_missing) {
			$missing = array();
			foreach($this->args as $arg => $settings) {
				
				$value = $this->get_arg_value($arg);
				if($settings['required'] === FALSE && empty($value)) {
					$missing[] = $arg;
				}
				
			}
			if(!empty($misssing)) {
				throw new InvalidArgumentException("Missing required arguments: ".implode(', ', $missing));
			}
		}
		
	}
	
	
	private function set_arg_value($arg, $value) {
		
		if(isset($this->args[$arg]['alias'])) {
			$arg = $this->args[$arg]['alias'];
		}
		
		if(isset($this->args[$arg])) {
			$this->args[$arg]['value'] = $value;
			return TRUE;
		} else {
			return FALSE;
		}
		
	}
	
	
	private function get_arg_value($arg, $prompt = NULL) {
		
		if(isset($this->args[$arg]['alias'])) {
			$arg = $this->args[$arg]['alias'];
		}
		
		if(empty($this->args[$arg]['value'])) {
			$default = $this->args[$arg]['default'];
			if($prompt !== NULL) {
				$description = $this->args[$arg]['description'];
				if(!is_string($prompt)) {
					$prompt = "$description\nPress ENTER for default ($default)";
				}
				$value = $this->input($prompt);
				$this->args[$arg]['value'] = (empty($value))? $default : $value;
			} else {
				return $default;
			}
		}
		
		return $this->args[$arg]['value'];
		
	}
	
	
	private function usage($return = FALSE) {
		
		$usage = <<<TXT

$this->description
-----------------------------

Usage: $this->name [args]

Arguments:

TXT;

		$col1_width = $this->get_arg_col_width();
		$col2_width = $this->get_description_col_width();
		
		foreach($this->args as $arg => $settings) {
			$usage .= "\n";
			$usage .= str_pad($arg, $col1_width);
			$usage .= wordwrap(
				(($settings['required'] === TRUE)? '[required] ' : '') . $settings['description'],
				$col2_width,
				"\n".str_repeat(' ', $col1_width)
			);
			#$usage .= "\n";
		}
		
		if($return) {
			return $usage;
		} else {
			$this->output($usage);
		}
		
	}
	
	
	private function get_arg_col_width() {
		
		$max = 0;
		
		foreach($this->args as $arg => $settings) {
			if(strlen($arg) > $max) {
				$max = strlen($arg);
			}
		}
		
		return $max + 2;
	}
	
	
	private function get_description_col_width() {
		
		return 80 - $this->get_arg_col_width();
		
	}
	
	
	private function error(Exception $e) {
		
		$this->output(get_class($e).' in '.$e->getFile().'['.$e->getLine()."]: ".$e->getMessage());
		
		if($e instanceof InvalidArgumentException) {
			$this->usage();
		}
		
		exit;
	}
	
	
	public function run() {
		
		# Startup
		$this->parse_args();
		#print_r($this->args); die;
		$this->output("\n$this->description\n-----------------------------\n");
		$this->output("Step 1: Download your application\n");
		
		
		# Get params, prompt interactively if needed
		$dir = $this->get_arg_value('--dir', TRUE);
		$svn = $this->get_arg_value('--svn', TRUE);
		$username = $this->get_arg_value('--username', TRUE);
		$password = $this->get_arg_value('--password', TRUE);
		$core_url = $this->get_arg_value('--core', TRUE);
		$app_url  = $this->get_arg_value('--app', TRUE);
		
		if(empty($dir)) {
			$dir = dirname(__FILE__);
		} elseif(!file_exists($dir)) {
			mkdir($dir, 0777, TRUE);
		}
		
		
		# Check out the core (svn co)
		if(!file_exists("$dir/core")) {
			$this->output("Downloading core, please wait...");
			exec("$svn checkout $core_url --username $username --password $password $dir/core");
		} else {
			$this->output("Core directory already exists, skipping");
		}
		
		# Check out the app (svn co)
		if(!file_exists("$dir/app")) {
			$this->output("Downloading application, please wait...");
			exec("$svn checkout $app_url --username $username --password $password $dir/app");
		} else {
			$this->output("Application directory already exists, skipping");
		}
		
		
		# Prompt for additional settings
		$this->output("\n-----------------------------\n\nStep 2: Configure\n");
		$app_name = $this->get_arg_value('[app-name]', TRUE);
		$db_conn  = $this->get_arg_value('--db', TRUE);
		$auth_dir = $this->get_arg_value('--authdir', TRUE);
		
		
		# If nothing is in the app directory...
		if(!file_exists("$dir/app/config.php")) {
			
			# export from the core/app directory (svn export)
			exec("$svn export $core_url/app --username $username --password $password $dir/app");
			
			# Create the app/config.php file
			$config = <<<PHP
<?php

# Define the app name
define('APP_NAME', '$app_name');
PHP;
			file_put_contents("$dir/app/config.php", $config);
		}
		
		
		# Create an apache vhost configuration file
		$vhosts = <<<CONF
<VirtualHost *:80>
	ServerName $app_name
	DocumentRoot $dir/app/controllers

	<Directory "$dir/app/controllers">
		Options All
		AllowOverride All
		order allow,deny
		allow from all
	</Directory>
        
        Alias /static "$dir/app/static"
        <Directory "$dir/app/static">
		Options All
		AllowOverride All
		order allow,deny
		allow from all
	</Directory>
</VirtualHost>
CONF;
		file_put_contents("$dir/vhost.conf", $vhosts);
		$this->output("\nApache settings saved in $dir/vhost.conf\n");
		
		
		# Create an auth file
		$salt = md5(microtime(TRUE));
		$auth = <<<PHP
<?php

# Auth file for $app_name

######################################################################################
# misc settings
######################################################################################
	
	Config::set('EMAIL_DEBUG_RCPT', '');
	Config::set('salt', '$salt');


######################################################################################
# configure e-mail
######################################################################################

	Mailer::Initialize(array(
	      'from' => 'no-reply@$app_name.com',
	      'from_name' => '$app_name',
	      'word_wrap' => 75,
	      #'smtp' => TRUE,
	      #'smtp_host' => 'mail.$app_name.com',
	      #'smtp_port' => 25,
	      #'smtp_user' => 'user@$app_name.com',
	      #'smtp_pass' => 'qjpwoeiut23',
	      #'smtp_timeout' => '10',
	));
	
PHP;
		if(!empty($db_conn)) {
			
			list($user, $pass, $host, $db) = preg_split('/[@://]/');
			$auth .= <<<PHP
	
######################################################################################
# configure databases
######################################################################################

	DatabaseConnection::factory('default', array(
		'host'	=> '$host',
		'user'	=> '$user',
		'pass'	=> '$pass',
		'db'	=> '$db',
		'debug'	=> TRUE,
		'persist' => TRUE,
	), TRUE);

	Database::Initialize('default');

PHP;
		}
		
		file_put_contents("$auth_dir/$app_name.php", $auth);
		$this->output("Authentication settings saved in $auth_dir/$app_name.php\n");
		
		
		$this->output("Deploy successful!\n\n");
		
	}
	
	
	
	
}


$obj = new Deploy();
$obj->run();
