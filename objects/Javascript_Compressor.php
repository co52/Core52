<?php

/**
 * Javascript Class
 *
 * @author Kyle Simukka, Alex King
 * @package Core52
 * @version 1.1
 *
 * 1.1
 * - Collecting and compressing javascript in a single cached file 
 * - Adding position parameter to add(), to add files at the start or the end of the script
 * - Moving publish($include_inline) from a parameter to an option, defaulting to true 
 * - Adding an add_variable method to add inline variables to the document 
 *  
 * 1.0
 * - Collecting files and outputting javascript <script> tags
 * - Collecting and outputting snippets of javascript code 
 **/

if(!defined('JAVACRIPT_BASE')) {
	define('JAVASCRIPT_BASE', '/static/js/');
}

class Javascript_Compressor {
	
	/**
	 * Javascript files
	 *
	 * @var array
	 **/
	protected static $_files;
    
	protected static $_last_files = array();
	protected static $_middle_files = array();
	protected static $_first_files = array();

	protected static $_code;


	/**
	 * Add Javascript files
	 *
	 * @param	mixed	$files	either an array of file names, or a string with a file name 
	 * @return 	void	
	 * @author 	Alex King
	 **/
	static public function add($files, $position = false) {
		
		// If this isn't an array, then make it into one
		if (!is_array($files)) {	
			$files = array($files);
		}
		
		// Add this array to the end of the files array
		if ($position == 'last') {
			self::$_last_files = array_merge((array) self::$_last_files, (array) $files);
			
		} elseif ($position == 'first') {
			self::$_first_files = array_merge((array) self::$_first_files, (array) $files);

		} else {
			self::$_middle_files = array_merge((array) self::$_middle_files, (array) $files);
			
		}
	}
	
	/**
	 * Compatibility  
	 * @version 1.0
	 **/
	static public function add_remote($files) {
		self::add($files);
	}
	
	/**
     * Add code snippet
     *
     * @param 	$data
     * @return 	NULL
	 * @author	Kyle Simukka 
     */
    public static function add_inline($data) {
        self::$_code[] = $data;
    }
	
	/**
	 * Set variable 
	 *
	 * @return 	void	
	 * @author 	Alex King
	 **/
	public static function add_variable($variable, $value, $json = false) {
        
		// If this is a JSON variable, then we should encode it and not use quotes. 
		if ($json) { 
			
			// Convert to JSON
			$value_json = json_encode($value);
			
			// Create a snippet
			$snippet = "var $variable = $value_json;";
			
		} else {
		
			// Escape any single quotes 
			$value = str_replace("'", "\'", $value);

			// Create a snippet
			$snippet = "var $variable = '$value';";
		
		}
		
		// Add the snippet
		self::add_inline($snippet);
    }
   	
	/**
	 * Compress a string of javascript 
	 *
	 * @return 	void	
	 * @author 	Alex King
	 **/
	static public function compress($string) {
		
		// Make sure we have the compressor 
		require_once PATH_CORE.'3rdparty/jsmin/jsmin.php';
		
		// Compress 
		return JSMin::minify($string);
		
	}
	
	/**
	 * Loop through each file and combine it into an array 
	 *
	 * @return 	void	
	 * @author 	Alex King
	 **/
	static public function collect() {
		$scripts = '';

		foreach (self::$_files as $file) {

			// Find the path
			$path = PATH_APP . JAVASCRIPT_BASE . $file;
			
			// Check if the file exists
			if (file_exists($path)) {
				
				// Load the file 
				$script = file_get_contents($path);

			 	$scripts .= "\n\n" . $script;
			}
		}
		
		return $scripts;
	}

	/**
	 * Combine files from first, last, and middle arrays into one array
	 *
	 * @return 	void	
	 * @author 	Alex King
	 **/
	static protected function _sort_files() {
		
		// Put all the files together 
		self::$_files = array_merge(
			(array) self::$_first_files, 
			(array) self::$_middle_files,
			(array) self::$_last_files
		);

	}

	/**
	 * Return HTML of script tags for the original files in the files array
	 *
	 * @return 	string	
	 * @author 	Alex King
	 **/
	static public function get() {
		
		// Sort the files if we haven't yet 
		if (!self::$_files) {
			self::_sort_files();
		}
		
		foreach (self::$_files as $file) {
			$html .= '<script type="text/javascript" src="' . JAVASCRIPT_BASE . $file . "\"></script>\n";
    	}

		return $html;
	}

