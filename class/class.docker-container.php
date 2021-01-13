<?php


class Docker_Container
{

	private $_portBindings;
	private $_id;
	private $_name;
	private $_status;
	private $_url;

	private $_rootPassword;
	private $_appPassword;

	private $_projectType;


	/**
	 * Docker_Container constructor.
	 * @param $data
	 */
	public function __construct($data)
	{

		// Replace the prepended /
		if ( isset($data['Name']) )
			$this->_name = preg_replace('/^\//', '', $data['Name']);

		if ( isset($data['State']['Status']) )
			$this->_status = $data['State']['Status'];

		// Extract env variables we'e interested in
		if ( isset($data['Config']['Env']) ) {
			$env = [];
			foreach($data['Config']['Env'] as $e) {
				$k = explode('=', $e)[0];
				$v = explode('=', $e)[1];
				$env[$k] = $v;
			}

			if ( isset($env['BASE_URL']) )
				$this->_url = $env['BASE_URL'];

			if ( isset($env['APP_PASSWORD']) )
				$this->_appPassword = $env['APP_PASSWORD'];

			if ( isset($env['ROOT_PASSWORD']) )
				$this->_rootPassword = $env['ROOT_PASSWORD'];

			if ( isset($env['PROJECT_TYPE']) )
				$this->_projectType = $env['PROJECT_TYPE'];

		}

		if ( isset($data['HostConfig']) && isset($data['HostConfig']['PortBindings'] ) ) {
			foreach ( $data['HostConfig']['PortBindings'] as $key => $value ) {
				if ( !$value )
					continue;

				$val = is_array($value) ? $value[0]['HostPort'] : $value['HostPort'];

				$this->_portBindings[$key] = $val;
			}
		}

	}

	/**
	 * @return string
	 */
	public function getAppPassword()
	{
		return $this->_appPassword;
	}

	/**
	 * Get the host port bound to port 80 in the container
	 * @return string|bool
	 */
	public function getHTTPpPort()
	{
		if ( empty( $this->_portBindings ) )
			return false;

		if ( !isset($this->_portBindings['80/tcp']) )
			return false;

		return $this->_portBindings['80/tcp'];
	}

	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @return 	Project_Type
	 */
	public function getProjectType()
	{
		return $this->_projectType;
	}

	/**
	 * Get the host port bound to port 22 in the container
	 * @return string|bool
	 */
	public function getSSHPort()
	{
		if ( empty( $this->_portBindings ) )
			return false;

		if ( !isset($this->_portBindings['22/tcp']) )
			return false;

		return $this->_portBindings['22/tcp'];
	}

	/**
	 * @return string
	 */
	public function getRootPassword()
	{
		return $this->_rootPassword;
	}

	/**
	 * Get the base URL passed tot he container, with http:// prepended if it is missing
	 * @return mixed
	 */
	public function getHttpUrl()
	{
		if ( !preg_match('/^https?:\/\//', $this->_url) )
			return 'http://' . $this->_url;
		else
			return $this->_url;
	}


	/**
	 * Return wether the status equals runing
	 * @return bool
	 */
	public function isRunning()
	{
		return $this->_status === "running";
	}

}
