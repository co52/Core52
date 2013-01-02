<?php

class MultiFormException extends Exception {}

class MultiForm extends Form {
	
	protected $rows = array();
	protected $next_row_index = 0;
	
	
	public function add_row(array $data = NULL) {
		$i = $this->next_row_index++;
		$this->rows[$i] = new Form($this->method, ($data)? $data : $this->extract_row_fields($i), FALSE);
		$this->rows[$i]->rules = $this->rules;
		$this->rows[$i]->filters = $this->filters;
		$this->rows[$i]->defaults = $this->defaults;
		$this->rows[$i]->labels = $this->labels;
		$this->rows[$i]->types = $this->types;
		$this->rows[$i]->values = $this->values;
		$this->rows[$i]->attributes = $this->attributes;
		$this->rows[$i]->triggerfield = $this->triggerfield;
		if($this->controller instanceof Controller) {
			$this->rows[$i]->controller = $this->controller;
		}
		foreach($this->rules as $field => $rules) {
			if(!isset($this->rows[$i]->attributes[$field]['name'])) {
				$this->rows[$i]->attributes[$field]['name'] = $field."[$i]";
			} elseif(strpos($this->rows[$i]->attributes[$field]['name'], '[') !== FALSE) {
				# rewrite field[] as field[n][]
				$name = $this->rows[$i]->attributes[$field]['name'];
				$this->rows[$i]->attributes[$field]['name'] =
					substr($name, 0, strpos($name, '[')) . "[$i]" . substr($name, strpos($name, '['));
			}
		}
		return $this->rows[$i];
	}
	
	
	public function add_rows($num_rows, array $data = NULL) {
		$this->rows = array();
		for($i = 0; $i < $num_rows; $i++) {
			$this->add_row($data[$i]);
		}
	}
	
	
	public function extract_row_fields($row) {
		$rowdata = array();
		foreach($this->rules as $field => $rules) {
			$rowdata[$field] = $this->input[$field][$row];
		}
		if(!isset($rowdata[$this->triggerfield])) {
			$rowdata[$this->triggerfield] = $this->input[$this->triggerfield];
		}
		if(isset($this->input['__validate'])) {
			$rowdata['__validate'] = $this->input['__validate'];
		}
		return $rowdata;
	}
	
	
	public function validate($run_filters = TRUE) {
		
		if(!$this->submitted()) {
			return FALSE;
		}
		
		$valid = TRUE;
		
		for($i = 0; $i < $this->next_row_index; $i++) {
			$valid = ($this->validate_row($i, $run_filters)) && $valid;
		}
		
		return $valid;
	}
	
	
	public function validate_row($i, $run_filters = TRUE) {
		
		if(! $this->rows[$i] instanceof Form) {
			throw new MultiFormException("Cannot validate row $i (".count($this->rows)." rows exist)");
		}
		
		if(!$this->rows[$i]->validate($run_filters)) {
			$this->errors[$i] = $this->rows[$i]->errors;
			return FALSE;
		} else {
			return TRUE;
		}
		
	}
	
	
	public function render($method = 'table', $open_tag = '<table cellpadding="5" cellspacing="5">', $close_tag = '</table>', $row_limit = NULL) {
		
		$output = ''; //Data that we're rendering
		$output .= $open_tag;
		
		for($i = 0; $i < $this->next_row_index; $i++) {
			if($row_limit && $i == $row_limit) break;
			$output .= $this->render_row($i, $method);
		}
		
		$output .= $close_tag;
		return $output;
	}
	
	
	public function render_row($i, $method = 'table', $show_header = NULL) {
		
		if(! $this->rows[$i] instanceof Form) {
			throw new MultiFormException("Cannot render row $i (".count($this->rows)." rows exist)");
		} else {
			$row = $this->rows[$i];
		}
		
		
		# show header for first row by default
		if(!is_bool($show_header)) {
			$show_header = ($i === 0);
		}
		
		
		switch($method) {
			
			case 'csv':
			
				$output = array(); //Data that we're rendering
				
				if($show_header) {
					$r = array();
					foreach($this->rules as $field => $rule) {
						$r[] = $row->field_name($field);
					}
					$output[] = str_putcsv($r);
				}
				
				$r = array();
				foreach($this->rules as $field => $rule) {
					$r[] = $row->field($field);
				}
				$output[] = str_putcsv($r);
				
				return implode("\r\n", $output)."\r\n";

			case 'table':
			
				$output = ''; //Data that we're rendering
				
				if($show_header) {
					$output .= '<thead><tr>';
					foreach($this->rules as $field => $rule) {
						$output .= "<th>".$row->field_name($field)."</th>";
					}
					$output .= '</tr></thead>';
				}
				
				$output .= '<tr>';
				
				foreach($this->rules as $field => $rule) {
					$field_value = $row->field($field).' '. $row->error_message($field);
					$output .= "<td>$field_value</td>";
				}
				
				$output .= '</tr>';
				
				return $output;
				
				
			case 'form':
				
				$output = ''; //Data that we're rendering
				
				if($show_header) {
					$output .= '<thead><tr>';
					foreach($this->rules as $field => $rule) {
						$output .= "<th class=\"$field\">".$row->field_name($field)."</th>";
					}
					$output .= '</tr></thead>';
				}
				
				$output .= '<tr>';
				
				foreach($this->rules as $field => $rule) {
					$rendered_field = $row->render_field($field);
					$output .= "<td class=\"$field\">";
					$output .= ((is_array($rendered_field))? implode("<br />\n", $rendered_field) : $rendered_field).' '. $row->error_message($field);
					$output .= "</td>";
				}
				
				$output .= '</tr>';
				
				return $output;

				
			case 'dt':
			default:
				return false;
		}
	}
	

	/**
	 * Set up a MultiForm for an array of Models
	 *
	 * @param array $models
	 * @param array $exclude
	 * @param array $override
	 * @param string $method
	 * @param array $data
	 * @param string $sanitize
	 */
	public static function create_from_models($class, array $models = array(), array $exclude = array(), array $override = array(), $method = 'post', $data = NULL, $sanitize = 'xss', $triggerfield = 'form_submitted') {
		
		if(is_array($class)) {
			extract($class);
		}
		
		$multiform = new self($method, $data, $sanitize);
		$multiform->triggerfield = $triggerfield;
		
		$obj = new $class;
		$multiform->add_fields($obj->form_fields($exclude, $override));
		
		$i = 0;
		foreach($models as $model) {
			
			$row = $multiform->add_row();
			$row->set_defaults($model->toArray());
			
			$i++;
		}
		
		return $multiform;
	}
	
	
	public function rows() {
		return $this->rows;
	}
	
	
	public function row($i) {
		return $this->rows[$i];
	}

	
	public function error_messages() {
		
		$messages = array();
		foreach($this->rows() as $i => $row) {
			if($row->errors) $messages[$i] = $row->error_message();
		}
		
		return $messages;
		
	}
	
	
}