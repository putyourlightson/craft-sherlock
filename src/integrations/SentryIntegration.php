<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use Craft;
use Sentry;

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
        if (!class_exists('Sentry')) {
            return Craft::t('sherlock', 'The Sentry SDK must be installed with `composer require sentry/sdk` for this integration to be able to run.');
        }

        return '';
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
            'dsn' => self::$dsn,
            'traces_sample_rate' => self::$tracesSampleRate,
        ];

        Sentry\init([
            'dsn' => self::$dsn,
            'traces_sample_rate' => 0.1,
        ]);
    }
}
