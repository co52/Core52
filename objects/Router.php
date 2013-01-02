<?php

class RouterException extends Exception {}

/**
 * Core52 Routing class
 *
 * The Router object manages all the URL handling for C52 based software. It is extemely
 * flexible in its implementation. Following are the details of the initialization process
 * as well as information on how to implement it.
 *
 * @author "Jonathon Hill" <jhill@companyfiftytwo.com>
 * @package Core52
 * @version 1.0
 *
 * All directory references are to the /controllers directory unless otherwise specified.
 *
 * Example 1: -------------------------------------------------------------------------------------------------
 *
 * If your url is /account this is what the script will look for to handle it. These are executed
 * in order and once a handler has been found, it will not look for a handler in another place.
 * 1) Checks for handler declared by Router::set_handler()
 * 2) Checks for a file named account.php
 * 3) Checks for a file named account/_default.php
 * 4) Declares that there is no handler for this path.
 *
 * Example 2: --------------------------------------------------------------------------------------------------
 *
 * If your url is /account/post Example 1 will execute as normal until Step 3. If /account/_default.php
 * exists, it will check to see if handler has been assigned for the 'post' handle. If you have assigned
 * a handler with Router::set_handler(), then that handler will be executed. Otherwise before
 * declaring a handler doesn't exist, it will look for /account/post.php and /account/post/_default.php
 * Thus, you could technically have only one file in your controllers located in /account/post.php and
 * /account/post will execute that handler.
 *
 * Object Based Controllers -----------------------------------------------------------------------------------
 *
 * You may have noticed above that you can assign handlers "by Object". This is how. If your handler is
 * account.php. You can declare a class with "Controller_Account extends Controller". This is not a static
 * class. If the Router object discovers an object named Controller_Account, it will automatically instantiate it.
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
 * Function based controllers must be assigned manually with the Router::set_handler() function.
 *
 *
 * FUNCTIONALITY ||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
 *
 * --- Assign a handler.
 * Router::set_handler(string|%regex, file|controller:method|callback);
 *
 * --- Assign a default handler. This is executed at shutdown if no other handler has run.
 * Router::set_default_handler(file|controller:method|callback);
 *
 * --- Redirect to another url.
 * Router::redirect(url);
 *
 * --- Label the handles.
 * Router::label("[/]label/label/...");
 * For example, if you ran Router::label('/account/subscription/issue'); you could then access handle 1 with
 * Router::Handle('account'); instead of Router::Handle(1); So if you have run the label function, accessing /12/14/82
 * will hunt for handlers as if you were accessing /account/subscription/issue. You can run this function as many times
 * as you'd like and you can run it on paths relative to the current handle.
 *
 * --- Trace the url path to your current or specified handle.
 * Router::trace(null|integer);
 * This will return the trace up to the handle which would be returned by Router::handle(); So... if your
 * url is /account/subscription/1 then the trace for Router::trace('subscription'); would be "/account/",
 * and the trace for Router::trace(3); would be /account/subscription/
 *
 * --- Get the value of a handle
 * Router::handle(null|integer|string);
 * null: returns the current handle
 * integer (positive): returns the handle by # eg /1/2/3/4/5/6/..
 * integer (negative): returns the handle by reverse #: /-6/-5/-4/-3/-2/-1/
 * string (of style "+integer"): returns the value of the handle # relative to the current position.
 * string: returns the value of the handle by label
 *
 * --- Positioning the handle pointer
 * Router::position(null|integer);
 * null: returns the current position
 * integer: sets the position to whatever handle # specified.
 *
 * Router::next();
 * Moves the pointer forwards 1.
 * So, if you access /account/post/1234 and it is handled by /account/post.php, then Router::handle()
 * would return 1234 and Router::position() would return 3.
 *
 **/

class Router {

	# url data from parse_url()
	public static $data = array();
		
	# the requested url, eg: /something/else/now
	public static $path = '';
	
