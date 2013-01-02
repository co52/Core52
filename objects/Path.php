<?php

/**
 * Core52 Path class
 *
 * The Path object manages all the URL handling for C52 based software. It is extemely
 * flexible in its implementation. Following are the details of the initialization process
 * as well as information on how to implement it.
 *
 * @author "David Boskovic" <dboskovic@companyfiftytwo.com>
 * @package Core52
 * @version 2.1
 * @todo Overall purpose is well documented, but individual methods could use better docs.
 *
 * All, directory references are to the /controllers directory unless otherwise specified.
 *
 * Detection Example 1: ----------------------------------------------------------------------------------
 *
 * If your url is /account this is what the script will look for to handle it. These are executed
 * in order and once a handler has been found, it will not look for a handler in another place.
 * 1) Checks for handler declared by Path::Assign() or Path::ReAssign() or by Object
 * 2) Checks for a file named account.php
 * 3) Checks for a file named account/_default.php
 * 4) Declares that there is no handler for this path.
 *
 * Detection Example 2: ----------------------------------------------------------------------------------
 *
 * If your url is /account/post Example 1 will execute as normal until Step 3. If /account/_default.php
 * exists, it will check to see if handler has been assigned for the 'post' handle. If you have assigned
 * a handler with Path::Assign or Path::ReAssign() or by Object, then that handler will be executed. Otherwise before
 * declaring a handler doesn't exist, it will look for /account/post.php and /account/post/_default.php
 * Thus, you could technically have only one file in your controllers located in /account/post.php and
 * /account/post will execute that handler.
 *
 * Object Based Controllers ----------------------------------------------------------------------------------
 *
 * You may have noticed above that you can assign handlers "by Object". This is how. If your handler is
 * account.php. You can declare a class with "Controller_Account extends Controller". This is not a static
 * class. If the Path object discovers an object named Controller_Account, it will automatically instantiate it.
 * It will then look for functions with the same name or label as the handle it is processing. In this case you could
 * declare a function named "post" and then access /account/post to run that function. If you access /account/dork and there
 * is no dork function specified, it will continue looking for handlers as shown in example 1 and 2.
 *
 * Extending handling with __ in functions:
 * Supposing we have a Controller_Account object with a function named "post" in it. Accessing /account/post will
 * run that function. But now, here's a real cool thing. If you have a function named post__new. You can access
 * /account/post/new and it will run the post__new function. This is infinitely extensible. Each double underscore counts
 * for a /. Please note that the contents of the "post" function will NOT be executed when accessing post__new.
 *
 * Function Based Controllers ----------------------------------------------------------------------------------
 *
 * Function based controllers must be assigned manually with the Path::Assign() function since allowing auto
 * detection of functions could be a severe security risk. ie: /phpinfo would automatically return the phpinfo
 * function. We recommend moving url handling functions into an object namespace.
 *
 *
 * FUNCTIONALITY ||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
 *
 * --- Assign a handler.
 * Path::Assign(string|%regex|integer, file|::function|object->);
 *
 * --- Assign a default handler. This is executed at shutdown if no other handler has run.
 * Path::Default(file|::function|object->);
 *
 * --- Redirect to another url.
 * Path::ReAssign(string|%regex|integer, url|url{handle} ); // reference the handle with {handle}
 *
 * --- Label the handles.
 * Path::Label("[/]label/label/...");
 * For example, if you ran Path::Label('/account/subscription/issue'); you could then access handle 1 with
 * Path::Handle('account'); instead of Path::Handle(1); So if you have run the label function, accessing /12/14/82
 * will hunt for handlers as if you were accessing /account/subscription/issue. You can run this function as many times
 * as you'd like and you can run it on paths relative to the current handle.
 *
 * --- Trace the url path to your current or specified handle.
 * Path::Trace(null|integer|string);
 * This will return the trace up to the handle which would be returned by Path::Handle(); So... if your
 * url is /account/subscription/1 then the trace for Path::Trace('subscription'); would be "/account/",
 * and the trace for Path::Trace(3); would be /account/subscription/
 *
 * --- Get the value of a handle
 * Path::Handle(null|integer|string);
 * null: returns the current handle
 * integer (positive): returns the handle by # eg /1/2/3/4/5/6/..
 * integer (negative): returns the handle by reverse #: /-6/-5/-4/-3/-2/-1/
 * string (of style "+integer"): returns the value of the handle # relative to the current position.
 * string: returns the value of the handle by label
 *
 * --- Positioning the handle pointer
 * Path::Position(null|integer);
 * null: returns the current position
 * integer: sets the position to whatever handle # specified.
 *
 * Path::Next();
 * Moves the pointer forwards 1. This function also executes every time a handle discovers it's handler.
 * So, if you access /account/post/1234 and it is handled by /account/post.php. Path::Handle() would return
 * 1234 and Path::Position() would return 3.
 *
 **/

