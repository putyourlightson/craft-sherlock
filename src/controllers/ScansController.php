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
     * Runs a scan.
     *
     * @throws HttpException
     */
    public function actionRunScan()
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
}