	# the domain, broken up into an array, eg: array(1 => 'com', 2 => "domain", 3 => 'www')
	public static $domain = array();
	
	# the domain, plain text, eg: www.domain.com
	public static $server = '';
	
	# the url the individual came from
	public static $referer;


	# an array of handles, eg: array(1 => 'something', 2 => 'else', 3 => 'now')
	protected static $handles = array();
	
	# an array of handle parameters
	protected static $parameters = array();
	
	# current pointer
	protected static $pointer = 0;
	
	# matching runtime handlers
	protected static $routes = array();
	
	# an array of labels
	protected static $labels = array();
	
	# an array of custom routes
	protected static $handlers = array();
	protected static $default_handler = array();
	
	# handler root
	protected static $handler_root = '';


	
	/**
	 * Initialize
	 *
	 * @param string $dir	Controllers directory (defaults to PATH_CONTROLLERS)
	 */
	public static function Initialize($dir = NULL) {

		self::$data = parse_url(self::url());
		extract(self::$data);
		
		# initialize variables
		self::$path = $path;
		self::$domain = array_reverse(explode('.', $host));
		self::$server = $host;
		self::$referer = $_SERVER['HTTP_REFERER'];
		self::$pointer = 0;
		
		# separate each handle from its parameters
		foreach(explode("/", trim(self::$path, '/')) as $handle) {
			$args = explode(':', $handle);
			$handle = array_shift($args);
			self::$handles[] = $handle;
			self::$parameters[$handle] = $args;
		}
		
		# set the include path for the handler directory
		if(empty($dir)) $dir = PATH_CONTROLLERS;
		self::$handler_root = rtrim($dir, '/\\');
		
		# define SUBDOMAIN
		$subDomain = !empty(self::$domain[3]) ? self::$domain[3] : '';
		define('SUBDOMAIN', $subDomain);
		
	}
	