class Path {

	/**
	 * the requested url, eg: /something/else/now
	 *
	 * @var string
	 */
	public static $path = '';
	
	/**
	 * the domain, broken up into an array, eg: array(1 => 'com', 2 => "domain", 3 => 'www')
	 *
	 * @var array
	 */
	public static $domain = array();
	
	# the domain, plain text, eg: www.domain.com
	public static $server = '';
	
	# an array of handles, eg: array(1 => 'something', 2 => 'else', 3 => 'now')
	public static $handles = array();
	
	# an array of labels
	public static $labels = array();
	
	# an array of objects
	private static $objects = array();
	
	# run tracking for auto detection purposes
	private static $run = array();
	
	# the url the individual came from
	public static $referer;

	# current pointer
	public static $pointer = 1;
	
	# current controller directory
	private static $condir = '/';
	
	# handler root
	private static $handler_root = '';

	# flag indicates whether or not a controller has run
	public static $has_run = FALSE;


	public static function Initialize($dir = NULL) {

		# set the include path for the handler directory
		self::$handler_root = (empty($dir))? PATH_APP.'controllers' : $dir;
		
		# initialize variables
		self::$path = $_SERVER['REQUEST_URI'];
		self::$referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		self::$domain = self::_parse_domain();
		
		self::$handles = self::_parse_path();
		self::_parse_get();
		
		# define SUBDOMAIN
		$subDomain = !empty(self::$domain[3]) ? self::$domain[3] : '';
		define('SUBDOMAIN', $subDomain);
	}
	
	public static function Index($path) {
		if(count(self::$handles) == 0)
			self::$handles = self::_parse_path($path);
	}
	
	public static function Assign($handle, $script, $func_vars = array()) {
	
		# pointer to reassign at end of execution
		$pointer = self::$pointer;
		$cd = self::$condir;

		if( self::_match_handle($handle, self::$handles[self::$pointer]) ) {

			if( ($function = self::_is_function_call($script)) ) {
				if(function_exists($function)) {
	            	++self::$pointer;
	            	call_user_func_array($function, $func_vars);
	            	self::$pointer = $pointer;
	            	self::$run[$pointer] = true;
	            	return true;
				}
				else return false;
			}
			else {
				if( @file_exists(self::$handler_root.$cd.$script) ) {
	            	++self::$pointer;
	            	
	            	# move the controller directory up if a directory has been specified
	            	if(strpos($script, '/') !== false) {
	            		self::$condir = self::$condir.substr($script, 0, strrpos($script, '/'));
	            	}
					include(self::$handler_root.$cd.$script);
					self::Auto();
	            	self::$pointer = $pointer;
	            	self::$run[$pointer] = true;
	            	self::$condir = $cd;
					return true;
				}
				else return false;
			}
		}
		
	}
	
