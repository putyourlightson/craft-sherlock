<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\controllers;

use Craft;
use craft\helpers\Cp;
use craft\web\Controller;
use putyourlightson\sherlock\Sherlock;
use yii\web\Response;

class ScansController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Require permission
        $this->requirePermission('accessplugin-sherlock');

        return true;
    }

    /**
     * Renders the main Sherlock template.
     */
    public function actionIndex(): Response
    {
        // Set the current site to the requested site
        $site = Cp::requestedSite();
        if ($site !== null) {
            Craft::$app->getSites()->setCurrentSite($site);
        }

        return $this->renderTemplate('sherlock/index');
    }

    /**
     * Runs a scan via an AJAX request.
     */
    public function actionRunScanAjax(int $siteId = null): Response
    {
        Sherlock::$plugin->scans->runScan($siteId);

        return $this->asJson(['success' => true]);
    }
}
