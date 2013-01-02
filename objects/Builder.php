<?php

/**
 * Builder
 * 
 * Builds out basic scafolding for controllers, models, and view. Meant to be run via the build.php script.
 * 
 * @author Alex King
 */

class Builder extends CLI_Tool {

	/**
	 * Whether we can overwrite files without asking (-f)
	 * @var boolean
	 */
	protected $force = FALSE;

	/**
	 * The directory that build was run from 
	 * @var string
	 */
	protected $directory; 

	/**
	 * Run 
	 * @param  array $args arguments 
	 * @return void 
	 */
	public function run($args, $app, $directory) {
		$this->br();

		// Store the directory
		$this->directory = $directory;

		// Run some preliminary items
		$this->preflight();

		// If we have an app, let people know right away 
		if ($app) {

			$this->h1(strtoupper(APP_NAME) . ' APP', 'green');

			// We can do these things 
			$general_options = array(
				'Controller',
				'Model',
				'View',
				'Layout',
			);

		} else {

			// If we don't have an app, then we can only build 
			$general_options = array(
				'Project',
			);
		}

		// If we have arguments, then we should be able to skip the whole interactive bit
		$options = $this->_get_options();

		// Are we just doing help?
		if ($options['h'] || $options['help'] || $options['main'] == 'help') {
			$this->help();
			return; 
		}

		// So we can safely send an empty $specific to a function without overwriting the default 
		$specific = null; 

		// Do we have a main directive? 
		if ($options['main']) {

			$main = $options['main'];
			$main_parts = explode(" ", $main);

			// If there are two words, the second will be the category, and the first one specific  
			if (isset($main_parts[1]) && $main_parts[1]) {
				$general = $main_parts[1];
				$specific = $main_parts[0];
			} else {
				$general = $main_parts[0];
			}

			// Handle some special cases - if the general is "views", that's shorthand for all view
			if ($general == 'views') {
				$general = 'view';
				$specific = 'all';
			}

			// General is "what" we'll be doing
			// Specific will contain special arguments as to type - for views it can be index, 
			$what = $general;

			// Check if we have a force option set 
			if ($options['f']) {

				// Set the force property, this means we don't have to ask before overwriting files 
				$this->force = TRUE;
			}

		}

		// Do we still need to ask what we're doing? 
		if (!$what) {

			$what = $this->ask('What would you like to build? ' . $this->colorize('(ctrl-c to quit)', 'gray'), 
				false, true, $general_options);

		}


		switch(strtolower($what)) {
			
			case 'controller': 

				// Do we know what it extends and uses for a model 
				$extends = $options['extends'] ?: false;
				$model = $options['for'] ?: false;
				$in = $options['in'] ?: false;

				$this->controller($specific, $extends, $model, $in);
				break;

			case 'model':
				
				// Do we know what table it's for? 
				$table = $options['for'] ? $options['for'] : null;

				list($plain, $file) = $this->model($specific, $table);
				break;

			case 'view':

				// Do we know what it's for?
				$controller_class = null; 
				if ($for = $options['for']) {

					// Put it in the form of a controller_class 
					list($controller_class) = $this->_controller_class_and_filename($for);
				}

				$this->view($specific, $controller_class);


				break;

			case 'layout':

				$this->layout($specific);

			case 'debug':
				
				$this->say("Database: " . Database::c()->database);

				$this->possible_controller_classes();
				break;

			case 'project': 
				$in = $options['in'] ?: false;

				$this->project($specific, $in);
				break;

		}

		// Open option 
		if ($app = $options['o'] && $file) {
			if ($app != 1) {
				$suffix = ' -a "' . $app . '"';
			}

			shell_exec("open " . $file . $suffix);
		}

	}

	public function help() {

		$this->h2('Usage');


		$this->say('build [name] [type controller|model|view|views] [-for object] [-in folder]');
		$this->br();

		$this->indent('  [name] : name of object to build - prefixes are added (tasks becomes Tasks_Controller)');
		$this->indent('             for views - may be (index|form|view|all)');

		$this->indent('  [type] : type to build - may be (controller|model|view|views)');
		$this->indent('           views option builds all types of views (index, form, view)');

		$this->indent('-extends : specific type of object to extend, i.e. Model_Controller or My_Model');

		$this->indent('    -for : object or table to base on, for');
		$this->indent('           controllers - name of a model to base forms on');
		$this->indent('                 views - name of a controller to base form fields on');
		$this->indent('                models - name of a table to reference');

		$this->indent('     -in : directory to build in (for views only)');


		$this->h2('Examples');
		$this->say('Build a Form View for the "Clients" Controller in the "clients" folder');
		$this->indent('php build.php form view -for Clients -in clients');
		$this->br();

	}

