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
     *
     * @return bool
     */
    public function getIsInstalled(): bool;

    /**
     * Returns whether the integration is enabled.
     *
     * @return bool
     */
    public function getEnabled(): bool;

    /**
     * Returns the display description of this class.
     *
     * @return string
     */
    public static function displayDescription(): string;

    /**
     * Returns a warning if something is wrong.
     *
     * @return string
     */
    public function getWarning(): string;

    /**
     * Runs the integration.
     */
    public function run();
}
