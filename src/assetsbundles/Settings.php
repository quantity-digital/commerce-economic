<?php

namespace QD\commerce\economic\assetsbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class Settings extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@QD/commerce/economic/resources";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/settings.js',
        ];

        parent::init();
    }
}
