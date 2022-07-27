<?php

namespace Adue\WordPressPlugin\Admin\Pages;

use Adue\WordPressBasePlugin\Helpers\Traits\UseView;
use Adue\WordPressBasePlugin\Modules\Admin\BaseMenuPage;
use Adue\WordPressPlugin\Admin\Options\MobbexConfig;
use Adue\WordPressPlugin\Admin\Options\PlanesOption;

class HolaVeggie extends BaseMenuPage
{

    protected string $pageTitle = 'HolaVeggie';
    protected string $menuTitle = 'HolaVeggie Updater';
    protected string $capability = 'manage_options';
    protected string $menuSlug = 'holaveggie-updater';
    protected string $iconUrl = '';
    protected int $position = 150;
    protected array $submenuItems = [];

    private $mobbexConfig;

    public function __construct()
    {
        $this->mobbexConfig = new MobbexConfig();
        $this->planes = new PlanesOption();
    }

    public function render()
    {
        if($_POST['save'])
            $this->processData($_POST);

        $this->view()->set('mobbexConfig', $this->mobbexConfig->get());
        $this->view()->set('planes', $this->planes->get());
        $this->view()->render('admin/mobbex_config');
    }

    private function processData(array $data)
    {
        $this->mobbexConfig->update($data['mobbex_config']);
        $this->planes->update($data['planes']);
    }

}