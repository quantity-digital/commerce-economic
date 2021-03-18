<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use QD\commerce\economic\elements\Setting;

class Plugin extends Component
{
    public function getSettings()
    {
        return Setting::find()->one();
    }
}
