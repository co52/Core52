<?php

class BuilderController {

	public $project;

	public function __toString() {
		return $this->plain_name();
	}

	public function plain_name() {
		return str_replace('Controller_', '', $this->class_name()) . ' Controller';
	}

	/**
	 * Construct controller object 
	 * @param string $name Folder name of the project relative to the base directory 
	 */
	public function __construct($project, $path) {

		$this->project = $project;
		$this->path = $path;

	}

	public function path() {
		return $this->project->path() . $this->path . '.php';
	}

	public function class_name() {
		$this->tokenize();

		return $this->class;
	}

	public function functions() {
		$this->tokenize();

		return $this->functions;
	}

	public function tokenize($force = FALSE) {

		// Only tokenize once 
		if (!$this->_code || $force) {

			$this->_code = file_get_contents($this->path());

			$tokens = token_get_all($this->_code);

			$class = false;
			$functions = array();
			$function = false;
			$whitespace = 0;
			foreach($tokens as $idx=>$token)
			{

				if (is_array($token)) {

					list($type, $text) = $token;

					if ($type == T_CLASS) {

						// The next item will be our class
						$class =& $string;

					} else if($type == T_FUNCTION) {
						$function_i ++;

						array_push($functions, array(
							'name' => &$string,
							'arguments' => &$variables,
							'comment' => $comments
						));

						// We clear docblocks 
						unset($comments);

					} else if($type == T_VARIABLE) {

						$variables[] = $text;

					} else if($type == T_STRING) {

						// Set the string, should be hooked up to something 
						$string = $text;

						// Break the reference 
						unset($string);
					
					} else if ($type == T_DOC_COMMENT) {

						// Find all 
						preg_match('/\/[\*\s]*(.*)/', $text, $description);
						$comments['description'] = $description[1]; 

						preg_match('/\@author\s+(.*)/', $text, $author);
						$comments['author'] = $author[1]; 

						preg_match_all('/\@param\s+(.*?)\s+(.*)\n/', $text, $params, PREG_SET_ORDER);
						$comments['params'] = $params; 

						$comments['raw'] = $text;

					}

					if ($type == T_WHITESPACE) {
						$whitespace++;
						
						// Double Whitespace
						if ($whitespace == 2) {

							// Disconnect
							unset($variables);

							// Reset
							$whitespace = 0;
						}

					} else {
						$whitespace = 0;
					}


				}
			}

			$this->class = $class;
			$this->functions = $functions;

		}

	}
	
}