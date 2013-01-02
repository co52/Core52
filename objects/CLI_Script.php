<?php

abstract class CLI_Script {

	// Descriptive information
	public $appName;
	public $appDescription;
	public $authorName;
	public $authorEmail;
	public $emergencyNotifyEmail;
	
	
	// Allowed arguments and their defaults
	protected $options = array(
	    'help' => false,
	);
	
	protected $required_options = array();
	
	
	// Results from option callback functions
	protected $optiondata = array();
	
	// Runtime variables
	protected $started = 0;
	protected $ended = 0;
	protected $uptime = 0;
	protected $logfile;
	protected $log_message_fmt = "{date} [{script}] {msg}\n";
	
	
	/**
	 * Initialize the script object
	 */
	public function __construct() {
		
		if(PHP_SAPI !== 'cli') {
			die('This script must be run in CLI mode');
		}
		
		error_reporting($this->error_reporting);
	}
	
	/**
	 * Start and iterate the daemon
	 */
	public function run() {
		
		$this->_startup();

		// Parse and act on args passed
		foreach($this->_get_options() as $option) {
			$this->_call_option_callback($option);
		}
		
		// Check if all the required options are set,
		// and if not, display the usage screen
		foreach($this->required_options as $i => $ro) {
			if($this->options[$ro]) {
				unset($this->required_options[$i]);
			}
		}
		if(count($this->required_options)) $this->help();

		// Main processing
		$this->runtime();
		 
		$this->_shutdown();
	}
	
	/**
	 * Main processing occurs here
	 */
	abstract public function runtime();
	
	/**
	 * Initialize and start the daemon
	 */
	protected function _startup() {
		$this->started = microtime(TRUE);
		$this->runningOkay = TRUE;
	}
	
	/**
	 * Stop the daemon and set the ended and uptime
	 */
	protected function _shutdown() {
		$this->ended = microtime(TRUE);
		$this->uptime = $this->ended - $this->started;
	}
	
	/**
	 * Get the CLI options
	 * @return array
	 */
	protected function _get_options() {
		
		$options = array();
		
		// Scan command line attributes for allowed arguments
		foreach((array) $_SERVER['argv'] as $k => $arg) {
			list($arg, $value) = explode('=', $arg);
		    if(substr($arg, 0, 2) == '--' && isset($this->options[substr($arg, 2)])) {
		        $this->options[substr($arg, 2)] = true;
		        $this->optiondata[substr($arg, 2)] = $value;
		        $options[] = substr($arg, 2);
		    }
		}
		
		return $options;
	}
	
	/**
	 * Runs the callback function for each CLI option, if available
	 * @param string|array $mode
	 * @return NULL
	 */
	protected function _call_option_callback($mode) {
		$this->uptime += (microtime(TRUE) - $this->started);
		if(!$mode) return;
		$value = $this->optiondata[$mode];
		$mode = str_replace('-', '_', $mode);	// replace dashes with underscores
		if(method_exists($this, $mode)) {
			call_user_func(array($this, $mode), $value);
		}
		$this->uptime += (microtime(TRUE) - $this->started);
	}
	
	/**
	 * Displays the available script options
	 * Called via the --help CLI option
	 *
	 */
	protected function help() {
		// Help mode. Shows allowed argumentents and quit directly
	    echo "\nUsage: {$_SERVER['argv'][0]} [options]\n";
	    echo "\nAvailable options:\n";
	    foreach($this->options as $runmod => $val) {
	        echo " --$runmod";
	        if(in_array($runmod, $this->required_options)) {
	        	echo " (required)";
	        }
	        echo "\n";
	    }
	    exit(0);
	}
	
	/**
	 * Log a message to stdout, stderr, a file, and/or an e-mail address (if an emergency).
	 *
	 * @param string $msg
	 * @param boolean $emergency
	 * @param array $log_to = array('stdout', 'file')
	 * @param mixed $fmt
	 */
	protected function _log($msg, $emergency = FALSE, array $log_to = array('stdout', 'file'), $fmt = FALSE) {
		
		global $argv;
		
		if($fmt == FALSE) {
			$fmt = $this->log_message_fmt;
		}
		
		$replace = array(
			'{date}' => date('Y/m/d H:i:s'),
			'{script}' => $argv[0],
			'{msg}' => $msg,
		);
		
		$msg = str_replace(array_keys($replace), array_values($replace), $fmt);
		
		# normalize the destination array
		$log_to = array_flip($log_to);
		
		# log to STDOUT
		if(isset($log_to['stdout'])) {
			fputs(STDOUT, $msg);
		}
		
		# log to STDERR
		if(isset($log_to['stderr'])) {
			fputs(STDERR, $msg);
		}
		
		# log to one or more files
		if(isset($log_to['file']) && !empty($this->logfile)) {
			@error_log($msg, 3, $this->logfile)
				or $this->_log("Error logging \"$msg\" to logfile $this->logfile: logfile not writable.", $emergency, array('stderr'));
		}
		
		# try to call for emergency help via e-mail
		if($emergency && $this->emergencyNotifyEmail) {
			error_log($msg, 1, $this->emergencyNotifyEmail)
				or $this->_log("Failed sending emergency error e-mail to $this->emergencyNotifyEmail", FALSE, array('stderr'));
		}
	}
	
	/**
	 * Log a message without formatting it
	 *
	 * @param string $msg
	 * @param array $log_to = array('stdout')
	 */
	protected function _log_simple($msg, array $log_to = array('stdout', 'file')) {
		$this->_log($msg, FALSE, $log_to, '{msg}');
	}
	
	/**
	 * Log an informational message
	 *
	 * @param string $msg
	 * @param mixed $fmt
	 */
	protected function _log_notice($msg, $fmt = FALSE) {
		$this->_log($msg, FALSE, array('stdout', 'file'), $fmt);
	}
	
	/**
	 * Log an error message
	 *
	 * @param string $msg
	 * @param boolean $emergency = FALSE
	 * @param mixed $fmt
	 */
	protected function _log_error($msg, $emergency = FALSE, $fmt = FALSE) {
		$this->_log("ERROR: $msg", $emergency, array('stderr', 'file'), $fmt);
	}
	
	/**
	 * Prompt for input from the terminal
	 * 
	 * @param string $prompt Text to prompt
	 * @param array|string $valid_inputs An array of valid inputs (use '' to represent <enter>), or a callback to validate the input (such as is_string)
	 * @param mixed $default
	 * @return mixed
	 */
	protected function _prompt($prompt, $valid_inputs, $default = '') { 
	    while(
    		!isset($input) || 
    		(is_array($valid_inputs) && !in_array($input, $valid_inputs)) || 
    		(is_callable($valid_inputs) && !call_user_func($valid_inputs, $input))
	    ) { 
	        echo $prompt; 
	        $input = strtolower(trim(fgets(STDIN))); 
	        if(empty($input) && !empty($default)) { 
	            $input = $default; 
	        } 
	    } 
	    return $input; 
	}
	
	
}

