<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\migrations;

use craft\db\Migration;
use craft\records\Site;
use putyourlightson\sherlock\records\ScanRecord;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $table = ScanRecord::tableName();

        if (!$this->db->tableExists($table)) {
            $this->createTable($table, [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->notNull(),
                'highSecurityLevel' => $this->boolean()->notNull(),
                'pass' => $this->boolean()->notNull(),
                'warning' => $this->boolean()->notNull(),
                'results' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, $table, 'siteId');

            $this->addForeignKey(null, $table, 'siteId', Site::tableName(), 'id', 'CASCADE', 'CASCADE');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%sherlock}}');

        return true;
    }
}
