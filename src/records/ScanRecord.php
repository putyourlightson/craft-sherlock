<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\records;

use craft\db\ActiveRecord;

/**
 * Scan Record
 *
 * @property bool $highSecurityLevel          High security level
 * @property bool $pass                       Pass
 * @property bool $warning                    Warning
 * @property mixed $results                   Results
 * @property string $tableName
 */
class ScanRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

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
