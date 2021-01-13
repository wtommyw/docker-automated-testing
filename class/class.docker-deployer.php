<?php

class Docker_Deployer
{

	private $_dockerApi;
	private $_githubApi;
	private $_controllers;
	private $_actions;

	/**
	 * Docker_Deployer constructor.
	 * @param 	string $githubKey API KEy for github
	 * @param 	string $ghUserNmae  Github username to use
	 */
	public function __construct( $githubKey = null, $ghUserNmae = null )
	{
		if ( !$githubKey )
			$githubKey = getenv('GITHUB_API_KEY');

		if ( !$ghUserNmae )
			$ghUserNmae = getenv('GITHUB_USERNAME');

		$this->_dockerApi = new Docker_API_Wrapper($githubKey, $ghUserNmae);
		$this->_githubApi = new Github_API_Wrapper($githubKey);

		$this->_registerActions();
		$this->_registerControllers();
	}

	/**
	 * Get a list of supported project types, taken from $this->_controllers
	 */
	private function getSupportedTypes()
	{
		return array_keys($this->_controllers);
	}

	/**
	 * Handle the incoming request
	 * @param $request_url
	 */
	public function handle( $request_url )
	{

		$request = explode('?', $request_url);
		$url = $request[0];
		$url = preg_replace('/^\//', '', $url);

		$actions = array_keys($this->_actions);
		// Empty URL equals to /
		if ( in_array($url, $actions) || $url === '' ) {
			call_user_func($this->_actions[$url], $_REQUEST);

		} else {
			$this->_redirectTo('/');
		}

		exit;
	}

	/**
	 * Register action and their respective methods
	 */
	private function _registerActions()
	{
		$this->_actions = [
			'stop' => [$this, '_stopContainer'],
			'list' => [$this, '_listContainer'],
			'setup' => [$this, '_setupContainer'],
			'update' => [$this, '_updateContainer'],
			null => [$this, '_showNav'],
		];
	}

	/**
	 * Associative array with project types bound to their respective controller, using this
	 * we can easily can $this->_controllers['wordpress']->handle(); to do the wordpress setup
	 */
	private function _registerControllers()
	{
		$this->_controllers = [
			Project_Type::MAGENTO => new Magento_Setup_controller($this->_dockerApi, $this->_githubApi),
			Project_Type::WORDPRESS => new Wordpress_Setup_Controller($this->_dockerApi, $this->_githubApi)
		];
	}

	/**
	 * List all the containers
	 * @param $params
	 */
	private function _listContainer( $params )
	{
		$containers = $this->_dockerApi->listContainers();

		$containers = array_filter($containers, function(Docker_Container $e) {
			return in_array($e->getProjectType(), $this->getSupportedTypes());
		});

		include(dirname(__FILE__) . "/../views/view.overview.php");
	}

	/**
	 * Redirect the user to a different URL
	 * @param $location
	 */
	private function _redirectTo( $location )
	{
		header('Location: ' . $location);
		exit;
	}

	/**
	 * Set up a container
	 * @param $params
	 */
	private function _setupContainer( $params )
	{
		if ( !isset($params['type']) )
			$this->_redirectTo('/');

		if ( !in_array($params['type'], $this->getSupportedTypes() ) )
			$this->_redirectTo('/');

		$this->_controllers[$params['type']]->handle($params);

	}

	/**
	 * Show the home navigation
	 */
	private function _showNav()
	{
		$types = $this->getSupportedTypes();

		include(dirname(__FILE__) . "/../views/view.nav.php");
		exit;
	}

	/**
	 * Stop a container
	 * @param $params
	 */
	private function _stopContainer( $params )
	{
		if ( !isset($params['name']) )
			$this->_redirectTo('list');

		$name = $params['name'];

		try {
			$this->_dockerApi->stopContainer($name);
			$this->_redirectTo('list');

		} catch ( \GuzzleHttp\Exception\ClientException $e ) {
			if ( strpos($e->getMessage(), '404') )
				echo 'Container ' . $name .' could not be found';

			exit;
		}
	}

	private function _updateContainer( $params )
	{
		if ( !isset($params['name']) )
			$this->_redirectTo('list');

		$cmd = '/usr/local/bin/composer';
		$args = ['update'];
		$this->_dockerApi->executeCommand($params['name'], $cmd, $args);
		$this->_redirectTo('list');

	}

}