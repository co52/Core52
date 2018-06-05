<?php

/**
 *
 * Logic for handling bare-bones SQL migrations.
 *
 * This depends on the database having a table like this with a default value:
 *
 *     CREATE TABLE IF NOT EXISTS schema_version (last_migration INT);
 *     INSERT INTO schema_version (last_migration) VALUES (0);
 *
 * The table keeps track of which migrations have been applied to the schema.
 *
 * At runtime, you can specify the database connection to use. If no connection
 * is specified, the default connection will be used.
 *
 *      // use the database connection identified by the 'custom_connection' handle
 *      $migrator = new Database_Migrator(database('custom_connection'));
 *      $migrator->exec();
 *
 * @author Paul M. Jones <pmjones88@gmail.com>
 * @author Jonathon Hill <jhill9693@gmail.com>
 *
 */
class Database_Migrator {
    
	/**
	 *
	 * Schema version table name
	 *
	 * @var string
	 *
	 */
	protected $schema_version_table_name = 'schema_version';
	
	/**
	 *
	 * SQL queries affecting the schema_version table
	 *
	 * @var array
	 *
	 */
	protected $schema_version_queries = array(
		'create' => 'CREATE TABLE IF NOT EXISTS {{schema_version}} (last_migration INT); INSERT INTO {{schema_version}} (last_migration) VALUES (0);',
		'select' => 'SELECT last_migration FROM {{schema_version}}',
		'update' => 'UPDATE {{schema_version}} SET last_migration = :key',
	);
	
	/**
	 *
	 * PDO-compatible DSN of the database to connect to
	 *
	 * @var string
	 *
	 */
	protected $dsn;
	
	/**
	 *
	 * Username of the database to connect to
	 *
	 * @var string
	 *
	 */
	protected $username;
	
	/**
	 *
	 * Password of the database to connect to
	 *
	 * @var string
	 *
	 */
	protected $password;
	
    /**
     *
     * PDO instance
     *
     * @var PDO
     *
     */
    protected $_pdo;
    
    /**
     *
     * The top-level project directory.
     *
     * @var string
     *
     */
    protected $_base_dir;
    
    /**
     *
     * The migrations directory.
     *
     * @var string
     *
     */
    protected $_migrations_dir;
    
    /**
     *
     * List of all available migration files.
     *
     * @var array
     *
     */
    protected $_list = array();
    
    /**
     *
     * The last migration number applied to the schema.
     *
     * @var int
     *
     */
    protected $_last_migration;
    
    /**
     *
     * Initialize the database connection information
     *
     * @param array $conn Database configuration array. Must contain the following keys: user, password, dsn. If dsn is absent, must contain some or all of the following additional keys: type, host, port, database
     * @return NULL
     *
     */
    public function __construct(array $conn)
    {
    	$this->username = $conn['user'];
    	$this->password = $conn['password'];
    	$this->dsn = isset($conn['dsn'])? $conn['dsn'] : $this->_makeDSN($conn);
    	
    	// connect to the db
        $this->_connectPdo();
        
    	// find the list of avaiable migrations
        $this->_setBaseDir();
        $this->_setMigrationsDir();
    }
    
    /**
     *
     * Main execution method.
     *
     * @return void
     *
     */
    public function migrate($rev = FALSE)
    {
    	if((!is_numeric($rev) || $rev < 1) && FALSE !== $rev) {
    		$this->_outln("Revision not specified, zero, or non-numeric");
    		return FALSE;
    	}
        
    	$this->_outln("Base directory is {$this->_base_dir}.");
        $this->_outln("Migrations directory is {$this->_migrations_dir}.");
    	
        if ($this->loadLastMigration() < 0) {
            $this->_outln("No migrations have been applied.");
        } else {
            $this->_outln("Last migration applied was '{$this->_last_migration}'.");
        }
	        
        // apply the migrations
        if(!$this->_applyMigrations($rev)) return FALSE;
        
        $this->_outln("Done.");
        return TRUE;
    }
    
