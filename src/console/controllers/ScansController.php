<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\UrlHelper;
use putyourlightson\sherlock\Sherlock;

/**
 * Scans Controller
 *
 * @since 2.4.0
 */
class ScansController extends Controller
{
    /**
     * @inheritdoc
     */
    public function getHelp(): string
    {
        return 'Sherlock scans.';
    }

    /**
     * @inheritdoc
     */
    public function getHelpSummary(): string
    {
        return $this->getHelp();
    }

    /**
     * Runs a security scan.
     *
     * @param int|null $siteId
     */
    public function actionRun(int $siteId = null)
    {
        $this->stdout(Craft::t('sherlock', 'Running security scan...').PHP_EOL, Console::FG_YELLOW);

        $tests = Sherlock::$plugin->tests->getTestNames();

        Console::startProgress(0, count($tests), '', 0.8);

        Sherlock::$plugin->scans->runScan($siteId, [$this, 'setProgressHandler']);

        Console::endProgress();

        $lastScan = Sherlock::$plugin->scans->getLastScan();

        if ($lastScan) {
            if ($lastScan->pass) {
                $this->stdout(Craft::t('sherlock', 'Your site has passed the Sherlock '.($lastScan->highSecurityLevel ? 'high' : 'standard').' security scan'.($lastScan->warning ? ' with warnings' : '').'. View the scan result at {url}', ['url' => UrlHelper::cpUrl('sherlock')]).PHP_EOL, Console::FG_GREEN);
            }
            else {
                $this->stdout(Craft::t('sherlock', 'Your site has failed the Sherlock '.($lastScan->highSecurityLevel ? 'high' : 'standard').' security scan. View the scan result at {url}', ['url' => UrlHelper::cpUrl('sherlock')]).PHP_EOL, Console::FG_RED);
            }
        }
    }

    /**
     * Handles setting the progress.
     *
     * @param int $count
     * @param int $total
     */
    public function setProgressHandler(int $count, int $total)
    {
        Console::updateProgress($count, $total);
    }
}
