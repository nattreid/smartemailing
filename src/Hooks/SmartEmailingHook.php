<?php

declare(strict_types=1);

namespace NAttreid\SmartEmailing\Hooks;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use IPub\FlashMessages\FlashNotifier;
use NAttreid\Cms\Configurator\Configurator;
use NAttreid\Cms\Factories\DataGridFactory;
use NAttreid\Cms\Factories\FormFactory;
use NAttreid\Form\Form;
use NAttreid\SmartEmailing\CredentialsNotSetException;
use NAttreid\SmartEmailing\SmartEmailingClient;
use NAttreid\WebManager\Services\Hooks\HookFactory;
use Nette\ComponentModel\Component;
use Nette\InvalidArgumentException;
use Nette\Utils\ArrayHash;

/**
 * Class SmartEmailingHook
 *
 * @author Attreid <attreid@gmail.com>
 */
class SmartEmailingHook extends HookFactory
{
	/** @var IConfigurator */
	protected $configurator;

	/** @var SmartEmailingClient */
	private $smartEmailingClient;

	public function __construct(FormFactory $formFactory, DataGridFactory $gridFactory, Configurator $configurator, FlashNotifier $flashNotifier, SmartEmailingClient $smartEmailingClient)
	{
		parent::__construct($formFactory, $gridFactory, $configurator, $flashNotifier);
		$this->smartEmailingClient = $smartEmailingClient;
	}

	/** @return Component */
	public function create(): Component
	{
		$form = $this->formFactory->create();

		$form->addText('username', 'webManager.web.hooks.smartEmailing.username')
			->setDefaultValue($this->configurator->smartemailingUsername);
		$form->addText('apiKey', 'webManager.web.hooks.smartEmailing.apiKey')
			->setDefaultValue($this->configurator->smartemailingApiKey);

		try {
			$data = $this->smartEmailingClient->findContactsLists()->data;
			$items = [];
			foreach ($data as $row) {
				$items[$row->id] = $row->name;
			}
			$select = $form->addSelectUntranslated('list', 'webManager.web.hooks.smartEmailing.list', $items, 'form.none');

			$select->setDefaultValue($this->configurator->smartemailingListId);

		} catch (ClientException $ex) {
		} catch (CredentialsNotSetException $ex) {
		} catch (InvalidArgumentException $ex) {
		} catch (ConnectException $ex) {
		}

		$form->addSubmit('save', 'form.save');

		$form->onSuccess[] = [$this, 'smartemailingFormSucceeded'];

		return $form;
	}

	public function smartemailingFormSucceeded(Form $form, ArrayHash $values)
	{
		$this->configurator->smartemailingUsername = $values->username;
		$this->configurator->smartemailingApiKey = $values->apiKey;
		$this->configurator->smartemailingListId = $values->list;

		$this->flashNotifier->success('default.dataSaved');
	}
}