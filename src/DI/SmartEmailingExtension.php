<?php

namespace NAttreid\SmartEmailing\DI;

use NAttreid\SmartEmailing\Client;
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
		'key' => null,
		'debug' => false
	];

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		if ($config['username'] === null) {
			throw new InvalidStateException("SmartEmailing: 'username' does not set in config.neon");
		}
		if ($config['key'] === null) {
			throw new InvalidStateException("SmartEmailing: 'key' does not set in config.neon");
		}

		$builder->addDefinition($this->prefix('client'))
			->setClass(Client::class)
			->setArguments([$config['debug'], $config['username'], $config['key']]);
	}
}