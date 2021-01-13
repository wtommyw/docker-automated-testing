<?php

class Wordpress_Setup_Controller extends Setup_Controller
{

	public function __construct( $dockerApi, $githubApi )
	{
		parent::__construct( $dockerApi, $githubApi );

		$this->_projectType = Project_Type::WORDPRESS;
	}

	/**
	 * Prepare the form for the first setup step and include the view
	 * @param $params
	 */
	protected function _do_setup_step_1( $params )
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

		$projects = $this->_githubApi->getRepositories('/WP-WEB/');
		$options = [];

		foreach ( $projects as $project ) {
			$options[] = [
				'label' => $project['name'],
				'value' => $project['full_name']
			];
		}

		$form['fields']['project'] = [
			'name' => 'project',
			'label' => 'Project',
			'type' => 'select',
			'selected' => isset($params['project']) ? $params['project'] : '',
			'options' => $options
		];

		include(dirname(__FILE__)."/../views/view.form.php");
	}

	/**
	 * Prepare the form for the second setup step and include the view*
	 * @param $params
	 */
	protected function _do_setup_step_2( $params )
	{
		$form = [
			'title' => 'Select branch',
			'method' => 'POST',
			'action' => $this->_action,
			'fields' => [],
			'hidden' => [
				'name' => $params['name'],
				'project' => $params['project'],
				'volume' => $params['volume'],

				// Step to send the user to after form submission
				'step' => 3,
			],
		];

		$branches = $this->_githubApi->getBranches($params['project']);

		$form['fields']['branch'] = [
			'name' => 'branch',
			'label' => 'Select branch to deploy:',
			'type' => 'select',
			'selected' => isset($params['branch']) ? $params['branch'] : 'master',
			'options' => $branches
		];

		include(dirname(__FILE__) . "/../views/view.form.php");
	}

	/**
	 * Set up and start the container
	 * @param $params
	 */
	protected function _do_setup_step_3( $params )
	{
		$name = strtolower($params['project']);

		if ( !$this->_dockerApi->buildImageFromGithub($name, $params['project'], $params['branch']) ) {
			echo 'Could not build image ' . $name;
			exit;
		}

		$wpPassword = md5(uniqid());
		$rootPassword = md5(uniqid());
		$data = [
			"Image" => $name . ':' . $params['branch'],
			"Binds" => [
				"${params['volume']}/db.sql:/root/db.sql",
				"${params['volume']}/ssh:/root/ssh",
			],
			'Env' => [
				"ROOT_PASSWORD=${rootPassword}",
				"WP_PASSWORD=${wpPassword}",
				"PROJECT_TYPE=" . $this->_projectType,
			],
		];

		if ( isset($params['media_volume']) ) {
			$data['Binds'][] = "${params['media_volume']}:/home/wordpress/public_html/wp-content/uploads";
		}

		if ( !$container = $this->_dockerApi->createContainer($params['name'] . '-' . $params['branch'], $params, $data) ) {
			echo 'Could not create container';
			exit;
		}

		$this->_dockerApi->startContainer($container->getName());

		$this->_showConnectionInstructions($container);

	}

}