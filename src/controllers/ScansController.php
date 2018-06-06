<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use putyourlightson\sherlock\Sherlock;
use yii\web\HttpException;

/**
 * Scans Controller
 */
class ScansController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = array('actionGetLastScan', 'actionGetAllScans', 'actionVerify', 'actionRunScan');

    private $_settings;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Get settings
        $this->_settings = Sherlock::$plugin->getSettings();

        // prevent devMode logging from being output
        //\Craft::$app->getLog()->removeRoute('WebLogRoute');
        //craft()->log->removeRoute('ProfileLogRoute');
    }

    /**
     * Get last scan
     */
    public function actionGetLastScan()
    {
        $this->_verifyApiKey();

        // Check that secret key is set
        if (empty($this->_settings->secretKey)) {
            throw new HttpException(403, Craft::t('sherlock', 'Invalid secret key'));
        }

        // Get JSON encoded scan
        $scan = Json::encode(Sherlock::$plugin->sherlock->getLastScan());

        // Encrypt scan with secret key
        $scan = Craft::$app->getSecurity()->encrypt($scan, false, $this->_settings->secretKey);

        exit($scan);
    }

    /**
     * Get all scans
     */
    public function actionGetAllScans()
    {
        $this->_verifyApiKey();

        // Check that secret key is set
        if (empty($this->_settings->secretKey)) {
            throw new HttpException(403, Craft::t('sherlock', 'Invalid secret key'));
        }

        // Get offset ID
        $offsetId = Craft::$app->getRequest()->getParam('offsetId', 0);

        // Get JSON encoded scans from offset ID
        $scans = Json::encode(craft()->sherlock->getAllScans($offsetId));

        // Encrypt scans with secret key
        $scans = Craft::$app->getSecurity()->encrypt($scans, false, $this->_settings->secretKey);

        exit($scans);
    }

    /**
     * Verify
     */
    public function actionVerify()
    {
        $this->_verifyApiKey();

        // Check that secret key is set
        if (empty($this->_settings->secretKey)) {
            throw new HttpException(403, Craft::t('sherlock', 'Invalid secret key'));
        }

        // JSON encoded success
        $success = Json::encode(array('success' => 1));

        // Encrypt with secret key
        $data = Craft::$app->getSecurity()->encrypt($success, false, $this->_settings->secretKey);

        exit($data);
    }

    /**
     * Run scan
     */
    public function actionRunScan()
    {
        $this->_verifyApiKey();

        Sherlock::$plugin->sherlock->runScan();

        exit('Success');
    }

    /**
     * Run scan AJAX
     */
    public function actionRunScanAjax()
    {
        // Ensure user is logged in
        $this->requireLogin();

        $permissions = Craft::$app->getUserPermissions()->getPermissionsByUserId(Craft::$app->getUser()->id);

        // If user is not an admin and does not have access to the plugin
        if (!Craft::$app->getUser()->getIsAdmin() and !in_array('accessplugin-sherlock', $permissions, true)) {
            throw new HttpException(403, Craft::t('sherlock', 'Unauthorised access'));
        }

        Sherlock::$plugin->sherlock->runScan();

        exit('Success');
    }

    /**
     * Verify API key
     */
    private function _verifyApiKey()
    {
        // Verify API key against provided key
        if (empty($this->_settings->apiKey) or $this->_settings->apiKey != Craft::$app->getRequest()->getParam('key')) {
            throw new HttpException(403, Craft::t('sherlock', 'Unauthorised API key'));
        }
    }
}