	/**
	 * Maps URIs to the appropriate controller, and runs it
	 *
	 * @param boolean $reset	If TRUE, resets the internal handle pointer to 0
	 */
	public static function route($reset = FALSE) {
		
		if($reset) {
			self::$pointer = 0;
		}
		
		# shortcut references
		$ptr		=& self::$pointer;
		$handles	=& self::$handles;
		
		
		# get next handle
		for(; $ptr <= count($handles); $ptr++) {
			
			$handle = $handles[$ptr];
			
			# handler assigned?
			$handler = self::_match_handler($ptr);
			if(is_array($handler) && $ptr < count($handles)) {
				# store match
				if(!$handler['parameters']) {
					$handler['parameters'] = self::$parameters[$handle];
				}
				self::$routes[$ptr] = $handler;
				continue;
			}
			
			# is file?
			$handler = self::_match_method($ptr);
			if(is_array($handler)) {
				# store match
				self::$routes[$ptr] = $handler;
				continue;
			}
			
			# is directory?
			$handler = self::_match_directory($ptr);
			if(is_array($handler)) {
				# store default file match
				self::$routes[$ptr] = $handler;
				continue;
			}
		
		};
		
		# have any matches?
		$handler = array_pop(self::$routes);
		$ran = FALSE;
		if(is_array($handler)) {
			try {
				# run the last handler matched
				$ran = self::_run_handler($handler);
			} catch(PageNotFoundException $e) {
				# ignore
			}
		}
		if($ran) {
			# controller ran
			return TRUE;
		} elseif(!$ran && self::$default_handler) {
			# run the default handler
			return self::_run_handler(self::$default_handler);
		} else {
			# couldn't find anything to run
			return FALSE;
		}

	}

	
	/**
	 * Checks to see if a custom handler is present
	 *
	 * @param integer $ptr		Pointer to URI segment
	 * @return boolean|array	FALSE if no match, or handler array
	 */
	protected static function _match_handler($ptr) {

		$handle = self::$handles[$ptr];
		
		# no custom handlers
		if(empty(self::$handlers)) {
			return FALSE;
		}
		
		# exact match?
		$h = rtrim(self::trace($ptr + 1), '/');
		if(array_key_exists($h, self::$handlers)) {
			return self::$handlers[$h];
		}
		
		# test regexes
		foreach(self::$handlers as $regex => $hdata) {
			if(@preg_match($regex, $h, $matches) === 1) {
				array_shift($matches);
				if(!empty($matches) && empty($hdata['parameters'])) {
					$hdata['parameters'] = array_reverse($matches);
				}
				return $hdata;
			}
		}
		
		# no custom handler found
		return FALSE;
	}
	
	
	/**
	 * Checks to see if a directory default handler is present
	 *
	 * @param integer $ptr		Pointer to URI segment
	 * @return boolean|array	FALSE if no match, TRUE if there are still handles to process, or handler array
	 */
	protected static function _match_directory($ptr) {
		
		$path = rtrim(strtolower(self::$handler_root.self::trace($ptr)), '/\\');
		
		if(is_dir($path)) {
			
			# is a directory, no more handles, and default file exists?
			if($ptr + 1 > count(self::$handles) && file_exists("$path/_default.php")) {
				
				return array(
					'file'    => "$path/_default.php",
					'handler' => 'Controller_Default:_default',
				);
				
			}

			# is a directory, but there are more handles to process
			return TRUE;
			
		} else {
			
			# not a directory
			return FALSE;
			
		}
		
	}
	
	
	/**
	 * Checks to see if a controller method handler is present
	 *
	 * @param integer $ptr		Pointer to URI segment
	 * @return boolean|array	FALSE if no match, or handler array
	 */
	protected static function _match_method($ptr) {
		
		$handle = self::$handles[$ptr];
		$path = strtolower(self::$handler_root.rtrim(self::trace($ptr), '/').".php");
		
		if(file_exists($path)) {
			
			# is a file
			
			if($ptr == count(self::$handles)) {
				$method = '_default';
			} else {
				$method = $handle;
			}
			
			$class  = str_replace('-','_',ucfirst(self::$handles[$ptr - 1]));
			$method = str_replace('-','_',$method);
			
			return array(
				'file'		 => $path,
				'handler'	 => "Controller_$class:$method",
				'parameters' => self::$parameters[$handle],
			);
			
		} else {
			
			# not a file
			return FALSE;
			
		}
		
	}
	
	
	/**
	 * Executes a handler
	 *
	 * @param string|array $handler
	 */
	protected static function _run_handler($the_handler) {
		
		extract($the_handler);
		$call = FALSE;
		
		
		# figure out what kind of handler we have, and format it appropriately
		if(is_array($handler) && is_callable($handler)) {
			
			# callback handler
			$call = $handler;
			
		} elseif(strpos($handler, ':') !== FALSE && strpos($handler, ':/') === FALSE) {
			
			# controller:method handler
			list($controller, $method) = explode(':', $handler);
			
			# load the controller file
			if($file) {
				require_once $file;
			} else {
				$controller = core_load_controller($controller);
			}
			
			#$class = "Controller_$controller";
			try {
				if(class_exists($controller)) {
					$call = array(new $controller, $method);
				}
			} catch(AutoClassLoaderException $e) {
				return FALSE;
				#throw new RouterException("Invalid handler: ".var_export($the_handler, TRUE));
			}
			
		} elseif(file_exists($handler)) {
			
			# file handler
			$call = $handler;
			
		} elseif(function_exists($handler)) {
			
			# function handler
			$call = $handler;
			
		}
		
		
		# run the handler
		if(is_string($call) && file_exists($call)) {
			
			require_once $call;
			
		} elseif(is_string($call)) {
			
			call_user_func_array($call, (array) $parameters);
			
		} elseif(is_array($call)) {
			
			if(substr($call[1], 0, 1) === '_' && $call[1] !== '_default') {
				# private method
				throw new PageNotFoundException();
			} elseif(is_callable($call)) {
				call_user_func_array($call, (array) $parameters);
			} else {
				throw new PageNotFoundException();
			}
			
		} else {
			# procedural controller file
		}
		
		return TRUE;
	}
	
	
	/**
	 * Validate a custom handler. Throws a RouterException if invalid.
	 *
	 * @param string|array $handler
	 * @return boolean
	 */
	protected static function _validate_handler($handler) {
		
		$valid = TRUE;
		
		if(is_array($handler)) {
			# callback handler
			$valid = is_callable($handler);
		}
		elseif(strpos($handler, ':') !== FALSE && strpos($handler, ':/') === FALSE) {
			
			# controller:method handler
			list($controller, $method) = explode(':', $handler);
			
			try {
				$controller = core_load_controller($controller);
				$valid = method_exists($controller, $method);
			} catch(AutoClassLoaderException $e) {
				$valid = FALSE;
			}
		}
		else {
			# file or function handler
			$valid = (file_exists($handler) || function_exists($handler));
		}
		
		if($valid) {
			return TRUE;
		} else {
			throw new RouterException("Invalid handler: ".var_export($handler, TRUE));
		}
		
	}
	
	
	/**
	 * Set a custom URI handler
	 *
	 * @param string $path			Path or regular expression
	 * @param string|array $handler	Handler. Must be controller:method string, a file, or a callback.
	 * @param array $parameters		Special parameters to pass to the handler
	 *
	 * Examples:
	 *
	 *   Router::set_route('/login/special', PATH_APP.'custom_login.php');
	 *   Router::set_route('/login/mapper', array(new Login, 'map'), array('param' => 'value'));
	 *   Router::set_route('/users/[0-9]+/test', 'users:test');
	 *
	 */
	public static function set_handler($handle, $handler, array $parameters = array()) {
		if(self::_validate_handler($handler)) {
			self::$handlers["@$handle@"] = compact('handler', 'parameters');
		}
	}
	
	
	/**
	 * Set a default catch-all handler
	 *
	 * @param string|array $handler	Handler. Must be a controller:method string, a file, or a callback.
	 * @param array $parameters				 Special parameters to pass to the handler.
	 */
	public static function set_default_handler($handler, array $parameters = array()) {
		if(self::_validate_handler($handler)) {
			self::$default_handler = compact('handler', 'parameters');
		}
	}
	
	
	/**
	 * Increments the internal handle pointer
	 */
	public static function next() {
		self::$pointer++;
	}
	
	
	/**
	 * Decrements the internal handle pointer
	 *
	 */
	public static function prev() {
		self::$pointer++;
	}
	
	
	/**
	 * Get or set the internal handle pointer value (one-based indexing)
	 *
	 * @param integer|null $value
	 * @return integer|null
	 */
	public static function position($value = null) {
		if(is_null($value)) {
			return self::$pointer + 1;
		} else {
			self::$pointer = $value - 1;
		}
	}
	
	
	/**
	 * Get the URI segments up to a certain point
	 *
	 * @param integer $no	Where to stop tracing URI segments.
 	 * 						Formats:
 	 * 							positive integer	nth handle, counting from the beginning (one-based indexing)
 	 * 							negative integer	nth handle, counting from the end (one-based indexing)
 	 *
 	 * @return string		Partial URI segment string
	 */
	public static function trace($no = false) {
		
		$no = ($no !== false)? $no : self::$pointer;
		
		if($no < 0) {
			$no--;
			$no = $no / -1;	// invert sign
			$no = self::$pointer - $no;
		}
		
		$path = '/';
		foreach(self::$handles as $key => $val) {
			
			if($key + 1 > $no) {
				break;
			}
			
			$path .= $val.'/';
		}
		
		return $path;
	}

	
	/**
	 * Fetch a URI segment.
	 *
	 * @param integer|string $pointer	Which handle to fetch.
	 *
	 * 									Formats:
	 * 										positive integer	nth handle, counting from the beginning (one-based indexing)
	 * 										negative integer	nth handle, counting from the end (one-based indexing)
	 * 										"+integer"			nth handle relative to the current handle pointer position, counting forward
	 * 										"-integer"			nth handle relative to the current handle pointer position, counting backward
	 * 										"label"				previously declared segment label
	 *
	 * @return string
	 */
	public static function handle($pointer = false) {
		
		# if no pointer has been specified, get the current pointer
		$pointer = ($pointer !== FALSE)? $pointer : self::$pointer;
		
		# string "+integer"
		if(is_string($pointer) && substr($pointer, 0, 1) === '+') {
			$pointer = self::$pointer + str_replace('+', '', $pointer);
			return (string) self::$handles[$pointer];
		}
		
		# string "-integer"
		elseif(is_string($pointer) && substr($pointer, 0, 1) === '-') {
			$pointer = self::$pointer - str_replace('-', '', $pointer);
			return (string) self::$handles[$pointer];
		}
		
		# string
		elseif(is_string($pointer)) {
			$labels = array_flip(self::$labels);
			$pointer = $labels[$pointer];
				return (string) self::$handles[$pointer];
		}
		
		# integer (negative)
		elseif($pointer < 0) {
			$pointer = $pointer / -1;	// invert sign
			$pointer = $pointer - 1;	// subtract 1
			$endlevels = array_reverse(self::$handles);
			return (string) $pointer ?  $endlevels[$pointer] : $endlevels[$pointer];
		}
		
		# integer (positive)
		else {
			return (string) self::$handles[$pointer - 1];
		}
	}
	
