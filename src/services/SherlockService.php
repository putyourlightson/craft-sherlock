<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\services;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\errors\SiteNotFoundException;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use putyourlightson\sherlock\models\ScanModel;
use putyourlightson\sherlock\records\ScanRecord;
use putyourlightson\sherlock\Sherlock;
use yii\web\HttpException;

/**
 * @property-read string $nonce
 * @property-read array $cpAlerts
 * @property-read ScanModel|null $lastScan
 */
class SherlockService extends Component
{
    /**
     * @var string
     */
    private $_nonce;

    /**
     * Applies restrictions.
     *
     * @throws HttpException
     */
    public function applyRestrictions()
    {
        $request = Craft::$app->getRequest();

        $restrictControlPanelIpAddresses = Sherlock::$plugin->settings->restrictControlPanelIpAddresses;

        if (!empty($restrictControlPanelIpAddresses) && $request->getIsCpRequest()) {
            if (!Craft::$app->getUser()->getIsAdmin() && !$this->_matchIpAddresses($restrictControlPanelIpAddresses, $request->getUserIP())) {
                throw new HttpException(503);
            }
        }

        $restrictFrontEndIpAddresses = Sherlock::$plugin->settings->restrictFrontEndIpAddresses;

        if (!empty($restrictFrontEndIpAddresses) && $request->getIsSiteRequest()) {
            if (!Craft::$app->getUser()->getIsAdmin() && !$this->_matchIpAddresses($restrictFrontEndIpAddresses, $request->getUserIP())) {
                throw new HttpException(503);
            }
        }
    }

    /**
     * Applies header protection.
     */
    public function applyHeaderProtection()
    {
        $settings = Sherlock::$plugin->settings->headerProtectionSettings;

        if ($settings['enabled']) {
            foreach ($settings['headers'] as $header) {
                if ($header[0]) {
                    Craft::$app->getResponse()->getHeaders()->set(trim($header[1]), trim($header[2]));
                }
            }
        }
    }

    /**
     * Applies content security policy.
     */
    public function applyContentSecurityPolicy()
    {
        /** @var User|null $user */
        $user = Craft::$app->getUser()->getIdentity();

        // Exit if the debug toolbar is enabled
        if ($user && $user->getPreference('enableDebugToolbarForSite')) {
            return;
        }

        $settings = Sherlock::$plugin->settings->contentSecurityPolicySettings;

        if ($settings['enabled']) {
            if (Craft::$app->getRequest()->getIsSiteRequest()) {
                $name = 'Content-Security-Policy';
                $name .= $settings['enforce'] ? '' : '-Report-Only';
                $value = '';
                $nonce = $this->getNonce();

                foreach ($settings['directives'] as $directive) {
                    if ($directive[0]) {
                        $directive[2] = str_replace('{nonce}', 'nonce-'.$nonce, $directive[2]);
                        $value .= trim($directive[1]).' '.trim($directive[2]).'; ';
                    }
                }

                $value = trim($value);

                if ($settings['header']) {
                    Craft::$app->getResponse()->getHeaders()->add($name, $value);
                }
                else {
                    Craft::$app->getView()->registerMetaTag([
                        'http-equiv' => $name,
                        'content' => $value,
                    ]);
                }
            }
        }
    }

    /**
     * Returns or generates a nonce.
     *
     * @return string
     */
    public function getNonce(): string
    {
        if ($this->_nonce === null) {
            $this->_nonce = base64_encode(StringHelper::randomString());
        }

        return $this->_nonce;
    }

    /**
     * Returns CP alerts.
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
                    $alerts[] = 'Your site has failed the Sherlock '.($lastScan->highSecurityLevel ? 'high' : 'standard').' security scan. <a href="'.UrlHelper::cpUrl('sherlock').'" class="go">View Last Scan</a>';
                }
            }
        }

        return $alerts;
    }

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
     */
    public function runScan()
    {
        // Create model
        $scanModel = new ScanModel([
            'highSecurityLevel' => (bool)Sherlock::$plugin->settings->highSecurityLevel,
        ]);

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
            $scanModel->results[$status][$test] = $testModel;
        }

        // Log scan
        $user = Craft::$app->getUser()->getIdentity();
        Craft::info(
            'Scan run by '.($user ? $user->username : 'console command').' with result: '.($scanModel->pass ? 'pass'.($scanModel->warning ? ' with warnings' : '') : 'fail'),
            'sherlock'
        );

        // Check failed scan against last scan
        if (!$scanModel->pass) {
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
    }

    /**
     * Matches IP addresses.
     *
     * @param string[] $ipAddresses
     * @param string|null $userIp
     *
     * @return bool
     */
    private function _matchIpAddresses(array $ipAddresses, $userIp = null): bool
    {
        foreach ($ipAddresses as $ipAddress) {
            $ipAddress = is_array($ipAddress) ? $ipAddress[0] : $ipAddress;
            $ipAddress = str_replace(['\*', '\?'], ['.*', '.'], preg_quote($ipAddress));

            if (preg_match('/^'.$ipAddress.'$/', $userIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sends and logs notification email.
     *
     * @param $subject
     * @param $message
     * @param $log
     * @throws SiteNotFoundException
     */
    private function _sendLogNotificationEmail($subject, $message, $log)
    {
        // If live mode and notification email addresses exist
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
