<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig;

class m261124_120000_remove_saved_php_thresholds extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $schemaVersion = $projectConfig->get('plugins.sherlock.schemaVersion', true);
        if (version_compare($schemaVersion ?? '0', '4.7.0', '>=')) {
            return true;
        }

        $path = 'plugins.sherlock.settings';
        $originalSettings = ProjectConfig::unpackAssociativeArrays($projectConfig->get($path) ?? []);
        $settings = $originalSettings;

        unset($settings['phpVersion']['thresholds'], $settings['phpSecurityUpdates']['thresholds']);

        if ($settings !== $originalSettings) {
            $projectConfig->set($path, ProjectConfig::packAssociativeArrays($settings));
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
