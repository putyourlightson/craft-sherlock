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
     * Returns a nonce.
     *
     * @return string
     */
    public function getNonce(): string
    {
        return Sherlock::$plugin->security->getNonce();
    }

    /**
     * Get last scan
     *
     * @return ScanModel
     */
    public function getLastScan(): ScanModel
    {
        return Sherlock::$plugin->scans->getLastScan();
    }

    /**
     * Get all scans
     *
     * @return array
     */
    public function getAllScans(): array
    {
        return Sherlock::$plugin->scans->getAllScans();
    }

    /**
     * Returns whether the security level is high
     *
     * @return bool
     */
    public function isHighSecurityLevel(): bool
    {
        return Sherlock::$plugin->settings->highSecurityLevel;
    }

    /**
     * Run scan
     */
    public function runScan()
    {
        Sherlock::$plugin->scans->runScan();
    }
}
