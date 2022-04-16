<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $siteId
 * @property bool $highSecurityLevel
 * @property bool $pass
 * @property bool $warning
 * @property array $results
 */
class ScanRecord extends ActiveRecord
{
    /**
    * @inheritdoc
    */
    public static function tableName(): string
    {
        return '{{%sherlock}}';
    }
}
