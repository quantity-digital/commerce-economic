<?php

namespace QD\commerce\economic\variables;

use QD\commerce\economic\Economic as EconomicPlugin;

class Economic
{
    public function getPluginName()
    {
        return EconomicPlugin::$plugin->getPluginName();
    }
}
