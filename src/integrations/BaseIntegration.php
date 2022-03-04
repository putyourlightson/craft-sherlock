<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use Composer\InstalledVersions;
use craft\base\SavableComponent;

/**
 * @property-read bool $isInstalled
 * @property-read string $warning
 */
abstract class BaseIntegration extends SavableComponent implements IntegrationInterface
{
    /**
     * @var string|null A package that the integration requires.
     */
    protected ?string $requiredPackage = null;

    /**
     * @var bool Whether the integration is enabled.
     */
    public bool $enabled = false;

    /**
     * @inheritdoc
     */
    public function getIsInstalled(): bool
    {
        if ($this->requiredPackage === null) {
            return true;
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return InstalledVersions::isInstalled($this->requiredPackage);
    }

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
