<?php
/**
 * PHP self-monitoring Daemon class
 *
 * @author 		Jonathon Hill (jonathon@compwright.com)
 * @name 		Daemon.class.php
 * @version 	0.1
 * @uses 		Use whenever you need to watch or listen for data, and fork off processes to handle that data
 *
 */
abstract class Daemon {
	
	public $monitor_sleep_interval 	= 1;		// daemon monitor polling interval (seconds)
	public $daemon_memory_limit		= '32M';	// memory limit
	public $daemon_listen_interval	= 2000000;	// daemon polling interval microseconds
	public $daemon_restart_limit   	= 100;		// max daemon restart attempts
	public $max_concurrent 			= 5;		// max number of child processes the daemon can fork at a given time
	public $emergency_email 		= 'jonathon@compwright.com';	// send emergency error e-mails here
	public $log_file_path			= 'daemon.log';					// log to this file
	public $log_to					= 'file';						// log to file or screen?
	public $pid_file				= 'daemon.pid';
	
	protected $daemon_pidfile_handle;
	protected $monitor_pid = FALSE; // This is the PID for the monitor process (parent of daemon)
	protected $daemon_pid = false;	// IMPORTANT: This is the PID for the main daemon process which we will fork off.
	protected $daemon_pids = array();	// Array of daemon child processes;
									// used by the daemon process to ensure we don't exceed the max_concurrent limit
	protected $retry_cnt  = 0;		// Daemon restart counter
	protected $started_on;			// Daemon start microtime()
	protected $uptime;				// Daemon uptime

	protected static $err_levels = array(
		E_ERROR				=>	'UNEXPECTED FATAL ERROR',
		E_WARNING			=>	'UNEXPECTED WARNING',
		E_PARSE				=>	'PARSING ERROR',
		E_NOTICE			=>	'NOTICE',
		E_CORE_ERROR		=>	'CORE ERROR',
		E_CORE_WARNING		=>	'CORE WARNING',
		E_COMPILE_ERROR		=>	'COMPILE ERROR',
		E_COMPILE_WARNING	=>	'COMPILE WARNING',
		E_USER_ERROR		=>	'FATAL ERROR',
		E_USER_WARNING		=>	'WARNING',
		E_USER_NOTICE		=>	'NOTICE',
		E_STRICT			=>	'RUNTIME NOTICE'
	);
	
