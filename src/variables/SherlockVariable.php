<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\variables;


/**
 * Sherlock Variable
 */
class SherlockVariable
{
    /**
     * Get plugin name
     *
	 * @return string
     */
    public function getPluginName()
    {
        return craft()->plugins->getPlugin('sherlock')->getName();
    }

    /**
     * Get random string
     *
	 * @return string
     */
    public function getRandomString()
    {
        return StringHelper::randomString(32);
    }

    /**
     * Get last scan
     *
	 * @return Sherlock_ScanModel
     */
    public function getLastScan()
    {
        return craft()->sherlock->getLastScan();
    }

    /**
     * Get all scans
     *
	 * @return array
     */
    public function getAllScans()
    {
        return craft()->sherlock->getAllScans();
    }

    /**
     * Check High Security Level
     *
	 * @return string
     */
    public function checkHighSecurityLevel()
    {
        return craft()->plugins->getPlugin('sherlock')->getSettings()->highSecurityLevel;
    }

    /**
     * Run scan
     *
	 * @return Sherlock_ScanModel
     */
    public function runScan()
    {
        craft()->sherlock->runScan();
    }
}
