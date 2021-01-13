<?php

use GuzzleHttp\Exception\ClientException;

class Docker_API_Wrapper extends API_Wrapper
{

	private $_ghhApiKey;
	private $_ghUsername;

	private $_dockerBase = getenv('DOCKER_API_BASE');
	private $_registryBase = getenv('DOCKER_REGISTRY');
	private $_slackWebhook = getenv('SLACK_WEBHOOK');

	const HOST_IP = getenv('HOST_IP');

	public function __construct($githubApiKey, $githubUsername, $dockerBase = '', $registryBase = '')
	{
		parent::__construct();

		$this->_ghhApiKey = $githubApiKey;
		$this->_ghUsername = $githubUsername;

		if ( $dockerBase )
			$this->_dockerBase = $dockerBase;

		if ( $registryBase )
			$this->_registryBase = $registryBase;

	}

	/**
	 * Build an image from a git repository
	 * @see https://docs.docker.com/engine/api/v1.40/#operation/ImageBuild
	 * @param    $name string Name of the image
	 * @param    $repo string Github repo to build image from
	 * @param    $branch string Branch to build the image from, default is master
	 * @return array|bool
	 */
	public function buildImageFromGithub( $name , $repo, $branch = '' )
	{
		// docker build -t gh-test https://<user_name>:<github_api_key>>@github.com/<repo>
		$imageName = $name;

		$remote = 'http://' . $this->_ghUsername . ':' . $this->_ghhApiKey .  '@github.com/' . $repo . '.git';

		if ( !empty($branch) ) {
			$imageName .= ':' . $branch;
			$remote .= '#' . $branch;
		}

		$url = $this->_dockerBase . 'build?t=' . $imageName . '&remote=' . urlencode($remote) . '&pull=true';

		$res = $this->_post($url);

		return $res;

	}

	/**
	 * Test if a container with the given name already exists
	 * @param $name
	 * @return bool
	 */
	public function containerExists( $name )
	{
		return $this->getContainer($name) !== false;
	}

	/**
	 * Create a contrainer
	 * @see https://docs.docker.com/engine/api/v1.40/#operation/ContainerCreate
	 * @param $name
	 * @param $params
	 * @param $data
	 * @return Docker_Container|false
	 */
	public function createContainer( $name, $params, $data ) {

		$ports = $this->_getRandomPorts();
		$baseUrl = self::HOST_IP . ':' . $ports['80/tcp'][0]['HostPort'];

		$data['Env'][] = "BASE_URL=${baseUrl}";
		$data['Env'][] = 'SSH_PORT=' . $ports['22/tcp'][0]['HostPort'];
		$data['Env'][] = "NOTIFY_SLACK=true";
		$data['Env'][] = $_slackWebhook;
		$data['Env'][] = "CONTAINER_NAME=${name}";
		$data['PortBindings'] = $ports;
		$data['AutoRemove'] = true;


		try {
			$this->_post($this->_dockerBase . "containers/create?name=${name}", $data);

			return $this->getContainer($name);


		} catch ( ClientException $clientException ) {

			// See https://docs.docker.com/engine/api/v1.40/#operation/ContainerCreate
			// for error codes
			if ( $clientException->getCode() === 409 ) {
				return $this->getContainer($name);
			} else {
				var_dump($clientException->getMessage());
				return false;
			}

		}

	}

	/**
	 * Execute a command within a container
	 * @see https://docs.docker.com/engine/api/v1.40/#tag/Exec
	 * @param $name    string        Name or ID of the container to run the command in
	 * @param $command string    Command to run
	 * @param $args array        Arguments of the command
	 * @param $workDir string    Directory to execute the command in
	 * @param $user string        User to run the command as
	 * @return bool
	 */
	public function executeCommand( $name, $command, $args, $workDir = '/data/web/magento2', $user = 'app' )
	{
		$data = [
			"Cmd" => [
				$command
			],
			"WorkingDir" => $workDir,
			"User" => $user,
		];

		foreach($args as $arg) {
			$data['Cmd'][] = $arg;
		}
		$res = $this->_post($this->_dockerBase .  "containers/${name}/exec", $data);

		$res = $this->_post($this->_dockerBase . "exec/" . $res['Id'] . "/start", [
			"Detach" => true,
			"Tty" => false
		]);

		return true;
	}

	/**
	 * Get a container by name
	 * @param $name
	 * @return Docker_Container|false
	 */
	public function getContainer( $name )
	{
		try {
			$data = $this->_get($this->_dockerBase . "containers/${name}/json");

			$container = new Docker_Container($data);
			return $container;

		} catch (ClientException $clientException) {
			return false;
		}
	}

	/**
	 * Get a list of available images in the registry
	 */
	public function getImages()
	{
		$content = $this->_get($this->_registryBase . 'v2/_catalog');

		if ( !isset($content['repositories']) )
			return false;

		foreach( $content['repositories'] as &$repo )
			$repo = preg_replace('/http(s?):\/\//m', '', $this->_registryBase) . $repo;

		return $content['repositories'];
	}

	/**
	 * Gets all the tags for a given image
	 * @param $image
	 * @return false|array
	 */
	public function getImageTags( $image )
	{
		$content = $this->_get($this->_registryBase . "v2/${image}/tags/list");

		if ( !isset($content['tags']) )
			return false;

		return $content['tags'];
	}

	/**
	 * Generate a random usable port numbers
	 * @return array
	 */
	private function _getRandomPorts()
	{
		$config = [];
		$internalPorts = ['22/tcp', '80/tcp'];

		/*
		 * The minimum is set to 5001, as ports in the 1-5000 range are typically
		 * reserved by operating systems
		 *
		 * The max is set to 49151, as ports above this are used as ethereal ports,
		 * see: https://en.wikipedia.org/wiki/Ephemeral_port
		 */
		foreach( $internalPorts as $internalPort ) {
			$hostPort = rand(5001, 49151);
			$config[$internalPort] = [
				[
					"HostPort" => strval($hostPort)
				]
			];
		}

		return $config;
	}

	/**
	 * Get a list of all the containers on thee server
	 * @return Docker_Container[]|false
	 */
	public function listContainers()
	{
		$data = $this->_get($this->_dockerBase . "containers/json");

		$containers = array_map(function($container) {
			return $this->getContainer($container['Id']);
		}, $data);

		return $containers;

	}

	/**
	 * Pull an image from a remote repository
	 * @param $name
	 * @return bool
	 */
	public function pullImage( $name, $tag )
	{
		$res = $this->_post($this->_dockerBase . "images/create?fromImage=${name}&tag=${tag}");
		return $res;
	}



	/**
	 * Starts a container by name or ID
	 * @param $name
	 * @return bool
	 */
	public function startContainer($name)
	{
		$res = $this->_post($this->_dockerBase . "containers/${name}/start");

		return $res;
	}

	/**
	 * Stop a docker container
	 * @see https://docs.docker.com/engine/api/v1.40/#operation/ContainerStop
	 * @param	$name string Name or ID of the container
	 * @return 	bool
	 * @throws	GuzzleHttp\Exception\ClientException
	 */
	public function stopContainer( $name )
	{
		$res = $this->_post($this->_dockerBase . "containers/${name}/stop");

		return $res;
	}

}