    /**
     *
     * Echoes text with an end-of-line.
     *
     * @return void
     *
     */
    protected function _outln($text)
    {
        echo $text . PHP_EOL;
    }
    
    /**
     *
     * Figure out where the root project directory is.
     *
     * @return void
     *
     * @see $_base_dir
     *
     */
    protected function _setBaseDir()
    {
        $this->_base_dir = str_replace('\\', '/', dirname(dirname(dirname(__FILE__))));
    }
    
    /**
     *
     * Figure out where the "migrations" directory is.
     *
     * @return void
     *
     * @see $_migrations_dir
     *
     */
    protected function _setMigrationsDir()
    {
        $dir = "{$this->_base_dir}/app/updates";
        if (! is_dir($dir)) {
            throw new Exception("Cannot find migrations directory at '$dir'.");
        }
        
        $this->_migrations_dir = $dir;
    }
    
    /**
     *
     * Look in the "migrations" directory and load the list of names.
     *
     * @return void
     *
     * @see $_list
     *
     */
    public function loadMigrationList($migration_filename = 'migration.sql', $rev = FALSE)
    {
    	$this->loadLastMigration();
    	
    	$list = glob("{$this->_migrations_dir}/*");
        $list2 = array();
        
        // retain the migration file names
        foreach ($list as &$file) {
            $val = basename($file);
            if(!is_numeric($val) || !file_exists("$file/$migration_filename")) {
            	unset($file);
            	continue;
            }
            $key = (int) $val;
            $list2[$key] = "$val/$migration_filename";
        }
        
        // sort based on integer key, so we don't get weird orders like
        // "1, 10, 11, 12, ... 2, 20, 21, 22" that a filesystem might return.
        ksort($list2);
        
        if($rev === FALSE) {
    		
        	// forward update to the latest available revision
	        foreach ($list2 as $key => $val) {
	            if($key > $this->_last_migration) {
	                $this->_list[$key] = $val;
	            }
	        }
	        
    	} elseif($rev > $this->_last_migration) {
    		
    		// forward update to a specific later revision
    		foreach ($this->loadMigrationList('migration.sql') as $key => $val) {
	            if($key > $this->_last_migration && $key <= $rev) {
	               $this->_list[$key] = $val;
	            }
	        }
	        
    	} elseif($rev < $this->_last_migration) {
    		
    		// reverse update to a specific older revision
    		krsort($list); // apply undo files in reverse order
    		reset($list);  // set the internal array pointer so that we can get the previous database revision when rolling back a rev
    		foreach ($list as $key => $val) {
    		    if($key > $rev && $key <= $this->_last_migration) {
    		    	$this->_list[$key] = $val;
    		    }
    		}
    		
    	} else {
    		
    		foreach ($list2 as $key => $val) {
	            $this->_list[$key] = $val;
	        }
    		
    	}
        
        return $this->_list;
    }
    
    /**
     *
     * Build a DSN string for a database from an array containing the following keys: type, host, [port,] database (schema name)
     *
     * @param array $conn
     * @return string
     *
     */
    protected function _makeDSN(array $conn) {
    
    	// elements for the DSN
        $dsn_elem = array();
        
        // hostname
        $host = $conn['host'];
        if ($host) {
            $dsn_elem[] = "host=$host";
        }
        
        // port
        $port = $conn['port'];
        if ($port) {
            $dsn_elem[] = "port=$port";
        }
        
        // database name
        $dbname = $conn['database'];
        if ($dbname) {
            $dsn_elem[] = "dbname=$dbname";
        }
        
        // database type
        $type = isset($conn['type']) ? $conn['type'] : 'mysql';
        
        // build the DSN
        return $type . ':' . implode(';', $dsn_elem);
        
    }
    
