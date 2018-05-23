<?php

/**
 * Core52 Database Class
 *
 * This Database class evolved from a db class originally written by Alex King.
 * Totally rebuilt by Jonathon Hill, with inspiration from David Boskovic,
 * CodeIgniter, and some of my own ideas added in.
 *
 * @author "Jonathon Hill" <jhill@companyfiftytwo.com>
 * @package Core52
 * @version 3.0
 *
 **/

class Database {

	/**
	 * DatabaseConnection object
	 *
	 * @var DatabaseConnection
	 * @access public
	 * @static
	 */
	private static $connection;
	
    public static $query_history = array();
	public static $initialized = FALSE;
	public static $benchmark = FALSE;
	private static $cache = TRUE;

	/**
	 * How many queries to log in Database::$query_history
	 */
	const QUERY_LOG_LIMIT = 10;
	
	
	/**
	 * Enable/disable caching
	 * @param boolean $setting
	 * @return boolean
	 */
	public static function cache($setting) {
		# swap values
		list(self::$cache, $setting) = array((boolean) $setting, self::$cache);
		return $setting;
	}


	/**
	 * Initialize the database connection
	 *
	 * @static
	 * @param string $host Database server name
	 * @param string $database Database schema name
	 * @param string $user Database user name
	 * @param string $password Database password
	 * @param boolean $debug Enable database error messages
	 * @return resource MySQL connection resource
	 */
	public static function Initialize($group = NULL) {
		self::$connection = DatabaseConnection::factory($group);
		self::$initialized = (is_object(self::connection()));
		return self::connection();
	}
	
	
	/**
	 * Load the database configuration file
	 *
	 */
	public static function load_settings() {
		
		# Grab database configuration file
		if($_ENV['dba']) {
			$db_config_path = $_ENV['dba'];
		} elseif($_SERVER['dba']) {
			$db_config_path = $_SERVER['dba'];
		} else {
			$db_config_path = PATH_AUTH;
		}
		
		
		# load application settings file if it exists
		if(file_exists($db_config_path.APP_NAME.'.php')) {
			require_once($db_config_path.APP_NAME.'.php');
			return $db_config_path.APP_NAME.'.php';
		}
		elseif(file_exists($db_config_path.APP_NAME)) {
			require_once($db_config_path.APP_NAME);
			return $db_config_path.APP_NAME;
		}
		
		# otherwise, load default settings file
		elseif(file_exists($db_config_path.'_default.php')) {
			require_once($db_config_path.'_default.php');
			return $db_config_path.'_default.php';
		}
		
		else {
			return FALSE;
			#die('Missing database connection file');
		}
	}
	
	
	/**
	 * Returns MySQLi connection resource for the current database connection
	 *
	 * @return resource
	 */
	public static function connection() {
		return self::$connection->connection();
	}
	
	
	public static function escape($value, $options = NULL) {
		return self::c()->escape($value, $options);
	}


	/**
	 * Finds a record by the specified id.
	 *
	 * @static
	 * @access public
	 * @param string $table Table name
	 * @param integer|string|float $id Primary key value
	 * @param string $col Primary key column name (defaults to 'id')
	 * @return DatabaseResult
	 */
	public static function findById($table, $id, $col = 'id') {
		return self::c()->findById($table, $id, $col);
	}
	
	
	/**
	 * Finds the first record (any limit option will be overwritten)
	 *
	 * @static
	 * @access public
	 * @param string $table Table name
	 * @param array $where Associative array of columns/values for the WHERE clause
	 * @return DatabaseResult
	 */
	public static function findFirst($table, $where = array()) {
		return self::c()->findFirst($table, $where);
	}
	
	
	/**
	 * Finds records in a table
	 *
	 * @param string $table
	 * @param array $where
	 * @return DatabaseResult
	 */
	public static function find($table, $where = array(), $order = array(), $limit = array()) {
		return self::c()->find($table, $where, $order, $limit);
	}
	
	
	/**
	 * Get an associative array from a database table
	 *
	 * @param string $table Table name
	 * @param string $key Column to use for the array key. A '*' here will create a simple indexed array instead of an associative array
	 * @param string|array $value Column or columns to use for the array value. This can be an indexed array, in which case the values of the columns indicated will be concatenated together (the first element of the array is text to use as a delimiter, and the remaining elements are column names)
	 * @param array $where Associative array for the WHERE part
	 * @return array
	 *
	 * Example:
	 *
	 * $array = Database::findSelArray('table', 'id', array(' - ', 'date', 'title'));
	 *
	 * Produces:
	 *
	 *   array(
	 *     1 => '2009-03-10 - Hello, World!',
	 *     2 => '2009-03-11 - Another post',
	 *   )
	 *
	 */
	public static function findSelArray($table, $key, $value, $where = array(), $order = array()) {
		$result = self::c()->find($table, $where, $order);
		$array = array();
		foreach($result->result() as $row) {
			$array[$row->$key] = $row->$value;
		}
		return $array;
	}
	
	
	/**
	 * Filters an associative array by the column names in a database table
	 *
	 * @static
	 * @access public
	 * @param string $table Table name
	 * @param array $array Array of fields
	 * @return array
	 */
	public static function fields($table, $array = array()) {
		return self::c()->fields($table, $array = array());
	}
	
	
	/**
	 * Creates rows in the specified table using the provided associative array for values.
	 *
	 * @static
	 * @access public
	 * @param string $table Table name
	 * @param array $values Associative array of columns => values to save
	 * @return DatabaseResult
	 */
	public static function create($table, $values) {
		return self::c()->create($table, $values);
	}
	
	
	/**
	 * Update a row by its ID
	 *
	 * @static
	 * @param mixed $id
	 * @param string $table
	 * @param array $values
	 * @return DatabaseResult
	 */
	public static function updateById($id, $table, $values) {
		return self::c()->updateById($id, $table, $values);
	}
	
	
	/**
	 * Updates a table row
	 *
	 * @static
	 * @param string $table
	 * @param array $values
	 * @return DatabaseResult
	 */
	public static function update($table, $values, $where = array()) {
		return self::c()->update($table, $values, $where);
	}
	
	
	/**
	 * Replaces a table row
	 *
	 * @static
	 * @param string $table
	 * @param array $values
	 * @return DatabaseResult
	 */
	public static function replace($table, $values, $where = array()) {
		return self::c()->replace($table, $values, $where);
	}
	
	
	/**
	 * Deletes a row by its ID
	 *
	 * @static
	 * @param mixed $id
	 * @param string $table
	 * @param string $pk
	 * @return DatabaseResult
	 */
	public static function deleteById($id, $table, $pk = 'id') {
		return self::c()->deleteById($id, $table, $pk);
	}
	
	
	/**
	 * Deletes no more than one row from a table
	 *
	 * @static
	 * @param string $table
	 * @return DatabaseResult
	 */
	public static function deleteFirst($table, $where = array()) {
		return self::c()->deleteFirst($table, $where);
	}
	
		
	/**
	 * Deletes from a table
	 *
	 * @static
	 * @param string $table
	 * @return DatabaseResult
	 */
	public static function delete($table, $where = array(), $limit = NULL) {
		return self::c()->delete($table, $where, $limit);
	}
	
		
	/**
	 * Returns an integer with the number of rows in a particular table, with options.
	 *
	 * @static
	 * @param string $table
	 * @param array $where
	 * @return int
	 */
	public static function count($table, $where = array()) {
		return self::c()->count($table, $where);
	}
	
		
	/**
	 * Increments the indicated field in the indicated row.
	 *
	 * @static
	 * @param mixed $id
	 * @param string $field
	 * @param string $table
	 * @return DatabaseResult
	 */
	public static function increment($id, $field, $table) {
		return self::c()->increment($id, $field, $table);
	}
	
	
	/**
	 * Decrements the indidated field in the indicated row.
	 *
	 * @static
	 * @param mixed $id
	 * @param string $field
	 * @param string $table
	 * @return DatabaseResult
	 */
	public static function decrement($id, $field, $table) {
		return self::c()->decrement($id, $field, $table);
	}
	
	
	/**
	 * Toggles the field - sets to 1 if it is 0, sets to 0 if it is 1.
	 *
	 * @param mixed $id
	 * @param string $field
	 * @param string $table
	 * @return DatabaseResult
	 */
	public function toggle($id, $field, $table) {
		return self::c()->toggle($id, $field, $table);
	}
	
		
	/**
	 * Runs an SQL query and returns the result.
	 *
	 * @static
	 * @param string $sql
	 * @return DatabaseResult
	 */
	public static function e($sql, $cache = FALSE) {
		if(is_null($cache)) {
			$cache = self::$cache;
		}
		return self::$connection->execute($sql, $cache);
	}
	
	
	/**
	 * Converts a UNIX timestamp to MySQL timestamp format
	 *
	 * @param int $timestamp
	 * @return string
	 */
	public function timestampToMySQL($timestamp) {
		return date('Y-m-d H:i:s', $timestamp);
	}
	
