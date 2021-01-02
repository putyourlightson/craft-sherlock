<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\sherlock\Sherlock;

class m210102_120000_disable_tests_can_fail extends Migration
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

        if (!version_compare($schemaVersion, '2.4.0', '<')) {
            return true;
        }

        $settings = Sherlock::$plugin->settings;
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
