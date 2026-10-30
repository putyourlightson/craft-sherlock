<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig;

class m261030_120000_update_php_thresholds extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $path = 'plugins.sherlock.settings.phpVersion';
        $settings = ProjectConfig::unpackAssociativeArray($projectConfig->get($path) ?? []);
        $thresholds = $settings['thresholds'] ?? [];
        $updatedThresholds = array_replace($thresholds, [
            '8.2' => '2027-01-01',
            '8.3' => '2028-01-01',
            '8.4' => '2029-01-01',
            '8.5' => '2030-01-01',
        ]);

        if ($updatedThresholds !== $thresholds) {
            $settings['thresholds'] = $updatedThresholds;
            $projectConfig->set($path, ProjectConfig::packAssociativeArray($settings));
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
