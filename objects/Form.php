<?php

# Form helper functions from CodeIgniter
require_once(PATH_CORE .'helpers/form.php');


/**
 * Form class - simplify form handling
 *
 * @author Jonathon Hill <jonathon@compwright.com>
 * @package Core52
 * @version 1.1
 *
 * Usage example:
 *
 * 		$form = new Form('post');				// new form using post method
 * 		$form->add_rule('name', 'required');
 * 		$form->add_filter('name', 'htmlentities');
 * 		$form->set_default('name', 'Your Name Please');
 * 		$form->set_label('name', 'Your Name');	// name of field used for
 *
 * 		$form->add_rules($rules);				// alternately you can use arrays
 * 		$form->add_filters($filters);
 * 		$form->set_defaults($defaults);
 *
 * 		if($form->validate()) {
 * 			echo 'Your name is: '.$form->field('name', 'xss');	// arg 2 enables xss filtering
 * 		} else {
 * 			echo 'Errors: '.$form->error_message();
 * 		}
 *
 *
 * Validation rules:
 *
 * 		required, required_if[field], required_if[field=value], valid_email, valid_ip, min_length[n], max_length[n],
 * 		exact_length[n], alpha, alpha_numeric, alpha_dash, numeric, numeric_dash, integer, matches[field], values, exact_value
 *
 */
class Form {
	
	/**
	 * Form submission method (get or post)
	 */
	public $method;


	/**
	 * Internal data storage arrays
	 */
	public $rules = array();		// validation rules (pipe-delimited)
	public $filters = array();		// names of callback functions used to filter form data before validation
	public $defaults = array();	// default field values
	public $labels = array();		// field descriptive names
	public $input = array();		// submitted form data
	public $types = array();		// form field types
	public $values = array();		// form field value options
	public $attributes = array();	// form field attributes

	
	/**
	 * Array of error messages
	 * 	'form field name' => 'error message'
	 */
	public $errors = array();


	/**
	 * Error messages - can be modified using Form::set_error_message()
	 */
	private $error_messages = array(
		'required' 		=> "%s is required.",
		'required_if'	=> "%s is required.",
		'required_unless'=>"%s is required.",
		'required_file'	=> "Error: %s",
		'isset'			=> "%s must have a value.",
		'valid_file'	=> "%s must be a file.",
		'valid_email'	=> "%s is not a valid email address.",
		'valid_phone'	=> '%s is not a valid phone number (xxx-xxx-xxxx).',
		'valid_url'		=> "%s must contain a valid URL.",
		'valid_ip' 		=> "%s must contain a valid IP.",
		'valid_password'=> "%s must be %s or more characters long and have at least one number or symbol.",
		'valid_username'=> "%s can only contain letters, numbers, and dash, underscore, or period.",
		'valid_day'		=> "%s is invalid.",
		'valid_month'	=> "%s is invalid.",
		'valid_year'	=> "%s is invalid.",
		'valid_date'	=> "%s is not a valid date",
		'valid_time'	=> "%s is not a valid time (HH:MM:SS AM/PM)",
		'after'			=> "%s must come after %s",
		'on_or_after'	=> "%s must come on or after %s",
		'valid_creditcard_type' => "%s is invalid.",
		'valid_creditcard_exp' => "%s is invalid or expired (MMYY)",
		'valid_creditcard' => "%s is invalid.",
		'valid_model'	=> "%s is invalid.",
		'valid_image'	=> "%s is not a valid image, or the image is corrupt",
		'filetype'		=> "Incorrect filetype for %s",
		'min_length'	=> "%s must be at least %s characters in length.",
		'max_length'	=> "%s can not exceed %s characters in length.",
		'exact_length'	=> "%s must be exactly %s characters in length.",
		'min_value'		=> "%s cannot be less than %s.",
		'max_value'		=> "%s cannot exceed %s.",
		'exact_value'	=> "%s is invalid.",
		'alpha'			=> "%s may only contain alphabetical characters.",
		'alpha_numeric'	=> "%s may only contain alpha-numeric characters.",
		'alpha_numeric_space'	=> "%s may only contain alpha-numeric characters.",
		'alpha_dash'	=> "%s may only contain alpha-numeric characters, underscores, and dashes.",
		'alpha_dash_dot'=> "%s may only contain alpha-numeric characters, underscores, dashes, and perods (.)",
		'numeric'		=> "%s must contain a number.",
		'numeric_dash'	=> "%s may only contain numbers and dashes.",
		'integer'		=> "%s must contain an integer.",
		'boolean'		=> "%s must be a boolean value (0 or 1)",
		'matches'		=> "%s does not match the %s field.",
		'values'		=> "%s is invalid.",
		'valid_zip'		=> "%s must be a valid 5- or 9-digit zip code",
	);
	
	private $upload_error_messages = array(
	    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
	    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
	    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
	    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
	    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
	    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
	    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
	);
	
	
	public $triggerfield = 'form_submitted';
	
	/**
	 * Error message template - can be set to either a string or a template filename
	 */
	public $error_template = '<ul class="error"><!--LOOP errors AS message--><li>{message}</li><!--END LOOP errors--></ul>';
	public $wrap_field_error = '<span class="error {field}">{message}</span>';
	
	
	
