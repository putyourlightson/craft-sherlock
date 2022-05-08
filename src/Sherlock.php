<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterCpAlertsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\App;
use craft\helpers\Cp;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\log\MonologTarget;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use putyourlightson\sherlock\models\SettingsModel;
use putyourlightson\sherlock\services\IntegrationsService;
use putyourlightson\sherlock\services\ScansService;
use putyourlightson\sherlock\services\SecurityService;
use putyourlightson\sherlock\services\TestsService;
use putyourlightson\sherlock\twigextensions\SherlockTwigExtension;
use putyourlightson\sherlock\variables\SherlockVariable;
use yii\base\Event;
use yii\log\Logger;

/**
 * @property-read IntegrationsService $integrations
 * @property-read ScansService $scans
 * @property-read SecurityService $security
 * @property-read TestsService $tests
 * @property-read bool $isLite
 * @property-read bool $isPro
 * @property-read SettingsModel $settings
 */
class Sherlock extends Plugin
{
    /**
     * Lite
     */
    public const EDITION_LITE = 'lite';

    /**
     * Plus
     */
    public const EDITION_PLUS = 'plus';

    /**
     * Pro
     */
    public const EDITION_PRO = 'pro';

    /**
     * @var Sherlock
     */
    public static Sherlock $plugin;

    /**
     * @inerhitdoc
     */
    public static function config(): array
    {
        return [
            'components' => [
                'integrations' =>  ['class' => IntegrationsService::class],
                'scans' =>  ['class' => ScansService::class],
                'security' =>  ['class' => SecurityService::class],
                'tests' =>  ['class' => TestsService::class],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PLUS,
            self::EDITION_PRO,
        ];
    }

    /**
     * @inheritdoc
     */
    public bool $hasCpSection = true;

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '3.1.3';

    /**
     * @inheritdoc
     */
    public string $minVersionRequired = '3.1.3';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerVariables();
        $this->_registerLogTarget();
        $this->_registerTwigExtensions();

        $this->integrations->runEnabledIntegrations();

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $this->security->applyRestrictions();
        $this->security->applyHeaderProtection();

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->security->applyContentSecurityPolicy();
        }
        elseif (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpUrlRules();
            $this->_registerCpAlerts();
            $this->_registerAfterInstallEvent();
        }
    }

    /**
     * Logs a message.
     */
    public function log(string $message, array $params = [], int $type = Logger::LEVEL_INFO): void
    {
        $message = Craft::t('sherlock', $message, $params);

        Craft::getLogger()->log($message, $type, 'sherlock');
    }

    /**
     * Returns true if lite version.
     *
     * @return bool
     */
    public function getIsLite(): bool
    {
        return $this->is(self::EDITION_LITE);
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
     * Returns whether the current user can access the plugin.
     *
     * @return bool
     */
    public function userCanAccessPlugin(): bool
    {
        $user = Craft::$app->getUser()->getIdentity();

        return $user && $user->can('accessplugin-sherlock');
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

        return $settings;
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
     * Registers a custom log target, keeping the format as simple as possible.
     *
     * @see LineFormatter::SIMPLE_FORMAT
     */
    private function _registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'sherlock',
            'categories' => ['sherlock'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'formatter' => new LineFormatter(
                format: "[%datetime%] %message%\n",
                dateFormat: 'Y-m-d H:i:s',
            ),
        ]);
    }

    /**
     * Registers Twig extensions.
     */
    private function _registerTwigExtensions()
    {
        Craft::$app->getView()->registerTwigExtension(new SherlockTwigExtension());
    }

    /**
     * Registers CP URL rules event.
     */
    private function _registerCpUrlRules()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['sherlock'] = 'sherlock/scans/index';
                $event->rules['sherlock/<siteHandle:{handle}>'] = 'sherlock/scans/index';

                // Merge so that settings controller action comes first (important!)
                $event->rules = array_merge([
                        'settings/plugins/sherlock' => 'sherlock/settings/edit',
                    ],
                    $event->rules
                );
            }
        );
    }

    /**
     * Registers CP alerts.
     */
    private function _registerCpAlerts()
    {
        Event::on(Cp::class, Cp::EVENT_REGISTER_ALERTS,
            function(RegisterCpAlertsEvent $event) {
                $event->alerts = array_merge($event->alerts, $this->security->getCpAlerts());
            }
        );
    }

    /**
     * Registers after install event.
     */
    private function _registerAfterInstallEvent()
    {
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
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
