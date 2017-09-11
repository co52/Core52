<?php

/**
 * Model_Controller - implementation of CRUD controller functionality for Models
 *
 * Setup:
 *
 *   1)	Define the form fields based on the editable fields of the model:
 *
 * 		protected $_fields = array(
 * 			array(
 *  			'name' => 'name',
 * 				'rules' => 'required|max_length[50]',
 *  		),
 *  		array(
 *  			'name' => 'address',
 *  			'rules' => 'required',
 *  			'filter' => 'trim',
 *  		),
 *  		...
 * 		);
 *
 * 		These fields will be used to validate the form submission and generate
 * 		the HTML form fields.
 *
 *
 *   2)	Link the model class:
 *
 *   	protected $_class = 'User';
 *
 *
 *   3) Define the URL segment which contains the ID of the model loaded:
 *
 *   	protected $_id_segment = 2; # example URL: /users/edit:14
 *   	protected $_id_segment = 3; # example URL: /admin/users/edit:14
 *   	protected $_id_segment = 4; # example URL: /site/admin/users/edit:14
 *
 *
 *   4) Set the template directory:
 *
 *   	protected $_tpl_dir = 'admin/User/';  # note: case sensitive on UNIX systems
 *
 *
 *   5)	Create a set of views for each model class, in '/app/views/admin/[class]':
 *
 *   	Index page:		/app/views/admin/[model]/index.php
 *   		Variables:
 *   			(array) $records	Array of model objects to display
 *   			(string) $success	Success/fail message to show after add/edit/delete actions
 *
 *   		Action URLs: your index page needs to include links for the add, edit and delete actions.
 *   		Example URLs:
 *   			Add:	/admin/users/add
 *   			Edit:	/admin/users/edit:14
 *   			Delete: /admin/users/delete:14:12351235124514512351235
 *   				Note that the second delete parameter is a checksum of the model,
 *   				which is included as a safety feature to prevent deletion of
 *   				objects by rogue or unintentional hits to the delete method.
 *   				Alternately, checksum can be submitted via POST.
 *   				Use Model::checksum() to compute this hash.
 *
 *   	Add/edit page:	/app/views/admin/[model]/form.php
 *   		Variables:
 *   			(Model) $model		Currently selected model object
 *   			(array)	$fields		Associative array of field labels => rendered HTML fields
 *   			(string) $error		Error message displayed if form validation fails
 *   			(string) $pk		ID of the currently selected model object
 *   			(string) $url		Method the form should submit to, either 'add' or 'edit:[id]'.
 *   								The form URL is based on your controller structure and
 *   								should be consistent with your $_id_segment setting.
 *   								Example:
 *   									/admin/users/<?=$url;?>
 *
 *
 * Notes:
 *
 *   	These templates use the FastView parser.
 *
 *   	Model_Controller supports file uploads. Files will be uploaded to PATH_UPLOAD,
 *   	and the filename will be saved in the model.
 *
 *		Don't forget to include a hidden form triggerfield! The name attribute should be set to $pk:
 *			<input type="hidden" name="<?=$pk;?>" value="1" />
 *
 *		The easiest way to add delete functionality to your form.php template is to add
 *		a hidden checksum field, and a regular submit button named 'delete':
 *			<input type="hidden" name="checksum" value="<?=$model->checksum();?>" />
 *			<input type="submit" name="delete" value="Delete" />
 *		You can also do it as a hyperlink if you prefer:
 *			<a href="/admin/users/delete:14:12351235124514512351235">Delete</a>
 *
 *
 * @author Jonathon Hill
 *
 */
class Model_Controller extends Admin_Controller {

	/**
	 * @var Model
	 */
	protected $_model;
	protected $_models;
	
	protected $_class;
	protected $_fields = array();
	protected $_id_segment = 3;
	protected $_triggerfield = NULL;
	protected $_sanitize_data = FALSE;
	protected $_tpl_dir = NULL;
	protected $_enable_paging = FALSE;
	protected $_per_page = 25;
	protected $_pg_key = 'pg';
	
