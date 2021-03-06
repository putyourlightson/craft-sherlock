<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\sherlock\models\SettingsModel;
use putyourlightson\sherlock\Sherlock;

class m190615_120000_update_duration_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $schemaVersion = Craft::$app->projectConfig
            ->get('plugins.sherlock.schemaVersion', true);

        if (!version_compare($schemaVersion, '2.1.2', '<')) {
            return true;
        }

        // Set duration settings to defaults and resave
        $defaults = new SettingsModel();
        $settings = Sherlock::$plugin->settings;
        $settings->defaultTokenDuration = $defaults->defaultTokenDuration;

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
