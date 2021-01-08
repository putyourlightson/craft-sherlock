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
    /**
     * @inheritdoc
     */
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * @inheritdoc
     *
     * @throws HttpException
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $key = Craft::$app->getRequest()->getParam('key', '');
        $apiKey = Craft::parseEnv(Sherlock::$plugin->settings->apiKey);

        // Verify provided key against API key
        if (empty($key) || empty($apiKey) || $key != $apiKey) {
            throw new HttpException(403, Craft::t('sherlock', 'Unauthorised API key'));
        }

        return true;
    }

    /**
     * Runs a scan.
     */
    public function actionRunScan()
    {
        Sherlock::$plugin->sherlock->runScan();

        exit('Success');
    }

    /**
     * Returns the last scan, encrypted.
     *
     * @throws HttpException
     */
    public function actionGetLastScan()
    {
        $secretKey = $this->_getSecretKey();

        // Get JSON encoded scan
        $scan = Json::encode(Sherlock::$plugin->sherlock->getLastScan());

        // Encrypt scan with secret key
        $scan = Craft::$app->getSecurity()->encryptByKey($scan, $secretKey);

        exit($scan);
    }

    /**
     * Returns all scans, encrypted.
     *
     * @throws HttpException
     */
    public function actionGetAllScans()
    {
        $secretKey = $this->_getSecretKey();

        // Get offset ID
        $offsetId = Craft::$app->getRequest()->getParam('offsetId', 0);

        // Get JSON encoded scans from offset ID
        $scans = Json::encode(Sherlock::$plugin->sherlock->getAllScans($offsetId));

        // Encrypt scans with secret key
        $scans = Craft::$app->getSecurity()->encryptByKey($scans, $secretKey);

        exit($scans);
    }

    /**
     * Verifies that a secret key exists.
     *
     * @throws HttpException
     */
    public function actionVerify()
    {
        $secretKey = $this->_getSecretKey();

        // JSON encoded success
        $success = Json::encode(array('success' => 1));

        // Encrypt with secret key
        $data = Craft::$app->getSecurity()->encryptByKey($success, $secretKey);

        exit($data);
    }

    /**
     * Returns the secret key or throws an exception if not set.
     *
     * @return string
     * @throws HttpException
     */
    private function _getSecretKey(): string
    {
        $secretKey = Craft::parseEnv(Sherlock::$plugin->settings->secretKey);

        // Check that secret key is set
        if (empty($secretKey)) {
            throw new HttpException(403, Craft::t('sherlock', 'Invalid secret key'));
        }

        return $secretKey;
    }
}
