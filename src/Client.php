<?php

namespace NAttreid\SmartEmailing;

use GuzzleHttp\Client as GClient;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * Class Client
 *
 * @author Attreid <attreid@gmail.com>
 */
class Client
{
	/** @var GClient */
	private $client;

	/** @var string */
	private $uri;

	/** @var string */
	private $username;

	/** @var string */
	private $password;

	/**
	 * Client constructor.
	 * @param string $username
	 * @param string $password
	 */
	function __construct($username, $password)
	{
		$this->uri = 'https://app.smartemailing.cz/api/v3/';
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * @param ResponseInterface $response
	 * @return mixed
	 */
	private function getResponse(ResponseInterface $response)
	{
		$json = $response->getBody()->getContents();
		if (!empty($json)) {
			return Json::decode($json);
		}
		return null;
	}

	private function getClient()
	{
		if ($this->client === null) {
			$this->client = new GClient(['base_uri' => 'https://app.smartemailing.cz/api/v3/']);
		}
		return $this->client;
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param $args
	 * @return bool|stdClass
	 */
	private function request($method, $url, $args = [])
	{
		try {
			$options = [
				RequestOptions::AUTH => [
					$this->username,
					$this->password
				]
			];

			if (count($args) > 1) {
				$options[RequestOptions::JSON] = $args;
			}

			$response = $this->getClient()->request($method, $url, $options);
			return $this->getResponse($response);
		} catch (ClientException $ex) {
			return false;
		}
	}

	/**
	 * @param string $url
	 * @return bool|stdClass
	 */
	private function get($url)
	{
		return $this->request('GET', $url);
	}

	/**
	 * @param string $url
	 * @param array $args
	 * @return bool|stdClass
	 */
	private function post($url, $args = [])
	{
		return $this->request('POST', $url, $args);
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	private function delete($url)
	{
		$response = $this->request('DELETE', $url);
		return $response === false ? false : true;
	}

	/**
	 * @return stdClass
	 */
	public function ping()
	{
		$response = $this->client->get('ping');
		return $this->getResponse($response);
	}

	/**
	 * @return stdClass|false
	 */
	public function testLogin()
	{
		return $this->post('check-credentials');
	}

	/**
	 * @param array $select Allowed values: "id", "name", "category", "publicname", "sendername", "senderemail", "replyto", "signature", "segment_id"
	 * @return false|stdClass
	 */
	public function findContacts(...$select)
	{
		$args = '';
		if (count($select) > 0) {
			$args = '?select=' . implode(',', $select);
		}
		return $this->get('contactlists' . $args);
	}

	/**
	 * @param int $id
	 * @param array $select Allowed values: "id", "name", "category", "publicname", "sendername", "senderemail", "replyto", "signature", "segment_id"
	 * @return false|stdClass
	 */
	public function getContact($id, ...$select)
	{
		$args = '';
		if (count($select) > 0) {
			$args = '?select=' . implode(',', $select);
		}
		return $this->get('contactlists/' . $id . $args);
	}

	/**
	 * @param string $name
	 * @param string $type Allowed values: "text", "textarea", "date", "checkbox", "radio", "select"
	 * @return bool|stdClass
	 */
	public function createCustomField($name, $type)
	{
		return $this->post('customfields', [
			'name' => $name,
			'type' => $type
		]);
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function deleteCustomField($id)
	{
		return $this->delete('customfields/' . $id);
	}

}