<?

/**
 * Controllers Controller
 */
class Controller_Controllers extends Controller {
	protected $_view_type = 'FastViewObject';
	
	public function view($project, $name) {
		
		$project = new Project($project);
		$controller = $project->controller($name);

		$this->view->load('controllers/view');
		$this->view->data('project', $project);
		$this->view->data('controller', $controller);

		$this->output();

	}

}