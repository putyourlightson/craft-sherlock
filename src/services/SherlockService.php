<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\services;

use Craft;
use craft\base\Component;
use craft\errors\SiteNotFoundException;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use putyourlightson\sherlock\models\ScanModel;
use putyourlightson\sherlock\records\ScanRecord;
use putyourlightson\sherlock\Sherlock;
use yii\web\HttpException;

/**
 * Sherlock Service
 *
 * @property ScanModel|null $lastScan
 * @property array $cpAlerts
 */
class SherlockService extends Component
{
    // Properties
    // =========================================================================

    private $_settings;

    // Public Methods
    // =========================================================================

    /**
    * Init
    */
    public function init()
    {
        parent::init();

        // Get settings
        $this->_settings = Sherlock::$plugin->getSettings();
    }

    /**
     * Check Header Protection
     */
    public function checkHeaderProtection()
    {
        $request = Craft::$app->getRequest();
        $headers = Craft::$app->getResponse()->getHeaders();

        if (!empty($this->_settings->headerProtection) and $this->_settings->headerProtection) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
            $headers->set('X-Content-Type-Options', 'nosniff');
            $headers->set('X-Xss-Protection', '1; mode=block');

            if ($request->getIsSecureConnection()) {
                $headers->set('Strict-Transport-Security', 'max-age=31536000');
            }
        }
    }

    /**
     * Check Restrictions
     */
    public function checkRestrictions()
    {
        $request = Craft::$app->getRequest();

        if (!empty($this->_settings->restrictControlPanelIpAddresses) and $request->getIsCpRequest() and !Craft::$app->getUser()->getIsAdmin() and !in_array($request->getUserIP(), explode("\n", $this->_settings->restrictControlPanelIpAddresses), true)) {
            throw new HttpException(503);
        }

        if (!empty($this->_settings->restrictFrontEndIpAddresses) and $request->getIsSiteRequest() and !Craft::$app->getUser()->getIsAdmin() and !in_array($request->getUserIP(), explode("\n", $this->_settings->restrictFrontEndIpAddresses), true)) {
            throw new HttpException(503);
        }
    }

    /**
     * Get CP alerts
     *
     * @return array
     */
    public function getCpAlerts(): array
    {
        $alerts = [];

        if ($this->_settings->liveMode) {
            $userId = Craft::$app->getUser()->id;
            if (Craft::$app->getEdition() == Craft::Solo or Craft::$app->getUser()->getIsAdmin() or in_array('accessplugin-sherlock', Craft::$app->getUserPermissions()->getPermissionsByUserId($userId), true)) {
                $lastScan = $this->getLastScan();

                if ($lastScan and !$lastScan->pass) {
                    $alerts[] = 'Your site has failed the Sherlock '.($lastScan->highSecurityLevel ? 'high' : 'standard').' security scan. <a href="'.UrlHelper::cpUrl('sherlock').'" class="go">View Last Scan</a>';
                }
            }
        }

        return $alerts;
    }

    /**
     * Get last scan
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

        return ScanModel::populateModel($scanRecord);
    }

    /**
     * Get all scans
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
     * Run scan
     */
    public function runScan()
    {
        // Create model
        $scanModel = new ScanModel([
            'highSecurityLevel' => (bool)$this->_settings->highSecurityLevel,
        ]);

        $results = $scanModel->results;
        $tests = Sherlock::$plugin->tests->getTestNames();

        foreach ($tests as $test) {
            $testModel = Sherlock::$plugin->tests->runTest($test);

            if (!$testModel->pass) {
                $scanModel->pass = false;
            }

            if ($testModel->warning) {
                $scanModel->warning = true;
            }

            $status = !$testModel->pass ? 'fail' : ($testModel->warning ? 'warning' : 'pass');
            $results[$status][$test] = $testModel;
        }

        $scanModel->results = $results;

        // Log scan
        $user = Craft::$app->getUser()->getIdentity();
        Craft::info(
            'Scan run by '.$user->username.' with result: '.($scanModel->pass ? 'pass'.($scanModel->warning ? ' with warnings' : '') : 'fail'),
            'sherlock'
        );

        // check failed scan against last scan
        if (!$scanModel->pass) {
            $lastScan = $this->getLastScan();

            // if last scan exists
            if ($lastScan) {
                if ($lastScan->pass) {
                    // send and log notification email
                    $this->_sendLogNotificationEmail(
                        'Security Scan Failed',
                        'Sherlock security scan failed at the following site: ',
                        'Sent email about failed scan to '
                    );
                }

                // check critical updates and plugin vulnerabilities against last scan
                if (
                    (isset($scanModel->results['fail']['criticalCraftUpdates']) and empty($lastScan->results['fail']['criticalCraftUpdates'])) or
                    (isset($scanModel->results['fail']['criticalPluginUpdates']) and empty($lastScan->results['fail']['criticalPluginUpdates'])) or
                    (isset($scanModel->results['fail']['pluginVulnerabilities']) and empty($lastScan->results['fail']['pluginVulnerabilities']))
                ) {
                    // send and log notification email
                    $this->_sendLogNotificationEmail(
                        'Security Scan Critical Updates',
                        'Sherlock security scan detected critical updates at the following site: ',
                        'Sent email about critical updates to '
                    );
                }
            }
        }

        // Populate and save record
        $scanRecord = new ScanRecord;
        $scanRecord->setAttributes($scanModel->getAttributes(), false);

        // Simlplify results
        $results = array();
        foreach ($scanRecord->results as $key => $result) {
            foreach ($result as $test => $testModel) {
                $results[$key][$test] = $testModel->value;
            }
        }

        $scanRecord->results = $results;
        $scanRecord->save();
    }

    /**
     * Send and Log Notification Email
     *
     * @param $subject
     * @param $message
     * @param $log
     * @throws SiteNotFoundException
     */
    private function _sendLogNotificationEmail($subject, $message, $log)
    {
        // If live mode and notification email addresses exist
        if ($this->_settings->liveMode and !empty($this->_settings->notificationEmailAddresses)) {
            $mailer = Craft::$app->getMailer();

            /** @var Message $message*/
            $message = $mailer->compose()
                ->setTo($this->_settings->notificationEmailAddresses)
                ->setSubject(Craft::$app->getSites()->getCurrentSite()->name.' - '.$subject)
                ->setHtmlBody($message.UrlHelper::cpUrl('sherlock'));

            $message->send();

            // Log notification email
            Craft::info($log.$this->_settings->notificationEmailAddresses, 'sherlock');
        }
    }
}
