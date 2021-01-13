<?php

class Magento_Setup_controller extends Setup_Controller
{

	/**
	 * Magento_Setup_controller constructor.
	 * @param 	$dockerApi Docker_API_Wrapper API Wrapper instance for Docker
	 * @param 	$githubApi Github_API_Wrapper API Wrapper instance for Github
	 */
	public function __construct( $dockerApi, $githubApi )
	{
		parent::__construct($dockerApi, $githubApi);

		$this->_projectType = Project_Type::MAGENTO;
		$this->_action = '/setup/' . $this->_projectType;
	}

	/**
	 * Prepare the form for the first setup step and include the view
	 * Set up the container settings form and siplay the view
	 * @param $params
	 */
	protected function _do_setup_step_1($params)
	{
		$form = [
			'title' => 'Container settings (' . $this->_projectType . ')',
			'method' => "POST",
			'action' => $this->_action,
			'fields' => [],
			'hidden' => [

				// Step to send the user to after the form submission
				'step' => 2
			]
		];

		foreach(['name', 'volume', 'media_volume'] as $fieldName) {
			$value = isset($params["${fieldName}"]) ? $params["${fieldName}"] : '';
			$form['fields'][] = [
				'name' => $fieldName,
				'label' => ucwords(str_replace('_', ' ', $fieldName)),
				'type' => 'text',
				'value' => $value,
				'required' => $fieldName === 'media_volume' ? false : true,
			];
		}

		$projects = $this->_githubApi->getRepositories( '/MAG-INSTALL-(?!TEMPLATE)/');
		$projectOptions = [];
		foreach ( $projects as $project ) {
			$projectOptions[] = [
				'label' => $project['name'],
				'value' => $project['full_name']
			];
		}
		$form['fields']['project'] = [
			'name' => 'project',
			'label' => 'Project',
			'type' => 'select',
			'selected' => isset($params['project']) ? $params['project'] : '',
			'options' => $projectOptions
		];


		$images = $this->_dockerApi->getImages();
		$img = [];
		foreach( $images as $image ) {

			if ( !preg_match('/magento2-.*/', $image, $matches) )
				continue;

			$label = $matches[0];
			$img[] = [
				'value' => $image,
				'label' => $label
			];
		}

		$selected = isset( $params['image'] ) ? $params['image'] : null;
		$form['fields'][] = [
			'name' => 'image',
			'label' => 'Select docker image',
			'type' => 'select',
			'options' => $img,
			'selected' => $selected
		];

		include(dirname(__FILE__)."/../views/view.form.php");
	}

	/**
	 * Prepare the form for the second setup step and include the view
	 * Get all the modules used in the project, create the form ans show the view
	 * @param $params
	 */
	protected function _do_setup_step_2($params)
	{
		$form = [
			'title' => 'Select versions',
			'method' => 'POST',
			'action' => $this->_action,
			'fields' => [],
			'hidden' => [
				'name' => $params['name'],
				'project' => $params['project'],
				'image' => $params['image'],
				'volume' => $params['volume'],
				'media_volume' => $params['media_volume'],

				// Step to send the user to after form submission
				'step' => 3,
			],
		];

		// Get all the git repositories for the modules in the given project
		$repos = $this->_githubApi->getProjectModuleRepos( $params['project'] );

		// Get all the default branches of the modules
		$defaults = $this->_githubApi->getProjectModuleDefaults( $params['project'] );

		if ( preg_match('/(.[^\/]*){2}$/', $params['image'], $matches) ) {
			$tags = $this->_dockerApi->getImageTags($matches[0]);

			$form['fields'][] = [
				'name' => 'image_tag',
				'label' => 'Select docker image version',
				'type' => 'select',
				'options' => $tags,
				'selected' => 'latest'
			];
		}

		$modules = [];

		foreach($repos as $repo) {

			$name = $this->_githubApi->getModuleName($repo);

			$modules[$name] = $this->_githubApi->getBranches($repo);
		}

		foreach($modules as $name => $branches) {

			if ( !isset($defaults[$name]) )
				continue;

			$default = $defaults[$name];

			// Branches in composer.json are prepended with dev-, make sure that the default branch actually
			// exists within the module and replace the prepended dev- if needed
			if ( !in_array($default, $branches) &&  preg_match('/^dev-/', $default) )
				$default = preg_replace('/^dev-/', '', $default);

			$form['fields'][] = [
				'name' => "modules[${name}]",
				'label' => $name,
				'options' => $branches,
				'type' => 'select',
				'selected' => $default
			];
		}

		include(dirname(__FILE__)."/../views/view.form.php");
	}

	/**
	 * Set up and start the container
	 * @param $params
	 */
	protected function _do_setup_step_3($params)
	{
		$name = $params['name'];

		$modules = [];
		if ( isset($params['modules'] )) {
			foreach($params['modules'] as $key => $value) {
				if ( $value )
					$modules[$key] = $value;
			}

			// Create a hash based on the modules
			$text = '';
			foreach($modules as $key => $val)
				$text .= "${key}:${val};";

			$hash = md5($text);
			// Shorten the name
			$hash = substr($hash, 0, 5);
			$name .= "-${hash}";
		}

		if ( $container = $this->_dockerApi->getContainer($name) )
			echo "Container <b>${name}</b> already exists <br />";

		if ( !$container ) {
			if (!$this->_dockerApi->pullImage($params['image'], $params['image_tag'])) {
				echo 'Cannot pull';
				exit;
			}

			$appPassword = md5(uniqid());
			$rootPassword = md5(uniqid());
			$data = [
				"Image" => $params['image'] . ':' . $params['image_tag'],
				"Binds" => [
					"${params['volume']}:/data/web/includes",
					"/root/.composer/auth.json:/data/web/.composer/auth.json",
				],
				'Env' => [
					"ROOT_PASSWORD=${rootPassword}",
					"APP_PASSWORD=${appPassword}",
					"PROJECT_TYPE=" . $this->_projectType,
				],
			];

			if ( !empty($params['media_volume']) )
				$data['Binds'][] = "${params['media_volume']}:/data/web/magento2/pub/media";



			$container = $this->_dockerApi->createContainer($name, $params, $data);
			echo 'Created container <b>' . $container->getName() . '</b> <br />';
		}

		if ( $container->isRunning() ) {
			echo 'Container is already running. </br >';

			echo 'Running composer update <br />';
			$cmd = '/usr/local/bin/composer';
			$args = ['update'];
			$this->_dockerApi->executeCommand($container ->getName(), $cmd, $args);

		} else {
			$this->_dockerApi->startContainer($container->getName());
			echo "Container is starting up.. </br >";

			if ( !empty($modules) ) {

				// Docker requires the arguments to be an array
				$cmd = '/usr/local/bin/composer';
				$args = ['require', '--dev'];

				foreach($params['modules'] as $key => $value) {

					if ( $value === '')
						continue;

					$args[] = $key . ':dev-' . $value;
				}

				$this->_dockerApi->executeCommand($container->getName(), $cmd, $args);

			}
		}

		$this->_showConnectionInstructions($container);
	}
}