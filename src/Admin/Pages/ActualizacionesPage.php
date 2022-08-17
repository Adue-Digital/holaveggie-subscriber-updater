<?php

namespace Adue\WordPressPlugin\Admin\Pages;

use Adue\WordPressBasePlugin\Modules\Admin\BaseMenuPage;

class ActualizacionesPage extends BaseMenuPage
{
    protected string $pageTitle = 'Actualizaciones';
    protected string $menuTitle = 'Actualizaciones';
    protected string $capability = 'manage_options';
    protected string $menuSlug = 'holaveggie-actualizaciones';
    protected string $iconUrl = '';
    protected int $position = 200;
    protected array $submenuItems = [];

    public function render()
    {
        if(isset($_POST['eliminar'])) {
            unlink(__DIR__.'/../../../storage/logs/'.$_POST['file']);
        }
        $files = scandir(__DIR__.'/../../../storage/logs');
        $this->view()->set('actualizaciones', $files);
        $this->view()->render('admin/actualizaciones');
    }

}