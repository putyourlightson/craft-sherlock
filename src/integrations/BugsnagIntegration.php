<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use Bugsnag\Client;
use Bugsnag\Handler;
use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use InvalidArgumentException;
use putyourlightson\sherlock\Sherlock;

/**
 * @property-read string $settingsHtml
 */
class BugsnagIntegration extends BaseIntegration
{
    /**
     * @inheritdoc
     */
    protected $requiredClass = 'Bugsnag\Client';

    /**
     * @var string|null
     */
    public $apiKey;

    /**
     * Can be overridden using config settings only.
     *
     * @var string|null
     */
    public $notifyEndpoint;

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
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'apiKey',
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
            'apiKey' => Craft::t('sherlock', 'API Key'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['apiKey'], 'required'],
            [['apiKey'], 'string', 'length' => 32],
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

        return Craft::t('sherlock', 'The Bugsnag PHP SDK must be installed with composer for the integration to be able to run: `composer require bugsnag/bugsnag:^3.0`');
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!$this->getIsInstalled()) {
            return;
        }

        // Catch exception, otherwise the CP cannot be accessed
        try {
            $bugsnag = Client::make(Craft::parseEnv($this->apiKey), $this->notifyEndpoint);
            Handler::register($bugsnag);
        }
        catch (InvalidArgumentException $exception) {
            Sherlock::$plugin->log(Craft::t('sherlock', 'Bugsnag integration error: ').$exception->getMessage());
        }
    }
}
