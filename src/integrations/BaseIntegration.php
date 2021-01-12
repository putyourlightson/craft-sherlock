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
     * @var bool Whether the integration is enabled.
     */
    public $enabled = false;

    /**
     * @inheritdoc
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @inheritdoc
     */
    public function getWarning(): string
    {
        return '';
    }
}
