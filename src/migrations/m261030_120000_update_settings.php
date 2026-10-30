<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig;

class m261030_120000_update_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $path = 'plugins.sherlock.settings';
        $settings = ProjectConfig::unpackAssociativeArrays($projectConfig->get($path) ?? []);
        $settings['phpVersion']['thresholds'] = [
            '8.2' => '2027-01-01',
            '8.3' => '2028-01-01',
            '8.4' => '2029-01-01',
            '8.5' => '2030-01-01',
        ];

        $headers = $settings['headerProtectionSettings']['headers'] ?? null;

        if (is_array($headers)) {
            $settings['headerProtectionSettings']['headers'] = array_values(array_filter($headers, function(array $header): bool {
                $name = strtolower(trim($header[1] ?? ''));
                $value = trim(explode(';', $header[2] ?? '')[0]);

                return $name !== 'x-xss-protection' || $value !== '1';
            }));
        }

        $projectConfig->set($path, ProjectConfig::packAssociativeArrays($settings));

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
