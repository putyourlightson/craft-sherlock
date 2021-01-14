<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\sherlock\Sherlock;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Scans Controller
 */
class ScansController extends Controller
{
    /**
     * For the deprecated `run-scan` action
     */
    protected $allowAnonymous = ['run-scan'];

    /**
     * @inheritdoc
     *
     * @throws ForbiddenHttpException
     */
    public function init()
    {
        parent::init();

        // Allow deprecated `run-scan` action
        if ($this->id == 'run-scan') {
            return;
        }

        // Require permission
        $this->requirePermission('accessplugin-sherlock');
    }

    /**
     * @param string|null $siteHandle
     * @return Response
     */
    public function actionIndex(string $siteHandle = null): Response
    {
        // Set the current site to the site handle if set
        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if ($site !== null) {
                Craft::$app->getSites()->setCurrentSite($site);
            }
        }

        // Render the template
        return $this->renderTemplate('sherlock/index');
    }

    /**
     * Runs a scan.
     *
     * @param int|null $siteId
     * @return Response
     */
    public function actionRunScanAjax(int $siteId = null): Response
    {
        Sherlock::$plugin->scans->runScan($siteId);

        return $this->asJson(['success' => true]);
    }

    /**
     * Deprecated in 3.0.0, use `api/run-scan` instead.
     *
     * @deprecated
     */
    public function actionRunScan()
    {
        Sherlock::$plugin->runAction('api/run-scan');
    }
}