	protected $_prepare_data = TRUE;
	protected $data = array();
	
	
	public function __construct() {
		$this->db()->set_escape_options(DatabaseConnection::ESCAPE_QUOTE);
		parent::__construct();
		$this->view = new FastViewObject();
		if(empty($this->_tpl_dir)) {
			$this->_tpl_dir = $this->_class.'/';
		}
	}
	
	
	protected function _load_model($id) {
		$cls = $this->_class;
		$this->_model = new $cls($id);
		if(!$this->_model->exists()) {
			throw new PageNotFoundException();
		}
	}
	
	
	protected function output($return = FALSE) {
		$this->view->Global_Data('model', $this->_model);
		parent::output();
	}

	
	public function _default() {
		Router::redirect('index');
	}
	
	
	public function index() {
		$this->_model = new $this->_class;
		$this->view->Load("{$this->_tpl_dir}/index");
		$this->view->Global_Data('success', Session::FlashData('success'));
		$this->_index_data();
		$this->output();
		Session::Data('last_modelcontroller_index_page', Router::url());
	}
	
	
	public function add() {
		
		$this->_page_title = 'Add '.ucwords(str_replace('_', ' ', $this->_class));
		$this->view->Load("{$this->_tpl_dir}/form");
		
		$this->_model = new $this->_class;
		$pk = $this->_model->pk_col();
		
		$this->_load_form($this->_fields);
		$this->form->triggerfield = ($this->_triggerfield === NULL)? $pk : $this->_triggerfield;
		
		if($this->form->validate()) {
			try {
				$this->data = array();
				
				if($this->_prepare_data) {
					if($this->form instanceof CompositeForm) {
						foreach($this->form->forms() as $label => $form) {
							if($this->_models[$label] instanceof Model) {
								$model = $this->_models[$label];
							} else {
								$model = $this->_model;
							}
							$this->data[$label] = $this->_prepare_data($model, $form);
						}
					} else {
						$this->data = $this->_prepare_data($this->_model, $this->form);
					}
				}

				if(!isset($this->data['user_id'])) {
					$this->data['user_id'] = $this->user->id;
				}
				
				if($this->_save()) {
					Session::FlashData('success', 'Record Added');
					Router::Redirect(Router::trace($this->_id_segment - 1).'edit:'.$this->_model->pk());
				}
			}
			catch(ModelControllerException $e) {
				$this->view->Data('error', $e);
			}
		} elseif($this->form->submitted()) {
			$this->view->Data('error', $this->form->error_message());
		}

		$this->form->input[$this->_triggerfield] = 1;
		$fields = array();
		foreach($this->form->render_fields() as $field => $html) {
			$label = ($field == $pk)? $field : $this->form->field_name($field);
			$fields[$label] = $html;
		}
		$this->view->Data('fields', $fields);
		$this->view->Data('errors',  $this->form->errors);
		$this->view->Data('form_obj', $this->form);
		$this->view->Data('pk', $pk);
		$this->view->Data('url', 'add');
		
		$this->output();
	}


