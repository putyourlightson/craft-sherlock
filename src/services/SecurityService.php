<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\services;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use putyourlightson\sherlock\Sherlock;
use yii\web\ForbiddenHttpException;

/**
 * @property-read array $cpAlerts
 */
class SecurityService extends Component
{
    /**
     * @var string|null
     */
    private ?string $nonce = null;

    /**
     * Applies restrictions.
     */
    public function applyRestrictions(): void
    {
        if (Sherlock::$plugin->getIsLite()) {
            return;
        }

        $request = Craft::$app->getRequest();

        $restrictControlPanelIpAddresses = Sherlock::$plugin->settings->restrictControlPanelIpAddresses;

        if (!empty($restrictControlPanelIpAddresses) && $request->getIsCpRequest()) {
            if (!Craft::$app->getUser()->getIsAdmin() && !$this->matchIpAddresses($restrictControlPanelIpAddresses, $request->getUserIP())) {
                throw new ForbiddenHttpException();
            }
        }

        $restrictFrontEndIpAddresses = Sherlock::$plugin->settings->restrictFrontEndIpAddresses;

        if (!empty($restrictFrontEndIpAddresses) && $request->getIsSiteRequest()) {
            if (!Craft::$app->getUser()->getIsAdmin() && !$this->matchIpAddresses($restrictFrontEndIpAddresses, $request->getUserIP())) {
                throw new ForbiddenHttpException();
            }
        }
    }

    /**
     * Applies header protection.
     */
    public function applyHeaderProtection(): void
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
    public function applyContentSecurityPolicy(): void
    {
        /** @var User|null $user */
        $user = Craft::$app->getUser()->getIdentity();

        // Exit if the debug toolbar is enabled
        if ($user && $user->getPreference('enableDebugToolbarForSite')) {
            return;
        }

        $settings = Sherlock::$plugin->settings->contentSecurityPolicySettings;

        if ($settings['enabled']) {
            $name = 'Content-Security-Policy';
            $name .= $settings['enforce'] ? '' : '-Report-Only';
            $value = '';
            $nonce = $this->getNonce();

            foreach ($settings['directives'] as $directive) {
                if ($directive[0]) {
                    $directive[2] = str_replace('{nonce}', 'nonce-' . $nonce, $directive[2]);
                    $value .= trim($directive[1]) . ' ' . trim($directive[2]) . '; ';
                }
            }

            $value = trim($value);

            if ($settings['header']) {
                Craft::$app->getResponse()->getHeaders()->add($name, $value);
            } else {
                Craft::$app->getView()->registerMetaTag([
                    'http-equiv' => $name,
                    'content' => $value,
                ]);
            }
        }
    }

    /**
     * Returns CP alerts.
     */
    public function getCpAlerts(): array
    {
        if (Sherlock::$plugin->getIsLite() || !Sherlock::$plugin->settings->monitor) {
            return [];
        }

        if (!Sherlock::$plugin->userCanAccessPlugin()) {
            return [];
        }

        $alerts = [];
        $lastScan = Sherlock::$plugin->scans->getLastScan();

        if ($lastScan && !$lastScan->pass) {
            $alerts[] = 'Your site has failed the Sherlock ' . ($lastScan->highSecurityLevel ? 'high' : 'standard') . ' security scan. <a href="' . UrlHelper::cpUrl('sherlock') . '" class="go">View last scan</a>';
        }

        return $alerts;
    }

    /**
     * Returns or generates a nonce.
     */
    public function getNonce(): string
    {
        if ($this->nonce === null) {
            $this->nonce = base64_encode(StringHelper::randomString());
        }

        return $this->nonce;
    }

    /**
     * Matches IP addresses.
     *
     * @param string[]|string[][] $ipAddresses
     */
    private function matchIpAddresses(array $ipAddresses, string $userIp = null): bool
    {
        foreach ($ipAddresses as $ipAddress) {
            $ipAddress = is_array($ipAddress) ? $ipAddress[0] : $ipAddress;
            $ipAddress = str_replace(['\*', '\?'], ['.*', '.'], preg_quote($ipAddress));

            if (preg_match('/^' . $ipAddress . '$/', $userIp)) {
                return true;
            }
        }

        return false;
    }
}
