<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

class SettingsModel extends Model
{
    /**
     * @var bool
     */
    public bool $monitor = false;

    /**
     * @var mixed
     */
    public mixed $notificationEmailAddresses = null;

    /**
     * @var bool
     */
    public bool $highSecurityLevel = false;

    /**
     * @var array
     */
    public array $headerProtectionSettings = [
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
    public array $contentSecurityPolicySettings = [
        'enabled' => false,
        'enforce' => false,
        'header' => true,
        'directives' => [[1, 'default-src', "'self'"]],
    ];

    /**
     * @var string
     */
    public string $apiKey = '';

    /**
     * @var array|string
     */
    public array|string $restrictControlPanelIpAddresses = [];

    /**
     * @var array|string
     */
    public array|string $restrictFrontEndIpAddresses = [];

    /**
     * @var array The integration type classes to add to the plugin’s default integration types.
     */
    public array $integrationTypes = [];

    /**
     * @var array The integration settings.
     */
    public array $integrationSettings = [];

    /**
     * @var array
     */
    public array $disabledTests = [];

    /**
     * Individual test settings
     */

    // Updates

    public array $criticalCraftUpdates = [
        'forceFail' => true,
    ];

    public array $criticalPluginUpdates = [
        'forceFail' => true,
    ];

    public array $craftUpdates = [];

    public array $pluginUpdates = [];

    // HTTPS

    public array $httpsControlPanel = [
        'forceFail' => true,
    ];

    public array $httpsFrontEnd = [
        'forceFail' => true,
    ];

    // System

    public array $craftFilePermissions = [
        'canFail' => true,
    ];

    public array $craftFolderPermissions = [
        'canFail' => true,
    ];

    public array $craftFoldersAboveWebRoot = [
        'canFail' => true,
    ];

    public array $phpVersion = [
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

    public array $phpComposerVersion = [
        'canFail' => true,
    ];

    // Setup

    public array $adminUsername = [];

    public array $requireEmailVerification = [
        'canFail' => true,
    ];

    public array $webAliasInSiteBaseUrl = [
        'forceFail' => true,
    ];

    public array $webAliasInVolumeBaseUrl = [
        'forceFail' => true,
    ];

    // Headers

    public array $contentSecurityPolicy = [
        'canFail' => true,
    ];

    public array $cors = [
        'forceFail' => true,
    ];

    public array $expectCT = [
        'canFail' => true,
    ];

    public array $referrerPolicy = [
        'canFail' => true,
    ];

    public array $strictTransportSecurity = [
        'canFail' => true,
    ];

    public array $xContentTypeOptions = [
        'canFail' => true,
    ];

    public array $xFrameOptions = [
        'canFail' => true,
    ];

    public array $xXssProtection = [];

    // General config settings

    public array $blowfishHashCost = [
        'threshold' => 13,
    ];

    public array $cooldownDuration = [
        'threshold' => 300, // 5 minutes
    ];

    public array $cpTrigger = [];

    public array $defaultDirMode = [
        'canFail' => true,
        'threshold' => 0775,
    ];

    public array $defaultFileMode = [
        'canFail' => true,
        'threshold' => 0664,
    ];

    public array $defaultTokenDuration = [
        'canFail' => true,
        'threshold' => 86400, // 1 day
    ];

    public array $deferPublicRegistrationPassword = [];

    public array $devMode = [
        'forceFail' => true,
    ];

    public array $elevatedSessionDuration = [
        'canFail' => true,
        'threshold' => 300, // 5 minutes
    ];

    public array $enableCsrfProtection = [
        'canFail' => true,
    ];

    public array $invalidLoginWindowDuration = [
        'threshold' => 3600, // 1 hour
    ];

    public array $maxInvalidLogins = [
        'canFail' => true,
        'threshold' => 5,
    ];

    public array $preventUserEnumeration = [
        'canFail' => true,
    ];

    public array $rememberedUserSessionDuration = [
        'threshold' => 12096004, // 14 days
    ];

    public array $requireMatchingUserAgentForSession = [];

    public array $requireUserAgentAndIpForSession = [];

    public array $sanitizeSvgUploads = [
        'canFail' => true,
    ];

    public array $testToEmailAddress = [
        'canFail' => true,
    ];

    public array $translationDebugOutput = [
        'canFail' => true,
    ];

    public array $useSecureCookies = [
        'canFail' => true,
    ];

    public array $userSessionDuration = [
        'canFail' => true,
        'threshold' => 3600, // 1 hour
    ];

    public array $verificationCodeDuration = [
        'canFail' => true,
        'threshold' => 86400, // 1 day
    ];

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

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
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
    protected function defineRules(): array
    {
        return [
            [['apiKey'], 'string', 'length' => [32]],
        ];
    }
}
