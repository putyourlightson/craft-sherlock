<?php
namespace Craft;

/**
 * Sherlock Controller
 */
class SherlockController extends BaseController
{
    protected $allowAnonymous = array('actionGetLastScan', 'actionGetAllScans', 'actionVerify', 'actionRunScan');

    private $_settings;

    /**
    * Init
    */
    public function init()
    {
        parent::init();

        // get settings
        $this->_settings = craft()->plugins->getPlugin('sherlock')->getSettings();

        // prevent devMode logging from being output
        craft()->log->removeRoute('WebLogRoute');
        craft()->log->removeRoute('ProfileLogRoute');
    }

    /**
     * Get last scan
     */
    public function actionGetLastScan()
    {
        $this->_verifyApiKey();

        // check that secret key is set
        if (empty($this->_settings->secretKey)) {
            throw new HttpException(403, Craft::t('Invalid secret key'));
        }

        // get json encoded scan
        $scan = JsonHelper::encode(craft()->sherlock->getLastScan());

        // encrypt scan with secret key
        $scan = craft()->security->encrypt($scan, $this->_settings->secretKey);

        exit($scan);
    }

    /**
     * Get all scans
     */
    public function actionGetAllScans()
    {
        $this->_verifyApiKey();

        // check that secret key is set
        if (empty($this->_settings->secretKey)) {
            throw new HttpException(403, Craft::t('Invalid secret key'));
        }

        // get offset id
        $offsetId = craft()->request->getParam('offsetId', 0);

        // get json encoded scans from offset id
        $scans = JsonHelper::encode(craft()->sherlock->getAllScans($offsetId));

        // encrypt scans with secret key
        $scans = craft()->security->encrypt($scans, $this->_settings->secretKey);

        exit($scans);
    }

    /**
     * Verify
     */
    public function actionVerify()
    {
        $this->_verifyApiKey();

        // check that secret key is set
        if (empty($this->_settings->secretKey)) {
            throw new HttpException(403, Craft::t('Invalid secret key'));
        }

        // json encoded success
        $success = JsonHelper::encode(array('success' => 1));

        // encrypt with secret key
        $data = craft()->security->encrypt($success, $this->_settings->secretKey);

        exit($data);
    }

    /**
     * Run scan
     */
    public function actionRunScan()
    {
        $this->_verifyApiKey();

        craft()->sherlock->runScan();

        exit('Success');
    }

    /**
     * Run scan AJAX
     */
    public function actionRunScanAjax()
    {
        // ensure user is logged in
        $this->requireLogin();

        // if user is not an admin and does not have access to the plugin
        if (!craft()->userSession->isAdmin() and !in_array('accessplugin-sherlock', craft()->userPermissions->getPermissionsByUserId(craft()->userSession->user->id))) {
            throw new HttpException(403, Craft::t('Unauthorised access'));
        }

        craft()->sherlock->runScan();

        exit('Success');
    }

    /**
     * Verify API key
     */
    private function _verifyApiKey()
    {
        // verify API key against provided key
        if (empty($this->_settings->apiKey) or $this->_settings->apiKey != craft()->request->getParam('key')) {
            throw new HttpException(403, Craft::t('Unauthorised API key'));
        }
    }
}
