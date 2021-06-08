<?php

namespace putyourlightson\sherlock\migrations;

use Craft;
use craft\db\Migration;
use craft\records\Site;
use putyourlightson\sherlock\records\ScanRecord;

class m210608_120000_add_site_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = ScanRecord::tableName();

        if (!$this->db->columnExists($table, 'siteId')) {
            $this->addColumn($table, 'siteId', $this->integer()->after('id'));

            $this->createIndex(null, $table, 'siteId', false);

            $this->addForeignKey(null, $table, 'siteId', Site::tableName(), 'id', 'CASCADE', 'CASCADE');

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();

            $primarySite = Craft::$app->getSites()->getPrimarySite();

            // Update site ID of all records
            ScanRecord::updateAll(['siteId' => $primarySite->id]);
        }

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
