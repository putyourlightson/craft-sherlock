<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\sherlock\Sherlock;
use yii\web\HttpException;

/**
 * AJAX Controller
 */
class AjaxController extends Controller
{
    /**
     * Runs a scan.
     *
     * @throws HttpException
     */
    public function actionRunScan()
    {
        if (!Sherlock::$plugin->userCanAccessPlugin()) {
            throw new HttpException(403, Craft::t('sherlock', 'Unauthorised access'));
        }

        Sherlock::$plugin->scans->runScan();

        exit('Success');
    }
}
