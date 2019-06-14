<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\sherlock\models\SettingsModel;
use putyourlightson\sherlock\Sherlock;

class m190614_120000_update_duration_settings extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Set duration settings to defaults and resave
        $defaults = new SettingsModel();
        $settings = Sherlock::$plugin->settings;
        $settings->cooldownDuration = $defaults->cooldownDuration;
        $settings->invalidLoginWindowDuration = $defaults->invalidLoginWindowDuration;
        $settings->rememberedUserSessionDuration = $defaults->rememberedUserSessionDuration;
        $settings->userSessionDuration = $defaults->userSessionDuration;
        $settings->verificationCodeDuration = $defaults->verificationCodeDuration;
        $settings->cooldownDuration = $defaults->cooldownDuration;

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
