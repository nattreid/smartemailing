<?php

declare(strict_types = 1);

namespace NAttreid\SmartEmailing;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use NAttreid\SmartEmailing\Helper\Contact;
use Nette\SmartObject;
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
	use SmartObject;

	/** @var Client */
	private $client;

	/** @var string */
	private $uri = 'https://app.smartemailing.cz/api/v3/';

	/** @var string */
	private $username;

	/** @var string */
	private $apiKey;

	/** @var int */
	private $listId;

	/** @var bool */
	private $debug;

	/**
	 * Client constructor.
	 * @param bool $debug
	 * @param string $username
	 * @param string $apiKey
	 */
	public function __construct(bool $debug, string $username, string $apiKey)
	{
		$this->username = $username;
		$this->apiKey = $apiKey;
		$this->debug = $debug;
	}

	/**
	 * Set default ContactList for Contact
	 * @param int|null $id
	 */
	public function setListId(int $id = null)
	{
		$this->listId = is_int($id) ? $id : null;
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

	private function getClient(): Client
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
	 * @throws ConnectException
	 * @throws ClientException
	 */
	private function request(string $method, string $url, array $args = [])
	{
		if (empty($this->username) || empty($this->apiKey)) {
			throw new CredentialsNotSetException('Username and apiKey must be set');
		}

		try {
			$options = [
				RequestOptions::AUTH => [
					$this->username,
					$this->apiKey
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
				default:
					throw $ex;
					break;
				case 404:
				case 422:
					if ($this->debug) {
						throw $ex;
					} else {
						return false;
					}
			}
		}
		return false;
	}

	/**
	 * @param string $url
	 * @return bool|stdClass
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	private function get(string $url)
	{
		return $this->request('GET', $url);
	}

	/**
	 * @param string $url
	 * @param string[] $args
	 * @return bool|stdClass
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	private function post(string $url, array $args = [])
	{
		return $this->request('POST', $url, $args);
	}

	/**
	 * @param string $url
	 * @return bool
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	private function delete(string $url): bool
	{
		return $this->request('DELETE', $url);
	}

	/**
	 * @param string $url
	 * @param string[] $args
	 * @return bool|stdClass
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	private function patch(string $url, array $args = [])
	{
		return $this->request('PATCH', $url, $args);
	}

	/**
	 * Aliveness test
	 * @return stdClass
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function ping()
	{
		$response = $this->client->get('ping');
		return $this->getResponse($response);
	}

	/**
	 * Login test
	 * @return stdClass|false
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function testLogin()
	{
		return $this->post('check-credentials');
	}

	/**
	 * Get Contactlists
	 * @param string[] $select Allowed values: "id", "name", "category", "publicname", "sendername", "senderemail", "replyto", "signature", "segment_id"
	 * @return false|stdClass
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function findContactsLists(string...$select)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function getContactList(int $id, string...$select)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function createCustomField(string $name, string $type)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function findCustomFields(array $filter = [], array $select = [], array $sort = [], int $limit = 500, int $offset = 0)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function getCustomField(int $id, string...$select)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function deleteCustomField(int $id): bool
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function findContactCustomField(array $filter = [], array $select = [], array $sort = [], int $limit = 500, int $offset = 0)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function findContacts(array $filter = [], array $select = [], array $sort = [], int $limit = 500, int $offset = 0)
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
	 * @param int $id
	 * @param string[] $select Allowed values: "id", "language", "blacklisted", "emailaddress", "name", "surname", "titlesbefore", "titlesafter", "birthday", "nameday", "salution", "gender", "company", "street", "town", "country", "postalcode", "notes", "phone", "cellphone", "realname"
	 * @return bool|stdClass
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function getContact(int $id, array $select = [])
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function createCustomfieldOption(int $customfieldId, int $order, string $name)
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
	public function deleteCustomfieldOption(int $id)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function findCustomFieldOptions(array $filter = [], array $select = [], array $sort = [], int $limit = 500, int $offset = 0)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function getCustomFieldOption(int $id, string...$select)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function updateCustomFieldOption(int $id, int $customfieldId, int $order, string $name)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function createEmail(string $title, string $html, string $text = null, bool $template = false, int $footerId = null)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function findEmails(array $select = [], array $sort = [], int $limit = 500, int $offset = 0)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function getEmail(int $id, string...$select)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function createWebhook(string $url, string $event)
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function deleteWebhook(int $id): bool
	{
		return $this->delete('web-hooks/' . $id);
	}

	/**
	 * Blacklist email
	 * @param string $email
	 * @return bool|stdClass
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function blacklisted(string $email)
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
	 * @param Contact $contact
	 * @return bool|stdClass
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function addContact(Contact $contact)
	{
		if ($contact->emailaddress === null) {
			throw new InvalidArgumentException("'emailaddress' must be set.");
		}
		$data = get_object_vars($contact);

		$data['blacklisted'] = 0;

		if ($this->listId !== null) {
			$data['contactlists'] = [
				[
					'id' => $this->listId,
					'status' => 'confirmed'
				]
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
	 * @throws CredentialsNotSetException
	 * @throws ConnectException
	 * @throws ClientException
	 */
	public function send(string $senderName, string $senderEmail, string $recipientName, string $recipientEmail, string $subject, string $content, array $attachments = [])
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