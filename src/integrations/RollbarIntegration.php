<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use InvalidArgumentException;
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
    protected $requiredClass = 'Rollbar\Rollbar';

    /**
     * @var string|null
     */
    public $accessToken;

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
        return 'Integration with Rollbar.com error and exception reporting.';
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
                'accessToken',
            ],
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['accessToken'], 'required'],
            [['accessToken'], 'string', 'length' => 32],
        ];
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
        if (!$this->getIsInstalled()) {
            return '';
        }

        return Craft::t('sherlock', 'The Rollbar PHP SDK must be installed with composer for the integration to be able to run: `composer require rollbar/rollbar`');
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!$this->getIsInstalled()) {
            return;
        }

        $config = [
            'access_token' => Craft::parseEnv($this->accessToken),
            'environment' => CRAFT_ENVIRONMENT,
        ];

        // Catch exception, otherwise the CP cannot be accessed
        try {
            Rollbar::init($config);
        }
        catch (InvalidArgumentException $exception) {
            Sherlock::$plugin->log(Craft::t('sherlock', 'Rollbar integration error: ').$exception->getMessage());
        }
    }
}
