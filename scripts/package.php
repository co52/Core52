#!/usr/bin/env php
<?php

if(PHP_SAPI != 'cli') {
	die("You should not access this file.");
}

error_reporting(E_ALL ^ E_NOTICE);


/**
 * Allows adding directories to ZipArchive objects
 *
 * @author jhill
 *
 */
class Zipper extends ZipArchive {
    
	public function addDir($path, $basedir = '/') {
		$ltrim = strlen($basedir);
	    $this->addEmptyDir(substr($path, $ltrim));
	    $nodes = glob($path . '/*');
	    foreach ($nodes as $node) {
	        if (is_dir($node)) {
	            $this->addDir($node, $basedir);
	        } else if (is_file($node)) {
	            $this->addFile($node, substr($node, $ltrim));
	        }
	    }
	}
    
} // class Zipper


/**
 * Packages or unpackages a Core52 application PHP zip archive
 *
 * @author jhill
 *
 */
class App2Phpz {
	
	# general settings
	public $include_database = TRUE;
	public $include_vhost = FALSE;
	public $mysqldump = 'mysqldump';
	public $mysql = 'mysql';
	public $help = FALSE;
	
	# compress() settings
	public $app_name;
	public $base_dir;
	public $auth_file;
	public $package_filename = NULL;
	
	# extract() settings
	public $extract_to = FALSE;
	public $overwrite;
	public $single_dbhost;
	public $dbhost;
	public $dbname;
	public $dbuser;
	public $dbpass;
	public $auth_dir = '/fiftytwo/auth';
	public $vhost_dir = '/etc/apache2/sites-available';
	
	
	# runtime vars
	protected $databases = array();
	protected $vhosts = array();
	protected $authfiles = array();
	protected $zip = FALSE;
	protected $compressed_package_filename;

	
	public function compress() {
		
		# Initialize the core
		chdir(dirname(dirname(__FILE__)));
		require_once 'initialize.php';
		
		# set runtime defaults
		$this->app_name  = APP_NAME;
		$this->base_dir  = PATH_BASE;
		$this->auth_file = Database::load_settings();
		$this->package_filename = $this->app_name;
		
		# set runtime options from the command line
		$this->_parse_commandline();
		
		# force extension to .phpz
		$this->package_filename = dirname($this->package_filename).'/'.pathinfo($this->package_filename, PATHINFO_FILENAME).'.phpz';
		
		# unlink current package file
		if(file_exists($this->package_filename)) {
			unlink($this->package_filename)
				or $this->_panic("Package already exists and is not writable: $this->package_filename");
		}
		
		# set (protected) script runtime options
		$this->databases = $this->_parse_databases();
		$this->vhosts    = $this->_parse_vhosts();
		$this->authfiles = $this->_parse_authfiles();
		
		# create the archive
		$tempnam = $this->_get_tempfile();
		$this->zip = new Zipper();
		$this->zip->open($tempnam, ZIPARCHIVE::CREATE)
			or $this->_panic("Could not open $tempnam for writing");
		
		# copy the base dir
		$this->_out("Adding $this->base_dir to archive");
		$this->zip->addDir($this->base_dir, $this->base_dir.'/');
		
		# copy the auth file
		$this->_out("Adding auth files to archive");
		foreach($this->authfiles as $file) {
			$this->_out(" + $file");
			$this->zip->addFile($file, 'auth/'.basename($file));
		}
		
		# copy the vhost file(s)
		$this->_out("Adding vhost files to archive");
		foreach($this->vhosts as $file) {
			$this->_out(" + $file");
			$this->zip->addFile($file, 'vhosts/'.basename($file));
		}
		
		# copy the database(s)
		$this->_out("Copying databases to archive");
		foreach($this->databases as $conn) {
			$this->_out(" + $conn->database.sql");
			$file = tempnam(sys_get_temp_dir(), $conn->database);
			$this->_mysqldump($conn, $file)
				or $this->_panic("Could not dump database $conn->database to: $file");
			$this->zip->addFile($file, $conn->database.'.sql');
		}
		
		$this->_out("Compressing");
		$this->zip->close();
		
		# compile the archive
		$this->_out("Compiling .phpz archive");
		$fp_dest = fopen($this->package_filename, 'w');
		fwrite($fp_dest, file_get_contents(__FILE__));
		$fp_zip = fopen($tempnam, 'r');
		while($buffer = fread($fp_zip, 10240)) {
	    	fwrite($fp_dest, $buffer);
		}
		fclose($fp_zip);
		fclose($fp_dest);
		unlink($tempnam);
			
		chmod($this->package_filename, 0755)
			or $this->_panic("Could not make $this->package_filename executable");
		$this->_out("Your application has been packaged as a self-extracting PHP Zip archive: $this->package_filename");
	}
	