    /**
     * Return all inline javascript as a string for printing
     *
     * @return 	string
 	 * @author	Kyle Simukka
     */
    public static function get_inline() {
        
		// Check if we have inline scripts
		if (self::$_code) { 
			$string = "<script type=\"text/javascript\">\n//<![CDATA[\n";
      
	  		foreach(self::$_code as $code) {
	            $string .= "" . $code . "\n";
	        }

	        $string .= "//]]>\n</script>\n";
	        return $string;
		}
		
    }

    /**
     * Compatibility
     *
     * @version 1.0
     */
    public static function publish_inline() {
    	echo self::get_inline();
    }


	/**
	 * Return a script tag or tags with all Javascript 
	 *
	 * @param	array 		$options
	 * 			collect		collect all javascript into one file (default true)
	 * 			compress	compress the javascript using JSmin (default true, requires collect)
	 *			stats		prepend a comment with statistics to the top of the page 
	 * @return 	void	
	 * @author 	Alex King
	 **/
	static public function publish(array $options = null) { 

		$defaults = array(
			'collect' => true,
			'compress' => true, 
			'stats'	=> true,
			'include_inline' => true,
			'refresh_cache' => false,
		);

		// Combine defaults and options 
		$options = array_merge($defaults, (array) $options);
		
		// Override with any development settings 
	 	$dev = Config::get('JAVASCRIPT_DEV', false);
		if ($dev === 'compress') {

			// Compress on each run
			$options['refresh_cache'] = true;	
			
		} elseif ($dev == true) {
			
			// Don't even run the files through the compressor
			$options['collect'] = false;
		}

		// Sort the files
		self::_sort_files();
		
		// Find the cache directory and version number 
		$cache = PATH_STATIC."cache/js/";
		$version = svn_get_version(PATH_APP); 
		
		// The files and their order are also important
		$files_included = implode("-", self::$_files);
		
		// Shorten down to a md5
		$md5 = md5($version . "-" . $files_included);
		
		// Put together the filename
		$filename = "all-$md5.min.js";
		$filepath = $cache . $filename;

		if ($options['collect']) {

			// Assume we have this cached
			$cache_available = true;

			// If there is no file for this revision, or if we are in developement mode, build the file 
			if (!file_exists($filepath) || $options['refresh_cache']) {

				// Collect the scripts 
				$scripts = self::collect();
				$pre_compress_size = self::_string_size($scripts);
				$compress_start = time();
		
				// Compress the scripts 
				if ($options['compress']) {
					$scripts = self::compress($scripts);
					$post_compress_size = self::_string_size($scripts);
				}
				
		
				// Add some stats to the top 
				if ($options['stats']) {
			
					$scripts = "/* " . format_filesize($pre_compress_size) . "/" . format_filesize($post_compress_size) 
								. " (" . format_filesize($pre_compress_size - $post_compress_size) . " saved)"
								. " last generated at " . format_date() . " in " . (time() - $compress_start) . "ms - r" . $version . " */\n" . $scripts;

				}

				// Make sure the cache directory exists 
				if(!is_dir($cache)) {
					mkdir($cache);
				}
	
				// Write this to a file 
				$cache_available = file_put_contents($filepath, $scripts); 
			
				
			}
			
			// If we have a cache available
			if ($cache_available) { 
				
				// Write a script tag pointing to the new script
				print '<script type="text/javascript" src="/static/cache/js/' . $filename . "\"></script>\n";
		
			// If we don't 
			} else {	
		
				// Fall back on writing script tags, and note an error 
				print "<!-- Script files are uncompressed, please fix cache to save " . format_filesize($pre_compress_size - $post_compress_size) . " -->\n";
				print self::get();

			}
			
		// No collection or compression, just output the scripts 
		} else {
			
			print self::get();
		}
		
		// Handle any inline javascript 
		if ($options['include_inline']) { 
			print self::get_inline();
		}

	}
	
	/**
	 * Find out the "file size" of a string
	 *
	 * @return 	void	
	 * @author 	Alex King
	 **/
	static protected function _string_size($string) {
		
		if (function_exists('mb_strlen')) {
		    $size = mb_strlen($string, '8bit');
		} else {
		    $size = strlen($string);
		}
		
		return $size;
	}
	
	/**
	 * Convert object to JSON, print, and halt the core
	 *
	 * @return 	void	
	 * @author 	Alex King
	 **/
	static public function output_json($object) {
		
		print json_encode($object);
		core_halt();
	}

    
}
