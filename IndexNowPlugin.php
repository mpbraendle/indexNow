<?php

/**
 * @file IndexNowPlugin.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief IndexNow plugin; see https://www.indexnow.org
 */

namespace APP\plugins\generic\indexNow;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\Registry;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\core\JSONMessage;
use PKP\facades\Locale;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class IndexNowPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                Hook::add('Publication::publish', function($hookName, $args) {
                    $newPublication = $args[0];
                    $submission = $args[2];

                    $application = Application::get();
                    $request = $application->getRequest();
                    $url = $request->getDispatcher()->url(
                        $request, Application::ROUTE_PAGE, null, 'article', 'view',
                        [$newPublication->getData('urlPath') ?? $submission->getId()],
                        urlLocaleForPage: ''
                    );

                    $key = $this->getSetting($submission->getData('contextId'), 'key');
                    if (empty($key)) return Hook::CONTINUE;

                    $httpClient = $application->getHttpClient();
                    $response = $httpClient->request(
                        'GET',
                        'https://api.indexnow.org/indexnow',
                        ['query' => ['url' => $url, 'key' => $key]]
                    );

                    return Hook::CONTINUE;
                });
            }
            return true;
        }
        return false;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.indexNow.name');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.indexNow.description');
    }

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		return array_merge(
			$this->getEnabled()?[
				new LinkAction(
					'settings',
					new AjaxModal(
							$router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']),
							$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			]:[],
			parent::getActions($request, $verb)
		);
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();

				$templateMgr = TemplateManager::getManager($request);
				$form = new IndexNowSettingsForm($this, $context?$context->getId():CONTEXT_ID_NONE);

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}
}
