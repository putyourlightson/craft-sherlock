<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\sherlock\models\SettingsModel;

class m240526_120000_add_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.sherlock.schemaVersion', true);

        if (version_compare($schemaVersion, '4.5.0', '<')) {
            $settings = new SettingsModel();
            $projectConfig->set('plugins.sherlock.settings.maxScans', false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
