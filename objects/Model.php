<?php

/**
* Model
*
* This class is for extending and holds a lot of the default manipulation functions
*
* @author "David Boskovic" <dboskovic@companyfiftytwo.com>
* @version 1.0
* @package Core52
* @copyright Company Fifty Two, 2 April, 2009
*
*/

abstract class Model {
	
	protected $_database = 'default';
	protected $_pk = 'id';
	protected $_subobjects;
	protected $_null_fields = array();
	
	/**
	 * Stores View object
	 *
	 * @var View
	 */
	protected $_view;
	
	/**
	 * Stores View_Data object
	 *
	 * @var View_Data
	 */
	protected $_vdata;
	
	/**
	 * Enables caching of constructor queries and result sets
	 *
	 * @var boolean
	 */
	protected $cache = TRUE;
	
	/**
	 * This property defines the foreign keys that this model's table relates to
	 *
	 * @example
	 * 	array(
	 * 		'ForeignTable' => array('_on' => 'foreign_primary_id', 'type', 'amount'),
	 * 	);
	 * @var array()
	 */
	protected $_joinmap = array();
	
	private static $disable_subobjects = FALSE;
	

	public function __construct($var = NULL, $subobjects = NULL) {
		$this->_subobjects = $subobjects;
	
		// process straight through if we were given a valid array
		if(is_array($var)) {
		
			// apply keys to object
			$this->_apply_keys($var);
			
			if ($this->exists() && !Model::$disable_subobjects && ($this->_subobjects || $this->_subobjects === NULL)) {
				$this->_subobjects();
				return TRUE;
			}
			else {
				return FALSE;
			}
		}
		
		return $this->_load_data($var);

	}
	
	protected function _load_data($var = NULL){
	
		// return false if var is invalid or not supplied.
		if($var === NULL) {
			return false;
		}
		
		// lookup by slug
		elseif($this->_slug && !is_numeric($var)) {
			$query = (string) $this->db()
				->start_query($this->_table)
				->where($this->_slug, $var)
				->limit(1);
		}
		
		// lookup by ID, with custom query
		elseif($this->custom_query) {
			$query = $this->custom_query($var, $this->custom_query);
		}

		// lookup by ID, with default query
		else {
			$query = (string) $this->db()
				->start_query($this->_table)
				->where($this->_pk, $var)
				->limit(1);
		}
		
		$result = $this->db()->execute($query, $this->cache);
		if(!$result || $result->null_set()) {
			return FALSE;
		}


		// apply keys to object
		$this->_apply_keys($result->row_array());
		
		if($this->exists() && !Model::$disable_subobjects) {
			$this->_subobjects();
		}

		return TRUE;
	}
	
	public function reload_nocache($var = NULL) {
		if(is_null($var)) {
			$var = $this->pk();
		}

		$this->cache = FALSE;
		$this->_load_data($var);
	}

	public function __clone() {
		foreach($this->__properties() as $property => $value) {
			$this->$property = deep_clone($this->$property);
		}
	}

	public function __properties() {
		return (array) get_object_vars($this);
	}

	public function __toString() {
		return get_class($this).' '.$this->pk();
	}

	/**
	 * Fetches the database connection
	 *
	 * @return DatabaseConnection
	 */
	public function db($database = false) {
		return DatabaseConnection::factory($database ? $database : $this->_database);
	}
	
	public function pk() {
		return $this->{$this->_pk};
	}
	
	public function pk_col() {
		return $this->_pk;
	}
	
	public function table() {
		return $this->_table;
	}
	
	public function toArray($include_subobjects = FALSE) {
		return object_to_array($this, FALSE, $include_subobjects);
	}
	
	public function exists() {
		return ($this->{$this->_pk} !== NULL) ? true : false;
	}

	protected function _apply_keys($array) {
		if(!is_object($array) && !is_array($array)) {
			throw new InvalidArgumentException('$array must either be an object or an array');
		}
		foreach((array) $array as $key => $value) {
			if(substr($key, 0, 1) == '_' && $key != '_clearance') {
				$value = @unserialize($value);
			}
			trim($key);
			$this->$key = $value;
		}
	}
	
	protected function custom_query($id, $query) {
		return str_replace('_ID_', $this->db()->escape($id, $this->db()->get_escape_options() | DatabaseConnection::ESCAPE_QUOTE), $query);
	}
	
	protected function _subobjects() {}
	
