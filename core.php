<?php

class CoreException extends Exception {}


function core_execute() {
	
	try {
		
		if(defined('CORE52_EXECUTE')) {
			call_user_func(CORE52_EXECUTE);
			if(!defined('ENABLE_LEGACY_SESSIONS')) session_write_close(); // Save session data before shutting down
			return;
		}
		
		if((defined('BAD_BEHAVIOR_PRESENT') && !defined('BAD_BEHAVIOR_ALLOW')) || defined('HALT_CORE') || php_sapi_name() == 'cli') {
			if(!defined('ENABLE_LEGACY_SESSIONS')) session_write_close(); // Save session data before shutting down
			exit;
		}
		
		# version headers
		if(!headers_sent()) {
			$core_rev = svn_get_version(PATH_CORE, FALSE);
			$app_rev = svn_get_version(PATH_APP, FALSE);
			@header("X-Core52-Version: core=$core_rev; app=$app_rev");
		}
		
		# run the controller
		ob_start();
		if(defined('ENABLE_LEGACY_ROUTING')) {
			Path::Auto();
		} else {
			Router::route();
		}
		$output = ob_get_clean();
		
		# Save session data before shutting down
		if(!defined('ENABLE_LEGACY_SESSIONS')) session_write_close();
		
		# detect output
		if(!Router::is_ajax() && !headers_sent() && strlen(trim($output)) == 0) {
			Error::show_404();
		} else {
			echo $output;
		}
		
		# session debugger
		if(C52_DEV('ENABLE_SESSION_DEBUG')) {
			Session::debug();
		}
		
		# database cache debugger
		if(C52_DEV('ENABLE_DBCACHE_REPORT')) {
			DatabaseCache::report();
		}
		
	} catch(Exception $e) {
		
		try {
			if(!defined('ENABLE_LEGACY_SESSIONS')) session_write_close();
		} catch(Exception $e) {
			core_handle_exception($e);
		}
		core_handle_exception($e);
		
	}
	
}

function core_load_object($object, $core = FALSE) {
	
	if(is_array($object)) {
		foreach($object as $path) {
			if(core_load_helper($path)) {
				continue;
			} else {
				return FALSE;
			}
		}
		return TRUE;
	}
	
	if($core) {
		# loading core helper
		$check = array(PATH_CORE.'objects/'.$object.'.php');
	} else {
		# check app helpers first
		$check = array(
			PATH_APP.'objects/'.$object.'.php',
			PATH_CORE.'objects/'.$object.'.php'
		);
	}
	
	foreach($check as $path) {
		if(file_exists($path)) {
			require_once($path);
			return TRUE;
		}
	}
	
	throw new CoreException("Could not find an object called '$object'");
}

function core_load_helper($helper, $core = FALSE) {
	
	if(is_array($helper)) {
		foreach($helper as $path) {
			if(core_load_helper($path)) {
				continue;
			} else {
				return FALSE;
			}
		}
		return TRUE;
	}
	
	if($core) {
		# loading core helper
		$check = array(PATH_CORE.'helpers/'.$helper.'.php');
	} else {
		# check app helpers first
		$check = array(
			PATH_APP.'helpers/'.$helper.'.php',
			PATH_CORE.'helpers/'.$helper.'.php'
		);
	}
	
	foreach($check as $path) {
		if(file_exists($path)) {
			require_once($path);
			return TRUE;
		}
	}
	
	throw new CoreException("Could not find a helper called '$helper'");
}

function core_load_controller($controller, $path = PATH_CONTROLLERS) {
	
	# separate any subdirectories from the class and add them to the controller file path
	# (i.e. crm/Client_Controller -> controllers/crm/client.php)
	$subdir = explode('/', $controller);
	$controller = strtolower(array_pop($subdir));
	$subdir = trim(implode('/', $subdir), '/');
	if(!empty($subdir)) {
		$path .= "/$subdir";
	}
	
	$file = "$path/$controller.php";
	if(file_exists($file)) {
		include_once strtolower($file);
	}
	
	$class = "Controller_".ucfirst($controller);
	if(!class_exists($class)) {
		throw new AutoClassLoaderException("Controller $controller does not exist");
	} else {
		return $class;
	}
	
}

/**
 * Get a controller instance
 *
 * @param $controller
 * @param $path
 * @return Controller
 */
function core_get_controller_instance($controller, $path = PATH_CONTROLLER) {
	$cls = core_load_controller($controller, $path);
	return new $cls;
}

function core_halt() {
	if(!defined('ENABLE_LEGACY_SESSIONS')) session_write_close();
	if(!defined('HALT_CORE')) {
		define('HALT_CORE', TRUE);
	}
	die;
}

function core_set_exception_handler($callback) {
	if(is_callable($callback)) {
		$hash = (is_string($callback))? md5($callback) : md5(serialize($callback));
		$handlers = array($hash => $callback) + (array) Config::get('exception_handler', FALSE);
		Config::set('exception_handler', $handlers);
	} else {
		throw new Exception('Error handler not callable');
	}
}

function core_unset_exception_handler($callback = NULL) {
	$handlers = (array) Config::get('exception_handler', FALSE);
	if(count($handlers) == 0) {
		return FALSE;
	}
	if(is_null($callback)) {
		array_unshift($handlers);
	} else {
		$hash = (is_string($callback))? md5($callback) : md5(serialize($callback));
		if(isset($handlers[$hash])) {
			unset($handlers[$hash]);
		} else {
			return FALSE;
		}
	}
	Config::set('exception_handler', $handlers);
	return TRUE;
}

function core_handle_exception(Exception $e) {
	foreach((array) Config::get('exception_handler', FALSE) as $callback) {
		if(is_callable($callback)) {
			call_user_func($callback, $e);
			core_halt();
		}
	}
	Error::handle_exception($e);
}

function core_log($msg, $logfilename = 'application.log', $logfilepath = 'logs/', $br = "\n") {
	if(substr($logfilepath, 0, 1) !== '/') $logfilepath = PATH_APP.$logfilepath;
	error_log($br.$msg, 3, $logfilepath.$logfilename);
}