	/**
	 * Initiate the form object, load the form data and sanitize
	 * @return null
	 * @param $method String[optional]
	 * @param $data String[optional]
	 * @param $sanitize Boolean[optional]
	 */
	public function __construct($method = 'post', $data = NULL, $sanitize = 'xss') {
		$this->set_method($method, $data, $sanitize);
	}
	
	
	public function set_method($method, $data = NULL, $sanitize = 'xss') {
		// form method (get|post)
		$this->method = $method;

		// input data
		if(is_array($data)) {
			$this->input = $data;
		}
		elseif($method == 'post') {
			$this->input = $_POST;
		}
		elseif($method == 'get') {
			$this->input = $_GET;
		}
		elseif($method == 'request') {
			$this->input = $_REQUEST;
		}

		// sanitize input data
		if ($sanitize != false) {
			$this->sanitize(NULL, $sanitize);
		}
	}
	
	
	/**
	 * Sanitize form data
	 * @return null
	 * @param $fields Mixed[optional]
	 * @param $threat String
	 */
	public function sanitize($fields = null, $threat)
	{
		if(is_null($fields)) {
			$this->input = $this->clean($threat, $this->input);
		}
		elseif(is_array($fields)) {
			foreach($fields as $field => $val) {
				$this->input[$field] = $this->clean($threat, $this->input[$field]);
			}
		}
	}
	
	
	private function clean($threat = 'xss|sql', $value = NULL)
	{
		# get threats to filter against
		$filter = explode('|', strtolower($threat));
		
		# normalize to an array
		if(!is_array($value)) {
			$value = array($value);
			$pop = TRUE;
		}

		# every variable...
		foreach($value as &$v) {
			
			# recurse multi-dimensional arrays...
			if(is_array($v)) {
				$v = $this->clean($threat, $v);
				continue;
			}
		
			# every threat...
			foreach($filter as $f) {
				switch($f) {
					case 'sql': $v = Database::escape($v, Database::c()->get_escape_options() | DatabaseConnection::ESCAPE_QUOTE); break;
					case 'xss': $v = strip_tags($v); break;
				}
			}
		}
	
		# return single item or array
		return ($pop)? array_pop($value) : $value;
	}
	
	/**
	 * Add a associative array of field attributes to this form
	 * Valid array keys include:
	 *    rules, filters, default, label, type, values, attributes
	 *
	 * @param array $field
	 */
	public function add_field($field)
	{
		$map = array(
			'rules' => 'rules',
			'filters' => 'filters',
			'default' => 'defaults',
			'label' => 'labels',
			'type' => 'types',
			'values' => 'values',
			'attributes' => 'attributes',
		);
		
		if($field['field']) {
			$name = $field['field'];
			unset($field['field']);
		} else {
			$name = $field['name'];
			unset($field['name']);
		}
		
		foreach($map as $key => $array) {
			if(isset($field[$key])) {
				if($key == 'values') {
					$this->set_value($name, $field[$key]);
				} else {
					$this->{$array}[$name] = $field[$key];
				}
				unset($field[$key]);
			}
		}
		
		if(count($field) > 0) {
			$this->attributes[$name] = (array) $this->attributes[$name] + $field;
		}
	}
	
	
	public function remove_field($field) {
		unset($this->rules[$field]);
		unset($this->filters[$field]);
		unset($this->default[$field]);
		unset($this->label[$field]);
		unset($this->types[$field]);
		unset($this->values[$field]);
		unset($this->attributes[$field]);
	}
	
	
	
	public function clear() {
		$this->values = array();
		$this->input = array();
	}
	
	/**
	 * Add multiple fields to this form
	 * Takes an array of associative arrays for add_field()
	 *
	 * @param array $fields
	 */
	public function add_fields($fields)
	{
		foreach($fields as $field) {
			$this->add_field($field);
		}
	}
	
	/**
	 * Adds data from a model as the default values for a form
	 *
	 * @param object $model
	 * @return true if any values merged, else false
	 */
	public function add_model_values(Model $model){
		
		$data_added = false;
		
		#Loop through all fields and see if there are any matches in the model
		foreach($this->rules as $input_name => &$val){
			
			#If key exists in model
			if(!empty($model->$input_name)){
				if(isset($attributes[$input_name]['value']))
					$this->attributes[$input_name] = array('value' => $model->$input_name);
				else
					$this->attributes[$input_name]['value'] = $model->$input_name;
				$data_added = true;
			}
			
		}
		return $data_added;
	}
	
	
	/**
	 * Add a validation rule
	 * @return null
	 * @param $field String
	 * @param $rule_string String
	 */
	public function add_rule($field, $rule_string)
	{
		$this->rules[$field] = (strlen($this->rules[$field]) > 0)?
			rtrim($this->rules[$field], '|').'|'.$rule_string :
			$rule_string;
	}
	
	
	/**
	 * Add a filter callback to a field
	 * @return null
	 * @param $field String
	 * @param $filter String
	 */
	public function add_filter($field, $filter)
	{
		$this->filters[$field] = $filter;
	}
	
	
	/**
	 * Specify a default field value
	 * @return null
	 * @param $field String
	 * @param $default_value Mixed
	 */
	public function set_default($field, $default_value)
	{
		$this->defaults[$field] = $default_value;
	}

	
	/**
	 * Specify a descriptive name for a form field
	 * @return null
	 * @param $field String
	 * @param $label String
	 */
	public function set_label($field, $label)
	{
		$this->labels[$field] = $label;
	}
	
	
	/**
	 * Specify a form field type
	 * @return null
	 * @param $field String
	 * @param $label String
	 * @param optional $values Array
	 */
	public function set_type($field, $type, $values = FALSE)
	{
		$this->types[$field] = $type;
		if($values !== FALSE) $this->values[$field] = $values;
	}
	
	
	/**
	 * Specify value options for a form field
	 * @return null
	 * @param $field String
	 * @param $label String
	 */
	public function set_value($field, $options)
	{
		if(is_string($options)) {
			
			list($table, $index, $value, $where, $order) = explode('|', $options);
			$options = array();
			
			# SELECT
			$table = trim($table);
			if(substr($table, 0, 1) === '+') {
				$table = ltrim($table, '+');
				$options[''] = '';
			}
			$query = database()->start_query($table)->select(
				$this->_csv_to_concat(trim($index), $table),
				$this->_csv_to_concat(trim($value), $table)
			);
			
			# WHERE
			$where = trim($where);
			if(!empty($where)) $query->raw_where($where);
			
			# ORDER BY
			$order = trim($order);
			if(!empty($order)) $query->order_by($order);
			
			# run the query
			$result = $query->run(FALSE); // don't cache
			while($data = mysql_fetch_array($result->qh, MYSQL_NUM)) {
				if(count($data) > 2) {
					throw new InvalidArgumentException("More than two columns were selected for the $field values, please check your shorthand syntax");
				}
				$options[$data[0]] = $data[1];
			}
		}
		
		$this->values[$field] = $options;
	}
	