	/**
	 * Goes through the process of building a controller 
	 * @return [type]
	 */
	public function controller($name = FALSE, $controller_type = FALSE, $model = FALSE, $views_directory = FALSE) {

		$this->h2('Build a Controller');

		// What should the controller be named? 
		if (!$name) { 
			$name = $this->ask('Name of Controller (should be plural)');
		}

		// Find the class and file from the name entered 
		list($class, $file, $plain) = $this->_controller_class_and_filename($name);

		// Find all possible controller types 
		$controller_types = (array) $this->possible_controller_classes();

		// What type of controller should this be?
		if (!$controller_type) {
			$controller_type = $this->ask('What type of controller should this be?', 1, true, $controller_types);
		}

		// Is this a model controller, or would we like to add form fields to a normal model? 
		if ($controller_type == 'Model_Controller' || $this->ask_bool('Would you like to add form fields?')) {
			
			// Do we already have a model? 
			if ($model) {
			
				list($model, $model_class) = $this->_confirm_model($model);

			} else { 

				// What model should we base it off? 
				// Get a real inflector in here at some point, but for now let's just guess without the s at the end 
				$possible_model = preg_replace('/s$/', '', $plain);

				// Ask for a model 
				list($model_class, $model) = $this->_ask_for_model($possible_model);
			}

			// Let's ask it for form fields 
			$form_fields = $model->form_fields();
			$form_fields_code = $this->_form_fields_to_code($form_fields);

		}

		// If this is a model controller, where are the views?
		$build_views = array(); 
		if ($controller_type == 'Model_Controller') {
			
			// Ask if we don't know where the views should be 
			if (!$views_directory) {  
				$views_directory = $this->ask('Where will the views be located? (relative to /app/views/)');
			}

			// Check if that directory exists 
			$views_path = PATH_APP . '/views/' . $views_directory;
			$expected_views = array('index', 'form', 'view');

			if (is_dir($views_path)) {
				
				// We already have that directory, are there any views missing there? 
				// Check if any files are missing 
				$missing_views = array();
				foreach ($expected_views as $expected_view) {
					
					if (!file_exists($views_path . '/' . $expected_view . '.php')) {
						
						// Missing
						$missing_views[] = $expected_view;
					}

				}

				// Yes
				if (count($missing_views)) {
					
					// Would you like to create them? 
					if ($this->ask_bool("You are missing " . implode("/", $missing_views) . " views there, would you like to build them?")) {
						
						// Queue for building 
						$build_views = $missing_views;

					}

				}

			} else {
				
				// Don't ask whether we want to create the views, let's just note it 
				$this->say("Creating views/$views_directory directory...");
				// There is no directory by that name, would you like to create the views? 
				//if ($this->ask_bool("There is no views/$views_directory, would you like to create it and add views?")) {
					
					// Queue for building 
					$build_views = $expected_views;

				//}

			}

		}


		// What template should we use?
		$template = $controller_type == 'Model_Controller' ? 'model_controller' : 'controller';

		// Create the file 
		$this->_build_from_template($template, array(
			'class_name' 	=> $class, 
			'controller_type' => $controller_type,
			'plain_name' 	=> $plain,
			'form_fields'	=> $form_fields_code,
			'model'			=> $model_class,
			'views_path' 	=> $views_directory,

		), '/controllers/' . $file);


		$this->say("Creating a $class at controllers/$file", 'green');
		$this->br();

		// Find our new controller 
		$cls = core_load_controller(str_replace('Controller_', '', $class));
		$controller = new $cls;
		
		// Let's run a few checks, just to make people aware of potential issues
		if ($controller && $controller->form) {

			if (in_array('upload', $controller->form->types)) {

				// File Upload Related
				if (!defined('PATH_UPLOAD')) {
					$this->say("WARNING: Your form will have upload fields, but it looks like you don't have a PATH_UPLOAD variable defined. Make sure to add it in _initialize.php (i.e. define('PATH_UPLOAD', PATH_APP . 'static/uploads/'); ), 'red'");
				} elseif (!file_exists(PATH_UPLOAD)) {
					$this->say("Warning: Your form will have upload fields, but it looks like your PATH_UPLOAD path (" . PATH_UPLOAD .") doesn't exist - make sure to create it.\033[0m", 'red');
				}
			}
		}

		// Do we still need to build some views?
		if (count($build_views)) {

			// Build them
			$this->views($build_views, $class, $views_directory);
		}

	}

