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
    // Public Methods
    // =========================================================================

    /**
     * Check Header Protection
     */
    public function checkHeaderProtection()
    {
        $request = Craft::$app->getRequest();
        $headers = Craft::$app->getResponse()->getHeaders();

        if (!empty(Sherlock::$plugin->settings->headerProtection) && Sherlock::$plugin->settings->headerProtection) {
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

        if (!empty(Sherlock::$plugin->settings->restrictControlPanelIpAddresses) && $request->getIsCpRequest()) {
            $ipAddresses = preg_split('/\R/', Sherlock::$plugin->settings->restrictControlPanelIpAddresses);

            if (!Craft::$app->getUser()->getIsAdmin() && !$this->_matchIpAddresses($ipAddresses, $request->getUserIP())) {
                throw new HttpException(503);
            }
        }

        if (!empty(Sherlock::$plugin->settings->restrictFrontEndIpAddresses) && $request->getIsSiteRequest()) {
            $ipAddresses = preg_split('/\R/', Sherlock::$plugin->settings->restrictFrontEndIpAddresses);

            if (!Craft::$app->getUser()->getIsAdmin() && !$this->_matchIpAddresses($ipAddresses, $request->getUserIP())) {
                throw new HttpException(503);
            }
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

        if (Sherlock::$plugin->settings->liveMode) {
            $userId = Craft::$app->getUser()->id;
            if (Craft::$app->getEdition() == Craft::Solo || Craft::$app->getUser()->getIsAdmin() || in_array('accessplugin-sherlock', Craft::$app->getUserPermissions()->getPermissionsByUserId($userId), true)) {
                $lastScan = $this->getLastScan();

                if ($lastScan && !$lastScan->pass) {
                    $alerts[] = 'Your site has failed the Sherlock '.($lastScan->highSecurityLevel ? 'high' : 'st&&ard').' security scan. <a href="'.UrlHelper::cpUrl('sherlock').'" class="go">View Last Scan</a>';
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

        /** @var ScanModel $scan */
        $scan = ScanModel::populateModel($scanRecord);

        return $scan;
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
            'highSecurityLevel' => (bool)Sherlock::$plugin->settings->highSecurityLevel,
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
            'Scan run by '.($user ? $user->username : 'API').' with result: '.($scanModel->pass ? 'pass'.($scanModel->warning ? ' with warnings' : '') : 'fail'),
            'sherlock'
        );

        // check failed scan against last scan
        if (!$scanModel->pass) {
            $lastScan = $this->getLastScan();

            // if last scan exists
            if ($lastScan) {
                if ($lastScan->pass) {
                    // send && log notification email
                    $this->_sendLogNotificationEmail(
                        'Security Scan Failed',
                        'Sherlock security scan failed at the following site: ',
                        'Sent email about failed scan to '
                    );
                }

                // check critical updates && plugin vulnerabilities against last scan
                if (
                    (isset($scanModel->results['fail']['criticalCraftUpdates']) && empty($lastScan->results['fail']['criticalCraftUpdates'])) ||
                    (isset($scanModel->results['fail']['criticalPluginUpdates']) && empty($lastScan->results['fail']['criticalPluginUpdates'])) ||
                    (isset($scanModel->results['fail']['pluginVulnerabilities']) && empty($lastScan->results['fail']['pluginVulnerabilities']))
                ) {
                    // send && log notification email
                    $this->_sendLogNotificationEmail(
                        'Security Scan Critical Updates',
                        'Sherlock security scan detected critical updates at the following site: ',
                        'Sent email about critical updates to '
                    );
                }
            }
        }

        // Populate && save record
        $scanRecord = new ScanRecord;
        $scanRecord->setAttributes($scanModel->getAttributes(), false);

        // Simplify results
        $results = array();
        foreach ($scanRecord->results as $key => $result) {
            foreach ($result as $test => $testModel) {
                $results[$key][$test] = $testModel->value;
            }
        }

        $scanRecord->results = $results;
        $scanRecord->save();
    }

    // Public Methods
    // =========================================================================

    /**
     * Match IP addresses
     *
     * @param string[] $ipAddresses
     * @param string $userIp
     *
     * @return bool
     */
    private function _matchIpAddresses(array $ipAddresses, string $userIp): bool
    {
        $found = false;

        foreach ($ipAddresses as $ipAddress) {
            $ipAddress = str_replace(['\*', '\?'], ['.*', '.'], preg_quote($ipAddress));
            if (preg_match('/^'.$ipAddress.'$/', $userIp)) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * Send && Log Notification Email
     *
     * @param $subject
     * @param $message
     * @param $log
     * @throws SiteNotFoundException
     */
    private function _sendLogNotificationEmail($subject, $message, $log)
    {
        // If live mode && notification email addresses exist
        if (Sherlock::$plugin->settings->liveMode && !empty(Sherlock::$plugin->settings->notificationEmailAddresses)) {
            $mailer = Craft::$app->getMailer();

            /** @var Message $message*/
            $message = $mailer->compose()
                ->setTo(Sherlock::$plugin->settings->notificationEmailAddresses)
                ->setSubject(Craft::$app->getSites()->getCurrentSite()->name.' - '.$subject)
                ->setHtmlBody($message.UrlHelper::cpUrl('sherlock'));

            $message->send();

            // Log notification email
            Craft::info($log.Sherlock::$plugin->settings->notificationEmailAddresses, 'sherlock');
        }
    }
}