	protected function _csv_to_concat($csv, $table) {
		# is it not a single column?
		if(preg_match('/[, \'"]/', $csv)) {
			# it is not a SQL SELECT clause already?
			if(!preg_match('/in|concat|[()]| as /i', $csv)) {
				# turn CSV shorthand into a CONCAT()
				$table_fields = database()->columns($table);
				$values = str_getcsv($csv, ',');
				foreach($values as &$v) {
					if(!array_key_exists($v, $table_fields)) {
						$v = sprintf('"%s"', $v); // quote anything that isn't a column name
					}
				}
				return sprintf('CONCAT(%s) AS index_column', implode(', ', $values));
			}
		}
		return $csv;
	}
	
	
	/**
	 * Specify attributes for a form field
	 * @return null
	 * @param $field String
	 * @param $label String
	 */
	public function set_attributes($field, $options)
	{
		$this->attributes[$field] = $options;
	}


	/**
	 * Bulk-add validation rules
	 * array(
	 * 	'field name' => 'rules',
	 * 	...
	 * )
	 *
	 * @return null
	 * @param $rules Array
	 */
	public function add_rules($rules)
	{
		foreach($rules as $field => $rule_string) $this->add_rule($field, $rule_string);
	}
	
	
	/**
	 * Bulk-add filters
	 * array(
	 * 	'field name' => 'callback',
	 *  ...
	 * )
	 *
	 * @return null
	 * @param $filters Array
	 */
	public function add_filters($filters)
	{
		foreach($filters as $field => $filter) $this->add_filter($field, $filter);
	}
	
	
	/**
	 * Bulk-add field default values
	 * array(
	 * 	'field name' => 'default value',
	 * 	...
	 * )
	 *
	 * @return null
	 * @param $defaults Array
	 */
	public function set_defaults($defaults)
	{
		foreach($defaults as $field => $default_value) $this->set_default($field, $default_value);
	}
	
	
	/**
	 * Bulk-add field descriptive names
	 * array(
	 * 	'field name' => 'label',
	 * 	...
	 * )
	 *
	 * @return null
	 * @param $labels String
	 */
	public function set_labels($labels)
	{
		foreach($labels as $field => $label) $this->labels[$field] = $label;
	}

	
	/**
	 * Bulk-add field types
	 * array(
	 * 	'field name' => 'type',
	 * 	...
	 * )
	 *
	 * @return null
	 * @param $labels String
	 */
	public function set_types($types)
	{
		foreach($types as $field => $type) $this->types[$field] = $type;
	}
	

