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
 * @author Paul M. Jones <pmjones@company52.com>
 *
 */
class Update_Post_Processor extends Database_Migrator {
	
	protected $_to_rev = 0;
	
	public function __construct($from, $to = 0)
	{
		$this->_last_migration = $from;
		$this->_to_rev   = $to;
	}
	
    /**
     *
     * Main execution method.
     *
     * @return void
     *
     */
    public function exec()
    {
        // find the list of avaiable migrations
        $this->_setBaseDir();
        $this->_setMigrationsDir();
        $this->_loadList();
        $this->_loadLastMigration();
        $this->_applyMigrations();
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
        $this->_base_dir = dirname(dirname(dirname(__FILE__)));
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
    protected function _loadLastMigration()
    {
        if ($this->_last_migration === false || $this->_last_migration == 0) {
            $this->_last_migration = -1;
            #$this->_outln("No migrations have been executed.");
        } else {
            #$this->_outln("Last migration applied was '{$this->_last_migration}'.");
        }
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
        #$this->_outln("Migrations directory is {$this->_migrations_dir}.");
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
    protected function _loadList()
    {
        $list = glob("{$this->_migrations_dir}/*");
        
        // retain the migration file names
        foreach ($list as &$file) {
            $val = basename($file);
            if(!is_numeric($val) || !file_exists("$file/migration.sh")) {
            	unset($file);
            	continue;
            }
            $key = (int) $val;
            $this->_list[$key] = "$val/migration.sh";
        }
        
        $k = count($this->_list);
        if (! $k) {
            #$this->_outln("Found no migration scripts, exiting.");
            exit(0);
        } else {
            #$this->_outln("Found $k migration scripts.");
        }
        
        // sort based on integer key, so we don't get weird orders like
        // "1, 10, 11, 12, ... 2, 20, 21, 22" that a filesystem might return.
        ksort($this->_list);
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
    protected function _applyMigration($key)
    {
        $file = $this->_list[$key];
        $dir = $this->_base_dir.'/app/updates/'.dirname($file);
        $file = $this->_base_dir.'/app/updates/'.$file;
        
        // chdir, set permissions, run the shell script
        // (discard STDOUT, capture STDERR)
        $cmd = sprintf('chdir %s; chmod a+x *.sh; %s 2>migration.err', $dir, $file);
        $output = trim(shell_exec($cmd));
        
        $err = trim(@file_get_contents($dir.'/migration.err'));
        @unlink($dir.'/migration.err');
        if(strlen($err) > 0) {
        	$this->_outln("Error in migration script '$file':");
        	$this->_outln($err);
        	$this->_outln('');
        }
    }

}