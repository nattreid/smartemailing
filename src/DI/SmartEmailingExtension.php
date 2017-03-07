<?php

declare(strict_types = 1);

namespace NAttreid\SmartEmailing\DI;

use NAttreid\Cms\Configurator\Configurator;
use NAttreid\Cms\DI\ExtensionTranslatorTrait;
use NAttreid\SmartEmailing\Hooks\SmartEmailingHook;
use NAttreid\SmartEmailing\SmartEmailingClient;
use NAttreid\WebManager\Services\Hooks\HookService;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Nette\InvalidStateException;

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

	public function loadConfiguration()
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

			$config['username'] = new Statement('?->smartemailingUsername', ['@' . Configurator::class]);
			$config['apiKey'] = new Statement('?->smartemailingApiKey', ['@' . Configurator::class]);
			$config['listId'] = new Statement('?->smartemailingListId', ['@' . Configurator::class]);
		}

		if ($config['username'] === null) {
			throw new InvalidStateException("SmartEmailing: 'username' does not set in config.neon");
		}
		if ($config['apiKey'] === null) {
			throw new InvalidStateException("SmartEmailing: 'apiKey' does not set in config.neon");
		}

		$client = $builder->addDefinition($this->prefix('client'))
			->setClass(SmartEmailingClient::class)
			->setArguments([$config['debug'], $config['username'], $config['apiKey']]);

		if ($config['listId'] !== null) {
			$client->addSetup('setListId', [$config['listId']]);
		}
	}
}