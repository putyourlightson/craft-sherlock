<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use craft\helpers\Json;
use DateTime;
use putyourlightson\sherlock\base\BaseModel;

/**
 * Scan Model
 */
class ScanModel extends BaseModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $siteId;

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
     * @var DateTime
     */
    public $dateCreated;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Decode results if not array
        $this->results = is_array($this->results) ? $this->results : Json::decode($this->results);
    }
}
