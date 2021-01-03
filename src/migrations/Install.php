<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install Migration
 */
class Install extends Migration
{
    /**
     * @return boolean
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%sherlock}}')) {
            $this->createTable('{{%sherlock}}', [
                'id' => $this->primaryKey(),
                'highSecurityLevel' => $this->boolean()->notNull(),
                'pass' => $this->boolean()->notNull(),
                'warning' => $this->boolean()->notNull(),
                'results' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @return boolean
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%sherlock}}');

        return true;
    }
}
