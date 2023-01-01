<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use InvalidArgumentException;
use putyourlightson\sherlock\events\IntegrationConfigEvent;
use putyourlightson\sherlock\Sherlock;
use Rollbar\Rollbar;

/**
 * @property-read string $settingsHtml
 */
class RollbarIntegration extends BaseIntegration
{
    /**
     * @inheritdoc
     */
    protected ?string $requiredPackage = 'rollbar/rollbar';

    /**
     * @var string|null
     */
    public ?string $accessToken = null;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Rollbar';
    }

    /**
     * @inheritdoc
     */
    public static function displayDescription(): string
    {
        return 'Integration with [Rollbar](https://rollbar.com/) error and exception reporting.';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sherlock/_integrations/rollbar', [
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

        return Craft::t('sherlock', 'The Rollbar PHP SDK must be installed with composer for the integration to be able to run: `composer require rollbar/rollbar`');
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        if (!$this->getIsInstalled()) {
            return;
        }

        $config = [
            'access_token' => App::parseEnv($this->accessToken),
            'environment' => Craft::$app->getConfig()->env,
        ];

        $event = new IntegrationConfigEvent(['config' => $config]);
        $this->trigger(BaseIntegration::BEFORE_RUN_INTEGRATION, $event);

        if (!$event->isValid) {
            return;
        }

        // Catch exception, otherwise the CP cannot be accessed
        try {
            Rollbar::init($config);
        } catch (InvalidArgumentException $exception) {
            Sherlock::$plugin->log(Craft::t('sherlock', 'Rollbar integration error: ') . $exception->getMessage());
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
                    'accessToken',
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
            [['accessToken'], 'required'],
            [['accessToken'], 'string', 'length' => 32],
        ];
    }
}
