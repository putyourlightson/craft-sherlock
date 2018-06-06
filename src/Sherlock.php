<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock;

use Craft;
use craft\base\Plugin;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use putyourlightson\sherlock\models\SettingsModel;
use putyourlightson\sherlock\services\SherlockService;
use putyourlightson\sherlock\services\TestsService;
use putyourlightson\sherlock\variables\SherlockVariable;
use yii\base\Event;


/**
 * Sherlock Plugin
 *
 * @property  SherlockService $sherlock
 * @property  TestsService $tests
 */
class Sherlock extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Sherlock
     */
    public static $plugin;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        // Register services as components
        $this->setComponents([
            'sherlock' => SherlockService::class,
            'tests' => TestsService::class,
        ]);

        // Register variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('sherlock', SherlockVariable::class);
        });

        // Check for header protection
        $this->sherlock->checkHeaderProtection();

        // Check for site resrictions
        $this->sherlock->checkRestrictions();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): SettingsModel
    {
        $settings = new SettingsModel();

        // Set defaults
        $settings->apiKey = StringHelper::randomString(32);
        $settings->secretKey = StringHelper::randomString(32);

        return $settings;
    }

    /**
     * @inheritdoc
     */
    protected function afterInstall()
    {
        // Create and save default settings
        $settings = $this->createSettingsModel();
        Craft::$app->plugins->savePluginSettings($this, $settings->getAttributes());

        // Redirect to settings page
        $url = UrlHelper::cpUrl('settings/plugins/sherlock');

        Craft::$app->getResponse()->redirect($url)->send();
    }
}
