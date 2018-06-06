<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\variables;

use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\sherlock\models\ScanModel;
use putyourlightson\sherlock\Sherlock;


/**
 * Sherlock Variable
 */
class SherlockVariable
{
    /**
     * Get random string
     *
	 * @return string
     */
    public function getRandomString(): string
    {
        return StringHelper::randomString(32);
    }

    /**
     * Get last scan
     *
	 * @return ScanModel
     */
    public function getLastScan(): ScanModel
    {
        return Sherlock::$plugin->sherlock->getLastScan();
    }

    /**
     * Get all scans
     *
	 * @return array
     */
    public function getAllScans(): array
    {
        return Sherlock::$plugin->sherlock->getAllScans();
    }

    /**
     * Check High Security Level
     *
	 * @return string
     */
    public function checkHighSecurityLevel(): string
    {
        return Sherlock::$plugin->getSettings()->highSecurityLevel;
    }

    /**
     * Run scan
     */
    public function runScan()
    {
        Sherlock::$plugin->sherlock->runScan();
    }
}
