<?php

declare(strict_types=1);

namespace NAttreid\SmartEmailing\Hooks;

use Nette\SmartObject;

/**
 * Class SmartEmailingConfig
 *
 * @property string|null $username
 * @property string|null $apiKey
 * @property int|null $listId
 *
 * @author Attreid <attreid@gmail.com>
 */
class SmartEmailingConfig
{
	use SmartObject;

	/** @var string|null */
	private $username;

	/** @var string|null */
	private $apiKey;

	/** @var int|null */
	private $listId;

	protected function getUsername(): ?string
	{
		return $this->username;
	}

	protected function setUsername(?string $username)
	{
		$this->username = $username;
	}

	protected function getApiKey(): ?string
	{
		return $this->apiKey;
	}

	protected function setApiKey(?string $apiKey)
	{
		$this->apiKey = $apiKey;
	}

	protected function getListId(): ?int
	{
		return $this->listId;
	}

	protected function setListId(?int $listId)
	{
		$this->listId = $listId;
	}
}