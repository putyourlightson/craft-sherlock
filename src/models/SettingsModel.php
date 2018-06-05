<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use Craft;
use craft\base\Model;

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
    public $liveMode = true;

    /**
     * @var bool
     */
    public $highSecurityLevel = true;

    /**
     * @var bool
     */
    public $logAllEvents = true;

    /**
     * @var bool
     */
    public $headerProtection = true;

    /**
     * @var mixed
     */
    public $notificationEmailAddresses;

    /**
     * @var string|null
     */
    public $pluginVulnerabilitiesFeedUrl;

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
    public $disabledTests;

    // Public Methods
    // =========================================================================

    /**
     * @return array
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
