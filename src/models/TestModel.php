<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use putyourlightson\sherlock\base\BaseModel;

/**
 * Test Model
 */
class TestModel extends BaseModel
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $canFail = false;

    /**
     * @var bool
     */
    public $forceFail = false;

    /**
     * @var int
     */
    public $threshold = 0;

    /**
     * @var mixed|null
     */
    public $thresholds;

    /**
     * @var string|null
     */
    public $format;

    /**
     * @var bool
     */
    public $pass = true;

    /**
     * @var bool
     */
    public $warning = false;

    /**
     * @var string
     */
    public $value = '';

    /**
     * @var bool
     */
    public $highSecurityLevel = false;

    // Public Methods
    // =========================================================================

    /**
     * Fail test
     */
    public function failTest()
    {
        $this->pass = $this->forceFail ? false : !($this->canFail AND $this->highSecurityLevel);
        $this->warning = true;
    }
}