	public static function Auto() {
		#Only run if a controller hasn't already been run
		if(!isset(self::$run[self::$pointer])) {
			
			$handle = isset(self::$labels[self::$pointer]) && isset(self::$handles[self::$pointer]) ? self::$labels[self::$pointer] : self::$handles[self::$pointer];
			$parameters = explode(':', $handle);
			$handle = array_shift($parameters);
			
			if(!$handle && self::$pointer > 1) return false;
			
			if(substr($handle, 0, 1) != '%' && is_string($handle)) {
			
				$cd = self::$condir; //Controller directory
				$pointer = self::$pointer;
				$no_func_exec = true;
				
				# check if an object has been instantiated for the previous handle
				if(is_object(self::$objects[self::$pointer - 1])) {
					$methods = get_class_methods(self::$objects[self::$pointer - 1]);
					$methods = array_flip($methods);
					$no_func_exec = false;
					
					if(substr($handle, 0, 1) != '__') {
						if(count(self::$handles) > $pointer) {
						
							$parameters = explode(':', self::$handles[$pointer+1]);
							$handle2 = array_shift($parameters);
							$sh = $handle.'__'.$handle2;
							if(isset($methods[$sh])) {
								self::$pointer = self::$pointer + 2;
								call_user_func_array(array(self::$objects[self::$pointer - 3], $sh), $parameters);
								self::$pointer = $pointer;
								$ex = true;
								self::$has_run = TRUE;
							}
							else $ex = false;
						}
						if(isset($methods[$handle]) && !$ex) {
							++self::$pointer;
							call_user_func_array(array(self::$objects[self::$pointer - 2], $handle), $parameters);
							self::$pointer = $pointer;
							self::$has_run = TRUE;
						}
						elseif (method_exists(self::$objects[self::$pointer - 1], '_default') && !$ex) {
							++self::$pointer;
							call_user_func_array(array(self::$objects[self::$pointer - 2], '_default'), $parameters);
							self::$pointer = $pointer;
							self::$has_run = TRUE;
						}
						elseif(!$ex) {
							$no_func_exec = true;
						}
					}
				}
				
				if ($no_func_exec) {
					# check if a file exists
					if (@file_exists(self::$handler_root.self::$condir.$handle.'.php')) {
						++self::$pointer;
						include(self::$handler_root.self::$condir.$handle.'.php');
						self::_load_object($handle, $pointer, $parameters);
						self::Auto();
						self::$pointer = $pointer;
						self::$has_run = TRUE;
					}				
				
					# check if a directory and default file exist
					elseif(@file_exists(self::$handler_root.self::$condir.$handle.'/_default.php')) {
						self::$pointer++;
						self::$condir = self::$condir.$handle.'/';
						include(self::$handler_root.$cd.$handle.'/_default.php');
						#self::_load_object('default', $pointer, $parameters);
						self::_load_object($handle, $pointer, $parameters);
						self::Auto();
						self::$pointer = $pointer;
						self::$condir = $cd;
						self::$has_run = TRUE;
					}
				
					# check if a directory exists
					elseif(@is_dir(self::$handler_root.self::$condir.$handle)) {
						self::$condir = self::$condir.$handle.'/';
						++self::$pointer;
						self::Auto();
						self::$pointer = $pointer;
					}
					

					# check if a default file exists
					elseif (@file_exists(self::$handler_root.self::$condir.'_default.php')) {
						++self::$pointer;
						include(self::$handler_root.self::$condir.'_default.php');
						self::_load_object('default', $pointer, $parameters);
						self::Auto();
						self::$pointer = $pointer;
						self::$has_run = TRUE;
					}
				}
			}
		}
		
		return self::$has_run;
	}
	
	public static function Destroy() {
		$count = 1;
		while(count(self::$objects) >= $count) {
			unset(self::$objects[$count]);
			$count++;
		}
	}

	public static function ReAssign($handle, $to)	{
		
		# redirect to new url
		if(self::_match_handle($handle, self::$handles[self::$pointer]))
			self::redirect(self::Link(self::Trace().$to));

	}
	
	
	public static function Position($no = null) {
		if(is_null($no)) return self::$pointer;
		else self::$pointer = $no;
	}
	
	
	public static function Next() {
		++self::$pointer;
	}
	
	
	public static function Prev() {
		--self::$pointer;
	}
	