	public function extract() {
		
		# set runtime options from the command line
		$this->_parse_commandline();
		
		# make sure we know where to extract to
		$this->_prompt_if_empty($this->extract_to, 'Where do you want to install this application to? Enter the basedir');
		if(!file_exists($this->extract_to)) {
			# need to create the directory
			if($this->_confirm("The basedir $this->extract_to does not exist, do you want to create it?")) {
				mkdir($this->extract_to, 0775, TRUE)
					or $this->_panic("Could not create $this->extract_to");
			} else {
				exit("Halted due to user input\n");
			}
		} elseif(file_exists($this->extract_to) && is_null($this->overwrite)) {
			# need to delete the directory
			if($this->_confirm("The basedir $this->extract_to already exists, do you want to remove it?")) {
				if($this->extract_to === '/') {
					$this->_panic("Nope, not gonna erase your hard disk. Try extracting do a different directory than root.");
				} else {
					$this->_rm($this->extract_to)
						or $this->_panic("Could not delete $this->extract_to");
					mkdir($this->_extract_to, 0775, TRUE);
				}
			} else {
				exit("Halted due to user input\n");
			}
		}
		
		# extract the zipfile
		$tempnam = $this->_get_tempfile();
		$temp = fopen($tempnam, 'wb');
		$self = fopen(__FILE__, 'rb');
		fseek($self, __COMPILER_HALT_OFFSET__ + strlen("#!/usr/bin/env php\n"));
		$this->_out("Unpacking zip file");
		while($buffer = fread($self, 10240)) {
			fwrite($temp, $buffer);
		}
		fclose($temp);
		fclose($self);
		$this->zip = new ZipArchive();
		$this->zip->open($tempnam)
			or $this->_panic("Failed to open zipfile $tempnam");
		$this->zip->extractTo($this->extract_to)
			or $this->_panic('Could not extract files to '.$this->extract_to);
		@unlink($tempnam);
			
		# import the database(s)
		if($this->include_database) {
			
			$dbdumps = glob($this->extract_to.'/*.sql');
			
			if(count($dbdumps) > 1 && !$this->_prompt_if_empty($this->single_dbhost, 'This application uses multiple databases, do they all use the same host?', 'boolean', FALSE, array('y' => 1, 'n' => 0))) {
				
				# different credentials for all databases
				foreach($dbdumps as $file) {
					
					$dbname_default = pathinfo($file, PATHINFO_FILENAME);
					
					$host = $this->_prompt_if_empty_default($this->dbhost, "Database host, enter to use default (localhost)", 'localhost');
					$user = $this->_prompt_if_empty_default($this->dbuser, "Database user, enter to use default (root)", 'root');
					$name = $this->_prompt_if_empty_default($this->dbname, "Database name, enter to use default ($dbname_default)", $dbname_default);
					$pass = $this->_prompt_if_empty($this->dbpass, "Database password");
				
					$this->_mysqlrestore($file, $host, $name, $user, $pass)
						or $this->_panic("Failed to import database dump ".basename($file));
				}
				
			} else {
				
				$file = array_pop($dbdumps);
				
				$dbname_default = pathinfo($file, PATHINFO_FILENAME);
				
				# same credentials for all databases
				$host = $this->_prompt_if_empty_default($this->dbhost, "Database host, enter to use default (localhost)", 'localhost');
				$user = $this->_prompt_if_empty_default($this->dbuser, "Database user, enter to use default (root)", 'root');
				$name = $this->_prompt_if_empty_default($this->dbname, "Database name, enter to use default ($dbname_default)", $dbname_default);
				$pass = $this->_prompt_if_empty($this->dbpass, "Database password");
				
				$this->_mysqlrestore($file, $host, $name, $user, $pass)
					or $this->_panic("Failed to import database dump ".basename($file));
				
			}
			
		}
		
		# move the auth file(s)
		if(file_exists($this->extract_to.'/auth/')) {
			$this->_prompt_if_empty_default($this->auth_dir, 'Auth file directory (enter to use default: /fiftytwo/auth)', '/fiftytwo/auth');
			if(!file_exists($this->auth_dir) && $this->_confirm("The auth dir $this->auth_dir does not exist, do you want to create it?")) {
				# try to create the directory
				mkdir($this->auth_dir, 0660, TRUE);
			}
			$this->_mv($this->extract_to.'/auth/*', $this->auth_dir.'/')
				or $this->_panic("Failed to move auth files from $this->extract_to/auth/ to $this->auth_dir/");
		}
		
		# move the vhost files
		if(file_exists($this->extract_to.'/vhosts/') && $this->include_vhost) {
			$this->_prompt_if_empty_default($this->vhost_dir, 'vhost file directory (enter to use default: /etc/apache2/sites-available)', '/etc/apache2/sites-available');
			if(!file_exists($this->vhost_dir) && $this->_confirm("The vhost dir $this->vhost_dir does not exist, do you want to create it?")) {
				# try to create the directory
				mkdir($this->vhost_dir, 0770, TRUE);
			}
			$this->_mv($this->extract_to.'/vhosts/*', $this->vhost_dir.'/')
				or $this->_panic("Failed to move vhost files from $this->extract_to/vhosts/ to $this->vhost_dir/");
		}
				
		# finished
		echo "Finished installing ".__FILE__."\n";
		echo "Please customize your auth file(s) and your vhost file(s) as needed and restart apache.\n";
	}
	
