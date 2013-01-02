<?php

/**
 * CLI_Tool Class
 * 
 * Extend this class for interactive CLI tools, provides output and other 
 * useful functions.  
 * 
 * @author Alex King
 */

class CLI_Tool extends CLI_Script {
	public function runtime() { }


	/**
	 * Output with line items 
	 */
	protected function say($str = '', $color = FALSE, $break = TRUE) {
		if (is_array($str)) {
			$str = implode(PHP_EOL, $str);
		}

		// Color?
		if ($color) {
			$str = $this->colorize($str, $color);
		}

		echo $str . ($break ? PHP_EOL : '');
	}

	protected function indent($str, $tabs = 1, $color = FALSE) {
		$this->say(str_repeat("  " , $tabs) . $str, $color);
	}

	/**
	 * Run repeatedly to write to the same line
	 */
	protected function line($str, $color = FALSE) {

		// Erase old line 
		$this->erase();

		// Write the new one, without a line break
		$this->say($str, $color, FALSE);

	}

	protected function erase() {

		// Erases the current line - don't set a line return if you want 
		echo "\x1B[2K\x1B[0E";
	}

	/**
	 * Ask the user to enter command line input 
	 * @param  string  	$message 	message to display asking for input 
	 * @param  array 	$options 	options that with be numbered and put on separate lines 
	 * @param  array 	$Validate   require an answer
	 * @return [type]
	 */
	protected function ask($message, $default = FALSE, $required = TRUE, $options = FALSE) {

		// If we have a default? 
		if ($default) {
			$message .= ' (default is ' . $default .')';
		}

		// If we have options 
		if ($options && is_array($options)) {

			$number = 0;
			foreach($options as $option) {
				$number++;

				$message .= PHP_EOL . $number . ') ' . $option;
			}
		}

		$this->say($message);

		// Allows us to start the process over again 
		ask_for_input: {
	
			// Ask for input 
			$input = chop(fgets(STDIN));

			// Add a line break
			$this->br();

			// Apply any default
			if (!$input && $default) {
				$input = $default;
			}

			// If this is required
			if (!$input) {
				$this->say('Please enter a value');
				goto ask_for_input;
			}

			// Validate if we have options 
			if ($options) {
				
				// Need to be numeric and in range 
				if (!is_numeric($input) || $input == 0 || $input > count($options)) {

					$this->say('Please enter a number between 1 and ' . count($options));

					// Ask again
					goto ask_for_input;
				}

				// Indexed or associative? 
				if (array_values($options) === $options) {

					// Indexed, return the option instead of the number 
					return $options[($input - 1)];

				} else {

					// Associative, return the key 
					$option_keys = array_keys($options);
					return $option_keys[($input - 1)];

				}
			}

		}

		return $input;


	}

	/**
	 * Asks the user and returns a boolean result
	 * @param  string  	$message 	message to display asking for input 
	 * @return bool message
	 */
	protected function ask_bool($message) {
		
		// Add (y/n)
		$message .= ' (y/n)';

		$result = $this->ask($message);

		return $result == 'y';

	}


	protected function br() {
		$this->say();
	}

	protected function h1($message, $color = false) {
		$this->heading($message, $color, "=", "=");
	}

	protected function h2($message, $color = false) {
		$this->heading($message, $color, false, "=");
	}

	protected function h3($message, $color = false) {
		$this->heading($message, $color, false, "-");
	}

	protected function heading($message, $color, $top = false, $bottom = "=") {
		$this->br();

		if ($top) { 
			$this->say(str_repeat($top, strlen($message)), $color);
		}

		$this->say($message, $color);

		if ($bottom) { 
			$this->say(str_repeat($bottom, strlen($message)), $color);
		}
	}


	/**
	 * Get the CLI options
	 * Differs from version in CLI_Script as it supports -k value instead of -k=value
	 * @return array
	 */
	protected function _get_options() {
		
		$options = array();

		$args = (array) $_SERVER['argv'];

		// We're not interested in the first argument, which is the filename 
		array_shift($args);

		// If there are values before any keys, assign them to the main array 
		$last_key = 'main';
		foreach($args as $arg) {
			
			// Remove the - characters 
			$value = ltrim($arg, '-');

			// If it's still the same, it's a value, not an argument  
			if ($value == $arg) {

				// If we have a last key, we can set it to that, otherwise it's discarded 
				if ($last_key) { 

					// We may want to append 
					if (isset($options[$last_key]) && $options[$last_key] !== TRUE) {
						$options[$last_key] = $options[$last_key] . ' ' . $value;
					} else {
						$options[$last_key] = $value;
					}
				}

			// If it's different, it's an argument 
			} else {

				// We may see a value for this soon, but for now, set it to true 
				$options[$value] = TRUE;

				// See the last key in case there's a value coming  
				$last_key = $value; 

			}

		}

		return $options;

	}


	// http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-
	// cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
	protected function colorize($str, $color) {
		$colors = array(
			'red' => '0;31',
			'green' => '0;32',
			'gray' => '0;37',
		);

		return "\033[" . $colors[$color] . 'm' . $str . "\033[0m";
	}


	/**
	 * Check if this script is in the user's PATH variable 
	 * @return bool
	 **/
	public function script_directory_in_path() {

		// Find an array of the directories in the PATH 
		$paths = $this->path_directories();

		// Original script directory 
		$script_directory = $_SERVER['SCRIPT_FILENAME'];

		foreach ($paths as $path) {

			// If our directory is or starts with a path directory, then yes 
			if (strpos($script_directory, $path) === 0) {

				// We can return right away 
				return TRUE; 
			}
		}

		// If we're still here, it's not in the PATH 
		return FALSE; 
	}

	/**
	 * Attempt to add a directory to the user's path variable.
	 * @param $directory 	string 	
	 */
	public function add_directory_to_path($directory = false) {

		// Use the directory we're in if one wasn't provided 
		if (!$directory) {
			$directory = getcwd();
		}

		// Profile 
		$profile = $this->bash_profile();

		// Append our own export line to the end 
		$export = "\n\nexport PATH=\$PATH:" . $directory;

		file_put_contents($profile, $export, FILE_APPEND);

		// Temporary for this run
		//shell_exec("source " . $profile);

	}

	/**
	 * Find a path to the user's bash profile 
	 * @return mixed 	filepath or false 
	 */
	public function bash_profile() {
		$home = getenv("HOME");

		$paths = array(
			'.bash_profile',
			'.profile',
		);

		// Check if any of the posibilities exist 
		foreach ($paths as $path) {
			$path = $home . '/' . $path;

			if (file_exists($path)) {

				// Use the first one that exists 
				return $path;
			}
		}

		// We can't find a profile
		return FALSE;

	}

	/**
	 * Array of directories in the PATH variable  
	 * @return array 
	 */
	public function path_directories() {
		return explode(PATH_SEPARATOR, getenv('PATH'));
	}


}