	public static function Trace($no = false) {
		$no = $no !== false ? $no : self::$pointer;
		
		if($no < 0) {
			$no = $no / -1;	// invert sign
			$no = self::$pointer - $no;
		}
		
		$path = '/';
		foreach(self::$handles as $key => $val) {
			if($key >= $no) { break; } else {
				$path .= $val.'/';
			}
		}
		return $path;
	}

	
	public static function Handle($pointer = false) {
		
		# if no pointer has been specified, get the current pointer
		$pointer = $pointer ? $pointer : self::$pointer;
		
		# string "+integer"
		if( is_string($pointer) && substr($pointer, 0, 1) == '+') {
			$pointer = self::$pointer + str_replace('+', '', $pointer);
			return self::$handles[$pointer];
		}
		
		# string
		elseif( is_string($pointer) ) {
			$labels = array_flip(self::$labels);
			$pointer = $labels[$pointer];
				return self::$handles[$pointer];
		}
		
		# integer (negative)
		elseif( $pointer < 0 ) {
			$pointer = $pointer / -1;	// invert sign
			$pointer = $pointer - 1;	// subtract 1
			$endlevels = array_reverse(self::$handles);
			return $pointer ?  $endlevels[$pointer] : $endlevels[$pointer];
		}
		
		# integer (positive)
		else return self::$handles[$pointer];
	}
	
	public static function Label($string, $condition = TRUE) {
		if($condition == FALSE) return FALSE;
		
		$labels = explode("/", $string);
		$output = array();
		if(substr($string, 0, 1) == '/') {
			unset($labels[0]);
			foreach($labels as $key => $val) {
				self::$labels[$key] = $val;
			}
		} else {
			foreach($labels as $key => $val) {
				self::$labels[$key + self::$pointer] = $val;
			}
		}
	}
	
	public static function _load_object($handle, $pointer, array $parameters = array()) {
	
		# check if an object exists
		if(class_exists('Controller_'.$handle)) {
			$object = 'controller_'.$handle;
			self::$objects[$pointer] = new $object;
			
			$methods = get_class_methods(self::$objects[self::$pointer - 1]);
			$methods = array_flip($methods);
	
			if(count(self::$handles) <= $pointer) {
				
				if(isset($methods['_default'])) {
					call_user_func_array(array(self::$objects[self::$pointer - 1], '_default'), $parameters);
				}
			}
		}
	}
	
	//Assigns all specially formatted GET variables ( /key:val/ ) to the $_GET superglobal
	public static function _parse_get() {
		$pathComponents = explode('/', self::$path);
		foreach($pathComponents as $val){
			if(stripos($val, ':') !== FALSE){
				$getParts = explode(':', $val);
				$getKey = $getParts[0];
				$getVal = $getParts[1];
				
				//If query string is formatted like:  /url?key:val, then parse out the url
				if( stripos($getParts[0], '?') !== FALSE){
					$getKey = substr($getKey, stripos($getKey, '?')+1 );
				}
				
				//If query string is formatted like:  /url?key:val, then this key will be set: $_GET['key:val'].  Let's unset it.
				/* Commented out because there is potential for errors (ie, $GET keys getting deleted)
				if( isset($_GET[$getKey]) )
					unset($_GET[$getKey]);*/
				
				$_GET[$getKey] = $getVal;
			}
		}
	}

	public static function _parse_path($uri = '') {
	
		if(empty($uri)) $uri = $_SERVER['REQUEST_URI'];
		
		$pathComponents = explode('?', $uri);
		if(empty($pathComponents[1])) $pathComponents[1] = '';
		
		list($path, $get) = $pathComponents;
		$path = explode("/", $path);

		$path = array_reverse($path);

		if($path[0] == '') {
			unset($path[0]);
		}
		$path = array_reverse($path);

		unset($path[0]);
		return $path;
	}