	public static function error_reporting($state = NULL) {
		if($state === FALSE || $state === TRUE) {
			$prev = self::c()->report_errors;
			self::c()->report_errors = $state;
			return $prev;
		} else {
			return self::c()->report_errors;
		}
	}
	
	
	/**
	 * Report a query error and terminate
	 *
	 */
	public static function error() {
		if(!self::$debug) exit();
		throw new DatabaseException(mysqli_connect_error(), mysqli_connect_errno());
	}
	
	
	/**
	 * Trace the origin of a query
	 *
	 * @param string $query
	 */
	private static function trace_query($query) {
		
		# Get a backtrace
		$backtrace = self::trace_error();
		$line = array_shift($backtrace);
		$args = '';
		foreach((array) $line['args'] as $i => $arg) {
			if(is_array($arg)) $line['args'][$i] = print_r($arg, TRUE);
		}
		$args = implode(', ', $line['args']);
		
		# Save the query
		$q->query = $query;
		$q->file  = $line['file'];
		$q->line  = $line['line'];
		$q->call  = $line['class'].$line['type'].$line['function']."(</b>$args<b>)</b>";
		
		# Only save the last 10 queries (LIFO registry pattern)
		array_unshift(self::$query_history, $q);
		if(count(self::$query_history) > Database::QUERY_LOG_LIMIT) {
			array_pop(self::$query_history);
		}
		
		
	}
	
	
	/**
	 * Trace the source of a query error
	 *
	 * @return array
	 */
	public static function trace_error() {
	
		# Get a backtrace
		$backtrace = debug_backtrace();
	
		foreach((array) $backtrace as $i => $line) {
			
			if($line['file'] != __FILE__) {
				return $backtrace;
			}
			else {
				unset($backtrace[$i]);
			}
		}
	}
	
	
	public static function last_query() {
		return self::$query_history[0]->query;
	}
	
	
	/**
	 * Returns the internal DatabaseConnection object
	 *
	 * @return DatabaseConnection
	 */
	public static function c() {
		return self::$connection;
	}
	
	
	/**
	 * Returns the internal DatabaseQuery object
	 * @return DatabaseQuery
	 */
	public static function q($reset = FALSE) {
		return self::c()->query_instance($reset = FALSE);
	}
	
	
	/**
	 * Run the current query
	 *
	 * @static
	 * @param string $table
	 * @param string $statement_type
	 * @param boolean $cache
	 * @return DatabaseResult
	 */
	public static function run($table, $statement_type = "SELECT", $cache = NULL) {

		if(! self::c() instanceof DatabaseConnection){
			die('Database not initialized');
		}

		# figure out whether or not to cache
		if(strtoupper(substr($statement_type, 0, 6)) != 'SELECT') {
			# never cache an update query
			$cache_setting = self::cache(FALSE);
			DatabaseCache::clear();
		}
		if(is_null($cache)) {
			$cache = self::$cache;
		}

		$result = self::c()->query_instance_run($table, $statement, $cache);

		# restore previous cache setting
		if(is_bool($cache_setting)) {
			self::cache($cache_setting);
		}

		return $result;
	}
	
	
	/**
	 * DatabaseQuery::where() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function where() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'where'), $args);
	}
	
	
	/**
	 * DatabaseQuery::raw_where() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function raw_where() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'raw_where'), $args);
	}
	
	
	/**
	 * DatabaseQuery::where_in() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function where_in() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'where_in'), $args);
	}
	
	
	/**
	 * DatabaseQuery::group_by() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function group_by() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'group_by'), $args);
	}
	
	
	/**
	 * DatabaseQuery::order_by() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function order_by() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'order_by'), $args);
	}
	
	
	/**
	 * DatabaseQuery::order_direction() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function order_direction() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'order_direction'), $args);
	}
	
	
	/**
	 * DatabaseQuery::set() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function set() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'set'), $args);
	}
	
	
	/**
	 * DatabaseQuery::select() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function select() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'select'), $args);
	}
	
	
	/**
	 * DatabaseQuery::limit() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function limit() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'limit'), $args);
	}
	
	
	/**
	 * DatabaseQuery::join() wrapper function
	 *
	 * @static
	 * @return DatabaseQuery
	 */
	public static function join() {
		$args = (array) func_get_args();
		return call_user_func_array(array(self::c()->query_instance(), 'join'), $args);
	}
	
	
		
}

class DatabaseCache {

	private static $cache = array();

	private static $resets = 0;
	private static $inserts_lost = array();
	private static $inserts = 0;
	private static $hits = 0;


	/**
	 * Computes a checksum of the database query to use as a cache lookup key
	 *
	 * @param string $sql
	 * @return string
	 */
	private static function hash($sql) {
		return md5($sql);
	}


	/**
	 * Caches a database result
	 *
	 * @param DatabaseResult $result
	 */
	public static function set(DatabaseResult $result) {
		$hash = self::hash($result->statement);
		self::$cache[$hash] = $result;
		self::$inserts++;
	}


	/**
	 * Get a database resultset from cache
	 *
	 * @param string $sql
	 * @param DatabaseConnection $db
	 * @return DatabaseResult
	 */
	public static function get($sql, DatabaseConnection $db = NULL) {

		$hash = self::hash($sql);

		# not cached?
		if(!isset(self::$cache[$hash])) {

			# get the default database connection if none given
			if(is_null($db)) {
				$db = DatabaseConnection::factory();
			}

			# run and cache the query
			self::set($db->execute($sql));

		} else {

			self::$hits++;

		}

		# return the query resultset
		return self::$cache[$hash];
	}



	public static function clear() {
		if(count(self::$cache) > 0) {
			self::$inserts_lost[] = count(self::$cache);
			self::$resets++;
			self::$cache = array();
		}
	}


	public static function report() {
		?>

		<div style="padding:20px;background:white;border:1px solid #ccc;margin:20px;">
			<h3 style="border-bottom:2px solid #333;">DatabaseCache report</h3>
			<table>
				<tr>
					<th>Resets</th>
					<td><?=number_format(self::$resets, 0);?></td>
				</tr>
				<tr>
					<th>Discards</th>
					<td><?=implode(', ', self::$inserts_lost);?></td>
				</tr>
				<tr>
					<th>Inserts</th>
					<td><?=number_format(self::$inserts, 0);?></td>
				</tr>
				<tr>
					<th>Hits</th>
					<td><?=number_format(self::$hits, 0);?></td>
				</tr>
			</table>
		</div>
		<?php
	}


}

abstract class DatabaseQueryHelper {
	
	abstract public function start_query($table = NULL, $statement = 'SELECT');
	
	/**
	 * @var DatabaseQuery
	 */
	protected $query;
	
	
	