	public function exec() {
		
		$this->_parse_commandline();
		
		try {

			if($this->help) {
				
				if($this->_has_payload()) {
					$this->_usage_extract();
				} else {
					$this->_usage_compress();
				}
				
			} elseif($this->_has_payload()) {
				
				$this->_check_dependencies();
				$this->extract();
				
			} else {
				
				$this->_check_dependencies();
				$this->compress();
				
			}
			
			exit (0);
			
		} catch(Exception $e) {
			
			throw $e;
			$this->_panic($e->getMessage());
			
		}
		
	}
	
	protected function _check_dependencies() {
		if(!extension_loaded('zip')) {
		    $this->_panic('Missing Zip extension, install using `pecl install Zip`');
		} else {
			return TRUE;
		}
	}
	
	protected function _usage_extract() {
		echo <<<USAGE

Usage:
=========================================================================================
{$_SERVER['argv'][0]} [arglist]  Installs a Core52 application PHP archive.

Options:

  --auth-dir=dir           Directory to install auth file(s) to
  --dbhost=host            Database hostname (default = localhost)
  --dbuser=user            Database username (default = root)
  --dbpass=pass            Database password
  --extract-to=dir         (required) Base directory to install to
  --help                   Display usage information
  --include-database[=0]   Install database dumps in the archive (default = 1)
  --include-vhost=[0]      Install apache vhost files in the archive (default = 1)
  --mysql=cmd              Use a custom mysql command
  --overwrite[=0]          Overwrite files during install
  --single-dbhost[=0]      Whether or not to use a single database host for all databases
  --vhost-dir=dir          Directory to install vhost file(s) to



USAGE;
	}
	
