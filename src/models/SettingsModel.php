<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use craft\base\Model;

/**
 * SettingsModel
 */
class SettingsModel extends Model
{
    /**
     * @var bool
     */
    public $liveMode = false;

    /**
     * @var mixed
     */
    public $notificationEmailAddresses;

    /**
     * @var bool
     */
    public $highSecurityLevel = false;

    /**
     * @var array
     */
    public $headerProtectionSettings = [
        'enabled' => true,
        'headers' => [
            [true, 'Strict-Transport-Security', 'max-age=31536000'],
            [true, 'X-Content-Type-Options', 'nosniff'],
            [true, 'X-Frame-Options', 'SAMEORIGIN'],
            [true, 'X-Xss-Protection', '1; mode=block'],
        ],
    ];

    /**
     * @var array
     */
    public $contentSecurityPolicySettings = [
        'enabled' => false,
        'reportOnly' => false,
        'header' => false,
        'directives' => [],
    ];

    /**
     * @var array
     */
    public $restrictControlPanelIpAddresses = [];

    /**
     * @var array
     */
    public $restrictFrontEndIpAddresses = [];

    /**
     * @var mixed
     */
    public $disabledTests = [];

    /**
     * Individual test settings
     */

    public $criticalCraftUpdates = [
        'forceFail' => true,
    ];

    public $criticalPluginUpdates = [
        'forceFail' => true,
    ];

    public $httpsControlPanel = [
        'forceFail' => true,
    ];

    public $httpsFrontEnd = [
        'canFail' => true,
    ];

    public $cors = [
        'forceFail' => true,
    ];

    public $xFrameOptions = [
        'canFail' => true,
    ];

    public $xContentTypeOptions = [
        'canFail' => true,
    ];

    public $xXssProtection = [];

    public $strictTransportSecurity = [
        'canFail' => true,
    ];

    public $referrerPolicy = [
        'canFail' => true,
    ];

    public $expectCT = [
        'canFail' => true,
    ];

    public $contentSecurityPolicy = [
        'canFail' => true,
    ];

    public $permissionsPolicy = [];

    public $craftFoldersAboveWebRoot = [
        'canFail' => true,
    ];

    public $craftFolderPermissions = [
        'canFail' => true,
    ];

    public $craftFilePermissions = [
        'canFail' => true,
    ];

    public $phpVersion = [
        'canFail' => true,
        'thresholds' => [
            '5.0' => '2005-09-05',
            '5.1' => '2006-07-24',
            '5.2' => '2011-01-06',
            '5.3' => '2014-07-14',
            '5.4' => '2015-09-03',
            '5.5' => '2016-07-10',
            '5.6' => '2018-12-31',
            '7.0' => '2018-12-03',
            '7.1' => '2019-12-01',
            '7.2' => '2020-11-30',
            '7.3' => '2021-12-06',
            '7.4' => '2022-11-28',
            '8.0' => '2023-11-26',
        ],
    ];

    public $craftUpdates = [];

    public $pluginUpdates = [];

    public $requireEmailVerification = [
        'canFail' => true,
    ];

    public $devMode = [
        'canFail' => true,
    ];

    public $translationDebugOutput = [
        'canFail' => true,
    ];

    public $defaultFileMode = [
        'canFail' => true,
        'threshold' => 0664,
    ];

    public $defaultDirMode = [
        'canFail' => true,
        'threshold' => 0775,
    ];

    public $defaultTokenDuration = [
        'canFail' => true,
        'threshold' => 86400, // 1 day
    ];

    public $enableCsrfProtection = [
        'canFail' => true,
    ];

    public $useSecureCookies = [
        'canFail' => true,
    ];

    public $cpTrigger = [];

    public $blowfishHashCost = [
        'threshold' => 13,
    ];

    public $cooldownDuration = [
        'threshold' => 300, // 5 minutes
    ];

    public $deferPublicRegistrationPassword = [];

    public $elevatedSessionDuration = [
        'canFail' => true,
        'threshold' => 300, // 5 minutes
    ];

    public $invalidLoginWindowDuration = [
        'threshold' => 3600, // 1 hour
    ];

    public $maxInvalidLogins = [
        'canFail' => true,
        'threshold' => 5,
    ];

    public $preventUserEnumeration = [
        'canFail' => true,
    ];

    public $rememberedUserSessionDuration = [
        'threshold' => 12096004, // 14 days
    ];

    public $requireMatchingUserAgentForSession = [];

    public $requireUserAgentAndIpForSession = [];

    public $sanitizeSvgUploads = [
        'canFail' => true,
    ];

    public $testToEmailAddress = [
        'canFail' => true,
    ];

    public $userSessionDuration = [
        'canFail' => true,
        'threshold' => 3600, // 1 hour
    ];

    public $verificationCodeDuration = [
        'canFail' => true,
        'threshold' => 86400, // 1 day
    ];
}
