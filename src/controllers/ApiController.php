<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\sherlock\Sherlock;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\Response;

/**
 * API Controller
 */
class ApiController extends Controller
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

        if (Sherlock::$plugin->getIsLite()) {
            throw new ForbiddenHttpException(Craft::t('sherlock', 'Sherlock Plus is required to perform this action.'));
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
     *
     * @param int|null $siteId
     */
    public function actionRunScan(int $siteId = null)
    {
        Sherlock::$plugin->scans->runScan($siteId);

        exit('Success');
    }

    /**
     * Returns the last scan.
     *
     * @param int|null $siteId
     * @return Response
     */
    public function actionGetLastScan(int $siteId = null): Response
    {
        return $this->asJson(Sherlock::$plugin->scans->getLastScan($siteId));
    }

    /**
     * Returns all scans.
     *
     * @param int|null $siteId
     * @return Response
     */
    public function actionGetAllScans(int $siteId = null): Response
    {
        // Get offset ID
        $offsetId = Craft::$app->getRequest()->getParam('offsetId', 0);

        return $this->asJson(Sherlock::$plugin->scans->getAllScans($siteId, $offsetId));
    }
}
