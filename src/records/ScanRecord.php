<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\records;

use craft\db\ActiveRecord;

/**
* Scan Record
*
*/
class ScanRecord extends ActiveRecord
{
    /**
    * Gets the database table name
    *
    * @return string
    */
    public function getTableName()
    {
        return 'sherlock';
    }

    /**
    * Define columns for our database table
    *
    * @return array
    */
    public function defineAttributes()
    {
        return array(
            'highSecurityLevel' => AttributeType::Bool,
            'pass' => AttributeType::Bool,
            'warning' => AttributeType::Bool,
            'results' => AttributeType::Mixed,
        );
    }
}