    /**
     *
     * Create a PDO object for `$this->_pdo` and connect to the database with
     * it.
     *
     * @return void
     *
     * @see $_pdo
     *
     */
    protected function _connectPdo()
    {
        if ($this->_pdo) {
            throw new Exception('ERR_PDO_ALREADY_CONNECTED');
        }
        
        // connect to the database
        $this->_pdo = new PDO($this->dsn, $this->username, $this->password);
        
        // always emulate prepared statements
        $this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        
        // always use exceptions
        $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // use buffered queries
        //$this->_pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        
        // done
        $this->_outln("Connected to the database.");
    }
    
    /**
     *
     * Get a required SQL query, with the correct table name
     *
     * @param string $key
     * @return string
     * @throws InvalidArgumentException
     *
     */
    protected function _getSQL($key) {
    	if(isset($this->schema_version_queries[$key])) {
	    	return str_replace('{{schema_version}}', $this->schema_version_table_name, $this->schema_version_queries[$key]);
    	} else {
    		throw new InvalidArgumentException("Invalid SQL query key specified: $key");
    	}
    }
    
    /**
     *
     * Find the schema version of the database.
     *
     * @return void
     *
     * @see $_last_migration
     *
     */
    public function loadLastMigration($force = FALSE, $recursion = FALSE)
    {
    	if($this->_last_migration === NULL || $force) {
	    	
    		try {
	        	
	    		$cmd = $this->_getSQL('select');
		        $stmt = $this->_pdo->prepare($cmd);
		        $stmt->execute();
		        $this->_last_migration = $stmt->fetchColumn(0);
		        if ($this->_last_migration === false) {
		            $this->_last_migration = -1;
		        } else {
		        	$this->_last_migration = (int) $this->_last_migration;
		        }
		        
	        } catch(PDOException $e) {
	        	
	        	if(!$recursion) {
		        	
		        	// assuming this was a 'table not found error', so try to create it
		        	$cmd = $this->_getSQL('create');
		        	$stmt = $this->_pdo->prepare($cmd);
		        	$stmt->execute();
		        	unset($stmt);
		        	
		        	// retry
		        	return $this->loadLastMigration(TRUE, TRUE);
		        	
	        	} else {
	        		
	        		throw $e;
	        		
	        	}
	        	
	        }
	        
	       
    	}
        return $this->_last_migration;
    }
    
    /**
     *
     * Apply each of the migrations in turn.
     *
     * @return void
     *
     */
    protected function _applyMigrations($rev)
    {
    	if($rev === FALSE) {
    		
    		$this->_outln("Updating to the latest revision");
    		
	    	$list = $this->loadMigrationList('migration.sql', $rev);
    		$k = count($list);
	        if (! $k) {
	            $this->_outln("Found no migration files, exiting.");
	            exit(0);
	        } else {
	            $this->_outln("Applying $k migration files.");
	        }
	        
    		// forward update to the latest available revision
	        foreach ($list as $key => $val) {
	            if(!$this->_applyMigration($key)) return FALSE;
	        }
	        
    	} elseif($rev > $this->_last_migration) {
    		
    		$this->_outln("Updating to revision $rev");
    		
    		$list = $this->loadMigrationList('migration.sql', $rev);
	        $k = count($list);
	        if (! $k) {
	            $this->_outln("Found no migration files, exiting.");
	            exit(0);
	        } else {
	            $this->_outln("Applying $k migration files.");
	        }
	        
    		// forward update to a specific later revision
    		foreach ($list as $key => $val) {
	            if(!$this->_applyMigration($key)) return FALSE;
	        }
	        
    	} elseif($rev < $this->_last_migration) {
    		
    		$this->_outln("Rolling back to revision $rev");
    		
    		// reverse update to a specific older revision
    		$list = $this->loadMigrationList('undo.sql', $rev);
    		
    		$k = count($list);
	        if (! $k) {
	            $this->_outln("Found no undo files, exiting.");
	            exit(0);
	        } else {
	            $this->_outln("Rolling back $k migration files.");
	        }
	        
	        krsort($list); // apply undo files in reverse order
    		reset($list);  // set the internal array pointer so that we can get the previous database revision when rolling back a rev
    		foreach ($list as $key => $val) {
    			
            	if(!$this->_applyMigration($key, FALSE)) return FALSE;
        		
            	// get the previous database revision number
            	// (the number we're rolling back to)
            	$undo_rev = key($list);
            	
            	if(NULL !== $undo_rev) {
            		$this->_updateLastMigration($undo_rev);
            	} else {
            		// if we just ran the last available undo file, then
            		// there is no previous migration number to roll back to,
            		// so just use the revision number at the end
            		unset($undo_rev);
            	}
	            
	            next($list);
	        }
	        
	        // update the database revision number
	        if(!isset($undo_rev)) {
	        	$this->_updateLastMigration($rev);
	        }
    		
    	} else {
    		
    		$this->_outln("Already at revision $rev, nothing to do");
    		
    		// revision specified == current revision
    		// do nothing
    		
    	}
    	
    	return TRUE;
    }
    
