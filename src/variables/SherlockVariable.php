<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\variables;

use putyourlightson\sherlock\models\ScanModel;
use putyourlightson\sherlock\Sherlock;

class SherlockVariable
{
    /**
     * Returns a nonce.
     */
    public function getNonce(): string
    {
        return Sherlock::$plugin->security->getNonce();
    }

    /**
     * Returns the last scan.
     */
    public function getLastScan(): ScanModel
    {
        return Sherlock::$plugin->scans->getLastScan();
    }

    /**
     * Returns all scans.
     */
    public function getAllScans(): array
    {
        return Sherlock::$plugin->scans->getAllScans();
    }

    /**
     * Returns whether the security level is high.
     */
    public function isHighSecurityLevel(): bool
    {
        return Sherlock::$plugin->settings->highSecurityLevel;
    }

    /**
     * Runs a scan.
     */
    public function runScan(): void
    {
        Sherlock::$plugin->scans->runScan();
    }
}
