<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;

class m261028_120000_remove_x_xss_protection extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $path = 'plugins.sherlock.settings.headerProtectionSettings.headers';
        $headers = $projectConfig->get($path);

        if (!is_array($headers)) {
            return true;
        }

        $filteredHeaders = array_filter($headers, function(array $header): bool {
            $name = strtolower(trim($header[1] ?? ''));
            $value = trim(explode(';', $header[2] ?? '')[0]);

            return $name !== 'x-xss-protection' || $value !== '1';
        });

        if ($filteredHeaders !== $headers) {
            $projectConfig->set($path, array_values($filteredHeaders));
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
