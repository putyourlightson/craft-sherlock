<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 * SettingsModel
 */
class SettingsModel extends Model
{
    /**
     * @var bool
     */
    public $monitor = false;

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
        'enforce' => false,
        'header' => true,
        'directives' => [[]],
    ];

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var array
     */
    public $restrictControlPanelIpAddresses = [];

    /**
     * @var array
     */
    public $restrictFrontEndIpAddresses = [];

    /**
     * @var array The integration type classes to add to the plugin’s default integration types.
     */
    public $integrationTypes = [];

    /**
     * @var array The integration settings.
     */
    public $integrationSettings = [];

    /**
     * @var mixed
     */
    public $disabledTests = [];

    /**
     * Individual test settings
     */

    // Updates

    public $criticalCraftUpdates = [
        'forceFail' => true,
    ];

    public $criticalPluginUpdates = [
        'forceFail' => true,
    ];

    public $craftUpdates = [];

    public $pluginUpdates = [];

    // HTTPS

    public $httpsControlPanel = [
        'forceFail' => true,
    ];

    public $httpsFrontEnd = [
        'forceFail' => true,
    ];

    // System

    public $craftFilePermissions = [
        'canFail' => true,
    ];

    public $craftFolderPermissions = [
        'canFail' => true,
    ];

    public $craftFoldersAboveWebRoot = [
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

    public $webAliasInBaseUrl = [
        'forceFail' => true,
    ];

    // Headers

    public $contentSecurityPolicy = [
        'canFail' => true,
    ];

    public $cors = [
        'forceFail' => true,
    ];

    public $expectCT = [
        'canFail' => true,
    ];

    public $referrerPolicy = [
        'canFail' => true,
    ];

    public $strictTransportSecurity = [
        'canFail' => true,
    ];

    public $xContentTypeOptions = [
        'canFail' => true,
    ];

    public $xFrameOptions = [
        'canFail' => true,
    ];

    public $xXssProtection = [];

    // Users

    public $adminUsername = [];

    public $requireEmailVerification = [
        'canFail' => true,
    ];

    // General config settings

    public $blowfishHashCost = [
        'threshold' => 13,
    ];

    public $cooldownDuration = [
        'threshold' => 300, // 5 minutes
    ];

    public $cpTrigger = [];

    public $defaultDirMode = [
        'canFail' => true,
        'threshold' => 0775,
    ];

    public $defaultFileMode = [
        'canFail' => true,
        'threshold' => 0664,
    ];

    public $defaultTokenDuration = [
        'canFail' => true,
        'threshold' => 86400, // 1 day
    ];

    public $deferPublicRegistrationPassword = [];

    public $devMode = [
        'forceFail' => true,
    ];

    public $elevatedSessionDuration = [
        'canFail' => true,
        'threshold' => 300, // 5 minutes
    ];

    public $enableCsrfProtection = [
        'canFail' => true,
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

    public $translationDebugOutput = [
        'canFail' => true,
    ];

    public $useSecureCookies = [
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

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['apiKey'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['apiKey'], 'string', 'length' => [32]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        // Set the field labels
        $labels['apiKey'] = Craft::t('sherlock', 'API Key');

        return $labels;
    }
}