	/**
	 * Returns the internal DatabaseQuery object instance
	 * @param $reset
	 * @return DatabaseQuery
	 */
	public function query_instance($reset = FALSE) {
		if(! $this->query instanceof DatabaseQuery || $reset) {
			$this->query = new DatabaseQuery($this);
		}
		return $this->query;
	}
	
	
	/**
	 * Run and reset the internal DatabaseQuery object
	 * @param string $table
	 * @param string $statement
	 * @param boolean $cache
	 * @return DatabaseResult
	 */
	public function query_instance_run($table = NULL, $statement = NULL, $cache = NULL) {
		if(!is_null($table)) {
			$this->query_instance()->set_table($table);
		}
		if(!is_null($statement)) {
			$this->query_instance()->set_statement($statement);
		}
		
		try {
			$result = $this->query_instance()->run($cache);
			$this->query_instance(TRUE);
			return $result;
		} catch (DatabaseException $e) {
			$this->query_instance(TRUE);
			throw $e;
		}
	}
	
	
	/**
	 * Finds a record by the specified id.
	 *
	 * @access public
	 * @param string $table Table name
	 * @param integer|string|float $id Primary key value
	 * @param string $col Primary key column name (defaults to 'id')
	 * @return DatabaseResult
	 */
	public function findById($table, $id, $col = 'id') {
		return $this->find($table, array($col => $id));
	}
	
	
	/**
	 * Finds the first record (any limit option will be overwritten)
	 *
	 * @access public
	 * @param string $table Table name
	 * @param array $where Associative array of columns/values for the WHERE clause
	 * @return DatabaseResult
	 */
	public function findFirst($table, $where = array()) {
		$this->query_instance()->limit(1);
		return $this->find($table, $where);
	}
	
	
	/**
	 * Finds records in a table
	 *
	 * @param string $table
	 * @param array|string $where
	 * @param array $order
	 * @param array $limit
	 * @return DatabaseResult
	 */
	public function find($table, $where = array(), $order = array(), $limit = array()) {
	
		$this->query_instance()->set_table($table);
		
		if(count($order) > 0) {
			$this->query_instance()->order_by($order);
		}
		
		if(count($limit) == 2) {
			$this->query_instance()->limit($limit[0], $limit[1]);
		}

		if(!empty($where)) {
			if(is_array($where)) {
				$this->query_instance()->where($where);
			} else {
				$this->query_instance()->raw_where($where);
			}
		}

		return $this->query_instance_run();
	}
	
	
	/**
	 * Get an associative array from a database table
	 *
	 * @param string $table Table name
	 * @param string $key Column to use for the array key. A '*' here will create a simple indexed array instead of an associative array
	 * @param string|array $value Column or columns to use for the array value. This can be an indexed array, in which case the values of the columns indicated will be concatenated together (the first element of the array is text to use as a delimiter, and the remaining elements are column names)
	 * @param array $where Associative array for the WHERE part
	 * @return array
	 *
	 * Example:
	 *
	 * $array = Database::findSelArray('table', 'id', array(' - ', 'date', 'title'));
	 *
	 * Produces:
	 *
	 *   array(
	 *     1 => '2009-03-10 - Hello, World!',
	 *     2 => '2009-03-11 - Another post',
	 *   )
	 *
	 */
	public function findSelArray($table, $key, $value, $where = array(), $order = array()) {

		$r = self::find($table, $where, $order);
		$f = array();
		
		# if multiple column names were given for the value,
		# then splice out the delimiter ($value[0]), and the
		# rest will be column names
		if(is_array($value)) {
			$val_delimiter = array_shift($value);
		}
		if(is_array($key)) {
			$key_delim = array_shift($key);
		}
		
		if($r) {

			$cnt = 0;
			foreach($r->result_array() as $q) {

				# Indexed or associative array?
				if ($key==='*')
					$ndx = $cnt;
				elseif (!is_array($key)) {	# create simple index
					$ndx = $q[$key];
				}
				else {						# create complex index (e.g. '3:34')
					$keys = array();
					foreach ($key as $k)
						$keys[] = $q[$k];
					$ndx = implode($key_delim, $keys);
				}

				# One or multiple columns for the value?
				if (is_array($value)) {

					$label = array();

					foreach($value as $v) {
						if (substr($v, 0, 8) == 'literal:')
							$label[] = substr($v, 8);
						else
							$label[] = $q[$v];
					}

					$f[$ndx] = implode($val_delimiter, $label);

				} else {

					$f[$ndx] = $q[$value];

				}

				$cnt++;
			}
		}

		return $f;
	}
	
	
	/**
	 * Filters an associative array by the column names in a database table
	 *
	 * @access public
	 * @param string $table Table name
	 * @param array $array Array of fields
	 * @return array
	 */
	public function fields($table, array $array = array()) {
		$query = $this->execute("SHOW COLUMNS FROM `$table`");
		$fields = array();
		
		foreach($query->result() as $col) {
			if(array_key_exists($col->Field, $array)) {
				$fields[$col->Field] = $array[$col->Field];
			}
		}
	  	
		return (count($fields) > 0)? $fields : array();
	}
	
	
	/**
	 * Fetches the columns from a table
	 *
	 * @access public
	 * @param string $table Table name
	 * @return array
	 */
	public function columns($table) {
		return $this->execute("SHOW COLUMNS FROM `$table`")->result('Field');
	}
	

	/**
	 * Creates rows in the specified table using the provided associative array for values.
	 *
	 * @access public
	 * @param string $table Table name
	 * @param array $values Associative array of columns => values to save
	 * @return DatabaseResult
	 */
	public function create($table, $values) {
		$this->query_instance()->set($values);
		return $this->query_instance_run($table, 'INSERT');
	}


	/**
	 * Update a row by its ID
	 *
	 * @param mixed $id
	 * @param string $table
	 * @param array $values
	 * @return DatabaseResult
	 */
	public function updateById($id, $table, $values)
	{
		return $this->update($table, $values, array('id' => $id));
	}
	
	
	/**
	 * Updates a table row
	 *
	 * @param string $table
	 * @param array $values
	 * @return DatabaseResult
	 */
	public function update($table, $values, $where = array()) {
	
		if(!empty($where)) {
			if(is_array($where)) {
				$this->query_instance()->where($where);
			} else {
				$this->query_instance()->raw_where($where);
			}
		}
		
		$this->query_instance()->set($values);

		# Execute the query.
		return $this->query_instance_run($table, 'UPDATE');
	}
		

	/**
	 * Replaces a table row
	 *
	 * @param string $table
	 * @param array $values
	 * @return DatabaseResult
	 */
	public function replace($table, $values, $where = array()) {
	
		if(!empty($where)) {
			if(is_array($where)) {
				$this->query_instance()->where($where);
			} else {
				$this->query_instance()->raw_where($where);
			}
		}
		
		$this->query_instance()->set($values);

		# Execute the query.
		return $this->query_instance_run($table, 'REPLACE');
	}


	/**
	 * Deletes a row by its ID
	 *
	 * @param mixed $id
	 * @param string $table
	 * @param string $pk
	 * @return DatabaseResult
	 */
	public function deleteById($id, $table, $pk = 'id') {
		return $this->delete($table, array($pk => $id));
	}
	
	
	/**
	 * Deletes no more than one row from a table
	 *
	 * @param string $table
	 * @return DatabaseResult
	 */
	public function deleteFirst($table, $where = array()) {
		$this->query_instance()->limit(1);
		return $this->delete($table, $where);
	}
	
	
	/**
	 * Deletes from a table
	 *
	 * @param string $table
	 * @param string|array $where
	 * @param integer $limit
	 * @return DatabaseResult
	 */
	public function delete($table, $where = array(), $limit = NULL) {
	
		if(!is_null($limit)) {
			$this->query_instance()->limit($limit);
		}

		if(!empty($where)) {
			if(is_array($where)) {
				$this->query_instance()->where($where);
			} else {
				$this->query_instance()->raw_where($where);
			}
		}
		
		return $this->query_instance_run($table, 'DELETE');
	}
	
	
	/**
	 * Returns an integer with the number of rows in a particular table, with options.
	 *
	 * @param string $table
	 * @param array $where
	 * @return int
	 */
	public function count($table, $where = array()) {
	
		# Count the rows with the specified criteria
		$this->query_instance()->select('COUNT(*) AS cnt');
		$query = $this->find($table, $where);
	
		# Return the number of rows
		return $query->row()->cnt;
	}
	
	/**
	 * Increments the indicated field in the indicated row.
	 *
	 * @param mixed $id
	 * @param string $field
	 * @param string $table
	 * @return DatabaseResult
	 */
	public function increment($id, $field, $table) {
		return $this->updateById($id, $table, array($field => $field + 1));
	}
	
	
	/**
	 * Decrements the indidated field in the indicated row.
	 *
	 * @param mixed $id
	 * @param string $field
	 * @param string $table
	 * @return DatabaseResult
	 */
	public function decrement($id, $field, $table) {
		return $this->updateById($id, $table, array($field => $field - 1));
	}
	
	
	/**
	 * Toggles the field - sets to 1 if it is 0, sets to 0 if it is 1.
	 *
	 * @param mixed $id
	 * @param string $field
	 * @param string $table
	 * @return DatabaseResult
	 */
	public function toggle($id, $field, $table) {
		DatabaseCache::clear();
		return $this->execute("UPDATE `$table` SET `$field` = ABS(`$field` - 1) WHERE `id` = '$id'");
	}
	
	
	
	
}

class DatabaseConnection extends DatabaseQueryHelper implements DatabaseConnectionInterface {

	public $name;
	public $host;
	public $user;
	public $password;
	public $database;
	public $debug;
    public $persist;
	public $allow_html = FALSE;
	public $charset;
    
	public $query_history = array();
	public $report_errors = TRUE;
	
	protected $connection;
	protected $permissions;
	
	protected static $connections = array();
	public static $default;
	
	protected $escape_options = 0;

	public static $terminate_on_connect_fail = TRUE;

	const ESCAPE_FORCE = 2;
	const ESCAPE_STRIP_HTML = 4;
	const ESCAPE_QUOTE = 8;
	
