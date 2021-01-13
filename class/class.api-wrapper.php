<?php

use GuzzleHttp\Client;


/**
 * Class API_Wrapper Basic class for API wrappers,
 * this class sets up a HTTP client and some methods to use that client.
 */
abstract class API_Wrapper
{
	protected $_client;

	/**
	 * API_Wrapper constructor.
	 * Sets up a default GuzzleHttp\Client to use
	 */
	public function __construct()
	{
		$this->_client = new Client();
	}

	/**
	 * Shorter method to call a GET request
	 * @param $url
	 * @param array $headers
	 * @return array|bool
	 */
	protected function _get( $url, $headers = [] )
	{
		return $this->_request($url, 'GET', null, $headers);
	}

	/**
	 * Shorter method to call a POST request
	 * @param $url
	 * @param array $body
	 * @param array $headers
	 * @return array|bool
	 */
	protected function _post( $url, $body = null, $headers = [] )
	{
		return $this->_request($url, 'POST', $body, $headers);
	}

	/**
	 * Perform a HTTP request
	 * @param 	$url
	 * @param 	string $method
	 * @param 	string $body
	 * @param 	$headers array
	 * @return 	bool|array Returns true if request was successful but no response was given,
	 *                     returns associative array on succesful response and
	 *                     returns false when request failed.
	 *
	 */
	protected function _request( $url, $method = 'GET', $body = null, $headers = [] )
	{
		$response = $this->_client->request($method, $url, [
			'headers' => $headers,
			'json' => $body
		]);

		if ( !$response )
			return false;

		$content = $response->getBody()->getContents();
		$content = json_decode($content, true);

		if ( !isset($content) && $response->getStatusCode() === 200 )
			return true;


		return $content;
	}

}