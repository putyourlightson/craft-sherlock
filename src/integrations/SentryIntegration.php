<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use craft\validators\UrlValidator;
use putyourlightson\sherlock\Sherlock;
use Sentry;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @property-read string $settingsHtml
 */
class SentryIntegration extends BaseIntegration
{
    /**
     * @inheritdoc
     */
    protected ?string $requiredPackage = 'sentry/sdk';

    /**
     * @var string|null
     */
    public ?string $dsn = null;

    /**
     * @var float|string|null
     */
    public float|string|null $tracesSampleRate = null;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Sentry';
    }

    /**
     * @inheritdoc
     */
    public static function displayDescription(): string
    {
        return 'Integration with [Sentry](https://sentry.io/) error and performance monitoring.';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'dsn' => Craft::t('sherlock', 'Data Source Name (DSN)'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sherlock/_integrations/sentry', [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getIsInstalled(): bool
    {
        return function_exists('Sentry\init');
    }

    /**
     * @inheritdoc
     */
    public function getWarning(): string
    {
        if ($this->getIsInstalled()) {
            return '';
        }

        return Craft::t('sherlock', 'The Sentry PHP SDK must be installed with composer for the integration to be able to run: `composer require sentry/sdk`');
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
            'dsn' => App::parseEnv($this->dsn),
            'traces_sample_rate' => (float)$this->tracesSampleRate,
        ];

        // Catch exception, otherwise the CP cannot be accessed
        try {
            Sentry\init($config);
        }
        catch (InvalidOptionsException $exception) {
            Sherlock::$plugin->log(Craft::t('sherlock', 'Sentry integration error: ') . $exception->getMessage());
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
                    'dsn',
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
            [['dsn', 'tracesSampleRate'], 'required'],
            [['dsn'], UrlValidator::class],
            [['tracesSampleRate'], 'double', 'min' => 0, 'max' => 1],
        ];
    }
}