	/**
	 * Initialize the database connection
	 *
	 * @param string $host
	 * @param string $database
	 * @param string $user
	 * @param string $password
	 * @param boolean $debug
	 * @param integer $escape
	 * @return resource MySQL connection resource
	 */
	public function __construct($host = '', $user = '', $pass = '', $db = '', $debug = TRUE, $persist = TRUE, $escape = NULL, $auto_connect = FALSE, $charset = '') {
	
		if(is_array($host)) extract($host);
		
		$this->user 		= $user;
		$this->password 	= $pass;
		$this->host 		= $host;
		$this->database 	= $db;
		$this->debug        = $debug;
		$this->persist      = $persist;
		$this->charset      = $charset;
		
        $this->escape_options = (is_null($escape))?
        	DatabaseConnection::ESCAPE_STRIP_HTML | DatabaseConnection::ESCAPE_QUOTE :
        	$escape;

		if(!defined('DBTRACELOG'))
			define('DBTRACELOG', 10);
		
		if($auto_connect) {
			$this->connect();
		}
	}


	/**
	 * Disconnects from the database.
	 *
	 */
	public function __destruct() {
		@mysqli_close($this->connection);
	}


	public function __toString() {
		return (string) $this->dsn();
	}
	
	public function dsn() {
		return "mysql://$this->user:$this->password@$this->host/$this->database";
	}

    /**
     * Connect or re-connect to the database
     *
     * @param bool $dieOnError
     * @return bool|mysqli
     */
	public function connect($dieOnError = TRUE) {
		
		# Close the connection if previously connected
		if($this->connection) {
			@mysqli_close($this->connection);
		}
	
		try {
			# Connect to the database.
            $this->connection = @mysqli_connect($this->host, $this->user, $this->password);

            # Catch any exceptions and use handle_connection_error instead.
		} catch(Exception $e) {
			$this->handle_connection_error($dieOnError && DatabaseConnection::$terminate_on_connect_fail);
		}
		
		if($this->connection === FALSE){
			$this->handle_connection_error($dieOnError && DatabaseConnection::$terminate_on_connect_fail);
		}

		# Select the correct database if one was specified.
		if(!empty($this->database)) {
			@mysqli_select_db($this->connection, $this->database)
				or $this->handle_connection_error($dieOnError && DatabaseConnection::$terminate_on_connect_fail);
		}

		# Set the character set for the connection
		if(!empty($this->charset)) {
			@mysqli_set_charset($this->connection, $this->charset)
				or $this->handle_connection_error($dieOnError && DatabaseConnection::$terminate_on_connect_fail);
		}

		# Return the connection object.
		return $this->connection;
		
	}


	/**
	 * Checks to see if the database connection is still active
	 *
	 * @return boolean
	 */
	public function ping() {
		return (is_object($this->connection) && @mysqli_ping($this->connection));
	}
   
   
	/**
	 * Return the MySQL connection ID
	 * @return integer
	 */
	public function thread_id() {
		return (is_object($this->connection))? mysqli_thread_id($this->connection) : FALSE;
	}
	
	
	/**
	 * Establish or fetch a database connection
	 *
	 * @param string $name
	 * @param array $connect
	 * @param boolean $default
	 * @return DatabaseConnection
	 */
	public static function factory($name = NULL, $connect = FALSE, $default = TRUE) {
		
		if(is_array($connect)) {
			self::$connections[$name] = new DatabaseConnection($connect);
			self::$connections[$name]->name = $name;
			if($default) {
				self::$default = $name;
			}
		}
		
		if(is_null($name)) {
			$name = self::$default;
		}
		
		if(empty($name)) {
			throw new FatalErrorException("No database connection specified");
		}
		
		if(self::$connections[$name] instanceof DatabaseConnection) {
			return self::$connections[$name];
		} else {
			throw new FatalErrorException("Invalid database connection: '$name'");
		}
	}

	
	/**
	 * Get all connections
	 *
	 * @return array
	 */
	public static function connections() {
		return self::$connections;
	}
	
		
	/**
	 * Start a new Database Query
	 *
	 * @param string $name
	 * @param string $table
	 * @param string $statement
	 * @return DatabaseQuery
	 */
	public function start_query($table = NULL, $statement = 'SELECT') {
		return new DatabaseQuery($this, $table, $statement);
	}
	
	/**
	 * Shortcut method for start_query
	 *
	 * @param string $table
	 * @return DatabaseQuery
	 * @author Alex King
	 **/
	public function insert($table = NULL) {
		return new DatabaseQuery($this, $table, 'INSERT');
	}

    /**
     * Returns the MySQLi connection handle
     *
     * @return bool|mysqli
     */
    public function connection() {
    	if(!is_object($this->connection)) $this->connect();
        return $this->connection;
    }
	
	
	/**
     * Get the current escape options
     *
     * @return integer
     */
    public function get_escape_options() {
    	return $this->escape_options;
    }


    /**
     * Set the current escape options
     *
     * @param integer $options
     */
    public function set_escape_options($options) {
    	$this->escape_options = $options;
    }


	/**
	 * Escape a value for safe use in SQL queries
	 *
	 * @param string $value
	 * @param boolean $options
	 * @return string
	 */
	public function escape($value, $options = NULL) {

		if(is_array($value)) {
			
			foreach($value as &$val) {
				$val = $this->escape($val, $options);
			}
			
			return $value;
			
		} else {
		
			$options = (is_null($options))? $this->get_escape_options() : $options;
	
			if(($options & DatabaseConnection::ESCAPE_STRIP_HTML) && isset($this->strip_tag) && $this->strip_tags == TRUE) {
				$value = strip_tags($value);
			}
	
			if(($options & DatabaseConnection::ESCAPE_FORCE) || !get_magic_quotes_gpc() || php_sapi_name() == 'cli') {
				$value = mysqli_real_escape_string($this->connection(), $value);
			}
	
			if(($options & DatabaseConnection::ESCAPE_QUOTE) && !is_integer($value)) {
				$value = "'$value'";
			}
	
		    return $value;
		}
	}


	/**
	 * Runs an SQL query and returns the result.
	 *
	 * @param string $sql
	 * @return DatabaseResult
	 */
	public function execute($sql, $cache = FALSE) {
	
		if($this->connection === FALSE || !is_object($this->connection)) {
			try {
				$this->connect();
			} catch(DatabaseException $e) {
				die('No database connection');
			}
		}
		
		$sql = ($sql instanceof DatabaseQuery)? $sql->__toString() : $sql;

		# Cache this query, or retrieve from cache
		if($cache === TRUE) {
			return DatabaseCache::get($sql, $this);
		}

		# Benchmark the query
		$trace = $this->trace_error();
		$trace = array_shift($trace);
		Benchmark::mark('', $sql, array(
			'file' => $trace['file'].':'.$trace['line'],
			'function' => $trace['class'].$trace['type'].$trace['function'].'()',
		));

		# Trace the origin of the query
		if($this->debug == TRUE) {
            $this->trace_query($sql);
        }
		
		# Execute and return the result.
		$start = microtime(TRUE);
		$result = mysqli_query($this->connection(), $sql);
		
		if($result === FALSE) {

			# Error 2006: "MySQL server has gone away"
			# Error 2013: "Lost connection to MySQL server during query"
			#
			# Reconnect and re-try the query
			if(in_array($this->error_code(), array(2006, 2013))) {
				$this->connect();
				$result = mysqli_query($this->connection(), $sql);
				if($result === FALSE && $this->report_errors) {
					$this->handle_error();
					return FALSE;
				}
			}

			# Some other error
			elseif($this->report_errors) {
				$this->handle_error();
				return FALSE;
			}
		}

		$time = microtime(TRUE) - $start;
		
		return new DatabaseResult($this, $result, $sql, $time);
	}
    
    
	/**
	 * Return the last query run
	 *
	 * @return StdClass
	 */
    public function last_query() {
        return $this->query_history[0]->query;
    }
	
	
    /**
     * Parse the error message template
     *
     * @return string
     */
	public function get_errors($echo = TRUE) {
		$output = ob_get_clean();
		ob_start();
		if(PHP_SAPI !== 'cli') {
?>
		
		<h1>Database Error</h1>
		
		<p>We're sorry, but the system has encountered an error. We have notified technical support with the details of this error and they are working to get the problem resolved. Please try again later.</p>

		<h2>MySQL Error <?=$this->error_code();?></h2>
		
		<h3>Description</h3>
		<pre><?=$this->error_msg();?></pre>
		
		<h3>Query</h3>
		<pre><?=$this->last_query();?></pre>

		<h2>Most Recent Queries:</h2>
<ol>
<?php
	foreach($this->query_history as $q) {
		echo "<li><b>$q->file[$q->line]:</b>\n<pre>$q->query</pre></li>\n";
	}
?>
</ol>
		
		
<?php
		} else {
?>


MySQL Error <?=$this->error_code();?>

--------------------
<?=$this->error_msg();?>


Query:
--------
<?=$this->last_query();?>


Recent Queries:
-----------------
<?php
			foreach($this->query_history as $q) {
				echo "$q->file[$q->line]:\n$q->query\n--\n";
			}
			echo "\n";
		}
		$errors = ob_get_clean();
		echo $output;
		if($echo) echo $errors;
		return $errors;
	}
	
	
	/**
	 * Database error handler
	 *
	 * @param boolean $exit
	 */
	public function handle_error($exit = TRUE) {
		throw new DatabaseException($this->get_errors(FALSE), $this->error_code());
		if($exit) die;
	}


