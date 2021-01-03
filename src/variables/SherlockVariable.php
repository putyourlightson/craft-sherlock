<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\variables;

use putyourlightson\sherlock\models\ScanModel;
use putyourlightson\sherlock\Sherlock;

/**
 * Sherlock Variable
 */
class SherlockVariable
{
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
     * Check high security level
     *
     * @return string
     */
    public function checkHighSecurityLevel(): string
    {
        return Sherlock::$plugin->settings->highSecurityLevel;
    }

    /**
     * Run scan
     */
    public function runScan()
    {
        Sherlock::$plugin->sherlock->runScan();
    }
}
