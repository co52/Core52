<?php

class ServerException extends Exception {}
class ServerConnectException extends ServerException {}
class ServerAuthenticationException extends ServerConnectException {}
class ServerExecException extends ServerException {}
class ServerExecTimeoutException extends ServerExecException {}


class Servers {
	
	protected static $nodes = array();
	
	public static function add_node(Server $server, $n = NULL) {
		
		$role = get_class($server);
		
		if($server->max_nodes() > 0 && self::get_node($role) !== FALSE) {
			throw new Exception("You may not add more than ".$server->max_nodes()." $role node(s)");
		}
		
		if($n === NULL) {
			self::$nodes[$role][] = $server;
		} else {
			self::$nodes[$role][$n] = $server;
		}
		
	}
	
	/**
	 * Gets a Server
	 *
	 * @param $role
	 * @param $n
	 * @return Server
	 */
	public static function get_node($role, $n = 0) {
		if(isset(self::$nodes[$role][$n]) && self::$nodes[$role][$n] instanceof $role) {
			return self::$nodes[$role][$n];
		} else {
			return FALSE;
		}
	}
	
	public static function get_nodes($role = '*') {
		
		if($role === '*') {
			
			$servers = array();
			foreach(self::$nodes as $role_servers) {
				$servers = array_merge($servers, $role_servers);
			}
			
			return $servers;
			
		} elseif(is_array(self::$nodes[$role])) {
			
			return self::$nodes[$role];
			
		} else {
			
			return array();
			
		}
		
	}
	
	public static function multi_exec(array $servers, $cmd, $timeout = 10) {
		
		// append a marker to the end of the output
		$cmd = rtrim($cmd, ';') . '; echo "__COMMAND_FINISHED__"';
		
		// Execute the $cmd on each server
		$data = array();
		$finished = 0;
        foreach($servers as &$server) {
			
        	$s = (string) $server;
        	
			// Establish connections to each server
			if(!$server instanceof Server) {
				throw new InvalidArgumentException('$servers must be an array of Server objects');
			}
			
			try {
			
				$data[$s]->server = $server;
		        $data[$s]->started = time();
		        $data[$s]->output = '';
		        
				// @HACK work around PECL bug: "ssh2_exec(): Unable to request a channel from remote host"
				// see http://pecl.php.net/bugs/bug.php?id=16875&edit=1
				$server->connect();
				
				// Execute command asynchronously
				if($server->raw_exec($cmd, FALSE) == FALSE) {
		            throw new ServerExecException("Failed to execute `$cmd` on $server->host");
		        }
		        
			} catch(Exception $e) {
				
				#echo $e;
				$data[$s]->result = $e;
				$server->close_stream();
				$finished++;
				
			}
		}
		
		// collect returning data from each server
        while($finished < count($servers)) {
			
			foreach($servers as &$server) {
				
				$s = (string) $server;
				
				// Skip if this server is finished
				if($server->get_stream() === NULL) continue;
				
				try {
				
					// Get data
					$read = fread($server->get_stream(), 4096);
					$data[$s]->output .= $read;
					#if(strlen($read)) echo "$read\n";
					
					// Are we done yet?
				    if(strpos($data[$s]->output, "__COMMAND_FINISHED__") !== false) {
				    	
				    	// end marker reached?
				    	$server->close_stream();
				        $data[$s]->result = TRUE;
				        $data[$s]->output = substr($data[$s]->output, 0, strpos($data[$s]->output, "__COMMAND_FINISHED__") - strlen($data[$s]->output));
				        $finished++;
				        continue;
				        
				    } elseif((time() - $data[$s]->started) > $timeout) {
				    	
				    	// stop waiting after $timeout seconds
				    	throw new ServerExecTimeoutException("Timeout on $server->host while executing `$cmd` after $timeout seconds");
				        
				    }
				    
				} catch(Exception $e) {
					
					$finished++;
					$server->close_stream();
					$data[$s]->result = $e;
					continue;
					
				}
				
			}
		    
		}
		
		// finished
		return $data;
        
	}
	
}


abstract class Server {
	
	public $host;
	public $user;
	public $password;
	
	protected $connected = FALSE;
	protected $connection;
	protected $connection_started;
	protected $stream;
	
	public function __construct($host, $user = '', $password = '') {
		if(is_array($host)) extract($host);
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
	}
	
	public function __toString() {
		return get_class($this).': '.$this->host;
	}
	
	public function max_nodes() {
		return 0; // no limit
	}
	
	public function connect() {
		
		// Require the SSH2 PECL extension
		if(!function_exists('ssh2_connect')) {
			throw new Exception('The SSH2 PECL extension is required');
		}
		
		// Connect to the remote server
		$this->connection = ssh2_connect($this->host, 22);
		if($this->connection == FALSE) {
			throw new ServerConnectException("Unable to establish SSH connection to $this->host");
		}
		
		// Authenticate to the remote server
		if(!ssh2_auth_password($this->connection, $this->user, $this->password)) {
	        throw new ServerAuthenticationException("Unable to authenticate to $this->user@$this->host");
		}
		
		// Connected!
		$this->connected = TRUE;
		return TRUE;
	}
	
	public function is_connected() {
		return (boolean) $this->connected;
	}
	
	public function raw_exec($cmd, $block = TRUE) {
		// Require the SSH2 PECL extension
		if(!function_exists('ssh2_exec')) {
			throw new Exception('The SSH2 PECL extension is required');
		}
		
		$this->stream = ssh2_exec($this->connection, $cmd);
		stream_set_blocking($this->stream, $block);
		return $this->stream;
	}
	
	public function get_stream() {
		return $this->stream;
	}
	
	public function close_stream() {
		@fclose($this->stream);
		$this->stream = NULL;
	}
	
	public function exec($cmd, $timeout = 10) {
		
		// @HACK work around PECL bug: "ssh2_exec(): Unable to request a channel from remote host"
		// see http://pecl.php.net/bugs/bug.php?id=16875&edit=1
		$this->connect();
		
		// append a marker to the end of the output
		$cmd = rtrim($cmd, ';') . '; echo "__COMMAND_FINISHED__"';
		
        // execute a command
        $this->raw_exec($cmd, FALSE);
        if($this->get_stream() == FALSE) {
            throw new ServerExecException("Failed to execute `$cmd` on $this->host");
        }
        	
        // collect returning data from command
        $time_start = time();
		$data = '';
		while(TRUE) {
			
		    $data .= fread($this->get_stream(), 4096);
		    
		    // end marker?
		    if(strpos($data, "__COMMAND_FINISHED__") !== false) {
		        $data = substr($data, 0, strpos($data, "__COMMAND_FINISHED__") - strlen($data));
		        break;
		    }
		    
		    // wait up to $timeout seconds
		    if((time() - $time_start) > $timeout) {
		        throw new ServerExecTimeoutException("Timeout on $this->host while executing `$cmd` after $timeout seconds");
		    }
		}
		
		// terminate
        $this->close_stream();
		return $data;
        
	}
	
}


class MasterServer extends Server {
	public function max_nodes() {
		return 1;
	}
}

class DatabaseMasterServer extends MasterServer {}

class SlaveServer extends Server {}

class WebServer extends Server {}