	public static function handle_params($pointer, $index = null) {
		$handle = self::handle($pointer);
		return ($index === null? (array) self::$parameters[$handle]:(string) self::$parameters[$handle][$index]);
	}
	
	/**
	 * Assign labels to URI segments
	 *
	 * @param string $string	labels ("/label1/label2/label3"...)
	 */
	public static function label($string) {
		
		$labels = explode("/", $string);
		
		if(substr($string, 0, 1) == '/') {
			
			array_shift($labels);
			foreach($labels as $key => $val) {
				self::$labels[$key] = $val;
			}
			
		} else {
			
			foreach($labels as $key => $val) {
				self::$labels[$key + self::$pointer] = $val;
			}
			
		}
		
	}


	/**
	 * Redirect to another page
	 *
	 * @param string $to = NULL		URL to redir to. Uses current URL from self::url() if none specified.
	 */
	public static function redirect($to = NULL, $redirect_method = 'http') {
		
		if(empty($to)) $to = self::url();
		
		ob_end_clean();
		
		if(strpos($to, '://') === FALSE) {
			if(substr($to, 0, 1) !== '/' && strpos($to, '.') === FALSE) {
				$url = rtrim(self::url(self::protocol()), '/') . '/'. ltrim($to, '/');
			} else {
				$url = self::protocol(). '://' . self::$server . $to;
			}
		} else {
			$url = $to;
		}
		
		if (!headers_sent() && $redirect_method == 'http'){ // If headers not sent yet... then do php redirect
	        header('Location: '.$url);
	        echo "If this page does not redirect, <a href=\"$url\">click here</a> to continue";
	    } else { // If headers are sent... do javascript redirect... if javascript disabled, do html redirect.
	        echo '<script type="text/javascript">';
	        echo 'window.location.href="'.$url.'";';
	        echo '</script>';
	        echo '<noscript>';
	        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
	        echo '</noscript>';
	    }
		
	    core_halt();
	}