	public function edit($id) {

		$this->_page_title = 'Edit '.ucwords(str_replace('_', ' ', $this->_class));
		$this->_load_model($id);
		$this->_load_form($this->_fields);
		
		$this->view->Load("{$this->_tpl_dir}/form");
		$this->view->Data('success', Session::FlashData('success'));
		
		$pk = $this->_model->pk_col();
		$this->form->triggerfield = ($this->_triggerfield === NULL)? $pk : $this->_triggerfield;
		
		if($_POST['delete']) {
			$this->delete($id);
		}
		elseif($this->form->validate()) {
			try {
				$this->data = array();

				if($this->_prepare_data) {
					if($this->form instanceof CompositeForm) {
						foreach($this->form->forms() as $label => $form) {
							if($this->_models[$label] instanceof Model) {
								$model = $this->_models[$label];
							} else {
								$model = $this->_model;
							}
							$this->data[$label] = $this->_prepare_data($model, $form);
						}
					} else {
						$this->data = $this->_prepare_data($this->_model, $this->form);
					}
				}
				
				if($this->_save()) {
					Session::flashData('success', 'Changes Saved');
					Router::redirect(Router::url());
				}
			}
			catch(ModelControllerException $e) {
				$this->view->Data('error', $e);
			}
		} elseif($this->form->submitted()) {
			$this->view->Data('error', $this->form->error_message());
		}
		
		foreach($this->form->rules as $field => $rule) {
			if($field != $pk && !empty($field)) {
				$this->form->input[$field] = $this->_model->$field;
			}
		}
		
		$this->form->input[$this->_triggerfield] = 1;
		$fields = array();
		foreach($this->form->render_fields() as $field => $html) {
			$label = ($field == $pk)? $field : $this->form->field_name($field);
			$fields[$label] = $html;
		}
		$this->view->Data('fields', $fields);
		$this->view->Data('errors',  $this->form->errors);
		$this->view->Data('form_obj', $this->form);
		$this->view->Data('pk', $pk);
		$this->view->Data('url', 'edit:'.$this->_model->pk());
		$this->view->Data('checksum', $this->_model->checksum());
		
		$this->output();
	}
	
	
	public function delete($id, $checksum = NULL) {
		
		$this->_load_model($id);
		
		$checksum = (isset($_POST['checksum']))? $_POST['checksum'] : $checksum;
		
		if($this->_model->checksum() == $checksum) {
			$this->_model->delete();
			Session::FlashData('success', 'Record Deleted');
			$redir = (Session::Data('last_modelcontroller_index_page'))? Session::Data('last_modelcontroller_index_page') : Router::Trace($this->_id_segment - 1);
			Router::redirect($redir);
		} else {
			throw new InvalidArgumentException("Bad checksum parameter (actual: {$this->_model->checksum()}, given: $checksum)");
		}
	}
	
	protected function _handle_upload($field, array $filedata = NULL) {
		
		$file = (is_null($filedata))? $_FILES[$field] : $filedata;

		if(empty($field) || $file['error'] == UPLOAD_ERR_NO_FILE) return FALSE;
		
		if($file['error'] == UPLOAD_ERR_OK) {
			
			try {
				
				# save as /app/uploads/<class><id>_<time>.<ext>
				$imageinfo = getimagesize($file['tmp_name']);
				if(is_array($imageinfo)) {
					list($type, $ext) = explode('/', $imageinfo['mime']);
				} else {
					# fallback for some UNIX-based systems
					$ext = array_pop(explode('.', $file['name']));
				}
				$filename = strtolower($this->_class).$this->_model->pk().'_'.time().".$ext";
				move_uploaded_file($file['tmp_name'], PATH_UPLOAD.$filename);
				
				return $filename;
				
			} catch(ErrorException $e) {
				
				throw new ModelControllerException($e->getMessage());
				
			}
	
		} else {
			
			throw new ModelControllerException("Error uploading ".$this->form->field_name($field));
			
		}
		
		return FALSE;
		
	}
	
	
	protected function _save() {
		return $this->_model->save($this->data);
	}
	
	
	protected function _index_data() {
		
		if($this->_enable_paging) {

			$query = $this->db()->start_query($this->_model->table());
			Page::init($query->run()->num_rows(), $this->_per_page, $this->_pg_key);
			$query->limit(Page::limit('%d, %d'));
			$records = (array) $query->run()->objects($this->_class, NULL, $this->_model->pk_col());
			
		} else {

			$records = (array) Database::find($this->_model->table())->objects($this->_class, NULL, $this->_model->pk_col());
			
		}
		
		if(count($records) > 0) {
			$this->view->Global_Data('records', $records);
		}
	}
	
	
	protected function _prepare_data(Model $model, Form $form) {
		$data = array();
		
		foreach($form->rules as $field => $rule) {
			if($field == $pk) {
				continue;
			}
			elseif($form->types[$field] == 'upload') {
				if(!$model->exists()) {
					$model->save($data);
				}
				$file = $this->_handle_upload($field);
				if($file !== FALSE) $data[$field] = $file;
			}
			elseif($form->types[$field] != 'readonly') {
				$data[$field] = $form->field($field);
			}
		}
		
		return $data;
	}

	/**
	 * Returns the template directory this class is using (null if none is set)
	 * @return string
	 */
	public function tpl_dir() {
		return $this->_tpl_dir;
	}
	
}


class ModelControllerException extends Exception {
	function __toString() {
		return sprintf('<ul class="error"><li>%s</li></ul>', $this->getMessage());
	}
}
