<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\updater\UpdaterAsset;

/**
 * Sherlock Asset bundle
 */
class SherlockAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@putyourlightson/sherlock/resources';

        $this->depends = [
            CpAsset::class,
            UpdaterAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page when this asset bundle is registered
        $this->css = [
            'css/style.css',
        ];
        $this->js = [
            'js/script.js',
        ];

        parent::init();
    }
}
