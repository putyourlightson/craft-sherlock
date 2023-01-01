<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use Bugsnag\Client;
use Bugsnag\Handler;
use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use InvalidArgumentException;
use putyourlightson\sherlock\events\IntegrationConfigEvent;
use putyourlightson\sherlock\Sherlock;

/**
 * @property-read string $settingsHtml
 */
class BugsnagIntegration extends BaseIntegration
{
    /**
     * @inheritdoc
     */
    protected ?string $requiredPackage = 'bugsnag/bugsnag';

    /**
     * @var string|null
     */
    public ?string $apiKey = null;

    /**
     * Can be overridden using config settings only.
     *
     * @var string|null
     */
    public ?string $notifyEndpoint = null;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Bugsnag';
    }

    /**
     * @inheritdoc
     */
    public static function displayDescription(): string
    {
        return 'Integration with [Bugsnag](https://bugsnag.com/) error and exception monitoring.';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'apiKey' => Craft::t('sherlock', 'API Key'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sherlock/_integrations/bugsnag', [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getWarning(): string
    {
        if ($this->getIsInstalled()) {
            return '';
        }

        return Craft::t('sherlock', 'The Bugsnag PHP SDK must be installed with composer for the integration to be able to run: `composer require bugsnag/bugsnag`');
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        if (!$this->getIsInstalled()) {
            return;
        }

        $bugsnag = Client::make(App::parseEnv($this->apiKey), $this->notifyEndpoint);

        $event = new IntegrationConfigEvent(['config' => $bugsnag->getConfig()]);
        $this->trigger(BaseIntegration::BEFORE_RUN_INTEGRATION, $event);

        if (!$event->isValid) {
            return;
        }

        // Catch exception, otherwise the CP cannot be accessed
        try {
            Handler::register($bugsnag);
        } catch (InvalidArgumentException $exception) {
            Sherlock::$plugin->log(Craft::t('sherlock', 'Bugsnag integration error: ') . $exception->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'apiKey',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['apiKey'], 'required'],
            [['apiKey'], 'string', 'length' => 32],
        ];
    }
}