	/**
	 * Request a model from the user 
	 * @return void
	 */
	protected function _ask_for_model($possible_model = false) {
		
		$model = $this->ask("What model should we use?", $possible_model);

		// Find the object, and possibly ask if we want to create it 
		list($m, $model_class) = $this->_confirm_model($model);

		return array($model_class, $m);
	}


	/**
	 * Find the object for a model, or offer to create it
	 * @param  strign $model name of model
	 * @return array Model object, class name, filename, plain name 
	 */
	protected function _confirm_model($model) {

		// Return false if user doesn't want to create the model 
		$m = false;

		// Check if that model is real 
		try {
			$m = new $model;

		} catch (Exception $e) {
			
			// The autoloader couldn't find that - give them the option to create it 
			if ($this->ask_bool("The model $model doesn't exist - would you like to create it?")) {
				
				$this->model($model);
				$m = new $model;

			}

		}

		// Find the class name and info 
		$info = $this->_model_class_and_filename($model);

		return array_merge(array($m), (array) $info);
	}

	/**
	 * Request a controller from the user 
	 * @param  string 	$default 	if nothing is entered, default to this 
	 * @return void
	 */
	protected function _ask_for_controller($default = false) {
		
		$controller = $this->ask("Which controller should we use? (plural)", $default);

		// Find the class name
		list($controller_class, $file_name, $plain, $name) = $this->_controller_class_and_filename($controller);

		// Check if that controller is real 
		try {

			// This function expects a controller name without Controller_ prefix 
			$cls = core_load_controller($name);
			$c = new $cls;

		} catch (Exception $e) {

			// The autoloader couldn't find that - give them the option to create it 
			if ($this->ask_bool("The controller $controller doesn't exist - would you like to create it?")) {
				
				$this->controller($controller);
				$cls = core_load_controller($name);
				$c = new $cls;
			}

		}

		return array($controller_class, $c);
	}

	/**
	 * Returns the class and filename from a controller name 
	 * @param  string $name name for controller 
	 * @return array 	
	 */
	protected function _controller_class_and_filename($name) {
		
		// Remove any _Controller prefix to start
		$plain = str_replace('Controller_', '', $name);

		// Uppercase first letters
		$name = ucwords($plain);

		// Spaces are underscores 
		$name = str_replace(' ', '_', $name);

		// Add Controller_ to the class name 
		$class_name = 'Controller_' . $name;

		// Add .php to filename and lowercase
		$file_name  = strtolower($name) . '.php';

		// Class Name 	Controller_Test_Things
		// File name 	test_things.php
		// Plain 		Test Things
		// Name 		Test_Things
		return array($class_name, $file_name, $plain, $name);
	}

	/**
	 * Returns possible controller class types 
	 * @return array
	 */
	protected function possible_controller_classes() {
		
		$types = array('Controller');

		// Look for possible controller classes 
		$paths = array(PATH_OBJECTS, PATH_CORE_OBJECTS);
		foreach ($paths as $path) { 

			if (is_dir($path)) {
				
				// Try to open it
				if ($path_objects = opendir($path)) {
				
					// Loop through 
					while (($file = readdir($path_objects)) !== false) {
						
						// If it has controller
						if (strpos($file, '_Controller') !== FALSE) {

							$types[] = str_replace('.php', '', $file);
						}
					}
				}
			
				closedir($path_objects);
			}

		}

		return $types;

	}

	/**
	 * Convert a form fields array to a code version
	 * Uses _array_to_code, but removes extra properties in the form fields
	 * 
	 * @param  array $fields fields array 
	 * @return string
	 */
	protected function _form_fields_to_code($fields) {
		
		// Prepare the array 
		// Each field
		foreach ($fields as &$field) {
			
			// If the type is text, no need to specify 
			if ($field['type'] == 'text') {
				unset($field['type']);
			}

			// Remove any blank values or empty arrays (unless it's rules, we always need that)
			foreach ($field as $key => $item) {
				if ($item == '' && $key != 'rules' || (is_array($item) && count($item) == 0)) {
					unset($field[$key]);
				}
			}

		}

		// Convert to string of code
		return $this->_array_to_code($fields);
	}

