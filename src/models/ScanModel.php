<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use craft\base\Model;
use DateTime;

class ScanModel extends Model
{
    /**
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var int|null
     */
    public ?int $siteId = null;

    /**
     * @var bool
     */
    public bool $highSecurityLevel = false;

    /**
     * @var bool
     */
    public bool $pass = true;

    /**
     * @var bool
     */
    public bool $warning = false;

    /**
     * @var array
     */
    public array $results = [
        'fail' => [],
        'warning' => [],
        'pass' => [],
    ];

    /**
     * @var DateTime|null
     */
    public ?DateTime $dateCreated = null;
}
