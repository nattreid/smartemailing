<?php

declare(strict_types=1);

namespace NAttreid\SmartEmailing\DI;

use NAttreid\Cms\Configurator\Configurator;
use NAttreid\Cms\DI\ExtensionTranslatorTrait;
use NAttreid\SmartEmailing\Hooks\SmartEmailingConfig;
use NAttreid\SmartEmailing\Hooks\SmartEmailingHook;
use NAttreid\WebManager\Services\Hooks\HookService;
use Nette\DI\Statement;

if (trait_exists('NAttreid\Cms\DI\ExtensionTranslatorTrait')) {
	class SmartEmailingExtension extends AbstractSmartEmailingExtension
	{
		use ExtensionTranslatorTrait;

		protected function prepareHook(array $config)
		{
			$builder = $this->getContainerBuilder();
			$hook = $builder->getByType(HookService::class);
			if ($hook) {
				$builder->addDefinition($this->prefix('smartEmailingHook'))
					->setType(SmartEmailingHook::class);

				$this->setTranslation(__DIR__ . '/../lang/', [
					'webManager'
				]);

				return new Statement('?->smartEmailing \?: new ' . SmartEmailingConfig::class, ['@' . Configurator::class]);
			} else {
				return parent::prepareHook($config);
			}
		}
	}
} else {
	class SmartEmailingExtension extends AbstractSmartEmailingExtension
	{
	}
}