	/**
	 * Database connection error handler
	 * Throws a DatabaseConnectionException instead of a DatabaseException, which doesn't
	 * include a backtrace, to avoid exposing the database password.
	 *
	 * @param boolean $exit
	 */
	public function handle_connection_error($exit = TRUE) {
		throw new DatabaseConnectionException($this->get_errors(FALSE));
		if($exit) die;
	}
	
	
	/**
	 * Trace the origin of a query
	 *
	 * @param string $query
	 */
	private function trace_query($query) {
		
		# Get a backtrace
		$backtrace = $this->trace_error();
		$line = array_shift($backtrace);
		$args = '';
		foreach((array) $line['args'] as $i => $arg) {
			if(is_array($arg)) $line['args'][$i] = print_r($arg, TRUE);
		}
		$args = implode(', ', $line['args']);
		
		# Save the query
		$q = new stdClass();
		$q->query = $query;
		$q->file  = $line['file'];
		$q->line  = $line['line'];
		$q->call  = $line['class'].$line['type'].$line['function']."(</b>$args<b>)</b>";
		
		# Only save the last 10 queries (LIFO registry pattern)
		array_unshift($this->query_history, $q);
		if(count($this->query_history) > DBTRACELOG) {
			array_pop($this->query_history);
		}
		
	}
	
	
	/**
	 * Trace the source of a query error
	 *
	 * @return array
	 */
	private function trace_error() {
	
		# Get a backtrace
		$backtrace = debug_backtrace();
	
		foreach((array) $backtrace as $i => $line) {
			
			if($line['file'] != __FILE__) {
				return $backtrace;
			}
			else {
				unset($backtrace[$i]);
			}
		}
	}
	
	
	/**
	 * Return the last SQL error code
	 *
	 * @return string
	 * @access public
	 */
	public function error_code() {
		return @mysqli_connect_errno();
	}
	
	
	/**
	 * Return the last SQL error message
	 *
	 * @return string
	 * @access public
	 */
	public function error_msg() {
		return @mysqli_connect_error();
	}
	
}

class DatabaseQuery implements DatabaseQueryInterface {
	
	public $table;
	private $statement;
	private $limit;
	private $group  = array();
	private $order  = array();
	private $select = array();
	private $set    = array();
	private $where  = array();
	private $union  = array();
	private $distinct;
	private $order_direction = 'ASC';
	private $query;
	private $strip_tags = FALSE;
	
	/**
	 * DatabaseConnection object
	 *
	 * @var DatabaseConnection
	 */
	private $connection;
	
	/**
	 * Create a DatabaseQuery object
	 *
	 * @param DatabaseConnection $connection
	 * @param string $table
	 * @param string $statement
	 */
	function __construct(DatabaseConnection $connection, $table = NULL, $statement = 'SELECT') {
		$this->connection = $connection;
		$this->table = "`$table`";
		$this->statement = $statement;
	}
	
	
	/**
	 * Build the query statement
	 *
	 */
	function __toString() {
		
		switch(strtoupper($this->statement)) {
			
			case 'INSERT':
				
				$clauses = array();
				
				$clauses[] = "INSERT INTO $this->table";
				$clauses[] = "SET ". implode(', ', $this->set);
				
				break;
				
				
			case 'REPLACE':
				
				$clauses = array();
				
				$clauses[] = "REPLACE INTO $this->table";
				$clauses[] = "SET ". implode(', ', $this->set);
				
				break;
			

			case 'UPDATE':
				
				$clauses = array();
				
				$clauses[] = "UPDATE $this->table";
				$clauses[] = "SET ". implode(', ', $this->set);
				
				if(count($this->where) > 0) {
					$clauses[] = 'WHERE '. implode(' AND ', $this->where);
				}

                if(count($this->order) > 0) {
                    $clauses[] = $this->build_sort_clause($this->order, $this->order_direction);
                }

				if(strlen($this->limit) > 0) {
					$clauses[] = 'LIMIT '.$this->limit;
				}
				
				break;
				
				
			case 'DELETE':
				
				$clauses = array();
				
				$clauses[] = "DELETE FROM $this->table";
				
				if(count($this->where) > 0) {
					$clauses[] = 'WHERE '. implode(' AND ', $this->where);
				}

                if(count($this->order) > 0) {
                    $clauses[] = $this->build_sort_clause($this->order, $this->order_direction);
                }

				if(strlen($this->limit) > 0) {
					$clauses[] = 'LIMIT '.$this->limit;
				}
								
				break;
				
				
			case 'SELECT':
			case 'SELECT DISTINCT':
			default:
				
				$clauses = array();
				
				# select fields
				$distinct = ($this->distinct || stripos($this->statement, 'DISTINCT') !== FALSE)? 'DISTINCT ' : '';
				$select   = (count($this->select) > 0)? implode(",\n\t", $this->select) : '*';
				$clauses[] = "SELECT {$distinct}\n\t{$select}";
				
				# select table
				$clauses[] = "FROM $this->table";

				# select joins
				if(!empty($this->join) && count($this->join) > 0) {
					foreach((array) $this->join as $join) {
						if(is_array($join[1])) {
							$join_on = array();
							foreach((array) $join[1] as $left => $right) {
								$join_on[] = "$this->table.`$left` = `{$join[0]}`.`$right`";
							}
							
							$clauses[] = "\t". (($join[2] == 'LEFT' || $join[2] == 'RIGHT')? $join[2].' JOIN ' : 'JOIN ').$join[0].' ON '.implode(' AND ', $join_on);
						} else {
							$clauses[] = "\t". (($join[2] == 'LEFT' || $join[2] == 'RIGHT')? $join[2].' JOIN ' : 'JOIN '). "{$join[0]} ON {$join[1]}";
						}
					}
				}
				
				# select conditions
				if(count($this->where) > 0) {
					$clauses[] = "WHERE\n\t". implode("\n\tAND ", $this->where);
				}
		
				# UNION queries
				if(count($this->union) > 0) {
					foreach($this->union as $union) {
						$keyword = ($union[1])? 'UNION' : 'UNION ALL';
						$clauses[] = "\n$keyword\n\n$union[0]";
					}
					$clauses[] = "";
				}
				
				# select groups
				if(count($this->group) > 0) {
					$clauses[] = 'GROUP BY '.implode(', ', $this->escape_col_names($this->group));
				}
				
				# select order
				if(count($this->order) > 0) {
					$clauses[] = $this->build_sort_clause($this->order, $this->order_direction);
				}
				
				# select limit
				if(strlen($this->limit) > 0) {
					$clauses[] = 'LIMIT '.$this->limit;
				}
				
				break;
		}
		
		$this->query = implode("\n", $clauses);
		return $this->query;
	}


	private function build_sort_clause($order_by, $order_direction) {
        $order_fragments = array();
		foreach($this->escape_col_names($order_by) as $col) {
			$order_fragments[] = (preg_match('/ (ASC|DESC|RAND\(\))$/i', $col))? $col : "$col $order_direction";
		}
		return 'ORDER BY '.implode(', ', $order_fragments);
    }
	
	
	/**
	 * Get the current value of one or more query properties. If only one property is specified, returns the value; if an array of values is specified, then returns an array of values
	 * @param string|array $what
	 * @return array
	 */
	public function get($what = array('table', 'statement', 'limit', 'group', 'order', 'select', 'set', 'where', 'union', 'distinct', 'order_direction')) {
		if(is_array($what)) {
			$return = array();
			foreach((array) $what as $which) {
				$return[$which] = $this->$which;
			}
			return $return;
		} else {
			return $this->$what;
		}
	}
	
	
	/**
	 * Reset the query object. Preserves table name and strip_tags setting by default.
	 * @return DatabaseQuery
	 */
	public function reset($what = array('table', 'statement', 'limit', 'group', 'order', 'select', 'set', 'where', 'union', 'distinct', 'order_direction', 'query')) {

		foreach((array) $what as $var) {

			switch($var) {

				case 'group':
				case 'order':
				case 'select':
				case 'set':
				case 'where':
				case 'union':
					$this->$var = array();
					break;

				case 'order_direction':
					$this->$var = 'ASC';
					break;

				case 'strip_tags':
					$this->$var = TRUE;
					break;

				default:
					if(isset($this->$var)) {
						$this->$var = NULL;
					}
			}
		}

		return $this;
	}