	/**
	 * Converts array to PHP code, and then makes some indentation and style changes 
	 * @param  array $array array to convert to PHP
	 * @return string
	 */
	protected function _array_to_code($array) {

		// Start out by making valid code with var_export 
		$code = var_export($array, true);

		// Beautify 
		// array( instead of array (
		$code = str_replace('array (', 'array(', $code);

		// 'name' => array( on same line 
		$code = preg_replace('/\n(\s+)array/', 'array', $code);

		// Spaces to tabs 
		$code = str_replace('  ', "\t", $code);

		// Indent once more 
		$code = str_replace("\n", "\n\t", $code);

		return $code;
	}

	public function model($name = FALSE, $table = FALSE) {

		$this->h2('Build a Model');

		// Find the name 
		if (!$name) {
			$name = $this->ask('Model Name (should be singular)');
		}

		// Find the class name, file name, and plain names 
		list($class_name, $file_name, $plain) = $this->_model_class_and_filename($name);

		// Table 
		choose_table: {	
			
			$default_table = inflect_word(strtolower($name), false);
			if (!$table) {
				$table = $this->ask("Table", $default_table);
			}

			// Confirm that this table exists 
			if (database()->execute('SHOW TABLES LIKE ' . database()->escape($table))->null_set() && $table != 'none') {
				$this->say("The table $table doesn't exist.");

				// Reset choice to avoid infinate loops 
				$table = false; 

				goto choose_table;
			}
			
		}

		// Let's create the model, 
		$file = $this->_build_from_template('model', array(
			'class_name' => $class_name, 
			'table'		 => $table,
		), '/models/' . $file_name);

		if ($file) { 
			$this->say("Creating $class_name model in /models/$file_name", "green");
		}

		return array($plain, $file); 
	}

	/**
	 * Returns the class and filename from a model name 
	 * @param  string $name name for controller 
	 * @return array 	
	 */
	protected function _model_class_and_filename($name) {
		
		// Uppercase first letters
		$plain = ucwords($name);

		// Spaces are underscores 
		$class_name = str_replace(' ', '_', $plain);

		// Add .php to filename and lowercase
		$file_name  = $class_name . '.php';

		return array($class_name, $file_name, $plain);
	}



	/**
	 * Builds several views 
	 * @param  array $views view types (index, form, or view) 
	 * @param  string 	$controller_class 	model to base it on 
	 * @param  boolean 	$folder 		what subfolder of views to place them under 
	 */
	public function views($types, $controller_class, $folder) {

		foreach ($types as $type) {
			$this->view($type, $controller_class, $folder);

		}

	}

	/**
	 * Build a view 
	 * @param  string 	$type   			what type of view (index, form, or view)
	 * @param  string 	$controller_class 	controller to base it on, uses the default $_fields 
	 * @param  boolean 	$folder 			what subfolder of views to place them under 
	 * @return string 	filename of view that was built 
	 * @author Alex King
	 */
	public function view($type = FALSE, $controller_class = false, $folder = false) {

		if (!$type) {
			
			$type = $this->ask('What type of view would you like to build?', false, true, array(
				'all',
				'index',
				'form',
				'view',
			));
		}

		$file_name = $type . '.php';

		// Setup the controller
		$controller = $this->_confirm_controller($controller_class);

		// Ask if we want a subfolder 
		if (!$folder) {

			// Do we already have a subfolder to work in? 
			if ($folder = $controller->tpl_dir()) {
				$this->say("Assuming \$_tpl_dir subdirectory, /$folder");
			} else {
				$folder = $this->ask('Would you like them in a subfolder of /views? (directory, or leave blank for none)') ?: FALSE;
			}
		}

		// If we do 
		if ($folder) {

			// Create it if it doesn't exist 
			$folder_path = '/views/' . $folder;
			if (!is_dir(PATH_APP . $folder_path)) {
				mkdir(PATH_APP . $folder_path);
			}

		} else {
			$folder_path = '/views';
		}

		// If we're building all views, then we need to exit and call ->views(), which will call us back later.
		if ($type == 'all') {
			$this->views(array('index', 'form', 'view'), $controller_class, $folder);
			return; 
		}
		// Prepare singular and plural strings 
		// Controllers are already plural
		$sans_controller = str_replace('Controller_', '', $controller_class); 
		$plural = str_replace('_', ' ', $sans_controller); 
		$singular = preg_replace('/s$/', '', $plural);
		$url = '/' . strtolower($sans_controller);

		// The controller should have a form for us 
		$form = $controller->form;

		// Create the view 
		$this->say("Creating $file_name view in /views/$folder", "green");

		$this->_build_from_template($type . '_view', array(
			'model' 		=> $model,
			'singular' 		=> $singular,
			'plural' 		=> $plural,
			'url'			=> $url, 
			'form' 			=> $form,
			'field_names'	=> array_keys($form->rules),
		), $folder_path . '/' . $file_name);

		return $folder_path . '/' . $file_name;

	}