	/**
	 * Returns the full URL of the current page
	 *
	 * @param string $protocol = NULL	HTTP protocol override (without the '://')
	 * @param string $domain = NULL		Domain override
	 * @return string 					URL string for current page
	 */
	public static function url($protocol = NULL, $domain = NULL) {
		if(empty($protocol)) {
			$protocol = self::protocol();
		}
		if(empty($domain)) {
			$domain = $_SERVER['HTTP_HOST'];
		}
		return $protocol . '://' . $domain . $_SERVER['REQUEST_URI'];
	}
	
	
	/**
	 * Returns the full URI of the current page
	 *
	 * @return string 					URI string for current page
	 */
	public static function uri() {
		return $_SERVER['REQUEST_URI'];
	}
	
	
	/**
	 * Return the server domain
	 *
	 * @param string $prepend Character to prepend (such as '.' for cookie domains)
	 * @param boolean $root If TRUE, returns only the domain.tld (default); if FALSE, returns the FQDN
	 */
	public static function domain($prepend = '', $root = TRUE) {
		return ($root == TRUE)?
			$prepend.self::$domain[1].'.'.self::$domain[0] :			// domain.tld
			$prepend.implode('.', array_reverse(self::$domain));		// FQDN
	}
	
	
	/**
	 * Determine the protocol used to call the current page
	 *
	 * @return string http or https
	 */
	public static function protocol() {
		return ($_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
	}
	
	
	/**
	 * Determine if AJAX was used to call the current page
	 *
	 * @return boolean
	 */
	public static function is_ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest");
	}