	protected function _usage_compress() {
		echo <<<USAGE

Usage:
=========================================================================================
{$_SERVER['argv'][0]} [arglist]  Packages a Core52 app into a self-installing PHP archive.

Options:

  --app-name=appname              Application name (defaults to APP_NAME)
  --auth-file=file[,file...]      Include multiple auth files (if omitted, the primary
                                  auth file will be used, if it exists). Supports
                                  wildcards.
  --base-dir=dir                  Application base directory (defaults to PATH_BASE)
  --help                          Display usage information
  --include-core[=0]              Include the application core directory in the archive
  --include-vhost=file[,file...]  Include the apache vhost files (default = 0).
  --mysqldump=cmd                 Use a custom mysqldump command
  --package-filename=file         Full path and filename of the Phar archive to be created



USAGE;
	}
	
	protected function _has_payload() {
		$payload_size = filesize(__FILE__) - (__COMPILER_HALT_OFFSET__+strlen("#!/usr/bin/env php\n"));
		#echo "Payload: $payload_size bytes\n";
		return ($payload_size > 0);
	}
	
	protected function _prompt($msg, $datatype = 'string', $trim = TRUE, array $options = NULL) {
		if($options) $msg = sprintf('%s (%s)', $msg, implode('/', array_keys($options)));
		do {
			echo wordwrap("\n$msg: "); // avoid linebreaks in the middle of a word
			$input = fgets(STDIN);
			if($trim) $input = trim($input);
		} while($options && !isset($options[$input]));
		if($options) $input = $options[$input];
		settype($input, $datatype);
		return $input;
	}
	
	protected function _prompt_if_empty(&$var, $msg, $datatype = 'string', $trim = TRUE, array $options = NULL) {
		if(empty($var)) {
			$var = $this->_prompt($msg, $datatype, $trim, $options);
		}
		return $var;
	}
	
	protected function _prompt_if_empty_default($var, $msg, $default = '', $datatype = 'string', $trim = TRUE, array $options = NULL) {
		$input = $this->_prompt_if_empty($var, $msg, $datatype, $trim, $options);
		if(empty($input)) {
			return $default;
		} else {
			return $input;
		}
	}
	
	protected function _confirm($msg, array $options = array('y' => 1, 'n' => 0)) {
		return $this->_prompt($msg, 'boolean', TRUE, $options);
	}
	
	protected function _chmod($file, $mod) {
		$out = system(sprintf('chmod %s %s', $mod, $file), $retval);
		return ($retval == 0 && $out !== FALSE);
	}
	
	protected function _cp($from, $to) {
		$out = system(sprintf('cp %s %s', $from, $to), $retval);
		return ($retval == 0 && $out !== FALSE);
	}
	
	protected function _mv($from, $to) {
		$out = system(sprintf('mv %s %s', $from, $to), $retval);
		return ($retval == 0 && $out !== FALSE);
	}
	
	protected function _rm($dir, $cmd = 'rm -Rf') {
		$out = system(sprintf('%s %s', $cmd, $dir), $retval);
		return ($retval == 0 && $out !== FALSE);
	}
	
	protected function _mysqldump(DatabaseConnection $dbconn, $file) {
		$cmd = sprintf('%s --add-drop-database --routines -h %s -u %s -p%s %s > %s', $this->mysqldump, $dbconn->host, $dbconn->user, $dbconn->password, $dbconn->database, $file);
		$out = system($cmd, $retval);
		return ($retval == 0 && $out !== FALSE);
	}
	
	protected function _mysqlrestore($file, $host, $name, $user, $password) {
		# create the database
		$cmd = sprintf("%s -h %s -u %s -p%s -e 'CREATE DATABASE IF NOT EXISTS `%s`;'", $this->mysql, $host, $user, $password, $name);
		$out = system($cmd, $retval);
		if($retval == 0 && $out !== FALSE) {
			# import the dump
			$cmd = sprintf('%s -h %s -u %s -p%s %s < %s', $this->mysql, $host, $user, $password, $name, $file);
			$out = system($cmd, $retval);
			return ($retval == 0 && $out !== FALSE);
		} else {
			return FALSE;
		}
	}
	
