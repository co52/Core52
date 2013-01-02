<?php

	# First things first, lets get rid of those pesky notices
	# and set up our error handler
	if(!defined('ERROR_REPORTING'))
		define('ERROR_REPORTING', E_ALL & ~(E_NOTICE | E_DEPRECATED | E_STRICT));

	error_reporting(ERROR_REPORTING);
	
	# Remove the query string from $_GET, in case we're using a special rewrite rule with Apache's mod_fcgid
	$qs = ltrim(array_shift(explode('&', $_SERVER['QUERY_STRING'])), '/');
	unset($_GET[$qs]);
	
	# Core52 String constants
	if(!defined('CRLF')) {
		define('CRLF', "\r\n");
	}
	if(!defined('CR')) {
		define('CR', "\r");
	}
	if(!defined('LF')) {
		define('LF', "\n");
	}
	if(!defined('TAB')) {
		define('TAB', "\t");
	}
	
	# Core52 Path constants
	define('PATH_CORE', strtr(dirname(__FILE__), '\\', '/').'/');
	define('PATH_CORE_OBJECTS', PATH_CORE.'objects/');
	
	if(!defined('PATH_BASE')) {
		# yes, I know this is ugly, but it makes it work on windows, mac, and linux systems.
		define('PATH_BASE', substr(strtr(dirname(__FILE__), '\\', '/'), 0, (strrpos(strtr(dirname(__FILE__), '\\', '/'), '/')+1)));
	}
	
	# Application Path constants
	if(!defined('PATH_APP'))
		define('PATH_APP', PATH_BASE.'app/');
		
	if(!defined('PATH_CONTROLLERS'))
		define('PATH_CONTROLLERS', PATH_APP.'controllers/');
		
	if(!defined('PATH_OBJECTS'))
		define('PATH_OBJECTS', PATH_APP.'objects/');
		
	if(!defined('PATH_MODELS'))
		define('PATH_MODELS', PATH_APP.'models/');
		
	if(!defined('PATH_STATIC'))
		define('PATH_STATIC', PATH_APP.'static/');
		
	if(!defined('PATH_VIEWS'))
		define('PATH_VIEWS', PATH_APP.'views/');
		
	if(!defined('PATH_I18N'))
		define('PATH_I18N', PATH_APP.'i18n/');
		
	if(!defined('PATH_TEMP'))
		define('PATH_TEMP', PATH_APP.'temp/');
	
	if(!defined('PATH_CACHE'))
		define('PATH_CACHE', PATH_APP.'cache/');
	
	if(!defined('PATH_UPLOADS'))
		define('PATH_UPLOADS', PATH_APP.'uploads/');
		
	if(!defined('PATH_AUTH'))
		define('PATH_AUTH', '/fiftytwo/auth/');
	
	
	# View class constants
	if(!defined('TPL_EXT'))
		define('TPL_EXT', '.html');
		
	if(!defined('TPL_DIR'))
		define('TPL_DIR', 'templates/');
		
	
	# Deprecated paths (preserved for backward compatibility)
	define('FRAMEWORK_ROOT', PATH_CORE);
	define('FRAMEWORK_PATH', PATH_CORE);
	define('BENCHMARK_CONFIG', 'Tzo1OiJUaW1lciI6MTp7czo0OiJ0aW1lIjtpOjA7fQ');
	define('PATH_FRAMEWORK', PATH_CORE);
	define('PATH_APPLICATION', PATH_APP);
	define('APPLICATION_PATH', PATH_APP);
	define('TEMPDIR', PATH_TEMP);

	
	# PHP default settings
	ini_set('arg_separator.output', '&');
	ini_set('session.use_only_cookies', 1); // don't put the session token in the URL, for security
	
	# set up the core execution script
	require_once(PATH_CORE.'core.php');
	register_shutdown_function('core_execute');
	

	# Load core and additional classes
	$common_objects = array('AutoLoader', 'Config', 'Benchmark', 'SiteSettings', 'Database', 'View', 'FastView');
	
	if(isset($load_objects)) {
		$common_objects = array_merge($common_objects, (array) $load_objects);
	}
	
	foreach($common_objects as $object) {
		require_once(PATH_CORE."objects/$object.php");
	}
	
	
	
	# Load core helpers
	core_load_helper(array(
		'sys-functions',
		'input',
		'format',
		'object',
		'email',
		'json',
		'ci/text_helper',
		'svn',
	), TRUE);
	
	# Load additional (custom) helpers
	if(isset($load_helpers)) {
		core_load_helper($load_helpers);
	}
	
	
	# Set up error handling
	require(PATH_CORE.'exceptions.php');

	
	# Initialize path, browser, and database objects
	if(PHP_SAPI != 'cli') {
		Path::Initialize(PATH_CONTROLLERS);
		Router::Initialize(PATH_CONTROLLERS);
		Browser::Initialize();
		Benchmark::Initialize(C52_DEV('ENABLE_PROFILING'));
	}
	require_once(PATH_APP.'config.php');
	Database::load_settings();
	
	