	/**
	 * Manually set an internal property
	 *
	 * @param string $var The property to set
	 * @param unknown $data The value to set
	 * @return DatabaseQuery
	 */
	public function load_raw($var, $data) {

		switch($var) {

			case 'group':
			case 'order':
			case 'select':
			case 'set':
			case 'where':
			case 'union':
				if(!is_array($data)) {
					throw new Exception("DatabaseQuery->{$var} must be an array");
				} else {
					$this->$var = array();
				}
				break;

			case 'order_direction':
				if(!is_string($data) || !in_array(strtoupper($data), array('ASC', 'DESC'))) {
					throw new Exception("DatabaseQuery->order_direction must be a string value (either ASC or DESC)");
				} else {
					$this->order_direction = strtoupper($data);
				}
				break;

			case 'strip_tags':
				if(!is_bool($data)) {
					throw new Exception("DatabaseQuery->strip_tags must be a boolean value");
				} else {
					$this->strip_tags = $data;
				}
				break;

			default:
				if(isset($this->$var)) {
					$this->$var = NULL;
				} else {
					throw new Exception("DatabaseQuery property does not exist: $var");
				}
		}

		return $this;
	}


	/**
	 * Backtick-escapes an array of column and/or table names
	 *
	 * @param array $cols
	 * @return array
	 */
	private function escape_col_names($cols) {
		
		if(!is_array($cols)) $cols = array($cols);
		
		foreach($cols as &$col) {
			if(stripos($col, '(') === FALSE && stripos($col, ' ') === FALSE && stripos($col, '*') === FALSE) {
				if(stripos($col, '.')) {
					list($table, $c) = explode('.', $col);
					$col = "`$table`.`$c`";
				} else {
					$col = "`$col`";
				}
			}
		}
		
		return $cols;
	}
	
	
	/**
	 * Gets a variable list of function arguments and reformats them as needed for many of the functions of this class
	 *
	 * @param unknown_type $values
	 * @return unknown
	 */
	private function prep_args($values) {
		
		$values = (array) $values;
		if(!is_array($values[0]) && count($values) == 2) {
			$values = array($values[0] => $values[1]);
		} elseif(is_array($values[0]) && count($values) == 1) {
			$values = $values[0];
		}
		
		return $values;
		
	}
	
		
	/**
	 * Enable/disable HTML stripping
	 * @param boolean $value
	 * @return DatabaseQuery
	 */
	public function set_strip_tags($value) {
		$options = $this->connection->get_escape_options();
		if($value) {
			$options = $options | DatabaseConnection::ESCAPE_STRIP_HTML;
		} else {
			$options = $options & ~DatabaseConnection::ESCAPE_STRIP_HTML;
		}
		$this->connection->set_escape_options($options);
		return $this;
	}


	/**
	 * Adds a LIMIT 1 clause
	 *
	 * @return DatabaseQuery
	 */
	public function first() {
		return $this->limit(1);
	}

	
	/**
	 * Adds a SET clause
	 *
	 * @param mixed
	 * @return DatabaseQuery
	 */
	public function set() {

		$values = $this->prep_args(func_get_args());
		
		foreach((array) $values as $field => $value) {
			
			if(is_null($value)) {
				$this->set[] = "`$field` = NULL";
			}
			
			elseif(is_array($value)) {
				throw new Exception('Cannot save an unserialized array in the database. Data passed was: '.print_r($values, TRUE));
			}
			
			elseif(is_object($value)) {
				throw new Exception('Cannot save an unserialized object in the database. Data passed was: '.$value);
			}
			
			else {
				$this->set[] = sprintf("`$field` = %s", $this->escape($value, $this->connection->get_escape_options() | DatabaseConnection::ESCAPE_QUOTE));
			}
		}

		return $this;
	}

	
	/**
	 * Adds a SELECT clause
	 *
	 * @param string
	 * @return DatabaseQuery
	 */
	public function select() {
		$args = (array) func_get_args();
		if(count($args) == 1 && is_array($args[0])) $args = $args[0];
		$this->select = array_merge($this->select, $this->escape_col_names($args));
		return $this;
	}
	
	
	/**
	 * Add a complex WHERE clause (NOT ESCAPED)
	 *
	 * @return DatabaseQuery
	 */
	public function raw_where() {
		
		$criteria = $this->prep_args(func_get_args());
		
		foreach((array) $criteria as $clause) {
			$this->where[] = $clause;
		}
		
		return $this;
	}
	
	
	
	/**
	 * Adds a WHERE clause with all arguments sent separated by OR instead of AND inside a subclause
	 * @example array('a' => 1, 'b' => 2) becomes "AND (a = 1 OR b = 2)"
	 *
	 * @param mixed
	 * @return DatabaseQuery
	 */
	public function where_or() {
				
		$criteria = $this->prep_args(func_get_args());
		
		$or = array();
		foreach((array) $criteria as $field => $value) {
			
			if(!preg_match('/[\(\)<=>!]+/', $field) && stripos($field, ' IS ') === FALSE) {
				$operator = (is_null($value))? 'IS' : '=';
				$field = array_pop($this->escape_col_names($field))." ".$operator;
			}
			
			if(is_null($value) && stripos($field, ' IS ') !== FALSE) {
				# WHERE `field` IS NOT NULL
				$or[] = "$field NULL";
			}
			elseif(is_null($value)) {
				# WHERE `field` IS NULL
				$or[] = "$field NULL";
			}
			else {
				# WHERE `field` = 'val\\ue'
				# WHERE `field` = 3
				$or[] = sprintf("$field %s", $this->escape($value, $this->connection->get_escape_options() | DatabaseConnection::ESCAPE_QUOTE));
			}
		}
		
		// Create our subclause, and add it to the WHERE array
		$this->where[] = "(" . implode(' OR ', $or) . ")";
		
		return $this;
	}

	/**
	 * Adds a WHERE clause
	 *
	 * @param mixed
	 * @return DatabaseQuery
	 */
	public function where() {

		$criteria = $this->prep_args(func_get_args());
		
		foreach((array) $criteria as $field => $value) {
			
			if(!preg_match('/[\(\)<=>!]+/', $field) && stripos($field, ' IS ') === FALSE) {
				$operator = (is_null($value))? 'IS' : '=';
				$escaped_columns = $this->escape_col_names($field);
				$field = array_pop($escaped_columns)." ".$operator;
			}
			
			if(is_null($value) && stripos($field, ' IS ') !== FALSE) {
				# WHERE `field` IS NOT NULL
				$this->where[] = "$field NULL";
			}
			elseif(is_null($value)) {
				# WHERE `field` IS NULL
				$this->where[] = "$field NULL";
			}
			else {
				# WHERE `field` = 'val\\ue'
				# WHERE `field` = 3
				$this->where[] = sprintf("$field %s", $this->escape($value, $this->connection->get_escape_options() | DatabaseConnection::ESCAPE_QUOTE));
			}
		}
		
		return $this;
	}
	
	
	/**
	 * Adds a WHERE IN() clause
	 *
	 * @param mixed
	 * @return DatabaseQuery
	 */
	public function where_in() {

		$criteria = $this->prep_args(func_get_args());
		
		foreach((array) $criteria as $field => $values) {
			
			if(!is_array($values)) {
				$values = array($values);
			}
			elseif(count($values) == 0) {
				continue;
			}
			
			foreach($values as &$value) {
				if(is_numeric($value)) {
					# no change
				}
				elseif(is_null($value) || stristr($value, 'NULL') !== FALSE) {
					# change to a true NULL value
					$value = NULL;
				}
				else {
					# escape field value
					$value = sprintf("%s", $this->escape($value, $this->connection->get_escape_options() | DatabaseConnection::ESCAPE_QUOTE));
				}
			}
			
			$values = implode(',', $values);
			$this->raw_where("$field IN($values)");
		}
		
		return $this;
	}
	
	
	/**
	 * Adds a WHERE NOT IN() clause
	 *
	 * @param mixed
	 * @return DatabaseQuery
	 */
	public function where_not_in() {

		$criteria = $this->prep_args(func_get_args());
		
		foreach((array) $criteria as $field => $values) {
			
			if(!is_array($values)) {
				$values = array($values);
			}
			elseif(count($values) == 0) {
				continue;
			}
			
			foreach($values as &$value) {
				if(is_numeric($value)) {
					# no change
				}
				elseif(is_null($value) || stristr($value, 'NULL') !== FALSE) {
					# change to a true NULL value
					$value = NULL;
				}
				else {
					# escape field value
					$value = sprintf("%s", $this->escape($value, $this->connection->get_escape_options() | DatabaseConnection::ESCAPE_QUOTE));
				}
			}
			
			$values = implode(',', $values);
			$this->raw_where("$field NOT IN($values)");
		}
		
		return $this;
	}
	
	
	/**
	 * Adds a JOIN clause
	 *
	 * @param string $table
	 * @param string|array $conditions
	 * @param string $left
	 * @return DatabaseQuery
	 */
	public function join($table, $conditions, $left = FALSE) {
		
		$this->join[] = array($table, $conditions, $left);
		return $this;
	}
	
	
	/**
	 * Add a UNION query
	 *
	 * @var $query DatabaseQuery|string
	 * @var $distinct boolean
	 * @return DatabaseQuery
	 */
	public function union($query, $distinct = TRUE) {
		
		$this->union[] = array($query, $distinct);
		return $this;
	}
	
