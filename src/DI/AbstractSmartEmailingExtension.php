<?php

declare(strict_types=1);

namespace NAttreid\SmartEmailing\DI;

use NAttreid\SmartEmailing\Hooks\SmartEmailingConfig;
use NAttreid\SmartEmailing\SmartEmailingClient;
use Nette\DI\CompilerExtension;

/**
 * Class AbstractSmartEmailingExtension
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class AbstractSmartEmailingExtension extends CompilerExtension
{
	private $defaults = [
		'username' => null,
		'apiKey' => null,
		'listId' => null,
		'debug' => false
	];

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		$smartEmailing = $this->prepareHook($config);

		$builder->addDefinition($this->prefix('client'))
			->setType(SmartEmailingClient::class)
			->setArguments([$config['debug'], $smartEmailing]);
	}

	protected function prepareHook(array $config)
	{
		$smartEmailing = new SmartEmailingConfig;
		$smartEmailing->username = $config['username'];
		$smartEmailing->apiKey = $config['apiKey'];
		$smartEmailing->listId = $config['listId'];
		return $smartEmailing;
	}
}