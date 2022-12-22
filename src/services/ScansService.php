<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\services;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use putyourlightson\sherlock\models\ScanModel;
use putyourlightson\sherlock\records\ScanRecord;
use putyourlightson\sherlock\Sherlock;

class ScansService extends Component
{
    /**
     * Returns the last scan.
     */
    public function getLastScan(int $siteId = null): ?ScanModel
    {
        $siteId = $siteId ?: Craft::$app->getSites()->getCurrentSite()->id;

        $scanRecord = ScanRecord::find()
            ->where(['siteId' => $siteId])
            ->orderBy('dateCreated desc')
            ->one();

        if ($scanRecord === null) {
            return null;
        }

        $scan = new ScanModel();
        $scan->setAttributes($scanRecord->getAttributes(), false);

        return $scan;
    }

    /**
     * Returns all scans.
     *
     * @return ScanModel[]
     */
    public function getAllScans(int $siteId = null, int $offsetId = 0): array
    {
        $siteId = $siteId ?: Craft::$app->getSites()->getCurrentSite()->id;

        // Get records offset by ID
        $records = ScanRecord::find()
            ->where(['siteId' => $siteId])
            ->andWhere('id > :offsetId', [':offsetId' => $offsetId])
            ->orderBy('dateCreated desc')
            ->all();

        $scans = [];

        foreach ($records as $record) {
            $scan = new ScanModel();
            $scan->setAttributes($record->getAttributes(), false);
            $scans[] = $scan;
        }

        return $scans;
    }

    /**
     * Runs a scan.
     */
    public function runScan(int $siteId = null, callable $setProgressHandler = null)
    {
        if ($siteId !== null) {
            Craft::$app->getSites()->setCurrentSite($siteId);
        } else {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        // Create model
        $scanModel = new ScanModel([
            'siteId' => $siteId,
            'highSecurityLevel' => Sherlock::$plugin->settings->highSecurityLevel,
        ]);

        $tests = Sherlock::$plugin->tests->getTestNames();

        $count = 0;
        $total = count($tests);

        foreach ($tests as $test) {
            $testModel = Sherlock::$plugin->tests->runTest($test);

            if (!$testModel->pass) {
                $scanModel->pass = false;
            }

            if ($testModel->warning) {
                $scanModel->warning = true;
            }

            $status = !$testModel->pass ? 'fail' : ($testModel->warning ? 'warning' : 'pass');
            $scanModel->results[$status][$test] = $testModel;

            $count++;

            if (is_callable($setProgressHandler)) {
                call_user_func($setProgressHandler, $count, $total);
            }
        }

        // Log scan
        $user = Craft::$app->getUser()->getIdentity();

        if ($user) {
            $runBy = $user;
        } else {
            $runBy = Craft::$app->getRequest()->getIsConsoleRequest() ? 'console command' : 'API';
        }

        // Populate & save record
        $scanRecord = new ScanRecord();
        $scanRecord->setAttributes($scanModel->getAttributes(), false);

        // Simplify results
        $results = [];

        // Use results from scan model as the results on scan record may be json encoded
        foreach ($scanModel->results as $key => $result) {
            foreach ($result as $test => $testModel) {
                $results[$key][$test] = $testModel->value;
            }
        }

        $scanRecord->results = $results;

        // Unset ID if null to avoid Postgres throwing an error
        if (Craft::$app->getDb()->getIsPgsql()) {
            unset($scanRecord->id);
        }

        $scanRecord->save();

        Sherlock::$plugin->log('Scan run on site ID ' . $siteId . ' by ' . $runBy . ' with result: ' . ($scanModel->pass ? 'pass' . ($scanModel->warning ? ' with warnings' : '') : 'fail'));

        if (!$scanModel->pass
            && !Sherlock::$plugin->getIsLite()
            && Sherlock::$plugin->settings->monitor
            && !empty(Sherlock::$plugin->settings->notificationEmailAddresses)
        ) {
            $this->_sendNotifications($scanModel);
        }
    }

    /**
     * Sends notifications.
     *
     * @param ScanModel $scanModel
     */
    private function _sendNotifications(ScanModel $scanModel)
    {
        // Check failed scan against last scan
        $lastScan = $this->getLastScan();

        if ($lastScan === null) {
            return;
        }

        $site = Craft::$app->getSites()->getSiteById($scanModel->siteId);

        if ($lastScan->pass) {
            // Send & log notification email
            $this->_sendLogNotificationEmail(
                'Security Scan Failed',
                'Sherlock security scan for site "' . $site->name . '" failed: ',
                'Sent email about failed scan to '
            );
        }

        // Check critical updates against last scan
        if ((isset($scanModel->results['fail']['criticalCraftUpdates']) && empty($lastScan->results['fail']['criticalCraftUpdates']))
            || (isset($scanModel->results['fail']['criticalPluginUpdates']) && empty($lastScan->results['fail']['criticalPluginUpdates']))
        ) {
            // Send & log notification email
            $this->_sendLogNotificationEmail(
                'Security Scan Critical Updates',
                'Sherlock security scan for site "' . $site->name . '" detected critical updates: ',
                'Sent email about critical updates to '
            );
        }
    }

    /**
     * Sends and logs notification email.
     */
    private function _sendLogNotificationEmail(string $subject, string $body, string $log)
    {
        $mailer = Craft::$app->getMailer();

        /** @var Message $message*/
        $message = $mailer->compose()
            ->setTo(Sherlock::$plugin->settings->notificationEmailAddresses)
            ->setSubject(Craft::$app->getSites()->getCurrentSite()->name . ' - ' . $subject)
            ->setHtmlBody($body . UrlHelper::cpUrl('sherlock'));

        $message->send();

        Sherlock::$plugin->log($log . Sherlock::$plugin->settings->notificationEmailAddresses);
    }
}
