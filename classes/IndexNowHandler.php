<?php

/**
 * @file plugins/generic/indexNow/classes/IndexNowHandler.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SitemapHandler
 *
 * @brief Produce a sitemap in IndexNow JSON  format for submitting to search engines.
 */

namespace APP\plugins\generic\indexNow\classes;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Collector;
use APP\submission\Submission;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use PKP\config\Config;
use PKP\core\JSONMessage;
use PKP\core\Registry;
use PKP\facades\Locale;
use PKP\linkAction\LinkAction;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

define('INDEXNOW_URLLIMIT', 10000);

class IndexNowHandler 
{
    /** @var int URL counter */
    var $_urlcount;

    /** @var array the URL list */
    var $_urllist;

    public function _createJSONSitemap($journal)
    {
        $journalId = $journal->getId();
        $request = Application::get()->getRequest();
        $this->_urlcount = 0;

        // Journal root path
        $this->_urllist[] = $request->url($journal->getPath());
        $this->_urlcount++;

        // Issues
        if ($journal->getData('publishingMode') != \APP\journal\Journal::PUBLISHING_MODE_NONE) {
            $this->_urllist[] = $request->url($journal->getPath(), 'issue', 'current');
            $this->_urlcount++;
            $this->_urllist[] =  $request->url($journal->getPath(), 'issue', 'archive');
            $this->_urlcount++;

            $publishedIssues = Repo::issue()->getCollector()
                ->filterByContextIds([$journalId])
                ->filterByPublished(true)
                ->orderBy(Collector::ORDERBY_PUBLISHED_ISSUES)
                ->getMany();

            foreach ($publishedIssues as $issue) {
                $this->_urllist[] = $request->url($journal->getPath(), 'issue', 'view', $issue->getBestIssueId());
                $this->_checkSubmitURLList($journalId);
                
                // Articles for issue
                $submissions = Repo::submission()
                    ->getCollector()
                    ->filterByContextIds([$journal->getId()])
                    ->filterByIssueIds([$issue->getId()])
                    ->filterByStatus([Submission::STATUS_PUBLISHED])
                    ->getMany();

                foreach ($submissions as $submission) {
                    // Details page
                    $this->_urllist[] = $request->url($journal->getPath(), 'article', 'view', [$submission->getBestId()]);
                    $this->_checkSubmitURLList($journalId);

                    // Galley files
                    $galleys = Repo::galley()
                        ->getCollector()
                        ->filterByPublicationIds([($submission->getCurrentPublication()->getId())])
                        ->getMany();

                    foreach ($galleys as $galley) {
                        $this->_urllist[] = $request->url($journal->getPath(), 'article', 'view', [$submission->getBestId(), $galley->getBestGalleyId()]);
                        $this->_checkSubmitURLList($journalId);
                    }
                }
            }
        }

        // Submit remaining URLs
        $this->_submitURLList($journalId);

        return;
    }

    public function _checkSubmitURLList(int $journalId)
    {
        if ($this->_urlcount == INDEXNOW_URLLIMIT)
        {
            $this->_submitURLList($journalId);
            $this->_urlcount = 0;
            $this->_urllist = [];
        }
        else
        {
            $this->_urlcount++;
        }
        return;
    }

    public function _submitURLList(int $journalId)
    {
        $host = $this->_getHost();
        $key = $this->_getKey($journalId);

        $application = Application::get();    
        $httpClient = $application->getHttpClient();
        $response = $httpClient->request(
            'POST',
            'https://api.indexnow.org/indexnow',
            [   
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'host' => $host,
                    'key' => $key,
                    'urlList' => $this->_urllist,
                ],
                'verify' => false,
                'exceptions' => false,
            ]
        );
 
        return;
    }

    public function _getHost()
    {
        $request = Application::get()->getRequest();
        $baseUrl = $request->getBaseUrl();
        $host = str_replace('https://','',$baseUrl);

        return $host;
    }

    public function _getKey(int $contextId)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'indexnowplugin');
        $key = $plugin->getSetting($contextId, 'key');

        return $key;
    }
}
