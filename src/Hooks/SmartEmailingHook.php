<?php

declare(strict_types=1);

namespace NAttreid\SmartEmailing\Hooks;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use NAttreid\Form\Form;
use NAttreid\SmartEmailing\CredentialsNotSetException;
use NAttreid\SmartEmailing\SmartEmailingClient;
use NAttreid\WebManager\Services\Hooks\HookFactory;
use Nette\ComponentModel\Component;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

/**
 * Class SmartEmailingHook
 *
 * @author Attreid <attreid@gmail.com>
 */
class SmartEmailingHook extends HookFactory
{
	/** @var IConfigurator */
	protected $configurator;

	public function init(): void
	{
		if (!$this->configurator->smartEmailing) {
			$this->configurator->smartEmailing = new SmartEmailingConfig;
		}
	}

	/** @return Component */
	public function create(): Component
	{
		$form = $this->formFactory->create();

		$form->addText('username', 'webManager.web.hooks.smartEmailing.username')
			->setDefaultValue($this->configurator->smartEmailing->username);
		$form->addText('apiKey', 'webManager.web.hooks.smartEmailing.apiKey')
			->setDefaultValue($this->configurator->smartEmailing->apiKey);

		try {
			$smartEmailingClient = new SmartEmailingClient(false, $this->configurator->smartEmailing);
			$data = $smartEmailingClient->findContactsLists()->data;
			$items = [];
			foreach ($data as $row) {
				$items[$row->id] = $row->name;
			}
			$select = $form->addSelectUntranslated('list', 'webManager.web.hooks.smartEmailing.list', $items, 'form.none');

			try {
				$select->setDefaultValue($this->configurator->smartEmailing->listId);
			} catch (InvalidArgumentException $ex) {

			}
		} catch (ClientException | CredentialsNotSetException | InvalidStateException | ConnectException $ex) {
			Debugger::log($ex, Debugger::EXCEPTION);
		}

		$form->addSubmit('save', 'form.save');

		$form->onSuccess[] = [$this, 'smartemailingFormSucceeded'];

		return $form;
	}

	public function smartemailingFormSucceeded(Form $form, ArrayHash $values): void
	{
		$config = $this->configurator->smartEmailing;

		$config->username = $values->username ?: null;
		$config->apiKey = $values->apiKey ?: null;
		$config->listId = empty($values->list) ? null : $values->list;

		$this->configurator->smartEmailing = $config;

		$this->flashNotifier->success('default.dataSaved');

		$this->onDataChange();
	}
}