	/**
	 * Determine if this request is from an iPad
	 *
	 * @return boolean
	 */
	public static function is_ipad() {
		return !(strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'ipad') === FALSE);
	}

	
	/**
	 * Determine if this request is from an iPhone/iPod
	 *
	 * @return boolean
	 */
	public static function is_iphone() {
		return ((strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'iphone') !== FALSE) || (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'ipod') !== FALSE));
	}


	/**
	 * Determine if this request is from an Android
	 *
	 * @return boolean
	 */
	public static function is_android() {
		return !(strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') === FALSE);
	}


	/**
	 * Determine if this request came from a mobile device
	 * Stolen from http://www.brainhandles.com/techno-thoughts/detecting-mobile-browsers
	 *
	 * @return boolean
	 */
	public static function is_mobile() {

		if(isset($_SERVER["HTTP_X_WAP_PROFILE"])) return true;
		
		if(preg_match("/wap\.|\.wap/i",$_SERVER["HTTP_ACCEPT"])) return true;
		
		if(isset($_SERVER["HTTP_USER_AGENT"])) {
		
			// Quick Array to kill out matches in the user agent that might cause false positives
			$badmatches = array(
				"OfficeLiveConnector",
				"MSIE\ 8\.0",
				"OptimizedIE8",
				"MSN\ Optimized",
				"Creative\ AutoUpdate",
				"Swapper"
			);
			
			foreach($badmatches as $badstring){
				if(preg_match("/".$badstring."/i",$_SERVER["HTTP_USER_AGENT"])) return false;
			}
			
			// Now we'll go for positive matches
			$uamatches = array(
				"midp",
				"j2me",
				"avantg",
				"docomo",
				"novarra",
				"palmos",
				"palmsource",
				"240x320",
				"opwv",
				"chtml",
				"pda",
				"windows\ ce",
				"mmp\/",
				"blackberry",
				"mib\/",
				"symbian",
				"wireless",
				"nokia",
				"hand",
				"mobi",
				"phone",
				"cdm",
				"up\.b",
				"audio",
				"SIE\-",
				"SEC\-",
				"samsung",
				"HTC",
				"mot\-",
				"mitsu",
				"sagem",
				"sony",
				"alcatel",
				"lg",
				"erics",
				"vx",
				"NEC",
				"philips",
				"mmm",
				"xx",
				"panasonic",
				"sharp",
				"wap",
				"sch",
				"rover",
				"pocket",
				"benq",
				"java",
				"pt",
				"pg",
				"vox",
				"amoi",
				"bird",
				"compal",
				"kg",
				"voda",
				"sany",
				"kdd",
				"dbt",
				"sendo",
				"sgh",
				"gradi",
				"jb",
				"\d\d\di",
				"moto",
				"webos"
			);
			
			foreach($uamatches as $uastring){
				if(preg_match("/".$uastring."/i",$_SERVER["HTTP_USER_AGENT"])) return true;
			}
		
		}
		
		return false;
	}
	
	
	
	
}
