<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;

class m240526_120000_add_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->set('plugins.sherlock.settings.maxScans', false);

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
