<?php

namespace NAttreid\SmartEmailing;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * Class Client
 *
 * @author Attreid <attreid@gmail.com>
 */
class SmartEmailingClient
{
	/** @var Client */
	private $client;

	/** @var string */
	private $uri = 'https://app.smartemailing.cz/api/v3/';

	/** @var string */
	private $username;

	/** @var string */
	private $password;

	/** @var bool */
	private $debug;

	/**
	 * Client constructor.
	 * @param bool $debug
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($debug, $username, $password)
	{
		$this->username = $username;
		$this->password = $password;
		$this->debug = (bool)$debug;
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
			$this->client = new Client(['base_uri' => $this->uri]);
		}
		return $this->client;
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array $args
	 * @return bool|stdClass
	 * @throws CredentialsNotSetException
	 */
	private function request($method, $url, array $args = [])
	{
		if (empty($this->username) || empty($this->password)) {
			throw new CredentialsNotSetException('Username and password must be set');
		}

		try {
			$options = [
				RequestOptions::AUTH => [
					$this->username,
					$this->password
				]
			];

			if (count($args) >= 1) {
				$options[RequestOptions::JSON] = $args;
			}

			$response = $this->getClient()->request($method, $url, $options);

			switch ($response->getStatusCode()) {
				case 200:
				case 201:
					return $this->getResponse($response);
				case 204:
					return true;
			}
		} catch (ClientException $ex) {
			switch ($ex->getCode()) {
				case 404:
				case 422:
					if ($this->debug) {
						throw $ex;
					} else {
						return false;
					}
				case 401:
					throw $ex;
			}
		}
		return false;
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
	 * @param string[] $args
	 * @return bool|stdClass
	 */
	private function post($url, array $args = [])
	{
		return $this->request('POST', $url, $args);
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	private function delete($url)
	{
		return $this->request('DELETE', $url);
	}

	/**
	 * @param string $url
	 * @param string[] $args
	 * @return bool|stdClass
	 */
	private function patch($url, array $args = [])
	{
		return $this->request('PATCH', $url, $args);
	}

	/**
	 * Aliveness test
	 * @return stdClass
	 */
	public function ping()
	{
		$response = $this->client->get('ping');
		return $this->getResponse($response);
	}

	/**
	 * Login test
	 * @return stdClass|false
	 */
	public function testLogin()
	{
		return $this->post('check-credentials');
	}

	/**
	 * Get Contactlists
	 * @param string[] $select Allowed values: "id", "name", "category", "publicname", "sendername", "senderemail", "replyto", "signature", "segment_id"
	 * @return false|stdClass
	 */
	public function findContactsLists(...$select)
	{
		$args = '';
		if (count($select) > 0) {
			$args = '?select=' . implode(',', $select);
		}
		return $this->get('contactlists' . $args);
	}

	/**
	 * Get single Contactlist
	 * @param int $id
	 * @param string[] $select Allowed values: "id", "name", "category", "publicname", "sendername", "senderemail", "replyto", "signature", "segment_id"
	 * @return false|stdClass
	 */
	public function getContactList($id, ...$select)
	{
		$args = '';
		if (count($select) > 0) {
			$args = '?select=' . implode(',', $select);
		}
		return $this->get('contactlists/' . $id . $args);
	}

	/**
	 * Create new Customfield
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
	 * Find Customfield values
	 * @param string[] $filter Allowed values: "id", "name", "type"
	 * @param string[] $select Allowed values: "id", "name", "type"
	 * @param string[] $sort Allowed values: "id", "name", "type". Prepend - to any key for desc direction, eg. '-id'
	 * @param int $limit
	 * @param int $offset
	 * @return bool|stdClass
	 */
	public function findCustomFields(array $filter = [], array $select = [], array $sort = [], $limit = 500, $offset = 0)
	{
		$args = [
			"limit=$limit",
			"offset=$offset",
			'expand=customfield_options'
		];

		foreach ($filter as $variable => $value) {
			$args[] = "$variable=$value";
		}
		if (!empty($select)) {
			$args[] = "select=" . implode(',', $select);
		}
		if (!empty($sort)) {
			$args[] = "sort=" . implode(',', $sort);
		}

		if (!empty($args)) {
			$args = '?' . implode('&', $args);
		}
		return $this->get('customfields' . $args);
	}

	/**
	 * Get single Customfield
	 * @param int $id
	 * @param string[] $select Allowed values: "id", "name", "type"
	 * @return bool|stdClass
	 */
	public function getCustomField($id, ...$select)
	{
		$args = '';
		if (count($select) > 0) {
			$args = '?select=' . implode(',', $select);
		}
		return $this->get('customfields/' . $id . $args);
	}

	/**
	 * Delete Customfield
	 * @param int $id
	 * @return bool
	 */
	public function deleteCustomField($id)
	{
		return $this->delete('customfields/' . $id);
	}

	/**
	 * Get Customfield values
	 * @param string[] $filter Allowed values: "id", "contact_id", "customfield_id", "value", "customfield_options_id"
	 * @param string[] $select Allowed values: "id", "contact_id", "customfield_id", "value", "customfield_options_id"
	 * @param string[] $sort Allowed values: "id", "contact_id", "customfield_id", "value", "customfield_options_id". Prepend - to any key for desc direction, eg. '-id'
	 * @param int $limit
	 * @param int $offset
	 * @return bool|stdClass
	 */
	public function findContactCustomField(array $filter = [], array $select = [], array $sort = [], $limit = 500, $offset = 0)
	{
		$args = [
			"limit=$limit",
			"offset=$offset"
		];

		foreach ($filter as $variable => $value) {
			$args[] = "$variable=$value";
		}
		if (!empty($select)) {
			$args[] = "select=" . implode(',', $select);
		}
		if (!empty($sort)) {
			$args[] = "sort=" . implode(',', $sort);
		}

		if (!empty($args)) {
			$args = '?' . implode('&', $args);
		}
		return $this->get('contact-customfields' . $args);
	}

	/**
	 * Find Contacts
	 * @param string[] $filter Allowed values: "id", "language", "blacklisted", "emailaddress", "name", "surname", "titlesbefore", "titlesafter", "birthday", "nameday", "salution", "gender", "company", "street", "town", "country", "postalcode", "notes", "phone", "cellphone", "realname"
	 * @param string[] $select Allowed values: "id", "language", "blacklisted", "emailaddress", "name", "surname", "titlesbefore", "titlesafter", "birthday", "nameday", "salution", "gender", "company", "street", "town", "country", "postalcode", "notes", "phone", "cellphone", "realname"
	 * @param string[] $sort Allowed values: "id", "language", "blacklisted", "emailaddress", "name", "surname", "titlesbefore", "titlesafter", "birthday", "nameday", "salution", "gender", "company", "street", "town", "country", "postalcode", "notes", "phone", "cellphone", "realname". Prepend - to any key for desc direction, eg. '-id'
	 * @param int $limit
	 * @param int $offset
	 * @return bool|stdClass
	 */
	public function findContacts(array $filter = [], array $select = [], array $sort = [], $limit = 500, $offset = 0)
	{
		$args = [
			"limit=$limit",
			"offset=$offset",
			'expand=customfield_options'
		];

		foreach ($filter as $variable => $value) {
			$args[] = "$variable=$value";
		}
		if (!empty($select)) {
			$args[] = "select=" . implode(',', $select);
		}
		if (!empty($sort)) {
			$args[] = "sort=" . implode(',', $sort);
		}

		if (!empty($args)) {
			$args = '?' . implode('&', $args);
		}
		return $this->get('contacts' . $args);
	}

	/**
	 * Get single Contact
	 * @param string[] $select Allowed values: "id", "language", "blacklisted", "emailaddress", "name", "surname", "titlesbefore", "titlesafter", "birthday", "nameday", "salution", "gender", "company", "street", "town", "country", "postalcode", "notes", "phone", "cellphone", "realname"
	 * @return bool|stdClass
	 */
	public function getContact($id, array $select = [])
	{
		$args = [
			'expand=customfield_options'
		];

		if (!empty($select)) {
			$args[] = "select=" . implode(',', $select);
		}

		if (!empty($args)) {
			$args = '?' . implode('&', $args);
		}
		return $this->get('contacts/' . $id . $args);
	}

	/**
	 * Create new Customfield option
	 * @param int $customfieldId
	 * @param int $order Order of option as displayed in web forms and Contact detail. Lower number will be displayed higher in the list.
	 * @param string $name
	 * @return bool|stdClass
	 */
	public function createCustomfieldOption($customfieldId, $order, $name)
	{
		return $this->post('customfield-options', [
			'customfield_id' => $customfieldId,
			'order' => $order,
			'name' => $name
		]);
	}

	/**
	 * Delete Customfield option
	 * @param int $id
	 * @return bool
	 */
	public function deleteCustomfieldOption($id)
	{
		return $this->delete('customfield-options/' . $id);
	}

	/**
	 * Find Customfield options
	 * @param string[] $filter Allowed values: "id", "customfield_id", "name", "order"
	 * @param string[] $select Allowed values: "id", "customfield_id", "name", "order"
	 * @param string[] $sort Allowed values: "id", "customfield_id", "name", "order". Prepend - to any key for desc direction, eg. '-id'
	 * @param int $limit
	 * @param int $offset
	 * @return bool|stdClass
	 */
	public function findCustomFieldOptions(array $filter = [], array $select = [], array $sort = [], $limit = 500, $offset = 0)
	{
		$args = [
			"limit=$limit",
			"offset=$offset"
		];

		foreach ($filter as $variable => $value) {
			$args[] = "$variable=$value";
		}
		if (!empty($select)) {
			$args[] = "select=" . implode(',', $select);
		}
		if (!empty($sort)) {
			$args[] = "sort=" . implode(',', $sort);
		}

		if (!empty($args)) {
			$args = '?' . implode('&', $args);
		}
		return $this->get('customfield-options' . $args);
	}

	/**
	 * Get single Customfield option
	 * @param int $id
	 * @param string[] $select Allowed values: "id", "customfield_id", "name", "order"
	 * @return bool|stdClass
	 */
	public function getCustomFieldOption($id, ...$select)
	{
		$args = '';
		if (count($select) > 0) {
			$args = '?select=' . implode(',', $select);
		}
		return $this->get('customfield-options/' . $id . $args);
	}

	/**
	 * Update Customfield option
	 * @param int $id
	 * @param int $customfieldId
	 * @param int $order Order of option as displayed in web forms and Contact detail. Lower number will be displayed higher in the list.
	 * @param string $name
	 * @return bool|stdClass
	 */
	public function updateCustomFieldOption($id, $customfieldId, $order, $name)
	{
		return $this->patch('customfield-options/' . $id, [
			'customfield_id' => $customfieldId,
			'order' => $order,
			'name' => $name
		]);
	}

	/**
	 * Create new E-mail
	 * @param string $title
	 * @param string $html
	 * @param string $text
	 * @param bool $template
	 * @param int $footerId
	 * @return bool|stdClass
	 */
	public function createEmail($title, $html, $text = null, $template = false, $footerId = null)
	{
		$data = [
			'title' => $title,
			'htmlbody' => $html,
			'template' => $template ? 1 : 0,
			'footer_id' => $footerId
		];
		if ($text !== null) {
			$data['textbody'] = $text;
		}

		return $this->post('emails', $data);
	}


	/**
	 * Find E-mails
	 * @param string[] $select Allowed values: "id", "name", "title", "htmlbody", "textbody", "created"
	 * @param string[] $sort Allowed values: "id", "name", "title". Prepend - to any key for desc direction, eg. '-id'
	 * @param int $limit
	 * @param int $offset
	 * @return bool|stdClass
	 */
	public function findEmails(array $select = [], array $sort = [], $limit = 500, $offset = 0)
	{
		$args = [
			"limit=$limit",
			"offset=$offset"
		];

		if (!empty($select)) {
			$args[] = "select=" . implode(',', $select);
		}
		if (!empty($sort)) {
			$args[] = "sort=" . implode(',', $sort);
		}

		if (!empty($args)) {
			$args = '?' . implode('&', $args);
		}
		return $this->get('emails' . $args);
	}

	/**
	 * Get single E-mail
	 * @param int $id
	 * @param string[] $select Allowed values: "id", "name", "title", "htmlbody", "textbody", "created"
	 * @return bool|stdClass
	 */
	public function getEmail($id, ...$select)
	{
		$args = '';
		if (count($select) > 0) {
			$args = '?select=' . implode(',', $select);
		}
		return $this->get('emails/' . $id . $args);
	}

	/**
	 * Create new Webhook
	 * @param string $url
	 * @param string $event Allowed values: "new_contact,updated_contact,unsubscribed_contact"
	 * @return bool|stdClass
	 */
	public function createWebhook($url, $event)
	{
		return $this->post('web-hooks', [
			'target_url' => $url,
			'event' => $event
		]);
	}

	/**
	 * Delete Webhook
	 * @param int $id
	 * @return bool
	 */
	public function deleteWebhook($id)
	{
		return $this->delete('web-hooks/' . $id);
	}

	/**
	 * Blacklist email
	 * @param string $email
	 * @return bool|stdClass
	 */
	public function blacklisted($email)
	{
		return $this->post('import', [
			'data' => [
				[
					'emailaddress' => $email,
					'blacklisted' => 1
				]
			]
		]);
	}

	/**
	 * Add contact
	 * @param string $email
	 * @param string $name
	 * @param string $street
	 * @param string $town
	 * @param string $postalcode
	 * @param string $country
	 * @param string $phone
	 * @param int $contactListId
	 * @return bool|stdClass
	 */
	public function addContact($email, $name, $street, $town, $postalcode, $country, $phone, $contactListId = null)
	{
		$data = [
			'emailaddress' => $email,
			'name' => $name,
			'street' => $street,
			'town' => $town,
			'postalcode' => $postalcode,
			'country' => $country,
			'phone' => $phone,
			'blacklisted' => 0
		];

		if ($contactListId !== null) {
			$data['contactlists'] = [
				'id' => $contactListId,
				'status' => 'confirmed'
			];
		}

		return $this->post('import', [
			'data' => [
				$data
			]
		]);
	}

	/**
	 * @param string $senderName
	 * @param string $senderEmail
	 * @param string $recipientName
	 * @param string $recipientEmail
	 * @param string $subject
	 * @param string $content
	 * @param string[] $attachments
	 * @return bool|stdClass
	 */
	public function send($senderName, $senderEmail, $recipientName, $recipientEmail, $subject, $content, array $attachments = [])
	{
		$email = [
			'custom_id' => uniqid(),
			'sendername' => $senderName,
			'senderemail' => $senderEmail,
			'recipientname' => $recipientName,
			'recipientemail' => $recipientEmail,
			'tag' => 'custom_email',
			'email' => [
				'subject' => $subject,
				'htmlbody' => $content
			]
		];

		foreach ($attachments as $attachment) {
			$email['attachments'][] = [
				'file_name' => basename(Strings::webalize($attachment, '._')),
				'content_type' => mime_content_type($attachment),
				'data_base64' => base64_encode(file_get_contents($attachment))
			];
		}

		return $this->post('send/custom-emails', [
			'batch' => [
				$email
			]
		]);
	}
}