	protected static $signals = array(
		SIGTERM => 'SIGTERM',
		SIGINT  => 'SIGINT',
		SIGHUP  => 'SIGHUP',
		SIGKILL => 'SIGKILL',
		SIGCHLD => 'SIGCHLD',
	);
	
	
	/**
	 * Initialize the Daemon
	 *
	 */
	public function __construct() {
		ini_set('MAX_INPUT_TIME', 0);
		ini_set('MAX_EXECUTION_TIME', 0);
		set_time_limit(0);
		ini_set('memory_limit', $this->daemon_memory_limit);
		set_error_handler(array($this, 'error_handler'), E_ALL ^ E_NOTICE);
	}
	
	
	/**
	 * Shut down the Daemon
	 *
	 */
	public function __destruct() {
		if($this->in_child) return;	// this destructor is only for the monitor or daemon processes
		$this->stop();				// stop the daemon process
		$this->log("Exiting daemon (uptime was $this->uptime).");
		die("\n\n");
	}
	
	
	public function daemonize() {
		$this->started_on = null;	// reset the start time. we'll reset it in the parent, but not the child.
		$pid = pcntl_fork();		// fork off a process. This function returns twice - once in the parent, once in the child.
		switch($pid) {
			case -1:				// error (out of memory, usually)
				$this->in_child = TRUE; // @HACK to disable __destruct()
				return FALSE;
			case 0:					// the (orphan) child
				$this->run();		// launch the daemon
				exit;
			default:				// the parent
				$this->in_child = TRUE; // @HACK to disable __destruct()
				return $pid;		// return the child PID
		}
	}
	
	
	/**
	 * Run (start) the Daemon
	 *
	 */
	public function run() {
		
		$this->monitor_pid = $this->get_pid();
		$this->daemon_pid = $this->start();	// start the Daemon
		if(!$this->daemon_pid) {
			throw new Exception("Could not start the daemon");
		}
		else {
			
			if(file_exists($this->pid_file)) {
				unlink($this->pid_file)
					or trigger_error("PID file locked", E_USER_ERROR);
			}
			
			$this->daemon_pidfile_handle = fopen($this->pid_file, 'w')
				or trigger_error("ERROR: could not create PID file ($this->pid_file)", E_USER_ERROR);
			
			fwrite($this->daemon_pidfile_handle, $this->monitor_pid);
			@flock($this->daemon_pidfile_handle, LOCK_EX) or $this->log("WARNING: could not lock PID file ($this->pid_file)");
			
			$this->log("Daemon started");
			$this->monitor();				// watch the Daemon
		}
	}
	
	
	/**
	 * Forks off the daemon process as a child, which will be monitored by the parent
	 * Returns false on error or the daemon PID if successful.
	 *
	 * @return mixed
	 * @access protected
	 */
	protected function start() {
		$this->started_on = null;	// reset the start time. we'll reset it in the parent, but not the child.
		$pid = pcntl_fork();		// fork off a process. This function returns twice - once in the parent, once in the child.
		switch($pid) {
			case -1:				// error (out of memory, usually)
				return FALSE;
			case 0:					// the child
				$this->started_on = microtime(TRUE);
				$this->__daemon();	// launch the daemon listener
				exit();
			default:				// the parent
				$this->started_on = microtime(TRUE);
				return $pid;		// return the child PID
		}
	}
	
	
	/**
	 * Stops the daemon process.
	 *
	 * @access protected
	 */
	protected function stop() {
		$this->log("Stopping.... Monitor: $this->monitor_pid, Daemon: $this->daemon_pid");
		// if we have a start time, compute the time the daemon ran, in seconds
		$this->uptime = ($this->started_on)? microtime(true) - $this->started_on : 0;
		if($this->monitor_pid && $this->monitor_pid != $this->get_pid()) {
			if($this->pid_exists($this->monitor_pid)) {
				posix_kill($this->monitor_pid, SIGKILL)		// kill the daemon process
					or $this->log("Monitor PID set to $this->monitor_pid, but couldn't kill it (the error was: ".posix_strerror(posix_get_last_error()).")");
			}
			pcntl_waitpid($this->monitor_pid, $status);	// reap (clean up) the dead process
		}
		if($this->daemon_pid && $this->daemon_pid != $this->get_pid()) {
			if($this->pid_exists($this->daemon_pid)) {
				posix_kill($this->daemon_pid, SIGKILL)		// kill the daemon process
					or $this->log("Daemon PID set to $this->daemon_pid, but couldn't kill it (the error was: ".posix_strerror(posix_get_last_error()).")");
			}
			pcntl_waitpid($this->daemon_pid, $status);	// reap (clean up) the dead process
		}
		if(file_exists($this->pid_file)) {
			@flock($this->daemon_pidfile_handle, LOCK_UN)
				or $this->log("WARNING: could not unlock PID file ($this->pid_file)");
			@unlink($this->pid_file)
				or $this->log("WARNING: could not unlink PID file ($this->pid_file)");
		}
	}
	
	
	/**
	 * CTRL-C/CTRL-Break event handler
	 *
	 * @param integer $signal
	 * @access protected
	 */
	protected function terminate($signal) {
		$signal = (isset(self::$signals[$signal]))? self::$signals[$signal] : "OTHER SIGNAL ($signal)";
		$this->log("$signal: exiting");
		$this->stop();
		die;
	}
	
	
	/**
	 * Restart the daemon process if it dies. Restart or bust!
	 *
	 * @access protected
	 */
	protected function restart() {
		// if we have a start time, compute the time the daemon ran, in seconds
		$uptime = ($this->started_on)? microtime(true) - $this->started_on : 0;
		pcntl_waitpid($this->daemon_pid, $status);	// reap (clean up) the dead process
		$exit_code = pcntl_wexitstatus($status);	// get the exit error code
		// show a warning
		$this->log("Daemon died unexpectedly with error code $exit_code (uptime was $uptime). Restarting...");
		$this->daemon_pid = null;	// clear out the old daemon PID
		
		// We won't retry infinitely
		$this->retry_cnt = 0;
		do {
			$this->daemon_pid = $this->start();	// try to restart
			if(!$this->daemon_pid) {
				$this->retry_cnt++;
				$this->log("Failed to restart the daemon. Retry #$this->retry_cnt of $this->daemon_restart_limit...");
			}
		} // Keep a-trying until we restart or run out of tries
		while(!$this->daemon_pid && ($this->retry_cnt < $this->daemon_restart_limit));
		
		if($this->daemon_pid) {	// celebrate!
			$this->log("Daemon restarted successfully");
			return;
		}
		else { // bust!
			throw new Exception("Daemon died and could not be restarted!");
			exit(-1);
		}
	}
	
	
	/**
	 * Monitor loop - watch the daemon process and restart it if it goes down
	 *
	 * @access protected
	 */
	protected function monitor() {
		// set CTRL-C/CTRL-break event handler
		declare(ticks = 1);
		pcntl_signal(SIGINT,  array($this, "terminate"));
		pcntl_signal(SIGTERM, array($this, "terminate"));
		
		while(1) {
			if(! $this->pid_exists($this->daemon_pid)) $this->restart();
			sleep($this->monitor_sleep_interval);	// wait a bit so we don't tie up the CPU too much
		}
	}
	
	
	/**
	 * The daemon loop
	 *
	 * @access protected
	 */
	protected function __daemon() {
		declare(ticks=1);
		pcntl_signal(SIGINT,  SIG_DFL);	// use the system default for CTRL-C/CTRL-break events
		pcntl_signal(SIGTERM, SIG_DFL);
		pcntl_signal(SIGCHLD, array($this, '__daemon_child_term'));	// automatically remove child pids from $this->daemon_pids[]
		
		while(1) {
			$data = array();
			$data = (array) $this->__daemon_listen();			// check for data
			if(count($data) > 0) $this->__daemon_spawn($data);	// spawn processes to handle the data
																// (this will render the daemon unavailable if we have so
																// much data we reach the concurrency limit, but that's normal)
			usleep($this->daemon_listen_interval);	// wait a bit so we don't tie up the CPU too much
		}
	}
	
	
	/**
	 * The daemon listener. You must define it's functionality when extending this class.
	 *
	 * @abstract abstract
	 * @access protected
	 */
	abstract protected function __daemon_listen();
	

