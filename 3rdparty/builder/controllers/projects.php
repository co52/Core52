<?

/**
 * Projects Controller
 */
class Controller_Projects extends Controller {
	protected $_view_type = 'FastViewObject';

	public function _default() {
		
		$base = "/fiftytwo/projects";
		$projects_array = array();

		// Find all projects 
		$projects = Project::findAll();

		foreach ($projects as $project) { 

			$projects_array[] = array(
				'name' => $project->name,
				'project_path' => $project->path(),
				'revision' => $project->revision(),
			);

		}

		$this->view->load('projects/index');
		$this->view->data('projects', $projects_array);

		$this->output();

	}

	public function view($folder) {

		$project = new Project($folder);

		$this->view->load('projects/view');
		$this->view->data('project', $project);

		$this->output();
	}

	public function up_to_date($project) {
		$project = new Project($project);

		$up_to_date = false;
		if ($project->revision() == $project->remote_revision()) {
			$up_to_date = true;
		}

		$this->output_json(array(
			'up_to_date' => $up_to_date,
			'remote' => $project->remote_revision(),
			'revision' => $project->revision(),
		)); 

	}

}