	/**
	 * Bulk-add field value options
	 * array(
	 * 	'field name' => array('option1', 'option2', ...),
	 * 	...
	 * )
	 *
	 * @return null
	 * @param $labels String
	 */
	public function set_values($values)
	{
		foreach($values as $field => $options) {
			$this->set_value($field, $options);
		}
	}

	
	/**
	 * Bulk-add field attributes
	 * array(
	 * 	'field name' => array('attr1' => 'option1', 'attr2' => 'option2', ...),
	 * 	...
	 * )
	 *
	 * @return null
	 * @param $labels String
	 */
	public function set_attributes_multi($values)
	{
		foreach($values as $field => $options) $this->attributes[$field] = $options;
	}
	
	
	/**
	 * Merge or replace the form input data
	 *
	 * @param array   $data
	 * @param boolean $replace
	 * @return NULL
	 */
	public function set_inputs(array $data, array $skip = array(), $replace = FALSE) {
		if(!$replace) {
			# merge input data
			foreach($this->rules as $field => $rule) {
				if(!empty($field) && !in_array($field, $skip)) {
					$this->input[$field] = $data[$field];
				}
			}
		} else {
			# replace input data
			$this->input = $data;
		}
	}
	
	
	/**
	 * Run the filter callbacks on a form field - normally called on Form::Validate()
	 * @return null
	 */
	public function run_filters()
	{
		foreach($this->filters as $field => $filter)
		{
			$this->run_filter($field);
		}
	}
	
	
	public function run_filter($field)
	{
		$callbacks = explode('|', $this->filters[$field]);
		foreach($callbacks as $callback) {
			if(strlen($callback) > 0) {
				$this->input[$field] = call_user_func_array($callback, array($this->input[$field]));
			}
		}
	}
	
	
	/**
	 * Get the value of a form field
	 * @return Mixed
	 * @param $field String
	 * @param $sanitize Boolean[optional]	Sanitize the form field (values: 'xss', 'sql')
	 */
	public function field($field, $sanitize = false)
	{

		// Check whether the field input is a string or array
		if (is_array($this->input[$field])) {

			// It's an array, so loop through and decide whether to use input or defaults on each
			foreach($this->input[$field] as $key => $field_value) {
				$value[$key] = (strlen((string) $field_value) > 0)? $this->input[$field][$key] : $this->defaults[$field];
			}
			
		} else {
			
			// It's a string, so check whether to use input or defaults directly
			$value = (strlen($this->input[$field]) > 0)? $this->input[$field] : $this->defaults[$field];
		}
		
		// Sanatize the string or array
		$value = ($sanitize !== false)? $this->clean($sanitize, $value) : $value;
		
		// Return differently depending on whether it's an array or a string
		if (is_array($value)) {
			return $value;
		} else {
			return (strlen($value) > 0)? $value : false;
		}
	}
	
	
	public function field_raw($field, $sanitize = FALSE) {
		$value = $this->input[$field];
		$value = ($sanitize !== false)? $this->clean($sanitize, $value) : $value;
		if(is_array($value)) {
			return (count($value) > 0)? $value : FALSE;
		} else {
			return (strlen($value) > 0)? $value : FALSE;
		}
	}
	
	
	/**
	 * Returns an indexed array of arrays with 'name' => 'sanitized value'
	 * @return array example: array
	 */
	public function get_fields($raw = FALSE){

		$fields = array();
		foreach($this->rules as $field => $rule){
			if ($this->types[$field] != 'readonly') {
				$fields[$field] = ($raw)? $this->field_raw($field) : $this->field($field);
			}
		}
		if(count($fields) > 0)
			return $fields;
		else
			return false;
	}
	
	/**
	 * Sets value of field. Overrides submitted form data. Used for replacing
	 * the data gathered from the $_POST[] array.  Can be helpful if wanting to change the value of
	 * a field before it gets returned from ->get_fields() or ->render()
	 * @param string $field name of input
	 * @param string $value new value
	 */
	public function set_field($field, $value){
		$this->input[$field] = $value;
	}
	
	/**
	 * Return file array
	 *
	 * @param string $field name of field
	 * @return array array('name'=>'', 'tmp_name'=> string, 'type' => string, 'error'=> int, 'size' => bytes)
	 */
	public function get_file($field){
		return $_FILES[$field];
	}

	private function is_file($field){
		return ($this->types[$field] == 'upload') ? true : false;
	}
	
	/**
	 * Run all the validation rules
	 * @return Mixed
	 * @param $run_filters Boolean[optional]	Run the filters before validating (true/false, default true)
	 */
	public function validate($run_filters = true)
	{
		$this->errors = array();	// Clear out errors

		// Consider ajax requests submitted
		if(Router::is_ajax() && $this->input['__validate']) {
			$this->input[$this->triggerfield] = 1;
		}
		
		if(Router::is_ajax() && array_key_exists($this->input['__validate'], $this->rules)) {
			// Ajax field validation request?
			$this->validate_field($this->input['__validate']);
			json_send($this->errors);
			core_halt();
		}
		elseif($this->submitted() === false) {
			// Form not submitted?
			return false;
		}
		
		// validate each form field
		foreach($this->rules as $field => $rule_string) {
			$this->validate_field($field, $run_filters);
		}
		
		if(Router::is_ajax() && $this->input['__validate'] == 'all') {
			// Ajax form validation request?
			json_send($this->errors);
			core_halt();
		} else {
			return (count($this->errors) > 0)? FALSE : TRUE;
		}
	}
	
	
	
