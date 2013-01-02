<?php

class CompositeFormException extends Exception {}

class CompositeForm extends Form {
	
	protected $forms = array();
	
	
	public function add_form() {
		
		$that = $this;
		$args = func_get_args();
		$i = array_shift($args);
		
		if($args[0] instanceof CompositeFormChildInterface) {
			
			# $composite_form->add_form(Form $form)
			$this->forms[$i] = $args[0];
			
		} elseif(is_array($args[0])) {
			
			# $composite_form->add_form(array $fields, array $data = NULL, $class = 'CompositeFormChild')
			list($fields, $data, $class) = $args;
			
			# create the Form
			if(empty($class)) {
				$class = 'CompositeFormChild';
			} else {
				$reflection = new ReflectionClass($class);
				if(!$reflection->implementsInterface('CompositeFormChildInterface')) {
					throw new InvalidArgumentException('Third argument must be a class that implements CompositeFormChildInterface');
				}
			}
			$this->forms[$i] = new $class($this, $this->method, (is_array($data))? $data : array(), FALSE);
			
			# load the form fields
			$this->forms[$i]->add_fields($fields);
			
			# if no custom data array present, load from $this->input -
			# but only what's needed for this particular form
			if(!is_array($data)) {
				$this->forms[$i]->input = $this->extract_row_fields($i);
			}
			
		} else {
			
			throw new InvalidArgumentException("Improper usage of CompositeForm::add_form(), check your arguments");
			
		}
		
		
		# add field prefix to HTML field names
		$theform =& $this->forms[$i];
		foreach($theform->rules as $field => $rules) {
			
			# namespace each form field with the form label
			if(isset($theform->attributes[$field]['name'])) {
				
				# modify existing name attribute
				$name = $theform->attributes[$field]['name'];
				
				if(strpos($name, '[') !== FALSE) {
					# transform field[abc] -> form[field][abc]
					$segments = explode('[', $name);
					$segments[0] .= ']';
					array_unshift($segments, $i);
					$name = implode('[', $segments);
				} else {
					# transform field -> form[field]
					$name = "{$i}[{$name}]";
				}
				
				$theform->attributes[$field]['name'] = $name;
				
			} else {
				
				# add a name attribute
				$theform->attributes[$field]['name'] = "{$i}[{$field}]";
				
			}
		}
		
		
		# link controller to subfoms
		if($this->controller instanceof Controller) {
			$this->forms[$i]->controller =& $this->controller;
		}
		
		
		return $this->forms[$i];
	}
	
	
	public function add_forms(array $forms) {
		foreach($forms as $name => $form) {
			$this->add_form($name, $form);
		}
	}
	
	
	public function remove_form($form) {
		unset($this->forms[$form]);
	}
	
	
	protected function extract_row_fields($form) {
		$rowdata = array();
		$theform = $this->forms[$form];
		if($this->forms[$i] instanceof Multiform) {
			$this->forms[$i]->input = $this->input[$i];
		} else {
			foreach($theform->rules as $field => $rules) {
				$rowdata[$field] = $this->input[$form][$field];
			}
		}
		if(!isset($rowdata[$theform->triggerfield])) {
			$rowdata[$this->triggerfield] = $this->input[$this->triggerfield];
		}
		if(!isset($rowdata['__validate']) && isset($_POST['__validate'])) {
			#$rowdata['__validate'] = $_POST['__validate'];
		}
		return $rowdata;
	}
	
	
	public function validate($run_filters = TRUE) {
		
		// Consider ajax requests submitted
		if(Router::is_ajax() && $_POST['__validate']) {
			foreach($this->forms as $name => $form) {
				$this->forms[$name]->input[$this->triggerfield] = 1;
			}
		}
		
		// Ajax field validation request?
		list($label, $field) = explode(':', $_POST['__validate']);
		if(Router::is_ajax() && !empty($label) && array_key_exists($field, (array) $this->forms[$label]->rules)) {
			$form = $this->get_form($label);
			$form->validate_field($field);
			header('Content-type: application/json');
			echo json_encode($form->errors);
			core_halt();
		}
		elseif(!$this->submitted()) {
			return FALSE;
		}
		
		$valid = TRUE;
		
		foreach($this->forms as $name => $form) {
			if(!$this->validate_row($name, $run_filters)) $valid = FALSE;
		}
		
		if(Router::is_ajax() && $_POST['__validate'] == 'all') {
			// Ajax form validation request?
			header('Content-type: application/json');
			echo json_encode($this->errors);
			core_halt();
		} else {
			return $valid;
		}
	}
	
	
	public function validate_row($i, $run_filters = TRUE) {
		
		if(! $this->forms[$i] instanceof Form) {
			throw new CompositeFormException("Cannot validate form $i");
		}
		
		if(!$this->forms[$i]->validate($run_filters)) {
			$this->errors[$i] = $this->forms[$i]->errors;
			return FALSE;
		} else {
			return TRUE;
		}
		
	}
	
	
	public function render($method = 'table') {
		
		$output = array();
		
		foreach($this->forms as $name => $form) {
			$output[$name] = $this->render_form($name, $method);
		}
		
		return $output;
	}
	
	
	public function render_form($i, $method = 'table') {
		
		if(! $this->forms[$i] instanceof Form) {
			throw new CompositeFormException("Cannot render row $i (".count($this->forms)." rows exist)");
		} else {
			$row = $this->forms[$i];
		}
		
		return $row->render($method);
	}
	
	
	public function forms() {
		return $this->forms;
	}

	
	public function get_form($form, $throw_exception = TRUE) {
		if(!$this->forms[$form] instanceof Form) {
			if($throw_exception) {
				throw new InvalidArgumentException("Invalid form label: $form");
			} else {
				return FALSE;
			}
		} else {
			return $this->forms[$form];
		}
	}
	
	
}


interface CompositeFormChildInterface {
	public function __construct();
	public function submitted();
}


class CompositeFormChild extends Form implements CompositeFormChildInterface {
	
	/**
	 * Parent form
	 * @var CompositeForm
	 */
	public $parent;
	
	
	public function __construct() {
		$args = func_get_args();
		$this->parent = array_shift($args);
		if($this->parent->controller instanceof Controller) {
			$this->controller = $this->parent->controller;
		}
		if(! $this->parent instanceof CompositeForm) {
			throw new InvalidArgumentException("First argument must be a CompositeForm object");
		}
		
		call_user_func_array(array('parent', '__construct'), $args);
	}
	
	
	public function submitted() {
		return $this->parent->submitted();
	}
	
	
}


class CompositeMultiformChild extends MultiForm implements CompositeFormChildInterface {
	
	/**
	 * Parent form
	 * @var CompositeForm
	 */
	public $parent;
	
	
	public function __construct() {
		$args = func_get_args();
		$this->parent = array_shift($args);
		if($this->parent->controller instanceof Controller) {
			$this->controller = $this->parent->controller;
		}
		if(! $this->parent instanceof CompositeForm) {
			throw new InvalidArgumentException("First argument must be a CompositeForm object");
		}
		
		call_user_func_array(array('parent', '__construct'), $args);
	}
	
	
	public function submitted() {
		return $this->parent->submitted();
	}
	
	
}