	protected function _parse_commandline() {
		
		$args_allowed = $this->_get_commandline_options();
		
		# set each command line argument as a var
		foreach((array) $_SERVER['argv'] as $arg) {
			
			if(preg_match('/--[a-z0-9-_]+/i', $arg)) {
				
				#var_dump($arg);
				
				if(strpos($arg, '=') !== FALSE) {
					
					list($arg, $value) = explode('=', $arg);
					$value = trim($value, '"\''); // remove parameter value quotes, e.g. --restart-apache='apachectl restart'
					if($value === 'FALSE' || $value === '0') $value = FALSE;
					
				} else {
					
					$value = TRUE;
					
				}
				
				# convert `--include-core` to `include_core`
				$arg = str_replace('-', '_', ltrim($arg, '-'));
				
				#var_dump($arg, $value);
				
				if(in_array($arg, $args_allowed)) {
					$this->$arg = $value;
				}
				
			}
			
		}
		
	}
	
	protected function _parse_databases() {
		
		$databases = array();
		
		if($this->include_database === TRUE) {
			
			# default database
			return DatabaseConnection::connections();
			
		} elseif(strlen($this->include_database) > 0) {
			
			# specific list of databases
			foreach(explode(',', $this->include_database) as $db) {
				try {
					$databases[] = database(trim($db));
				} catch(Exception $e) {
					$this->_panic($e->getMessage());
				}
			}
			
		}
		
		return $databases;
	}
	
	protected function _parse_authfiles() {
		
		$this->_prompt_if_empty_default($this->auth_file, "Enter the full filepath to your project auth file (enter to use default: ".Database::load_settings().")", Database::load_settings());
		if(strpos($this->auth_file, ',')) {
			# expand vhost files list
			$files = explode(',', $this->auth_file);
			foreach($files as &$file) {
				$file = trim($file);
			}
		} elseif(strlen($this->auth_file) > 0) {
			$files = array($this->auth_file);
		} else {
			return array();
		}
		
		# expand vhost file wildcard patterns
		foreach($files as &$file) {
			if(preg_match('/[\*\?]/', $file) && glob($file) !== FALSE) {
				# expand wildcards
				$this->authfiles = $this->authfiles + glob($file);
				unset($file);
			}
		}
		
		return $files;
	}
	
	protected function _parse_vhosts() {
		
		if($this->include_vhost === FALSE) {
			return array();
		}
		elseif(strpos($this->include_vhost, ',')) {
			# expand vhost files list
			$files = explode(',', $this->include_vhost);
			foreach($files as &$file) {
				$file = trim($file);
			}
		} elseif(strlen($this->include_vhost) > 0) {
			$files = array($this->include_vhost);
		} else {
			return array();
		}
		
		# expand vhost file wildcard patterns
		foreach($files as &$file) {
			if(preg_match('/[\*\?]/', $file) && glob($file) !== FALSE) {
				# expand wildcards
				$files = $files + glob($file);
				unset($file);
			}
		}
		
		return $files;
	}
	
	protected function _get_commandline_options() {
		$func = create_function('$obj', 'return get_object_vars($obj);');
		return array_keys($func($this));
	}
	
	protected function _panic($msg, $code = 1) {
		fputs(STDERR, "\nError: $msg\n");
		exit($code);
	}
	
	protected function _out($msg, $show_memory_usage = FALSE) {
		fputs(STDOUT, "$msg\n");
		if($show_memory_usage) {
			self::show_memory_usage();
		}
	}
	
	public static function show_memory_usage() {
		fputs(STDOUT, sprintf(">>>> Current memory usage: %sk, peak: %sk\n", number_format(memory_get_usage(TRUE)/1024, 1), number_format(memory_get_peak_usage(TRUE)/1024, 1)));
	}
	
	protected function _get_tempfile() {
		return tempnam(sys_get_temp_dir(), pathinfo(__FILE__, PATHINFO_FILENAME));
	}
	
}


$obj = new App2Phpz();
$obj->exec();

__HALT_COMPILER();