	/**
	 * Run all the validation rules
	 * @return Mixed
	 * @param $run_filters Boolean[optional]	Run the filters before validating (true/false, default true)
	 */
	public function validate_field($field, $run_filters = true)
	{
		$that = $this;
		
		// Pre-filter the data
		if($run_filters) $this->run_filter($field);
		
		// Form submitted?
		if($this->submitted($this->triggerfield) === false) return false;

		// validate each form field
		$rule_string = $this->rules[$field];
		
		$rules = explode('|', $rule_string);

		// run each validation rule
		foreach($rules as $rule)
		{
			// don't run any more rules if we failed the last one
			if(count($this->errors[$field])) break;
			
			// parse parameter from rule (format: "rule[parameterlist]")
			$full_rule = $rule;
			list($rule, $parameters) = explode('[', $rule);
			$parameters = rtrim($parameters, ']');

			$str = $this->input[$field];
			
			// If type is file, then we have to process using special "required" rule
			if($this->is_file($field) && $rule == 'required'){
				$rule = 'required_file';
				$full_rule = $rule;
			}
				
			switch($rule)
			{
				case 'required':
					if(is_array($str)) {
						$pass = TRUE;
						foreach($str as $i => $j) {
							if(strlen($j) == 0) {
								$pass = FALSE;
							}
						}
						if($pass === TRUE) continue 2;
					}
					elseif(strlen($str) > 0) continue 2;
					break;
					
				case 'required_file':
					// Make sure file exists
					if(!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] == UPLOAD_ERR_OK)
						continue 2;
					break;
					
				case 'required_if':
					list($f1, $v) = explode('=', $parameters);
					if(strpos($parameters, '=') === FALSE && strlen($this->input[$parameters]) > 0) {
						// required_if[field]
						if(strlen($str) > 0) continue 2;
					} elseif($this->input[$f1] == $v) {
						// required_if[field=value]
						if((is_array($str) && count($str) > 0) || strlen($str) > 0) continue 2;
					} else {
						// require_if condition not met
						continue 2;
					}
					break;
					
				case 'required_unless':
					list($f1, $v) = explode('=', $parameters);
					if(strstr($parameters, '=') !== FALSE && $this->input[$f1] == $v) {
						continue 2;
					}
					elseif(strlen($this->input[$parameters]) > 0) {
						continue 2;
					}
					else {
						if(strlen($str) > 0) continue 2;
					}
					break;

				case 'values':
					if(empty($str)) {
						continue 2;
					}
					elseif(is_array($str)) {
						$pass = TRUE;
						foreach($str as $i => $j) {
							$values = array_keys($this->values[$field]);
							if(!in_array($j, $values)) {
								$pass = FALSE;
							}
						}
						if($pass === TRUE) continue 2;
					}
					elseif(array_key_exists($str, $this->values[$field])) {
						continue 2;
					}
					break;
					
				case 'matches':
					if(strlen($str) == 0 || $str == $this->input[$parameters]) continue 2;
					break;

				case 'min_value':
					if(strlen($str) == 0 || $str >= $parameters) continue 2;
					break;
					
				case 'max_value':
					if(strlen($str) == 0 || $str <= $parameters) continue 2;
					break;
				
				case 'exact_value':
					if(strlen($str) == 0 || $str == $parameters) continue 2;
					break;
					
				case 'min_length':
					if(strlen($str) == 0 || strlen($str) >= (int) $parameters) continue 2;
					break;
					
				case 'max_length':
					if(strlen($str) == 0 || strlen($str) <= (int) $parameters) continue 2;
					break;
					
				case 'exact_length':
					if(strlen($str) == 0 || strlen($str) == (int) $parameters) continue 2;
					break;
					
				case 'alpha':
					if(strlen($str) == 0 || preg_match("/^([a-z])+$/i", $str)) continue 2;
					break;
					
				case 'alpha_numeric':
					if(strlen($str) == 0 || preg_match("/^([a-z0-9])+$/i", $str)) continue 2;
					break;
					
				case 'alpha_numeric_space':
					if(strlen($str) == 0 || preg_match("/^([a-z0-9 ])+$/i", $str)) continue 2;
					break;
				
				case 'alpha_dash':
					if(strlen($str) == 0 || preg_match("/^([-a-z0-9_-])+$/i", $str)) continue 2;
					break;
					
				case 'alpha_dash_dot':
					if(strlen($str) == 0 || preg_match("/^([a-z0-9._-])+$/i", $str)) continue 2;
					break;
					
				case 'numeric_dash':
					if(strlen($str) == 0 || preg_match("/^([-0-9_-])+$/i", $str)) continue 2;
					break;
					
				case 'numeric':
					if(strlen($str) == 0 || preg_match("/^[\-+]?[0-9]*\.?[0-9]+$/", $str)) continue 2;
					break;
					
				case 'boolean':
					if(strlen($str) == 0 || preg_match("/^([0-1])+$/i", $str)) continue 2;
					break;
					
				case 'integer':
					if(strlen($str) == 0 || preg_match("/^[\-+]?[0-9]+$/", $str)) continue 2;
					break;
					
				case 'valid_email':
					if(strlen($str) == 0 || preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) continue 2;
					break;
				
				case 'valid_phone':
					if(strlen($str) == 0 || preg_match("/^([1]-)?[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $str)) continue 2;
					break;
					
				case 'valid_url':
					if(strlen($str) == 0 || valid_url($str)) continue 2;
					break;
					
				case 'valid_username':
					if(strlen($str) == 0 || preg_match("/^[A-Za-z0-9 _\-\.]+$/", $str)) continue 2;
					break;
					
				case 'valid_password':
					if(strlen($str) == 0 || (preg_match('/[^A-Za-z]+/', $str) && strlen($str) >= (int) $parameters)) continue 2;
					break;
					
				case 'valid_ip':
					$segments = explode('.', $str);
					$valid = TRUE;
					if(count($segments != 4) || substr($str, 0, 1) == '0') $valid = FALSE;	// must have 4 segments and can't start with 0
					foreach($segments as $seg) {
						if(preg_match("/[^0-9]/", $seg) || $seg > 255 || strlen($seg) > 3) $valid = FALSE;	// must be an integer from 0-255
					}
					if($valid) continue 2;
					break;
					
				case 'valid_base64':
					if(strlen($str) == 0 || preg_match('/[^a-zA-Z0-9\/\+=]/', $str)) continue 2;
					break;
				
				case 'valid_zip':
					if(strlen($str) == 0 || preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $str)) continue 2;
					break;
				
				case 'valid_file':
					// Make sure file exists
					if(!isset($_FILES[$field]) || in_array($_FILES[$field]['error'], array(UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE)))
						continue 2;
					break;
					
				case 'valid_image':
					// Make sure file exists
					if(isset($_FILES[$field]) && $_FILES[$field]['error'] == UPLOAD_ERR_OK) {
						$filetypes = explode(',', strtolower($parameters));
						$imageinfo = getimagesize($_FILES[$field]['tmp_name']);
						foreach($filetypes as &$filetype) {
							$filetype = "image/".trim($filetype);
						}
						if(in_array($imageinfo['mime'], $filetypes)) {
							continue 2;
						}
					} else {
						continue 2;
					}
					break;
					
				case 'filetype':
					// Make sure file exists
					if(isset($_FILES[$field]) && $_FILES[$field]['error'] == UPLOAD_ERR_OK) {
						$mime_types = explode(',', strtolower($parameters));
						try {
							$fi = new finfo(FILEINFO_MIME);
						} catch(ErrorException $e) {
							$fi = new finfo(FILEINFO_MIME, Config::get('fileinfo_magic_db'));
						}
						$fileinfo = $fi->file($_FILES[$field]['tmp_name']);
						$mime = array_shift(explode(';', $fileinfo));
						if(in_array($mime, $mime_types)) {
							continue 2;
						}
					} else {
						continue 2;
					}
					break;
					
				case 'valid_month':
					if(strlen($str) == 0 || ((int) $str <= 12 && (int) $str >= 1)) continue 2;
					break;
					
				case 'valid_day':
					list($m, $y) = explode(',', $parameters);
					$month = (int) $this->input[$m];
					$year =  (int) $this->input[$y];
					//var_dump($month, $year);
					// ensure we have a valid month and year
					if(($month <= 12 && $month >= 1 && strlen($year) == 4 && $year > 0)) {
						$days = days_in_month($month, $year);
						//var_dump($days);
						#var_dump(isLeapYear($year));
						if(strlen($str) == 0 || ((int) $str <= $days && (int) $str >= 1)) continue 2;
					}
					else {
						continue 2;
					}
					break;
				
				case 'valid_year':
					if(strlen($str) == 0 || (is_numeric($str) && strlen($str) == 4 && (int) $str > 0)) continue 2;
					break;
					
				case 'valid_date':
					if(strlen($str) == 0 || strtotime($str) !== FALSE) continue 2;
					break;
				
				case 'after':
					$before = $this->input[$parameters];
					if(strlen($str) == 0 || strtotime($str) > strtotime($before)) continue 2;
					break;
					
				case 'on_or_after':
					$before = $this->input[$parameters];
					if(strlen($str) == 0 || strtotime($str) >= strtotime($before)) continue 2;
					break;

				case 'valid_time':
					if(strlen($str) == 0 || preg_match("/^((0?[1-9]|1[012])(:[0-5]\d){0,2}(\ [AP]M))$|^([01]\d|2[0-3])(:[0-5]\d){0,2}$/i", $str)) continue 2;
					break;
					
				case 'valid_creditcard_type':
					if(strlen($str) == 0 || in_array($str, array('VISA', 'MC', 'AMEX', 'DISC'))) continue 2;
					break;
					
				case 'valid_creditcard':
					// Remove non-digits and reverse
					$s = strrev(preg_replace("/[^\d]/", '', $str));
					// compute checksum
					$sum = 0;
					for($i=0, $j=strlen($s); $i < $j; $i++) {
						// Use even digits as-is
						if(($i % 2) == 0) {
							$val = $s[$i];
						} else {
							// Double odd digits and subtract 9 if greater than 9
							$val = $s[$i]*2;
							if($val > 9) $val -= 9;
						}
						$sum += $val;
					}
					// Number is valid if sum is a multiple of 10
					if(strlen($str) == 0 || (strlen($s) > 0 && $sum % 10 == 0)) continue 2;
					break;
					
				case 'valid_creditcard_exp':
					$m = substr($str, 0, 2);
					$y = substr($str, 2);
					$exp = strtotime(sprintf('20%02d-%02d-01', $y, $m));
					if(strlen($str) == 0 || ($exp !== FALSE && $exp > strtotime("+1 month"))) continue 2;
					break;
					
				case 'valid_model':
					$class = $parameters;
					$model = new $class($str);
					if(! $model instanceof Model) {
						throw new FormException("Validation rule 'valid_model': $class does not extend the Model class");
					}
					if(strlen($str) == 0 || $model->exists()) continue 2;
					break;
					
				default:
					// @TODO: Implement user-defined callback functions here
					if(!empty($rule)) {
						if(function_exists($rule)) {
							if((is_array($str) && count($str) == 0) || (is_string($str) && strlen($str) == 0) || call_user_func($rule, $str, $parameters, $field, $this)) continue 2;
						} elseif($this->controller instanceof Controller && method_exists($this->controller, $rule)) {
							if((is_array($str) && count($str) == 0) || (is_string($str) && strlen($str) == 0) || call_user_func(array($this->controller, $rule), $str, $parameters, $field, $this)) continue 2;
						} else {
							throw new ErrorException("Invalid validation rule: $rule");
						}
					} else {
						continue 2;
					}
			}
			
			$this->set_error($field, $full_rule);
		}
		
		return (strlen($this->errors[$field]) == 0);
	}
	
	
	/**
	 * Sets an error message for a form field
	 * @return null
	 * @param $field String
	 * @param $rule String
	 */
	public function set_error($field, $rule)
	{
		list($rule, $parameters) = explode('[', $rule);
		list($parameters, ) = explode('=', $parameters);
		$parameters = trim($parameters, ' ]');
		
		if(in_array($rule, array('matches', 'after', 'on_or_after', 'required_unless', 'required_if'))) {
			$parameters = $this->field_name($parameters);
		}
		
		# Files need special error messages
		if($this->is_file($field) && $rule == 'required_file') {
			$msg = sprintf($this->error_messages[$rule], $this->upload_error_messages[$_FILES[$field]['error']]);
		} else {
			$msg = sprintf($this->error_messages[$rule], $this->field_name($field), $parameters);
		}
		
		$tpl = new ViewObject();
		$tpl->load_string($this->wrap_field_error);
		$tpl->parse();
		$tpl->data('field', $field);
		$tpl->data('message', $msg);
		$this->errors[$field] = $tpl->__toString();
	}
	
	
	/**
	 * Returns the Descriptive name of a form field
	 *
	 * @param string $field
	 */
	public function field_name($field)
	{
		return (isset($this->labels[$field]))?
			$this->labels[$field] :
			ucwords(strtr($field, array('_'=>' ')));
	}
	
	
	/**
	 * Set the error message for a validation rule (message follows sprintf() formatting rules)
	 * @return null
	 * @param $rule String
	 * @param $msg String
	 */
	public function set_error_message($rule, $msg)
	{
		$this->error_messages[$rule] = $msg;
	}