	/**
	 * The daemon data handler. You must define it's functionality when extending this class.
	 * Note: you don't have to exit() at the end; we're doing that in ().
	 *
	 * @abstract abstract
	 * @access protected
	 */
	abstract protected function __daemon_child($data);
	
	
	/**
	 * Fork as many children as we need to handle the data received.
	 *
	 * @param array $data
	 * @access protected
	 */
	protected function __daemon_spawn($data) {
		$this->log("Spawning...");
		foreach($data as $work_data)
		{
			$pid = pcntl_fork(); // conceive
			switch($pid)
			{
				case -1:	// miscarried
					throw new Exception("Out of memory");
					
				case 0:		// child's play (handle the data)
					$this->in_child = true;	// we're in the child, so don't do parental stuff like __destruct()
					try {
						$this->__daemon_child($work_data);
						exit();
					}
					catch (Exception $e) {
						$this->log("ERROR: $e");
						throw $e;
					}
					
				default:	// parental duties: keep up with the children
					$this->daemon_pids[$pid] = true;
			}
		
			// wait on a process to finish if we're at our concurrency limit.
			// this will tie up the daemon from receiving further requests, but we couldn't handle them now
			// anyways so it's OK
			while(count($this->daemon_pids) >= $this->max_concurrent);
		}
	}
	
	
	/**
	 * Automatic child termination event handler
	 *
	 * @access protected
	 */
	protected function __daemon_child_term() {
		// We'll get an error if this runs when we don't have any child processes to reap. So don't go there.
		if(count($this->daemon_pids) == 0) return;
		
		$tpid = pcntl_wait($status, WNOHANG || WUNTRACED);	// reap the first available child that's finished
		switch($tpid)
		{
			case -1: throw new Exception("Out of memory"); break;
			case  0: break;	// no child available
			default: unset($this->daemon_pids[$tpid]);	// remove the process from the list of running children
		}
	}

	
	/**
	 * The messenger. Logs messages to the screen, a logfile, and/or an e-mail address (if an emergency).
	 *
	 * @param string $msg
	 * @param boolean $emergency
	 * @access protected
	 */
	public function log($msg, $emergency = false) {
		// 2008/09/08 23:23:23  [4410->4411] ....\n
		
		if(PHP_SAPI == 'cli') {
			$ppid = $this->get_ppid();
			$pid  = $this->get_pid();
			$msg = date('Y/m/d H:i:s ')." [{$ppid}->{$pid}] $msg\n";
		}
		
		// do some gabbing
		if($this->log_to == 'screen') {
			echo $msg;
		} else {
			error_log($msg, 3, $this->log_to)
				or die("Error logging \"$msg\" to logfile $this->log_to: logfile not writable.");
		}
		
		// try to call for emergency help via e-mail
		if($emergency) {
			error_log($msg, 1, $this->emergency_email)
				or die("Failed sending emergency error e-mail to $this->emergency_email");
		}
	}
	
	
	/**
	 * Error event handler
	 *
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param integer $errline
	 * @return mixed
	 * @access protected
	 */
	public function error_handler($errno, $errstr, $errfile = null, $errline = null) {
		
		if(error_reporting() == 0) {
			return TRUE;
		}
		
		switch($errno) {
			case E_STRICT:			// insignificant errors - don't even bother
			case E_NOTICE:
				return true;
			case E_USER_ERROR:		// fatal errors
			case E_ERROR:
			case E_PARSE:
			case E_COMPILE_ERROR:
			case E_CORE_ERROR:
				throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
				exit(-$errno);
				break;
			default:				// non-fatal errors
				throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
				return true;
		}
	}
	
	
	/**
	 * Check to see if a process exists
	 *
	 * @param integer $pid
	 * @return boolean
	 * @access protected
	 */
	protected function pid_exists($pid) {
    	$str = exec("ps $pid");	// run the system "ps" command
    	//var_dump($pid, $str, strpos($str, (string) $pid));
    	if(strpos($str, (string) $pid) === FALSE) {
    		return false;		// process non-existent
    	}
    	else {
    		if(stripos($str, '<defunct>') === FALSE) {
    			return true;	// process running
    		}
    		else {
    			return false;	// process dead but not reaped
    		}
    	}
	}
	
	
	protected function get_pid() {
		return (function_exists('posix_getpid'))? posix_getpid() : 0;
	}
	
	
	protected function get_ppid() {
		return (function_exists('posix_getppid'))? posix_getppid() : 0;
	}
	
}

