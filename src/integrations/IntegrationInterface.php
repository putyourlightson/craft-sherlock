<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\integrations;

use craft\base\SavableComponentInterface;

interface IntegrationInterface extends SavableComponentInterface
{
    /**
     * Returns whether the integration is installed.
     */
    public function getIsInstalled(): bool;

    /**
     * Returns whether the integration is enabled.
     */
    public function getEnabled(): bool;

    /**
     * Returns the display description of this class.
     */
    public static function displayDescription(): string;

    /**
     * Returns a warning if something is wrong.
     */
    public function getWarning(): string;

    /**
     * Runs the integration.
     */
    public function run(): void;

    /**
     * @inerhitdoc
     */
    public function hasErrors($attribute = null): bool;

    /**
     * @inerhitdoc
     */
    public function validate($attributeNames = null, $clearErrors = true);
}
