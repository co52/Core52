<?php

/**
 * Core52 Class Autoloader
 *
 * This class works directly with __autoload() to loop through possible
 * locations for classes. It allows you to specify specific folders to
 * look in for an unknown class and you can also specify exactly where
 * specific classes are located.
 *
 * Originally written during the Core revamp that happened Spring 2009
 * in preparation for National Momentum development.
 *
 *
 * @author "Jake A. Smith" <jake@companyfiftytwo.com>
 * @package Core52
 * @version 1.0
 **/
class AutoClassLoader
{
	/**
	 * Holds an array of paths
	 *
	 * @var array
	 **/
	protected $paths;
	
	/**
	 * Holds the name of the class we're trying to load
	 *
	 * @var string
	 **/
	protected $name;
	
	/**
	 * Bool val if we've found the class or not
	 *
	 * @var bool
	 **/
	protected $found;
	
	protected $oddballs;
	
	public static $throw = TRUE;
	
	
	public static function Register() {
		spl_autoload_register('core52_autoload');
		ini_set('unserialize_callback_func', 'core52_autoload');
	}
	
	
	public static function Unregister() {
		spl_autoload_unregister('core52_autoload');
		ini_set('unserialize_callback_func', 'core52_autoload');
	}
	
	
	public static function ThrowExceptions($throw) {
		self::$throw = (boolean) $throw;
	}
	
	
	/**
	 * The Constuctor
	 *
	 * @return void
	 * @author Jake A. Smith
	 **/
	public function __construct($name)
	{
		$this->name = $name;
	}
	
	/**
	 * Adds path value to $paths array.
	 *
	 * @return void
	 * @author Jake A. Smith
	 **/
	public function attempt($path)
	{
		$this->paths[] = $path;
	}
	
	/**
	 * Saves oddball name / path stuff.
	 *
	 * @return bool
	 * @author Jake A. Smith
	 **/
	public function oddball($name, $path)
	{
		$this->oddballs[] = array(
			'name' => $name,
			'path' => $path
		);
	}
	
	/**
	 * Run through all the paths until we find the class
	 *
	 * @return void
	 * @author Jake A. Smith
	 **/
	public function process()
	{
		$this->_process_paths();
		$this->_process_oddballs();
		
		if($this->found) {
			// found
			return true;
		} elseif(count(spl_autoload_functions()) > 1) {
			// not found, but there are more autoload functions registered on the stack...
			return false;
		} else {
			// end of the line...
			$this->_throw_exception();
			return false;
		}
	}
	
	
	/**
	 * Throws an exception if the class was not found.
	 *
	 * @return void
	 * @author Jake A. Smith
	 **/
	private function _throw_exception() {
		if(self::$throw) {
			throw new AutoClassLoaderException('Could not load class: '. $this->name);
		}
	}
	
	
	/**
	 * Run through all the paths returning true if we find it and false if we don't.
	 *
	 * @return bool
	 * @author Jake A. Smith
	 **/
	private function _process_paths()
	{
		foreach($this->paths as $path) {
			if(file_exists($path . $this->name .'.php')) {
				include($path . $this->name .'.php');
				$this->found = true;
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Run through all the oddball possibilities, returning true if we find it, false if we don't.
	 *
	 * @return bool
	 * @author Jake A. Smith
	 **/
	private function _process_oddballs()
	{
		if(count($this->oddballs) > 0) {
			foreach($this->oddballs as $oddball) {
				if($this->name == $oddball['name']) {
					include($oddball['path']);
					$this->found = true;
					return true;
				}
			}
		}
	}
	
	
} // END AutoClassLoader class

class AutoClassLoaderException extends Exception {}



function core52_autoload($name) {
	$class = new AutoClassLoader($name);
	$class->attempt(PATH_MODELS); // Model
	$class->attempt(PATH_OBJECTS); // App objects
	$class->attempt(PATH_CORE_OBJECTS); // Core objects
	$class->oddball('DatabaseConnection', PATH_CORE_OBJECTS . 'Database.php');
	$class->process();
}

AutoClassLoader::Register();

