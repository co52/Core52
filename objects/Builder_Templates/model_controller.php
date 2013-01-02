<\x3f

/**
 * <?= ucfirst($plain_name); ?> Controller
 */
class <?= $class_name; ?> extends <?= $controller_type; ?> {

	protected $_class  = '<?= $model; ?>';
	protected $_fields = <?= $form_fields; ?>;
	public $_tpl_dir = '<?= $views_path; ?>';
	protected $_id_segment = 2;

	public function view($id) {
		$this->_load_model($id); 

		$this->view->load("{$this->_tpl_dir}/view");
		$this->view->data(array(
			"model" => $this->_model,
		));

		$this->output();
	}
	

}