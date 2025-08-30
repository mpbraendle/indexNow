<?php

/**
 * @file IndexNowSettingsForm.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @brief Settings form for the IndexNow plugin.
 */

namespace APP\plugins\generic\indexNow;

use PKP\form\Form;
use APP\core\Application;
use APP\template\TemplateManager;
use APP\notification\NotificationManager;
use APP\notification\Notification;

class IndexNowSettingsForm extends Form {
	/**
	 * Constructor
	 * @param $plugin CustomHeaderPlugin
	 * @param $contextId int
	 */
	function __construct(public $plugin, public $contextId) {
		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		$this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
		$this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$this->_data = ['key' => $this->plugin->getSetting($this->contextId, 'key')];
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(['key']);
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());
		return parent::fetch($request, $template, $display);
	}

	/**
	 * Save settings.
	 */
	function execute(...$functionArgs) {
		parent::execute(...$functionArgs);

		$request = Application::get()->getRequest();
		$this->plugin->updateSetting($this->contextId, 'key', $this->getData('key'), 'string');
		$notificationManager = new NotificationManager();
		$notificationManager->createTrivialNotification($request->getUser()->getId(), Notification::NOTIFICATION_TYPE_SUCCESS);
	}
}