	public function layout($type, $folder = FALSE) {

		$this->h2("Build a Layout");
		$this->say("Please select the type of head and foot files you'd like to generate.");
		
		if (!$type) {
			$type = $this->ask('What type of view would you like to build?', false, true, array(
				'Bootstrap',
			));
		}

		if ($type == 'Bootstrap') {

			// Check if the bootstrap files already exist 
			if (!is_dir(PATH_APP . 'static/bootstrap')) {

				// Offer to download the css/img/js 
				if ($this->ask_bool('Would you like to download the requires css/js/img files from Twitter to the /static/bootstrap directory?')) {

					// Check if we have a static directory 
					if (!is_dir(PATH_APP . 'static')) {

						// Create it 
						mkdir(PATH_APP . 'static');
					}

					$zip = file_get_contents('http://twitter.github.com/bootstrap/assets/bootstrap.zip');
					file_put_contents(PATH_APP . 'static/bootstrap.zip', $zip); 
					shell_exec('unzip ' . PATH_APP . 'static/bootstrap.zip -d ' . PATH_APP . 'static');
				}
			}

		}

		// Ask where we should put it 
		if (!$folder) {
			$folder = $this->ask('Where should the head.php and foot.php files go?', '_inc');
		}

		// Create the folder if it doesn't exist 
		$folder_path = '/views/' . $folder;
		if (!is_dir(PATH_APP . $folder_path)) {
			mkdir(PATH_APP . $folder_path);
		}
	
		// Ask for a project title?
		$title = $this->ask("What should the title be?", 'Project');

		$controllers = $this->list_controllers();

		// Header 
		$this->_build_from_template('layouts/' . strtolower($type) .'/head', array(
			'title' 		=> $title,
			'controllers'	=> $controllers,
		), $folder_path . '/head.php');

		// Footer
		$this->_build_from_template('layouts/' . strtolower($type) .'/foot', array(), $folder_path . '/foot.php');


	}


	/**
	 * Find a list of controllers in the app 
	 */
	protected function list_controllers() {

		$controllers = array();

		// Look for possible controller classes 

		// Try to open it
		if ($path_objects = opendir(PATH_CONTROLLERS)) {
		
			// Loop through 
			while (($file = readdir($path_objects)) !== false) {
				
				// Exceptions
				if (strpos($file, '.') != 0 && $file != '_initialize.php' && $file != '_default.php') {

					// Looks good, add it 
					$controllers[] = str_replace('.php', '', $file);
				}
			}
		}
	
		closedir($path_objects);
		
		return $controllers;

	}

	/**
	 * Find a controller from a name, or asks for one if necessary 
	 * @param  strings $controller_class name of class
	 * @return controller                 
	 */
	protected function _confirm_controller($controller_class = FALSE) {
		
		if (!$controller_class) {
			list($controller_class, $controller) = $this->_ask_for_controller();
		} else {

			$cls = core_load_controller(str_replace('Controller_', '', $controller_class));
			$controller = new $cls;
		}

		return $controller;

	}


	/**
	 * Uses the view system to build and save a PHP template, based on a template in /objects/Builder_Templates
	 * @param  string $template  template to use
	 * @param  array  $data      data to pass to the template
	 * @param  string $filepath  where to place the file 
	 * @return mixed  string to full path to where we've saved the file or false
	 */
	protected function _build_from_template($template, $data, $filepath) {
		
		// Figure out the absolute path of the template 
		$template = dirname(__FILE__) . '/Builder_Templates/' . $template;

		// Load the template  
		$view = new FastViewObject;
		$view->load($template);

		// Add data 
		$view->data($data);

		// Process 
		$php = $view->publish(TRUE);

		// Decode \x3f to ?
		$php = str_replace('\x3f', "?", $php);
		$php = str_replace('PHP', "?", $php);


		// Find the fill path 
		$path = PATH_APP . $filepath;

		// If force is false, then we need to check before overwriting 
		if (!$this->force) {
			if (file_exists($path)) {
				$overwrite = $this->ask_bool("The file $filepath already exists, are you sure you want to overwrite it?");

				if (!$overwrite) { 
					$this->say("Not writing $filepath");
					return false; 
				}

			}
		}

		// Save
		file_put_contents($path, $php);

		return PATH_APP . $filepath;

	}

