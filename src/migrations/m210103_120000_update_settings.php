<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\sherlock\Sherlock;

class m210103_120000_update_settings extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $schemaVersion = Craft::$app->projectConfig
            ->get('plugins.sherlock.schemaVersion', true);

        if (!version_compare($schemaVersion, '3.0.0', '<')) {
            return true;
        }

        $settings = Sherlock::$plugin->settings;

        // Update header protection enabled setting from project config value
        $headerProtection = Craft::$app->getProjectConfig()->get('sherlock.settings.headerProtection');
        $settings->headerProtectionSettings['enabled'] = (bool)$headerProtection;

        // Update restricted IP addresses
        $restrictControlPanelIpAddresses = Craft::$app->getProjectConfig()->get('sherlock.settings.restrictControlPanelIpAddresses');
        $settings->restrictControlPanelIpAddresses = explode("\n", $restrictControlPanelIpAddresses) ?: [];
        $restrictFrontEndIpAddresses = Craft::$app->getProjectConfig()->get('sherlock.settings.restrictFrontEndIpAddresses');
        $settings->restrictFrontEndIpAddresses = explode("\n", $restrictFrontEndIpAddresses) ?: [];

        // Update tests
        $settings->craftUpdates = [];
        $settings->pluginUpdates = [];
        $settings->xXssProtection = [];

        Craft::$app->getPlugins()->savePluginSettings(Sherlock::$plugin, $settings->getAttributes());

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class." cannot be reverted.\n";

        return false;
    }
}
