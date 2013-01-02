<?

/**
 * Project Model
 */
class Project {

	/**
	 * Configuration
	 */
	public static $base = "/fiftytwo/projects";

	/**
	 * Properties 
	 */
	public $name; 


	public function __toString() {
		return (string) $this->name;
	}


	/**
	 * Construct project object 
	 * @param string $name Folder name of the project relative to the base directory 
	 */
	public function __construct($name) {

		$this->name = $name;

	}

	/* List all projects in the base directory */
	public static function findAll() {
		$projects = array();

		// List projects 
		$dirs = dir(self::$base);
		while (($project = $dirs->read()) !== FALSE) {	
			$p = new Project($project);
		
			// Confirm we have a path before continuing 
			if ($p->path()) {
				$projects[] = $p;
			}

		}

		return $projects;
	}

	public function path() {

		if (!$this->_path) {

			$base = self::$base;
			$project = $this->name;

			// Needs to have either a trunk/app or app folder
			$this->_path = FALSE;

			if (file_exists("$base/$project/app")) { 
				$this->_path = "$base/$project/app";
			} else if (file_exists("$base/$project/trunk/app")) {
				$this->_path = "$base/$project/trunk/app";
			}

		}

		return $this->_path;
	}

	public function revision() {

		if (!$this->_revision) { 

			// Find the project path 
			$project_path = $this->path();

			if ($project_path) {

				// Find the revision
				$revision = '-';
				if (file_exists($this->path() . "/.svn/entries")) {
					$svn = File($this->path() . "/.svn/entries");
					$this->_revision = trim($svn[3]);
					unset($svn);
				}
			}
		}

		return $this->_revision;

	}

	public function remote_revision() {

		if (!$this->_remote_revision) {
			chdir($this->path());

			$log = shell_exec('svn log -r HEAD');
			preg_match('/r(\d+)/', $log, $matches);
			$this->_remote_revision = $matches[1];
		}

		return $this->_remote_revision;

	}


	/**
	 * Find a list of controllers in the app 
	 */
	public function controllers() {

		$controllers = array();

		// Figuring out the controllers path will have to be more clever
		if ($path_objects = opendir($this->path() . "/controllers")) {
		
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

	public function controller($name) {

		// Initialize with project, and path relative to the app 
		return new BuilderController($this, "/controllers/" . $name);

	}


	/**
	 * Find a list of models in the app 
	 */
	public function models() {

		$models = array();

		// Figuring out the controllers path will have to be more clever
		if ($path_objects = opendir($this->path() . "/models")) {
		
			// Loop through 
			while (($file = readdir($path_objects)) !== false) {

				if (strpos($file, '.') != 0 && $file != '_initialize.php' && $file != '_default.php') {

					$models[] = str_replace('.php', '', $file);
				}

			}
		}
	
		closedir($path_objects);
		
		return $models;

	}


}