	/**
	 * Create a new project 
	 */
	public function project($name = false, $in = false) {

		$this->h2("Build Project");

		// Fast pass - if the name is set, then just assume everything. 
			
		// If they didn't specify a name, let's just make sure they want the project name to be whatever the folder is 
		if (!$name) {

			$name = $this->ask("What should the project name be? (i.e. appname)");

			if (!$in) {
				$in = $this->ask("Where should we put the project? (use . for this directory)");
			}
		}

		// If the name was specified, and -in was not, we're going to assume that we should build it at the name
		if (!$in) {
			$in = $name;
		}

		// Figure out the full path for in
		if ($in == '.') {

			// . is the current directory - dir/. may work?
			$in_directory = $this->directory;

		} else {

			$in_directory = $this->directory .'/'. $in;

		}

		// Check if the directory exists already
		if (!file_exists($in_directory)) {

			// Make that directory!
			mkdir($in_directory);
			$this->say("Creating $in directory");

		}

		// Is there an svn folder?
		$svn = file_exists("$in_directory/.svn");

		if ($svn) {

			// We'll want to use externals!

		} else {

			$this->say("Checking out core in $in/core... ", false, false);

			// We'll use an svn checkout!
			$svn_co = "svn co -q https://svn.company52.com/core52/trunk/ $in_directory/core";
			passthru($svn_co);

		}

		// Make sure that worked
		if (file_exists("$in_directory/core")) {
			$this->say("[done]", "green");
		} else {
			$this->say("[error] couldn't check out core", "red");
			exit(0);
		}

		$this->say("Copying app directory... ", false, false);

		// And now let's copy the app directory out 
		shell_exec("cp -R $in_directory/core/app $in_directory/app");

		// Make sure that worked
		if (file_exists("$in_directory/app")) {
			$this->say("[done]", "green");
		} else {
			$this->say("[error] couldn't copy app directory", "red");
			exit(0);
		}

		// The config file may change at some point, so instead of using a template, we'll do a regex replace.
		$config = file_get_contents("$in_directory/app/config.php");
		$config = str_replace('your-app-name', $name, $config);
		file_put_contents("$in_directory/app/config.php", $config);

		// It worked
		$this->say("$name project created!", "green");

		// build ? project -in ?
		// build fun project (-in fun)
		// build fun project -in trunk
		// build trunk project
		

		// Example #1
		// ==========
		// svn co http://repo
		// mkdir trunk
		// cd trunk
		// build fun project
		// 
		// It looks like you're inside an SVN repository, but the current directory (trunk) hasn't been added yet. Would you like to svn add it so you can checkout the core using svn externals? (y/n)
		// 
		// Example #2
		// ==========
		// 
		// mkdir funproj
		// cd funproj
		// build project
		// 
		// Build Project 
		// In /projects/funproj
		// What should the project name be? (default funproj)
		// 
		// Example #3
		// cd projects
		// build fun project
		// 
		// Build Funproj Project
		// Assume name is fun prob
		// 
		// Example #4 
		// svn add trunk
		// cd trunk
		// build project
		// 
		// Build trunk project? Or Build ../trunk project?
		// 
		// build project 		Building project in /fiftytwo/projects/ directory
		// build fun project 	Building project in /fiftytwo/projects/fun directory 
		// build fun project 	

		// Keep it simple. If there's no project name specified, don't ask for one, just confirm where we want to put it.

	}

	/**
	 * Alert people they can add us to their path variable 
	 */
	public function preflight() {

		// Check if we're in the path 
		if (!$this->script_directory_in_path()) {

			$this->say("Looks like you don't have Builder in your PATH variable", 'red');

			// Is there a bash profile we could add it to?
			if ($bash_profile = $this->bash_profile()) {
				if ($this->ask_bool("Do you want to add this directory to your $bash_profile file?")) {
					
					$this->line('Adding to PATH variable...');
					$this->add_directory_to_path();

					$this->br();
					$this->say("Great, it worked! Restart your console and you can then run ", 'green');
					$this->indent('build.php', 1, 'green');
					$this->say("from any app, core, or project directory", 'green');
					$this->br();

				}

			}

		}
		
	}


}