	/**
	 * Adds a GROUP BY clause
	 *
	 * @param string
	 * @return DatabaseQuery
	 */
	public function group_by() {
		$args = (array) func_get_args();
		if(count($args) == 1 && is_array($args[0])) $args = $args[0];
		$this->group = array_merge($this->group, $args);
		return $this;
	}
	

	/**
	 * Adds an ORDER BY clause
	 *
	 * @param string
	 * @return DatabaseQuery
	 */
	public function order_by() {

		# normalize arguments
		$args = (array) func_get_args();
		if(count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}

		if($args[0] !== TRUE) {
			$this->order = array_merge($this->order, $args);
		} else {
			# This allows for overwriting a preexisting order-by setting
			array_shift($args);
			$this->order = $args;
		}
		return $this;
	}
	
	
	/**
	 * Sets the sort direction for ORDER BY clauses
	 *
	 * @param string $direction
	 * @return DatabaseQuery
	 */
	public function order_direction($direction = 'ASC'){
		$this->order_direction = $direction;
		return $this;
	}
	

	/**
	 * Adds a LIMIT clause
	 *
	 * @param mixed $options
	 * @return DatabaseQuery
	 */
	public function limit($limit, $offset = NULL) {

		$this->limit = ($offset === NULL)? $limit : "$offset, $limit";
		return $this;
	}
	
	
	/**
	 * Set the table to run this query against
	 *
	 * @param string $table
	 * @return DatabaseQuery
	 */
	public function set_table($table = NULL, $escape = TRUE) {
		if($table != NULL) {
			$this->table = ($escape)? "`$table`" : $table;
			return $this;
		} else {
			return $this->table;
		}
	}
	
	
	/**
	 * Set the SQL statement type
	 *
	 * @param string $st
	 * @return DatabaseQuery
	 */
	public function set_statement($st = NULL) {
		if($st != NULL) {
			$this->statement = $st;
			return $this;
		} else {
			return $this->statement;
		}
	}
	
	
	/**
	 * Escape a value for safe use in SQL queries
	 *
	 * @param string $value
	 * @param boolean $force
	 * @return string
	 */
	public function escape($value, $options = NULL) {
	    return $this->connection->escape($value, $options);
	}
	
	
	/**
	 * Run this query
	 *
	 * @return DatabaseResult
	 */
	public function run($cache = TRUE) {
		if(strtoupper(substr($this->statement, 0, 6)) != 'SELECT') {
			$cache = FALSE;
			DatabaseCache::clear();
		}
		return $this->connection->execute($this, $cache);
	}
	
	
	
}
	
class DatabaseResult implements DatabaseResultInterface {
	
	/**
	 * The SQL query run (may be a string or an instance of DatabaseQuery)
	 *
	 * @var DatabaseQuery
	 * @access public
	 */
	public $query;
	
	/**
	 * Time in seconds to run the query
	 *
	 * @var float
	 * @access public
	 */
	public $query_time;
	
	/**
	 * The SQL query run to get this result
	 *
	 * @var string
	 */
	public $statement;

	/**
	 * @var DatabaseConnection
	 */
	private $connection;

	/**
	 * MySQLi query resource handle
	 *
	 * @var resource
	 * @access private
	 */
	public $qh;
	
	/**
	 * Last insert ID
	 *
	 * @var string
	 * @access private
	 */
	private $insert_id = FALSE;
	
	/**
	 * Number of rows in the result set
	 *
	 * @var integer
	 * @access private
	 */
	private $num_rows = 0;
	
	/**
	 * Number of rows affected by the query (applies to update/delete queries)
	 *
	 * @var integer
	 * @access private
	 */
	private $affected_rows = FALSE;
	
	/**
	 * Database resultset, object format
	 *
	 * @var array
	 * @access private
	 */
	private $result = FALSE;
	
	/**
	 * Database resultset, array format
	 *
	 * @var array
	 * @access private
	 */
	private $result_array = FALSE;
	
	/**
	 * Database resultset, model format
	 *
	 * @var array
	 * @access private
	 */
	private $objects = FALSE;
	
	/**
	 * Database resultset, html format
	 *
	 * @var array
	 * @access private
	 */
	private $table = FALSE;


	/**
	 * History of all queries
	 *
	 * @var array
	 * @access private
	 */
	public static $history = [];

	/**
	 * Construct a DatabaseResult object
	 *
	 * @param DatabaseConnection $connection
	 * @param mysqli_result $qh
	 * @param string $sql
	 * @param float $time
	 */
	public function __construct(DatabaseConnection $connection, $qh, $sql = NULL, $time = NULL) {

		$this->connection = $connection;
		$this->qh = $qh;
		$this->query_time = $time;
		$this->query = $sql;
		$this->statement = (string) $sql;

		self::$history[] = [$sql, $time];

		if($qh !== FALSE) {
			$this->num_rows = @mysqli_num_rows($this->qh);
			$this->affected_rows = @mysqli_affected_rows($connection->connection());
			$this->insert_id = @mysqli_insert_id($connection->connection());
		}

	}


