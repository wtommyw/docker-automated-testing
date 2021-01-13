<?php

class Github_API_Wrapper extends API_Wrapper
{

	private $_apiKey;

	const BASE_URL = "https://api.github.com/";

	public function __construct( $api_key )
	{
		parent::__construct();

		$this->_apiKey = $api_key;
	}

	/**
	 * Get the list of branches for a repository
	 * @param $repoName	string	owner/repo
	 * @return array|bool
	 */
	public function getBranches( $repoName )
	{
		if ( !$data = $this->_get(self::BASE_URL . "repos/${repoName}/branches",  $this->_getHeaders()) )
			return false;

		$branches = array_map(function($element) {
			return $element['name'];
		}, $data);

		return $branches;
	}

	/**
	 * Get the name from a composer module, this is achieved by fetching and reading the composer.json
	 * file in a modules repository
	 * @param $repoName string	owner/name
	 * @param $file string		file to find the info about the module in, default composer.json
	 * @return string|false
	 */
	public function getModuleName( $repoName, $file = 'composer.json' )
	{
		if ( !$data = $this->_get(self::BASE_URL . "repos/${repoName}/contents/${file}",  $this->_getHeaders()) )
			return false;

		if ( !isset($data['content']) )
			return false;

		if ( !$content = base64_decode($data['content']) )
			return false;

		$content = json_decode($content, true);

		if ( !isset($content['name']) )
			return false;

		return $content['name'];

	}

	/**
	 * Get all the default branches used for the modules in a composer (magento 2) project
	 * @param $repoName
	 * @param string $file
	 * @return array|bool
	 */
	public function getProjectModuleDefaults( $repoName, $file = 'composer.json' )
	{
		$data = $this->_get(self::BASE_URL . "repos/${repoName}/contents/${file}", $this->_getHeaders());

		if ( !isset($data['content']) )
			return false;

		if ( !$content = base64_decode($data['content']) )
			return false;

		$data = json_decode($content, true);
		$modules = [];

		if ( isset($data['require']) )
			$modules = $data['require'];

		if ( isset($data['require-dev']) )
			$modules = array_merge($modules, $data['require-dev']);


		return $modules;

	}

	/**
	 * Get all the modules github repositories used required in a composer (magento 2) project
	 * @param $repoName string	owner/name
	 * @param $file string		file to find the info about the module in, default composer.json
	 * @return array|false
	 */
	public function getProjectModuleRepos( $repoName, $file = 'composer.json' )
	{
		$data = $this->_get(self::BASE_URL . "repos/${repoName}/contents/${file}", $this->_getHeaders());

		if ( !isset($data['content']) )
			return false;

		if ( !$content = base64_decode($data['content']) )
			return false;

		// Find all the github reposirty names
		if ( !preg_match_all('/(?<="git@github\.com:).*(?=\.git)/', $content, $matches ) )
			return false;

		return is_array($matches) ? $matches[0] : false;
	}


	/**
	 * Get standard headers for requests
	 * @return array
	 */
	protected function _getHeaders()
	{
		return [
			'Authorization' => 'token ' . $this->_apiKey
		];
	}


	/**
	 * Get all the repositories for an org
	 * @param string $filterRegex Regex to filter projects by
	 * @return array
	 */
	public function getRepositories( $filterRegex = null )
	{
		$pageNum = 1;

		// WEell have to iterate through a few pages as github does not allow us to pull everything
		// in one go
		$repositories = [];
		while(true) {
			$data = $this->_get(self::BASE_URL . "user/repos?per_page=100&direction=desc&sort=updated&page=${pageNum}",  $this->_getHeaders());

			// No response, so this page is empty
			if ( empty($data) )
				break;

			$repositories = array_merge($repositories, $data);
			$pageNum++;

		}

		foreach($repositories as $repo) {

			// Match the full name against the filter if it is set
			if ( isset($filterRegex) )
				if ( !preg_match($filterRegex, $repo['full_name']) )
					continue;

			$repos[] = [
				'name' => $repo['name'],
				'full_name' => $repo['full_name'],
				'url' => $repo['html_url']
			];
		}

		return $repos;

	}

}