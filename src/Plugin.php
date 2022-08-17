<?php

namespace Adue\WordPressPlugin;

use Adue\WordPressBasePlugin\BasePlugin;
use Adue\WordPressPlugin\Admin\Pages\ActualizacionesPage;
use Adue\WordPressPlugin\Admin\Pages\HolaVeggie;
use Adue\WordPressPlugin\Admin\Pages\UpdatePage;

class Plugin extends BasePlugin
{

    protected string $configFilePath = __DIR__.'/../config/config.php';

    public function init()
    {
        $pluginConfigPage = new HolaVeggie();
        $pluginConfigPage->setSubpage(new UpdatePage());
        $pluginConfigPage->setSubpage(new ActualizacionesPage());
        $pluginConfigPage->add();
        $pluginConfigPage->addSubmenus();
    }

}