	/**
	 * Return the query results as an array of rows in object format
	 *
	 * @return array
	 * @access public
	 */
	public function result($index = NULL) {
		
		# fetch the entire query resultset if it hasn't yet been done
		if($this->qh && $this->result === FALSE) {
		
			$this->result = array();

			while($row = mysqli_fetch_object($this->qh)) {
				$this->result[] = $row;
			}
			
			reset($this->result);
		}
		
		# return the resultset
		if(!is_null($index)) {

			# with a special array key
			$result = array();
			foreach($this->result as $row) {
				$result[$row->$index] = $row;
			}

			return $result;

		} else {

			# or just the plain 'ol data
			return $this->result;

		}

	}
	
	
	/**
	 * Return the query results as an array of rows in associative array format
	 *
	 * @return array
	 * @access public
	 */
	public function result_array($index = NULL, $value = NULL) {
		
		if($this->qh) {
			$this->result();
		}
		
		$i = 0;
		$result = array();
		foreach($this->result AS $row) {
			$ndx = ($index === NULL)? $i : $row->$index;
			$val = ($value === NULL)? (array) $row : $row->$value;
				$result[$ndx] = $val;
				$i++;
			}
			
		return $result;
		
	}
	
	
	/**
	 * Return the query results as an array of CSV data
	 *
	 * @return array
	 * @access public
	 */
	public function csv($delimiter = ',', $qualifier = '"', $linebreak = "\n", $add_column_names = TRUE) {
		
		$this->result_csv = '';

		if($this->qh && $this->result_array == FALSE) {
			$this->result_csv = DatabaseResult::array_to_csv($this->result_array(), $delimiter, $qualifier, $linebreak, $add_column_names);
		}
		
		return $this->result_csv;
	}
	
	
	public static function array_to_csv($array, $delimiter = ',', $qualifier = '"', $linebreak = "\n", $add_column_names = TRUE) {
		
		$result_csv = '';

		if(count($array) > 0) {
		
			$i = 0;
			foreach($array as $row) {
				
				if($add_column_names == TRUE && $i == 0) {
					$result_csv .= $qualifier.implode($qualifier.$delimiter.$qualifier, array_keys($row)).$qualifier.$linebreak;
				}
				
				if(count($row) > 0) {
					$result_csv .= $qualifier.implode($qualifier.$delimiter.$qualifier, $row).$qualifier.$linebreak;
				}
				
				$i++;
			}
			
		}
		
		return $result_csv;
		
	}
	
	
	/**
	 * Return a basic HTML table with the results
	 *
	 * @author Jake A. Smith <jake@company52.com>
	 * @return string
	 * @access public
	 */
	public function table() {
		
		if($this->qh && $this->table == FALSE) {
			
			$i = 0;
			$t = &$this->table;
			
			$t = '<table>';
			// Loop thru all the results
			foreach($this->result_array() as $row) {
				
				# Col names
				if($i == 0) {
					$t .= '<thead><tr>';
					foreach($row as $col => $val) $t .= '<th>'. $col .'</th>';
					$t .= '</tr></thead>';
				}
				
				
				$t .= '<tr>';
				foreach($row as $val) $t .= '<td>'. htmlentities($val) .'</td>';
				$t .= '</tr>';
				
				$i++;
			}
			
			$t .= '</table>';
		}
		
		return $this->table;
	}
	
	
	/**
	 * Return a basic ASCII table with the results
	 *
	 * @author Jonathon Hill <jhill@company52.com>
	 * @return string
	 * @access public
	 */
	public function ascii_table() {
		
		if($this->qh && $this->ascii_table == FALSE) {
			
			$i = 0;
			$t = &$this->ascii_table;
			
			$rows = $this->result_array();
		
			//first get your sizes
			$sizes = array();
			
			foreach($rows[0] as $key=>$value){
			    $sizes[$key] = strlen($key); //initialize to the size of the column name
			}
			
			for($i = 1; $i < count($rows); $i++){
				foreach($rows[$i] as $key=>$value){
				    $length = strlen($value);
				    if($length > $sizes[$key]) $sizes[$key] = $length; // get largest result size
				}
			}
			
			//top of output
			foreach($sizes as $length){
			    $t .= "+".str_pad("",$length+2,"-");
			}
			$t .= "+\n";
			
			// column names
			foreach($rows[0] as $key=>$value){
			    $t .= "| ";
			    $t .= str_pad($key,$sizes[$key]+1);
			}
			$t .= "|\n";
			
			//line under column names
			foreach($sizes as $length){
			    $t .= "+".str_pad("",$length+2,"-");
			}
			$t .= "+\n";
			
			//output data
			for($i = 1; $i < count($rows); $i++) {
			    foreach($rows[$i] as $key=>$value){
			        $t .= "| ";
			        $t .= str_pad($value,$sizes[$key]+1);
			    }
			    $t .= "|\n";
			}
			
			//bottom of output
			foreach($sizes as $length){
			    $t .= "+".str_pad("",$length+2,"-");
			}
			$t .= "+\n";
		}
		
		return $this->ascii_table;
	}
	
	
	/**
	 * Return an array of model objects from the result
	 *
	 * @param string $class
	 * @param string $pk
	 * @param string $index
	 * @return array
	 * @access public
	 */
	public function objects($class, $pk = NULL, $index = NULL, $subobject = NULL) {
		
		if($this->qh && $this->objects == FALSE) {
			
			$i = 0;
			$this->objects = array();
			foreach($this->result_array() as $row) {
				$var = ($pk === NULL)? $row : $row[$pk];
				$ndx = ($index === NULL)? $i : $row[$index];
				$this->objects[$ndx] = new $class($var, $subobject);
				$i++;
			}
			
		}
		
		return $this->objects;
		
	}
	
	
	/**
	 * Return a model object from a row
	 *
	 * @param string $class
	 * @param string $pk
	 * @return array
	 * @access public
	 */
	public function object($class, $pk = NULL, $subobject = NULL) {
		$row = $this->row();
		return ($row !== FALSE)? new $class((array) $row, $subobject) : FALSE;
	}
	
	
	/**
	 * Return a single row in object format
	 *
	 * @return object
	 * @access public
	 */
	public function row() {

		if($this->qh) {

			if($this->num_rows() > 0 && $this->result && count($this->result) == $this->num_rows) {
				$this->qh = FALSE;
				return $this->row();
			}
	
			$row = mysqli_fetch_object($this->qh);
			if($row !== FALSE) {
				$this->result[] = $row;
			}

		} else {

			$row = current($this->result);

		}

		return $row;
	}

	
	/**
	 * Return a single row in associative array format
	 *
	 * @return array
	 * @access public
	 */
	function row_array() {
		$row = $this->row();
		return ($row !== FALSE)? (array) $row : FALSE;
	}
	
	
	/**
	 * Get the number of rows the query returned
	 *
	 * @return integer
	 * @access public
	 */
	public function num_rows() {
		return $this->num_rows;
	}
	
	
	/**
	 * Get the number of rows affected by this query (applies to UPDATE, DELETE, etc)
	 *
	 * @return integer
	 * @access public
	 */
	public function affected_rows() {
		return $this->affected_rows;
	}


	/**
	 * Returns TRUE if the query didn't return any rows
	 *
	 * @return boolean
	 * @access public
	 */
	public function null_set() {
		return ($this->num_rows() < 1);
	}


	/**
	 * Get the ID from the last INSERT operation
	 *
	 * @return int
	 * @access public
	 */
	public function insert_id() {
		return $this->insert_id;
	}


	public function filter() {
		
		$result = $this->result();
		$filters = func_get_args();
		$filtered_result = array();
		
		foreach($filters as $filter) {
			foreach((array) $result as $row) {
				
				$comparison = str_replace(array('{row}'), array('$row'), "return ($filter);");
				if(!check_syntax($comparison)) {
					throw new ErrorException("Invalid filter passed to DatabaseResult::filter(). Must be valid PHP code: $comparison");
				}
				
				if(eval($comparison) == TRUE) {
					$filtered_result[] = $row;
				}
			}
		}
		
		return $filtered_result;
	}
	
	
	public function sum($col, $filter = NULL){
		
		$resultset = ($filter == NULL)? $this->result() : $this->filter($filter);
		
		$sum = 0;
		foreach((array) $resultset as $row){
			$sum += $row->$col;
		}
		
		return $sum;
		
	}
	
	
	public function values($col, $distinct = TRUE, $filter = NULL){
		
		$resultset = ($filter == NULL)? $this->result() : $this->filter($filter);
		
		$values = array();
		foreach((array) $resultset as $row){
			if($distinct){
				$values[$row->$col] = $row->$col;
			} else{
				$values[] = $row->$col;
			}
		}
		
		return array_values($values);
	}
	
	public function values_assoc($kcol, $vcol, $filter = NULL) {
		
		$resultset = ($filter == NULL)? $this->result() : $this->filter($filter);
		
		$kcol = trim(array_pop(explode('.', $kcol)), '`\'" ');
		
		$values = array();
		foreach((array) $resultset as $row){
			$values[$row->$kcol] = $row->$vcol;
		}
		
		return $values;
	}
	
	
	public function avg($col, $filter = NULL){
		
		$resultset = ($filter == NULL)? $this->result() : $this->filter($filter);
		
		$sum = 0;
		foreach((array) $resultset as $i => $row){
			$sum += $row->$col;
		}
		
		return $sum / ($i + 1);
		
	}
}


interface DatabaseConnectionInterface{
	public function __construct($host = '', $user = '', $pass = '', $db = '', $debug = TRUE, $persist = TRUE);
	public function __destruct();
	public function connection();
	public function execute($sql);
	public function escape($value, $options = NULL);
	public function last_query();
	public function error_code();
	public function error_msg();
	public function handle_error($exit = TRUE);
}

interface DatabaseQueryInterface{
	public function __construct(DatabaseConnection $connection, $table = NULL, $statement = NULL);
	public function __toString();
	public function first();
	public function set();
	public function select();
	public function where();
	public function raw_where();
	public function join($table, $conditions, $left = FALSE);
	public function group_by();
	public function order_by();
	public function limit($limit, $offset = NULL);
	public function set_table($table = NULL);
	public function set_statement($st = NULL);
	public function escape($value, $options = NULL);
	public function run();
}

interface DatabaseResultInterface{
	public function __construct(DatabaseConnection $connection, $qh, $sql = NULL, $time = NULL);
	public function result();
	public function result_array();
	public function objects($class, $pk = NULL);
	public function csv($delimiter = ',', $qualifier = '"', $linebreak = "\n", $add_column_names = TRUE);
	public function table();
	public function row();
	public function row_array();
	public function num_rows();
	public function affected_rows();
	public function null_set();
	public function insert_id();
}


/**
 * Shortcut to DatabaseConnection::factory()
 *
 * @param string $name = NULL
 * @param array $connect = FALSE
 * @param boolean $default = TRUE
 * @return DatabaseConnection
 */
function database($name = NULL, $connect = FALSE, $default = TRUE){
	return DatabaseConnection::factory($name, $connect, $default);
}


if(!function_exists('check_syntax')){
	function check_syntax($code){
		$good = @eval('return TRUE; '.$code);
		return $good;
	}
}

