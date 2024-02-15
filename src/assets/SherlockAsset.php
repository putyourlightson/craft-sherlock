<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\updates\UpdatesAsset;

/**
 * Sherlock Asset bundle
 */
class SherlockAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = '@putyourlightson/sherlock/resources';

        $this->depends = [
            CpAsset::class,
            UpdatesAsset::class,
        ];

        $this->css = [
            'css/cp.css',
            'lib/font-awesome/css/all.min.css',
        ];
        $this->js = [
            'js/script.js',
        ];

        parent::init();
    }
}
