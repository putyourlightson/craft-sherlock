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
    // Public Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $liveMode = false;

    /**
     * @var bool
     */
    public $highSecurityLevel = false;

    /**
     * @var bool
     */
    public $headerProtection = true;

    /**
     * @var mixed
     */
    public $notificationEmailAddresses;

    /**
     * @var string
     */
    public $pluginVulnerabilitiesFeedUrl = 'https://raw.githubusercontent.com/putyourlightson/craft-plugin-vulnerabilities/master/vulnerabilities.json';

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $secretKey;

    /**
     * @var string|null
     */
    public $restrictControlPanelIpAddresses;

    /**
     * @var string|null
     */
    public $restrictFrontEndIpAddresses;

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

    public $pluginVulnerabilities = [
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

    public $xXssProtection = [
        'canFail' => true,
    ];

    public $strictTransportSecurity = [
        'canFail' => true,
    ];

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
        ],
    ];

    public $craftUpdates = [
        'canFail' => true,
    ];

    public $pluginUpdates = [
        'canFail' => true,
    ];

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

    public $invalidLoginWindowDuration = [
        'threshold' => 3600, // 1 hour
    ];

    public $maxInvalidLogins = [
        'canFail' => true,
        'threshold' => 5,
    ];

    public $rememberedUserSessionDuration = [
        'threshold' => 12096004, // 14 days
    ];

    public $requireMatchingUserAgentForSession = [];

    public $requireUserAgentAndIpForSession = [];

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['apiKey', 'secretKey'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['apiKey', 'secretKey'], 'string', 'length' => [32]],
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
