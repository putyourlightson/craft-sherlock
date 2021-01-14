<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use Sentry;

/**
 * @property-read string $settingsHtml
 */
class SentryIntegration extends BaseIntegration
{
    /**
     * @var string|null
     */
    public $dsn;

    /**
     * @var double|null
     */
    public $tracesSampleRate;

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
        return 'Integration with Sentry.io error and performance monitoring.';
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'dsn',
            ],
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'dsn' => Craft::t('blitz', 'Data Source Name (DSN)'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['dsn', 'tracesSampleRate'], 'required'],
            [['tracesSampleRate'], 'double', 'min' => 0, 'max' => 1],
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
    public function getWarning(): string
    {
        if (class_exists('Sentry')) {
            return '';
        }

        return Craft::t('sherlock', 'The Sentry SDK must be installed with composer for the integration to be able to run: `composer require sentry/sdk`');
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!class_exists('Sentry')) {
            return;
        }

        $config = [
            'dsn' => $this->dsn,
            'traces_sample_rate' => $this->tracesSampleRate,
        ];

        Sentry\init($config);
    }
}
