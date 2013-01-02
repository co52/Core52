<?php
/**
 * A semaphore-locked shared memory implementation
 *
 * @author Jonathon Hill (jonathon@compwright.com)
 * @version 0.1
 * 
 */
class SHM_Accessor {
        
    /**
     * @access public
     * @var boolean persist the semaphores (don't close them on __destruct()) 
     */
    public $persist = false;
    
    /**
     * @access public
     * @var integer shared memory block size (default=10kb)
     */
    public $memsize;
    
    /**
     * Exception object containing info on the last error
     *
     * @access public
     * @var object
     */
    public $error;
    
    /**
     * @access private
     * @var resource read/write semaphores
     */
    private $sem_read;
    private $sem_write;
    
    /**
     * Read/write status flags
     *
     * @access public
     * @var boolean
     */
    private $reading = NULL;
    private $writing = NULL;
    
    /**
     * Shared memory block ID
     * 
     * @access private
     * @var resource
     */
    private $shm_block;
    
    
    
    /**
     * Default constructor
     */
    public function __construct($keys = null, $persist = null, $memsize = 10000) {
        if(is_array($keys)) {
        	$this->sem_read_key  = $keys['r'];
        	$this->sem_write_key = $keys['rw'];
        	$this->shm_block_key = $keys['m'];
        	
        	// persist by default since we are NOT creating a new memory block
        	$this->persist = (is_null($persist))? true : $persist;
        }
        else {
        	$this->sem_read_key = ftok(__FILE__, 'r'); //uniqid(mt_rand(), true);
        	$this->sem_write_key= ftok(__FILE__, 'w');//uniqid(mt_rand(), true);
        	$this->shm_block_key= ftok(__FILE__, 'm');//uniqid(mt_rand(), true);
        	
        	// don't persist by default since we ARE creating a new memory block
        	$this->persist = (is_null($persist))? false : $persist;
        }
    	
    	// Create the semaphores
        $this->sem_read  = sem_get($this->sem_read_key, 1);
        $this->sem_write = sem_get($this->sem_write_key, 1);
        
        // Open the shared memory block
        $this->shm_block = shm_attach($this->shm_block_key, $memsize);
        if($this->shm_block === FALSE) {
        	$this->error = new Exception("Could not access shared memory");
        	return false;
        }
    }
    
    
    /**
     * Destructor
     * 
     * Remove the read/write semaphore
     */
    public function __destruct() {
    	// close our semaphores
    	if($this->read)  sem_release($this->sem_read);
    	if($this->write) sem_release($this->sem_write);
    	
    	// close shared memory block handle
    	shm_detach($this->shm_block);
    	
    	// free up shared memory
    	if(!$this->persist) {
      		@sem_remove($this->sem_read);
      		@sem_remove($this->sem_write);
      		@shm_remove($this->shm_block);
    	}
    }
    
    
    /**
     * Acquire access to the shared memory
     * 
     * @param string $access
     * @return boolean
     */
    private function get_access($access = 'r') {
    	try {
	        switch($access) {
	        	case 'rw':
	        		if(!$this->writing) {
	        			$this->writing = sem_acquire($this->sem_write)
	        			or $this->throw_exception("Write access denied");
	        		}
	        	case 'r':
	        	default:
	        		if(!$this->reading) {
	        			$this->reading = sem_acquire($this->sem_read)
	        			or $this->throw_exception("Read access denied");
	        		}
	        }
    	}
    	catch(Exception $e) {
    		$this->error = $e;
    		return false;
    	}
        return true;
    }
    
    
    /**
     * Release access to the shared memory
     *
     * @param string $access_type
     * @return boolean
     */
    private function release_access($access = 'r') {
    	switch($access) {
    		case 'rw':
    			if($this->writing) {
    				if(!@sem_release($this->sem_write)) $this->error = new Exception("Could not release the write semaphore");
    			}
    		default:
    		case 'r':
    			if($this->reading) {
    				if(!@sem_release($this->sem_read)) $this->error = new Exception("Could not release the read semaphore");
    			}
    	}
        return ($this->reading || $this->writing)? FALSE : TRUE;
    }
     
    
    /**
     * Read a variable from shared memory
     *
     * @param mixed $key
     * @param boolean $release
     * @return mixed
     */
    public function read($key, $release = false) {
    	// get read access
    	if(!$this->get_access('r')) return false;
    	if($this->shm_block === FALSE) return FALSE;
    	
    	// do the reading
    	try {
    		$val = shm_get_var($this->shm_block, $key)
    			or $this->throw_exception("$key is not a variable key in shared memory");
    	}
    	catch(Exception $e) {
    		$this->error = $e;
    		return false;
    	}
    	
    	if($release) $this->release_access();
    	return $val;
    }
    
    
    /**
     * Store a variable in shared memory
     *
     * @param mixed $key
     * @param mixed $val
     * @param boolean $release
     * @return boolean
     */
    public function write($key, $val = null, $release = false) {
    	// get write access
   		if(!$this->get_access('rw')) return false;
   		if($this->shm_block === FALSE) return FALSE;
    	
    	// re-arrange our args to array format if we weren't supplied an array to begin with
    	if(!is_array($key)) {
    		// smarter than the average bear!
    		$key = array($key => $val);
    		$release = (is_null($val))? $release : $val;
    		$val = null;
    	}
    	
    	// do the writing
    	try {
	    	foreach($key as $k => $v) {
	    		shm_put_var($this->shm_block, $k, $v)
	    			or $this->throw_exception(__CLASS__."::write() failed when trying to store ($k => $v) in shared memory");
	    	}
    	}
    	catch(Exception $e) {
    		$this->error = $e;
    		return false;
    	}
    	
    	if($release) $this->release_access();
    	return true;
    }
    
    
    /**
     * Throw a tantrum
     *
     * @param string $message
     * @param integer $code
     */
	private function throw_exception($message = null,$code = null) {
    	throw new Exception($message, $code);
	}

    
}
?>