	public static function _parse_domain() {
		self::$server = $_SERVER['SERVER_NAME'];
		$array = explode('.', $_SERVER['SERVER_NAME']);
		$array = array_reverse($array);
		$pointer = 1;

		foreach($array as $domain) {
			$output[$pointer] = $domain;
			++$pointer;
		}

		// get the port if included in url
		$httpHost = explode(':',$_SERVER['HTTP_HOST']);
		if(empty($httpHost[1])) $httpHost[1] = ''; //Prevents port from being undefined on next line
		
		list($domain, $port) = $httpHost;
		$output['1'] = $port ? $output['1'].':'.$port : $output['1'];
		self::$server = $port ? self::$server.":".$port : self::$server;
		return $output;
	}

	public static function _is_function_call($call) {

		return
			strstr($call, '::') ?
			trim(str_replace('::','',$call)) :
			false;

	}
	public static function _match_handle($handler, $handle) {

		if(substr($handler, 0, 1) == '%') {
			if(match_type(substr($handler, 1), $handle)) {
				return true;
			}
		}
		elseif($handle == $handler) { #echo $handle.'=='.$handler;
		return true; }

		return false;

	}



	public static function Redirect($to = NULL) {
		
		if(empty($to)) $to = self::url();
		
		ob_end_clean();
		
		if(stripos($to, '://') === FALSE) {
			$url = self::protocol(). '://' . self::$server . self::Link($to);
		} else {
			$url = $to;
		}
		
		if (!headers_sent()){    // If headers not sent yet... then do php redirect
	        header('Location: '.$url);
	        echo "If this page does not redirect, <a href=\"$url\">click here</a> to continue";
	    } else {                 // If headers are sent... do javascript redirect... if javascript disabled, do html redirect.
	        echo '<script type="text/javascript">';
	        echo 'window.location.href="'.$url.'";';
	        echo '</script>';
	        echo '<noscript>';
	        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
	        echo '</noscript>';
	    }
		
	    core_halt();
	}


	public static function url($protocol = NULL, $domain = NULL) {
		if(empty($protocol)) {
			$protocol = self::protocol();
		}
		if(empty($domain)) {
			$domain = $_SERVER['HTTP_HOST'];
		}
		return $protocol . '://' . $domain . $_SERVER['REQUEST_URI'];
	}
	
	
	public static function domain($prepend = '', $root = TRUE) {
		return ($root == TRUE)?
			$prepend.self::$domain[2].'.'.self::$domain[1] :			// domain.tld
			$prepend.implode('.', array_reverse(self::$domain));		// FQDN
	}
	
	public static function protocol() {
		return ($_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
	}
	
	
	public static function is_ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest");
	}
	
	
	public static function Link($href, $text = 'none', $rel = false) {
		if($text != 'none') {
			$rel = $rel ? ' rel="'.$rel.'"' : false;
			if(strlen($text) == 0) $text = 'Default Link Text';
			$text = htmlspecialchars($text);
			$href = self::_strip_sid($href);
			$href = Session::$using == 'cookie' ? self::_strip_sid($href) : self::_append_sid($href);
			return "<a href=\"$href\" alt=\"$text\"$rel>$text</a>";
		}
		else {
			return (isset($_COOKIE[Session::$variable]))? self::_strip_sid($href) : self::_append_sid($href);
		}
	}
	
	private static function _append_sid($href) {
	
		$href = self::_strip_sid($href);
		list($link, $get) = explode("?", $href);
		$get = strlen($get) > 0 ? Session::$variable."=".Session::$sid."&".$get : Session::$variable."=".Session::$sid;
		
		// Killed SID appendage.
		return $link;		// ."?".$get;
	}
	
	private static function _strip_sid($href) {
		list($link, $get) = explode("?", $href);
		$gvars = explode('&', $get);
		if(count($gvars) > 0) {
			foreach($gvars as $key => $val) {
				if(strpos($val, Session::$variable.'=') !== false) unset($gvars[$key]);
			}
			$get = implode('&', $gvars);
		}
		return (count($gvars) > 0 && strlen($get) > 0)? $link.'?'.$get : $link;
	}



}
