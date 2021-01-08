<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\helpers\App;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use putyourlightson\logtofile\LogToFile;
use putyourlightson\sherlock\models\SettingsModel;
use putyourlightson\sherlock\services\ScansService;
use putyourlightson\sherlock\services\SecurityService;
use putyourlightson\sherlock\services\TestsService;
use putyourlightson\sherlock\twigextensions\SherlockTwigExtension;
use putyourlightson\sherlock\variables\SherlockVariable;
use yii\base\Event;
use yii\web\ForbiddenHttpException;

/**
 * Sherlock Plugin
 *
 * @property-read  ScansService $scans
 * @property-read  SecurityService $security
 * @property-read  TestsService $tests
 *
 * @property-read bool $isPro
 * @property-read SettingsModel $settings
 */
class Sherlock extends Plugin
{
    // Edition constants
    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';

    /**
     * @var Sherlock
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        // Register services as components
        $this->setComponents([
            'scans' => ScansService::class,
            'security' => SecurityService::class,
            'tests' => TestsService::class,
        ]);

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $this->security->applyRestrictions();
        $this->security->applyHeaderProtection();
        $this->security->applyContentSecurityPolicy();

        $this->_registerTwigExtensions();
        $this->_registerVariables();
        $this->_registerAfterInstallEvent();
    }

    /**
     * Logs an action.
     *
     * @param string $message
     * @param array $params
     * @param string $type
     */
    public function log(string $message, array $params = [], string $type = 'info')
    {
        $message = Craft::t('sherlock', $message, $params);

        LogToFile::log($message, 'sherlock', $type);
    }

    /**
     * Returns true if pro version.
     *
     * @return bool
     */
    public function getIsPro(): bool
    {
        return $this->is(self::EDITION_PRO);
    }

    /**
     * Throws an exception if the plugin edition is not pro.
     *
     * @throws ForbiddenHttpException
     */
    public function requirePro()
    {
        if (!$this->getIsPro()) {
            throw new ForbiddenHttpException(Craft::t('sherlock', 'Sherlock Pro is required to perform this action.'));
        }
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): SettingsModel
    {
        $mailSettings = App::mailSettings();
        $settings = new SettingsModel();

        // Set defaults
        $settings->notificationEmailAddresses = $mailSettings->fromEmail;
        $settings->apiKey = StringHelper::randomString(32);
        $settings->secretKey = StringHelper::randomString(32);

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

    /**
     * Registers Twig extensions
     */
    private function _registerTwigExtensions()
    {
        Craft::$app->getView()->registerTwigExtension(new SherlockTwigExtension());
    }

    /**
     * Registers variables.
     */
    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('sherlock', SherlockVariable::class);
        });
    }

    /**
     * Registers after install event.
     */
    private function _registerAfterInstallEvent()
    {
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // Don't proceed if plugin exists in incoming project config, otherwise updates won't be applied
                    // https://github.com/putyourlightson/craft-campaign/issues/191
                    if (Craft::$app->projectConfig->get('plugins.sherlock', true) !== null) {
                        return;
                    }

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
}
