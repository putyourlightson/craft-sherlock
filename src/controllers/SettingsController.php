<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\sherlock\Sherlock;
use yii\web\Response;

class SettingsController extends Controller
{
    /**
     * @inerhitdoc
     */
    public function beforeAction($action): bool
    {
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * Edit the plugin settings.
     */
    public function actionEdit(): Response
    {
        $integrations = Sherlock::$plugin->integrations->createAllTypes();

        // Validate integrations if pro
        if (Sherlock::$plugin->getIsPro()) {
            foreach ($integrations as $integration) {
                if ($integration->getEnabled()) {
                    $integration->validate();
                }
            }
        }

        return $this->renderTemplate('sherlock/_settings', [
            'settings' => Sherlock::$plugin->settings,
            'config' => Craft::$app->getConfig()->getConfigFromFile('sherlock'),
            'isLite' => Sherlock::$plugin->getIsLite(),
            'isPro' => Sherlock::$plugin->getIsPro(),
            'integrations' => $integrations,
        ]);
    }

    /**
     * Saves the plugin settings.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $postedSettings = Craft::$app->getRequest()->getBodyParam('settings', []);

        $settings = Sherlock::$plugin->settings;
        $settings->setAttributes($postedSettings, false);

        $hasIntegrationErrors = false;

        $integrations = Sherlock::$plugin->integrations->createEnabledTypes();

        foreach ($integrations as $integration) {
            $integration->validate();

            if ($integration->hasErrors()) {
                $hasIntegrationErrors = true;
            }
        }

        // Validate
        $settings->validate();

        if ($settings->hasErrors()
            || $hasIntegrationErrors
            || !Craft::$app->getPlugins()->savePluginSettings(Sherlock::$plugin, $settings->getAttributes())
        ) {
            Craft::$app->getSession()->setError(Craft::t('sherlock', 'Couldn’t save plugin settings.'));

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('sherlock', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