    /**
     *
     * Apply a single migration.
     *
     * @param int $key The migration key to apply.
     *
     * @return void
     *
     */
    protected function _applyMigration($key, $update_last_migration = TRUE)
    {
    	$file = $this->_list[$key];
    	$this->_outln("Applying migration '$file'.");
        
        // read in the migration SQL
        $queries = $this->getMigrationQueries("{$this->_migrations_dir}/$file", $line_numbers);
        
        // try the migration inside a transaction
        try {
        	$this->_pdo->beginTransaction();
        	foreach($queries as $i => $query) {
        		$line_number = $line_numbers[$i];
        		$this->_pdo->exec($query);
        	}
            $this->_pdo->commit();
        } catch (Exception $e) {
            $this->_pdo->rollBack();
            $this->_outln('--------------------------------------------------------');
            $this->_outln("Error executing query #".($i+1)." on line $line_number:\n");
            $this->_outln($query);
            $this->_outln('--------------------------------------------------------');
            $einfo = $this->_pdo->errorInfo();
            if(in_array($einfo[1], array(1005, 1025))) {
            	$status = $this->_pdo->query('SHOW INNODB STATUS')->fetch();
            	$start = strpos($status['Status'], "------------------------\nLATEST FOREIGN KEY ERROR\n------------------------");
            	$end = strpos($status['Status'], "------------\nTRANSACTIONS\n------------");
            	$status = substr($status['Status'], $start, $end - $start);
            }
            $this->_outln($e->getMessage());
            $this->_outln("\n$status");
            $this->_outln("\n\nRolled back migration '$file'.");
            return FALSE;
        }
        
        // update the database revision number
        if ($update_last_migration) $this->_updateLastMigration($key);
        
        return TRUE;
    }
    
    /**
     *
     * Split a file full of SQL queries into an array of individual queries.
     *
     * @param string $file The filename containing queries to split.
     *
     * @return array
     *
     */
    public function getMigrationQueries($file, &$line_numbers)
    {
    	$queries = array();
    	$query = '';
		$delimiter = ';';
		
		// loop through each line of the file
		$fh = fopen($file, 'r');
		$line_number = 0;
		$line_numbers = array();
		while($line = fgets($fh)) {
			
			$line_number++;
			
			// detect DELIMITER statements
			if(preg_match('/DELIMITER .+$/i', trim($line), $matches)) {
				$delimiter = array_pop(explode(' ', trim($line)));
				continue;
			}
			
			$query .= rtrim($line, "\r\n")."\n";
			
			if(preg_match("/$delimiter$/", rtrim($line))) {
				$queries[] = rtrim(trim($query), $delimiter);
				$line_numbers[] = $line_number;
				$query = '';
			}
		}
		
		return $queries;
    }
    
    /**
     *
     * Updates the schema_version table with the last migration applied.
     *
     * @param int $key The last migration applied.
     *
     * @return void
     *
     */
    protected function _updateLastMigration($key)
    {
        // retain internally
        $this->_last_migration = $key;
        
        // update the database
        $cmd = $this->_getSQL('update');
        $stmt = $this->_pdo->prepare($cmd);
        $stmt->bindValue('key', $key);
        $stmt->execute();
        unset($stmt);
    }

}
