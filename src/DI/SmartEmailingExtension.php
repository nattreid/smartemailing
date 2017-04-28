<?php

declare(strict_types=1);

namespace NAttreid\SmartEmailing\DI;

use NAttreid\Cms\Configurator\Configurator;
use NAttreid\Cms\DI\ExtensionTranslatorTrait;
use NAttreid\SmartEmailing\Hooks\SmartEmailingConfig;
use NAttreid\SmartEmailing\Hooks\SmartEmailingHook;
use NAttreid\SmartEmailing\SmartEmailingClient;
use NAttreid\WebManager\Services\Hooks\HookService;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;

/**
 * Class SmartEmailingExtension
 *
 * @author Attreid <attreid@gmail.com>
 */
class SmartEmailingExtension extends CompilerExtension
{
	use ExtensionTranslatorTrait;

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

		$hook = $builder->getByType(HookService::class);
		if ($hook) {
			$builder->addDefinition($this->prefix('smartEmailingHook'))
				->setClass(SmartEmailingHook::class);

			$this->setTranslation(__DIR__ . '/../lang/', [
				'webManager'
			]);

			$smartEmailing = new Statement('?->smartEmailing \?: new ' . SmartEmailingConfig::class, ['@' . Configurator::class]);
		} else {
			$smartEmailing = new SmartEmailingConfig;
			$smartEmailing->username = $config['username'];
			$smartEmailing->apiKey = $config['apiKey'];
			$smartEmailing->listId = $config['listId'];
		}

		$builder->addDefinition($this->prefix('client'))
			->setClass(SmartEmailingClient::class)
			->setArguments([$config['debug'], $smartEmailing]);
	}
}