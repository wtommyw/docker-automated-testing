<?php

abstract class Setup_Controller
{

	protected $_dockerApi;
	protected $_githubApi;
	protected $_projectType;
	protected $_action;

	/**
	 * Docker_Setup_Controller constructor.
	 * @param 	$dockerApi Docker_API_Wrapper API Wrapper instance for Docker
	 * @param 	$githubApi Github_API_Wrapper API Wrapper instance for Github
	 */
	public function __construct($dockerApi, $githubApi)
	{
		$this->_dockerApi = $dockerApi;
		$this->_githubApi = $githubApi;
	}

	/**
	 * Prepare the form for the first setup step and include the view
	 * @param $params
	 */
	abstract protected function _do_setup_step_1( $params );

	/**
	 * Prepare the form for the second setup step and include the view*
	 * @param $params
	 */
	abstract protected function _do_setup_step_2( $params );

	/**
	 * Set up and start the container
	 * @param $params
	 */
	abstract protected function _do_setup_step_3( $params );

	/**
	 * Handle the incoming request and do the right step
	 * @param $params
	 */
	public function handle( $params )
	{
		if ( !isset($params['step']) )
			$params['step'] = 1;

		call_user_func([$this, "_do_setup_step_${params['step']}"], $params);
	}

	/**
	 * Display the connection instructions on screen for the given container
	 * @param $container Docker_Container
	 */
	 protected function _showConnectionInstructions($container)
	{
		echo '<br /> Acces the container using: <br />';

		$httpPort = $container->getHTTPpPort();
		echo '<ul>' .
			"<li> <a href=\"http://42f.test:${httpPort}\" target=\"_blank\"> http://42f.test:${httpPort} </a> </li>" .
			'<li>Login as <b>app</b>: <code>ssh -p' . $container->getSSHPort() . ' app@42f.test</code> </li>' .
			'<li>App\'s password <b>' . $container->getAppPassword() . '</b> </li>' .
			'<li>Root\'s password <b>' . $container->getRootPassword() . '</b> </li>' .
			'</ul>';
	}

}