<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\records;

use craft\db\ActiveRecord;

/**
 * Scan Record
 *
 * @property int $id
 * @property int $siteId
 * @property bool $highSecurityLevel          High security level
 * @property bool $pass                       Pass
 * @property bool $warning                    Warning
 * @property mixed $results                   Results
 */
class ScanRecord extends ActiveRecord
{
     /**
     * @inheritdoc
     *
     * @return string the table name
     */
    public static function tableName(): string
    {
        return '{{%sherlock}}';
    }
}
