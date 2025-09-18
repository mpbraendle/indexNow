<?php

/**
 * @file tools/indexNowSitemap.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class indexNowSitemapTool
 * @ingroup plugins_generic_indexnow
 *
 * @brief CLI tool to send a sitemap to indexNow
 */

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/tools/bootstrap.php');

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\indexNow\IndexNowPlugin;
use APP\plugins\generic\indexNow\classes\IndexNowHandler;
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;


class indexNowSitemapTool extends CommandLineTool {

    /** @var indexNowPlugin */
    private $indexNowPlugin;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		if (!sizeof($this->argv)) {
			$this->usage();
			exit(1);
		}

		$this->parameters = $this->argv;
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "IndexNow Sitemap Generation Tool\n"
			. "Usage:\n"
			. "{$this->scriptName} journal_path\n";
	}

	/**
	 * Check 
	 */
	function execute() {
        $journal = null;
        if (count($this->parameters) == 1) {
            $journalPath = array_shift($this->argv);
            /** @var JournalDAO */
            $journalDao = DAORegistry::getDAO('JournalDAO');
            $journal = $journalDao->getByPath($journalPath);
            if (!$journal) {
                exit(__('plugins.generic.indexnow.unknownjournal', ['journalPath' => $journalPath]) . "\n");
            }
            PluginRegistry::loadCategory('generic', true, 0);
            $this->indexNowPlugin = PluginRegistry::getPlugin('generic', 'indexnowplugin');
            $indexNowHandler = $this->indexNowPlugin->getIndexNowHandler();
            $ret = $indexNowHandler->_createJSONSitemap($journal);
        }
        else
        {
            $this->usage();
            exit(1);
        }
	}
}

$tool = new indexNowSitemapTool(isset($argv) ? $argv : array());
$tool->execute();
