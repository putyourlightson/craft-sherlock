<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use craft\base\SavableComponent;

/**
 * @property-read string $warning
 */
abstract class BaseIntegration extends SavableComponent implements IntegrationInterface
{
    /**
     * @var bool
     */
    public $enabled = false;

    /**
     * @inheritdoc
     */
    public function getWarning(): string
    {
        return '';
    }
}