	public function setIfChanged($key, $value, $save = TRUE, $condition = 'return($this->$key != $value);') {
		if(eval($condition) == TRUE) {
			if($save == TRUE) {
				$this->save($key, $value);
			} else {
				$this->$key = $value;
			}
		}
	}

	
	protected function transform(array $data) {
		foreach($this->_null_fields as $field) {
			if(isset($data[$field]) && empty($data[$field])) {
				$data[$field] = NULL;
			}
		}
		return $data;
	}
	
	
	public function save($key = false, $value = false, $run_subobjects = TRUE) {
		
		// make sure we have a key
		if($key == false) {
			return false;
		}
		
		// if it's not an array, make it one
		if(!is_array($key)) {
			$key = array($key => $value);
		}
		
		// preprocess data
		$key = $this->transform($key);
		
		// save the items in this object
		foreach($key as $k => $v) {
			$this->$k = $v;
		}
		
		// save the items in the database
		$fields = $this->_filter($key);
		if(count($fields) > 0) {
			$pk = $this->_pk;
			$pkv = $this->$pk;
			
			if(isset($this->$pk)) {
				// PK specified
				$query = $this->db()->start_query($this->_table)->where(array($pk => $pkv))->run();
				if(!$query->null_set()) {
					// Row exists in database
					$this->db()->start_query($this->_table, 'UPDATE')->set($fields)->where(array($pk => $pkv))->run();
				} else {
					// Row does not exist
					$fields[$pk] = $pkv;
					$id = $this->db()->start_query($this->_table, 'INSERT')->set($fields)->run()->insert_id();
					if($id) {
						$this->$pk = $id;
					}
				}
			} else {
				$id = $this->db()->start_query($this->_table, 'INSERT')->set($fields)->run()->insert_id();
				if($id) {
					$this->$pk = $id;
				}
			}
		}
		
		if(isset($this->_joinmap)) {
			foreach((array) $this->_joinmap as $tn => $table) {
				$on = $table['_on'];
				$onv = $this->$on;
				if(!$onv) break;
				unset($table['_on']);
				$table = array_flip($table);
				
				$items = array_intersect_key($key, $table);
				
				$fields = $this->_filter($items, $tn);
				if(count($fields) > 0) {
					if(!$this->db()->start_query($tn)->where(array($on => $onv))->limit(1)->run()->null_set()) {
						$this->db()->start_query($tn, 'UPDATE')->set($fields)->where(array($on => $onv))->run();
					} else {
						$fields[$on] = $onv;
						$this->db()->start_query($tn, 'REPLACE')->set($fields)->run();
					}
				}
			}
		}
		
		if($run_subobjects) {
			$this->_subobjects();
		}
		
		return TRUE;
	}
	
	
	public function delete() {
		$this->db()->start_query($this->_table, 'DELETE')->where($this->_pk, $this->pk())->run();
		return NULL;
	}
	
	public function checksum() {
		$class = get_class($this);
		$obj = new $class($this->pk());
		return md5(serialize($obj));
	}
	
	public static function create($class, $data) {
		$obj = new $class();
		$obj->save($data);
		return $obj;
	}

	// filter the fields down to what we can actually store in the database
	protected function _filter($key, $table = NULL) {
		$table = ($table == NULL)? $this->_table : $table;
		$query = $this->db()->execute("SHOW COLUMNS FROM `$table`");
		$fields = array();
		foreach($query->result() as $col) {
			if(array_key_exists($col->Field, $key)) {
				$fields[$col->Field] = $key[$col->Field];
			}
		}
		return $fields;
	}

	public static function disable_subobjects() {
		self::$disable_subobjects = TRUE;
	}

	public static function enable_subobjects() {
		self::$disable_subobjects = FALSE;
	}

	public function textile($col, $restrict = FALSE) {
		$this->$col = input_textile($this->$col, $restrict);
		return $this->$col;
	}
	

