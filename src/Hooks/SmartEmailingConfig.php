<?php

declare(strict_types=1);

namespace NAttreid\SmartEmailing\Hooks;

use Nette\SmartObject;

/**
 * Class SmartEmailingConfig
 *
 * @property string $username
 * @property string $apiKey
 * @property int $listId
 *
 * @author Attreid <attreid@gmail.com>
 */
class SmartEmailingConfig
{
	use SmartObject;

	/** @var string */
	private $username;

	/** @var string */
	private $apiKey;

	/** @var int */
	private $listId;

	public function getUsername(): ?string
	{
		return $this->username;
	}

	public function setUsername(?string $username)
	{
		$this->username = $username;
	}

	public function getApiKey(): ?string
	{
		return $this->apiKey;
	}

	public function setApiKey(?string $apiKey)
	{
		$this->apiKey = $apiKey;
	}

	public function getListId(): ?int
	{
		return $this->listId;
	}

	public function setListId(?int $listId)
	{
		$this->listId = $listId;
	}
}