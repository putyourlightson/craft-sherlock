<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\models;

use craft\base\Model;

class TestModel extends Model
{
    /**
     * @var string
     */
    public string $name = '';

    /**
     * @var bool
     */
    public bool $canFail = false;

    /**
     * @var bool
     */
    public bool $forceFail = false;

    /**
     * @var int
     */
    public int $threshold = 0;

    /**
     * @var mixed|null
     */
    public mixed $thresholds = null;

    /**
     * @var string|null
     */
    public ?string $format = null;

    /**
     * @var bool
     */
    public bool $pass = true;

    /**
     * @var bool
     */
    public bool $warning = false;

    /**
     * @var string
     */
    public string $value = '';

    /**
     * @var bool
     */
    public bool $highSecurityLevel = false;

    /**
     * Fail test
     */
    public function failTest()
    {
        $this->pass = !$this->forceFail && !($this->canFail && $this->highSecurityLevel);
        $this->warning = true;
    }
}