	public function form_fields(array $exclude = array(), array $override = array(), $check_properties = FALSE) {
		
		$form_fields = array();
		
		foreach($this->db()->execute("DESCRIBE $this->_table", TRUE)->result() as $row) {
			
			# certain fields should be skipped (such as the PK)
			if($row->Field == $this->_pk || in_array($row->Field, $exclude)) {
				continue;
			}
			
			# skip fields that do not have corresponding properties
			if($check_properties && !isset($this->{$row->Field})) {
				continue;
			}
			
			
			# set defaults
			$name = $row->Field;
			$rules = array();
			$type = 'text';
			$values = array();
			$default = ($this->{$row->Field})? $this->{$row->Field} : $row->Default;
			$attributes = array();
			
			
			# column type/length - i.e. "VARCHAR(15)"
			$ctype = rtrim($row->Type, ')');
			list($ctype, $clength) = explode('(', $ctype);
			$ctype = strtolower($ctype);
			
			
			# deduce the needed rules
			if($row->Null !== 'YES') {
				# NOT NULL
				$rules[] = 'required';
			}
			if(stristr($row->Field, 'email') !== FALSE) {
				# email field
				$rules[] = 'valid_email';
			}
			if(stristr($row->Field, 'phone') !== FALSE || stristr($row->Field, 'fax') !== FALSE || stristr($row->Field, 'mobile') !== FALSE || stristr($row->Field, 'cell') !== FALSE) {
				# phone field
				$rules[] = 'valid_phone';
			}
			if(is_numeric($clength)) {
				# maximum length set
				$rules[] = "max_length[$clength]";
				$attributes['maxlength'] = $clength;
			}

			# deduce the field type
			if($ctype === 'enum') {
				# dropdown with ENUM() options
				$type = 'select';
				$rules[] = 'values';
				$values = csv2array($clength);
			}
			elseif(stristr($row->Field, 'password') !== FALSE) {
				# password field (minimum 8 characters)
				$type = 'password';
				$rules[] = 'min_length[8]';
			}
			elseif(stristr($ctype, 'text')) {
				# TEXT, MEDIUMTEXT, LONGTEXT are textareas
				$type = 'textarea';
			}
			elseif(stristr($ctype, 'blob') || stristr($row->Field, 'file') !== FALSE || stristr($row->Field, 'image') !== FALSE) {
				# BLOB, MEDIUMBLOB, LONGBLOB are file uploads, as well as anything containing file or image
				$type = 'upload';
			}
			elseif(stristr($ctype, 'date') || $ctype == 'TIMESTAMP') {
				# DATE, DATETIME, TIMESTAMP
				$rules[] = 'valid_date';
			}
			
			# values
			if(is_array($values) && count($values) > 0) {
				$values = array_combine($values, $values);
			}

			# after values, so we can control them directly
			if($ctype == 'tinyint' && $clength == 1) {
				# TINYINT with 1 is a checkbox
				$type = 'checkbox';
				$values = array(1 => '');
			}
			
			$field = array(
				'name'		=> $name,
				'default'	=> $default,
				'rules'		=> (isset($override[$row->Field]['rules']))? $override[$row->Field]['rules'] : implode('|', $rules),
				'type'		=> (isset($override[$row->Field]['type']))? $override[$row->Field]['type'] : $type,
				'values'	=> (isset($override[$row->Field]['values']))? $override[$row->Field]['values'] : $values,
				'attributes'=> $attributes,
			);
			
			$form_fields[$name] = $field;
		}
		
		return array_merge_recursive_keys($form_fields, (array) $override);
	}
	
	
	protected function _get_field_rules($field) {
		

	}
	
	
	protected function _get_field_type($field) {
		
		
	}

	
	public static function find($class, $where = array(), $limit = array(), $sort = array(), $key = FALSE, $cache = TRUE) {
		
		if(is_array($class)) extract($class);
		
		$obj = new $class;
		$key = (empty($key))? $obj->pk_col() : $key;
		
		# $where was passed as a string, process as raw_where()
		if(is_string($where)) {
			return $obj->db()->start_query($obj->table())
				->raw_where($where)
				->limit($limit[0], $limit[1])
				->order_by($sort)
				->run($cache)
					->objects($class, NULL, $key);
		}
		
		# $where was passed as a array, process as where()
		else {
			return $obj->db()->start_query($obj->table())
				->where($where)
				->limit($limit[0], $limit[1])
				->order_by($sort)
				->run($cache)
					->objects($class, NULL, $key);
		}
	}
	
	
	public function get_ref($use = 'class') {
		return ($use == 'table')? $this->_table.':'.$this->pk() : get_class($this).':'.$this->pk();
	}
	
	
	public static function findByRef($ref) {
		list($class, $id) = explode(':', $ref);
		if(class_exists($class)) {
			return new $class($id);
		} else {
			throw new InvalidArgumentException("Invalid ref string: $ref");
		}
	}
	
	
}