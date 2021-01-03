<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock;

use Craft;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\events\PluginEvent;
use craft\helpers\App;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
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
 *
 * @property SettingsModel $settings
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

        // Console request
        if (Craft::$app instanceof ConsoleApplication) {
            return;
        }

        // Register variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('sherlock', SherlockVariable::class);
        });

        $this->sherlock->applyRestrictions();
        $this->sherlock->applyHeaderProtection();
        $this->sherlock->applyContentSecurityPolicy();

        // Register after install event
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // Create and save default settings
                    $settings = $this->createSettingsModel();
                    Craft::$app->plugins->savePluginSettings($this, $settings->getAttributes());

                    // Redirect to settings page with welcome
                    Craft::$app->getResponse()->redirect(
                        UrlHelper::cpUrl('settings/plugins/sherlock', [
                            'welcome' => 1,
                        ])
                    )->send();
                }
            }
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): SettingsModel
    {
        $settings = new SettingsModel();
        $mailSettings = App::mailSettings();

        // Set defaults
        $settings->notificationEmailAddresses = $mailSettings->fromEmail;

        return $settings;
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('sherlock/_settings', [
            'settings' => $this->getSettings(),
            'config' => Craft::$app->getConfig()->getConfigFromFile('sherlock'),
        ]);
    }
}