	/**
	 * Get an error message for a specific form field or for the entire form
	 * @return String fieldname|all|null specific error|all errors, comma delimited|assigns error array to "error" view variable
	 * @param $field String[optional]
	 */
	public function error_message($field = null)
	{
		if(!is_null($field)) {
			// Error message for a specific field
			return $this->errors[$field];
		}
		elseif(count($this->errors) == 0)
		{
			// No errors
			return '';
		}
		elseif($field == 'all'){
			return implode(', ', $this->errors);
		}
		else
		{
			// Error message for entire form, using template
			$view = new ViewObject();
			$view->Load_String($this->error_template);
			$view->Parse();
			$view->Data('errors', array_values($this->errors));
			return $view->Publish(TRUE);
		}
	}
	
	
	/**
	 * Sets a template for error messages (can be a string or a template filename)
	 * @return null
	 * @param $tpl String
	 */
	public function set_error_template($tpl)
	{
		$this->error_template = $tpl;
	}
	
	
	/**
	 * Check to see if the form has been submitted
	 * @return boolean
	 * @param $field String[optional]
	 */
	public function submitted($field = NULL)
	{
		$field = (is_null($field))? $this->input[$this->triggerfield] : $this->input[$field];
		return (!empty($field));
	}
	
	
	/**
	 * Render all the form fields according to type
	 * Defaults to text fields if no type set
	 *
	 */
	public function render_fields()
	{
		$fields = array();
		
		foreach($this->rules as $field => $rule)
		{
			if(is_array($this->field($field)) && count($this->field($field)) > 0) {
				foreach($this->field($field) as $fv) {
					$this->{$field} = $this->render_field($field, $fv);
					if(!is_array($this->{$field})) {
						$fields[$field][] = $this->{$field};
					} else {
						$fields[$field] = $this->{$field};
					}
				}
			}
			elseif(!empty($field)) {
				$this->{$field} = $this->render_field($field);
				$fields[$field] = $this->{$field};
			}
		}
		
		return $fields;
	}
	
	
	public function render_field($field, $forced_value = NULL, $include_error = FALSE, array $attribute_overrides = array())
	{
		$fv = ($forced_value === NULL)? $this->field($field) : $forced_value;
		
		$attributes_value = $fv;
		$attributes = array(
			'name'  => $field,
			#'id'    => $this->id($field),
			'value' => (is_array($attributes_value) && $this->types[$field] != 'select')? array_shift($attributes_value) : $attributes_value,
		);
		$attributes = array_merge($attributes, (array) $this->attributes[$field], $attribute_overrides);
				
		# don't render default attributes
		unset($attributes['default']);
									
		# add error class if needed
		if($this->errors[$field]) {
			$classes = explode(' ', $attributes['class']);
			$classes[] = 'error';
			$attributes['class'] = implode(' ', $classes);
		}
		
		switch($this->types[$field])
		{
			case 'textarea':
				$theField = form_textarea($attributes);
				break;
				
			case 'password':
				$theField = form_password($attributes);
				break;
			
			case 'upload':
				$theField = form_upload($attributes);
				break;
			
			case 'radio':
				if(empty($attributes['id'])) $attributes['id'] = $this->id($field);
				foreach((array) $this->values[$field] as $val => $label) {
					$id = $attributes['id'].'_'.$this->id($val);
					$a = array(
						'id'    => $this->id($field).'_'.$this->id($val),
						'value' => $val,
						'checked' => ($fv == $val),
					);
					$a = array_merge($attributes, $a);
					$theField[] = form_radio($a) . form_label($label, $id);
				}
				break;
				
			case 'checkbox':
				if(empty($attributes['id'])) $attributes['id'] = $this->id($field);
				foreach((array) $this->values[$field] as $val => $label) {
					$id = $attributes['id'].'_'.$this->id($val);
					$a = array(
						'id'    => $id,
						'value' => $val,
					);
					if(is_array($this->field($field))) {
						$a['checked'] = in_array($val, $this->field($field));
					} else {
						$a['checked'] = ($fv == $val);
					}
					$a = array_merge($attributes, $a);

					# Add the label only if there is label text
					$theField[] = form_checkbox($a) . ($label ? form_label($label, $id) : '');
				}
				break;
				
			case 'select':
				
				if (!isset($attributes['id']))
					$attributes['id'] = $attributes['name'];

				$fname = $attributes['name'];
				#var_dump($field, $this->defaults[$field]);
				unset($attributes['name']);
				unset($attributes['value']);
				$attributes = parse_form_attributes($attributes, $attributes);
				$theField = form_dropdown($fname, (array) $this->values[$field], $fv, $attributes);
				break;
				
			case 'hidden':
				$theField = form_hidden($attributes, $fv);
				break;
				
			case 'readonly':
				$theField = $attributes['value'];
				break;
				
			default:
			case 'text':
				//print_r($attributes);
				$theField = form_input($attributes);
				break;
		}
		
		if($include_error && $this->errors[$field]) {
			$theField .= $this->errors[$field];
		}
		
		return $theField;
	}
	
