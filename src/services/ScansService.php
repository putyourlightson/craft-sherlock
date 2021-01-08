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

/**
 * @property-read ScanModel|null $lastScan
 */
class ScansService extends Component
{
    /**
     * Returns the last scan.
     *
     * @return ScanModel|null
     */
    public function getLastScan()
    {
        // Get record
        $scanRecord = ScanRecord::find()
            ->orderBy('dateCreated desc')
            ->one();

        if ($scanRecord === null) {
            return null;
        }

        /** @var ScanModel $scan */
        $scan = ScanModel::populateModel($scanRecord);

        return $scan;
    }

    /**
     * Returns all scans.
     *
     * @param int|null $offsetId
     *
     * @return array
     */
    public function getAllScans($offsetId = 0): array
    {
        // Get records offset by ID
        $scanRecords = ScanRecord::find()
            ->where('id > :offsetId', [':offsetId' => $offsetId])
            ->orderBy('dateCreated desc')
            ->all();

        return ScanModel::populateModels($scanRecords);
    }

    /**
     * Runs a scan.
     *
     * @param callable|null $setProgressHandler
     */
    public function runScan(callable $setProgressHandler = null)
    {
        // Create model
        $scanModel = new ScanModel([
            'highSecurityLevel' => (bool)Sherlock::$plugin->settings->highSecurityLevel,
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
        }
        else {
            $runBy = Craft::$app->getRequest()->getIsConsoleRequest() ? 'console command' : 'API';
        }

        // Populate & save record
        $scanRecord = new ScanRecord;
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

        Sherlock::$plugin->log('Scan run by '.$runBy.' with result: '.($scanModel->pass ? 'pass'.($scanModel->warning ? ' with warnings' : '') : 'fail'));

        if (!$scanModel->pass
            && Sherlock::$plugin->getIsPro()
            && Sherlock::$plugin->settings->liveMode
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

        // If last scan exists
        if ($lastScan) {
            if ($lastScan->pass) {
                // Send & log notification email
                $this->_sendLogNotificationEmail(
                    'Security Scan Failed',
                    'Sherlock security scan failed at the following site: ',
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
                    'Sherlock security scan detected critical updates at the following site: ',
                    'Sent email about critical updates to '
                );
            }
        }
    }

    /**
     * Sends and logs notification email.
     *
     * @param $subject
     * @param $message
     * @param $log
     */
    private function _sendLogNotificationEmail($subject, $message, $log)
    {
        $mailer = Craft::$app->getMailer();

        /** @var Message $message*/
        $message = $mailer->compose()
            ->setTo(Sherlock::$plugin->settings->notificationEmailAddresses)
            ->setSubject(Craft::$app->getSites()->getCurrentSite()->name.' - '.$subject)
            ->setHtmlBody($message.UrlHelper::cpUrl('sherlock'));

        $message->send();

        // Log notification email
        Sherlock::$plugin->log($log.Sherlock::$plugin->settings->notificationEmailAddresses);
    }
}
