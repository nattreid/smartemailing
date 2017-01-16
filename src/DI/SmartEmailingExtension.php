<?php

namespace NAttreid\SmartEmailing\DI;

use NAttreid\SmartEmailing\SmartEmailingClient;
use Nette\DI\CompilerExtension;
use Nette\InvalidStateException;

/**
 * Class SmartEmailingExtension
 *
 * @author Attreid <attreid@gmail.com>
 */
class SmartEmailingExtension extends CompilerExtension
{
	private $defaults = [
		'username' => null,
		'apiKey' => null,
		'listId' => null,
		'debug' => false
	];

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		if ($config['username'] === null) {
			throw new InvalidStateException("SmartEmailing: 'username' does not set in config.neon");
		}
		if ($config['apiKey'] === null) {
			throw new InvalidStateException("SmartEmailing: 'apiKey' does not set in config.neon");
		}

		$client = $builder->addDefinition($this->prefix('client'))
			->setClass(SmartEmailingClient::class)
			->setArguments([$config['debug'], $config['username'], $config['apiKey']]);

		if ($config ['listId'] !== null) {
			$client->addSetup('setListId', [$config['listId']]);
		}
	}
}