	/**
	 * Returns all form data in a certain way, depending on the $method passed
	 *
	 * @param string $method table (form, dt to be added)
	 * @return array|string All form values
	 */
	public function render($method = 'table') {
		
		switch($method){
			
			case 'table':
			
				$output = ''; //Data that we're rendering
				$output .= '<table cellpadding="5" cellspacing="5">';
				
				foreach($this->rules as $field => $rule) {

					$field_value = '';
					
					//Check to see if input is from a multi-value input
					if(is_array($this->field($field)) && count($this->field($field)) > 0) {
						
						// Delimit
						$field_value = implode(" | ", $this->field($field));
					
					} else {
						
						$field_value .= $this->field($field);
						
					}
					
					$output .= "<tr><td>";
					$output .= $this->field_name($field);
					$output .= "</td><td>";
					$output .= $field_value;
					$output .= "</td></tr>";
					
				}
				
				$output .= '</table>';
				
				return $output;
			break;
			
			
			case 'table-rows':
			
				$output = ''; //Data that we're rendering
				
				foreach($this->rules as $field => $rule) {

					$field_value = '';
					
					//Check to see if input is from a multi-value input
					if(is_array($this->field($field)) && count($this->field($field)) > 0) {
						
						// Delimit
						$field_value = implode(" | ", $this->field($field));
					
					} else {
						
						$field_value .= $this->field($field);
						
					}
					
					$output .= "<tr><td>";
					$output .= $this->field_name($field);
					$output .= "</td><td>";
					$output .= $field_value;
					$output .= "</td></tr>";
					
				}
				
				return $output;
			break;
			
			
			case 'form':
				
				$output = ''; //Data that we're rendering
				$output .= '<table cellpadding="5" cellspacing="5">';
				
				foreach($this->rules as $field => $rule) {
					
					$rendered_field = $this->render_field($field);
					
					$output .= "<tr><td>";
					$output .= $this->field_name($field);
					$output .= "</td><td>";
					$output .= ((is_array($rendered_field))? implode("<br />\n", $rendered_field) : $rendered_field).' '. $this->error_message($field);
					$output .= "</td></tr>";
				}
				
				$output .= '</table>';
				
				return $output;
			break;
			
			
			case 'form-rows':
				
				$output = ''; //Data that we're rendering
				
				foreach($this->rules as $field => $rule) {
					
					$rendered_field = $this->render_field($field);
					
					$output .= "<tr><td>";
					$output .= $this->field_name($field);
					$output .= "</td><td>";
					$output .= ((is_array($rendered_field))? implode("<br />\n", $rendered_field) : $rendered_field).' '. $this->error_message($field);
					$output .= "</td></tr>";
				}
				
				return $output;
			break;
			
				
			case 'dt':
			default:
				return false;
		}
	}

	
	public function triggerfield() {
		
		$html = form_hidden($this->triggerfield, 1);

		if(Session::get_xsrf_token()) {
			$html .= form_hidden(Session::Token_Variable, Session::get_xsrf_token());
		}
		
		return $html;
	}
	
	
	/**
	 * Convert a field name to an ID
	 *
	 * @param String $field
	 */
	private function id($field)
	{
		return strtr($field, array('['=>'', ']'=>'_', ' '=>'_'));
	}
	
}



if (! function_exists('days_in_month'))
{
	function days_in_month($month = 0, $year = '')
	{
		if ($month < 1 OR $month > 12)
		{
			return 0;
		}
	
		if ( ! is_numeric($year) OR strlen($year) != 4)
		{
			$year = date('Y');
		}
	
		if ($month == 2)
		{
			if ($year % 400 == 0 OR ($year % 4 == 0 AND $year % 100 != 0))
			{
				return 29;
			}
		}

		$days_in_month	= array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		return $days_in_month[$month - 1];
	}
}


