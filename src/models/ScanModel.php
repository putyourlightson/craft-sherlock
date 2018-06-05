<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use putyourlightson\sherlock\base\BaseModel;

/**
 * Scan Model
 */
class ScanModel extends BaseModel
{
    // Public Properties
    // =========================================================================

    /**
     * @var int
     */
    public $id;

    /**
     * @var bool
     */
    public $highSecurityLevel = false;

    /**
     * @var bool
     */
    public $pass = true;

    /**
     * @var bool
     */
    public $warning = false;

    /**
     * @var mixed
     */
    public $results = [
        'fail' => [],
        'warning' => [],
        'pass' => [],
    ];

    /**
     * @var \DateTime
     */
    public $dateCreated;
}
