<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use craft\base\Model;
use craft\helpers\Json;
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
     * @var mixed
     */
    public mixed $results = [
        'fail' => [],
        'warning' => [],
        'pass' => [],
    ];

    /**
     * @var DateTime
     */
    public DateTime $dateCreated;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Decode results if not array
        $this->results = is_array($this->results) ? $this->results : Json::decode($this->results);
    }
}
