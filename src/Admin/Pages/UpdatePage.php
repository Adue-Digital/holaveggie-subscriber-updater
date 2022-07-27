<?php

namespace Adue\WordPressPlugin\Admin\Pages;

use Adue\Mobbex\Mobbex;
use Adue\WordPressBasePlugin\Modules\Admin\BaseMenuPage;
use Adue\WordPressPlugin\Admin\Options\MobbexConfig;
use Adue\WordPressPlugin\Admin\Options\PlanesOption;

class UpdatePage extends BaseMenuPage
{

    protected string $pageTitle = 'Actualizar suscriptores';
    protected string $menuTitle = 'Actualizar suscriptores';
    protected string $capability = 'manage_options';
    protected string $menuSlug = 'holaveggie-update-subscriptors';
    protected string $iconUrl = '';
    protected int $position = 150;
    protected array $submenuItems = [];

    protected $mobbex;

    public function __construct()
    {
        $mobbexConfig = (new MobbexConfig())->get();
        $this->mobbex = new Mobbex($mobbexConfig['api_key'], $mobbexConfig['access_token']);
    }

    public function render()
    {
        if(isset($_POST['updating'])) {
            $processResponse = $this->preProcess();
            $this->view()->set('processResponse', $processResponse);
            $this->view()->render('admin/update');
            die;
        }
        $this->view()->render('admin/update');
    }

    private function preProcess()
    {
        $fila = 0;
        $processResponse = '';
        if (($gestor = fopen($_FILES['upload_file']['tmp_name'], "r")) !== FALSE) {
            while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
                if($fila == 0) {
                    $fila++;
                    continue;
                }
                $processResponse .= "Línea: ".$fila." - ";
                $processResponse .= "Suscriptor ".$datos[0]." - ";
                $processResponse .= "Acción ".$datos[1]." - ";
                $subscriber = $this->getSubscriberData($datos[0]);
                if (!$subscriber) {
                    $processResponse .= "No existe suscriptor\n";
                    continue;
                }
                $aditionalData = '';
                switch ($datos[1]) {
                    case "Suspender":
                        $response = $this->suspend($subscriber['uid'], $subscriber['subscription']['uid']);
                        break;
                    case "Cambiar":
                        $response = $this->move($subscriber['uid'], $subscriber['subscription']['uid'], $datos[2]);
                        $aditionalData = 'Nuevo plan '.$datos[2];
                        break;
                    case "Pausar":
                        $response = $this->reschedule($subscriber['uid'], $subscriber['subscription']['uid'], $datos[2]);
                        $aditionalData = 'Pausar hasta el '.$datos[2];
                        break;
                    case "Reactivar":
                        $response = $this->activate($subscriber['uid'], $subscriber['subscription']['uid']);
                        break;
                }

                $processResponse .= !empty($aditionalData) ? $aditionalData.' - ' : '';
                $processResponse .= "Resultado ";
                $processResponse .= $response['result'] ? 'éxito' : 'error';
                echo "\n";
            }
            fclose($gestor);
        }

        return $processResponse;
    }

    private function getSubscriberData($email)
    {
        $subscriptionsOption = new PlanesOption();
        foreach ($subscriptionsOption->get() as $planId) {
            $subscription = $this->mobbex->subscription->get(trim($planId));
            $subscriber = $subscription->search($email);
            if($subscriber['result'] && count($subscriber['data']['docs'])) {
                $firstSubscriber = $subscriber['data']['docs'][0];
                return $firstSubscriber;
            }
        }

    }

    private function suspend($subscriberId, $subscriptionId)
    {
        $subscription = $this->mobbex->subscription->get($subscriptionId);
        $response = $subscription->suspend($subscriberId);
        return $response;
    }

    private function move($subscriberId, $subscriptionId, $newSubscriptionId)
    {
        $subscription = $this->mobbex->subscription->get($subscriptionId);
        $response = $subscription->move($subscriberId, $newSubscriptionId);
        return $response;
    }

    private function reschedule($subscriberId, $subscriptionId, $date)
    {
        $subscription = $this->mobbex->subscription->get($subscriptionId);
        $dateArray = [
            'year' => explode('-', $date)[0],
            'month' => explode('-', $date)[1],
            'day' => explode('-', $date)[2],
        ];
        $response = $subscription->reschedule($subscriberId, $dateArray);
        return $response;
    }

    private function activate($subscriberId, $subscriptionId)
    {
        $subscription = $this->mobbex->subscription->get($subscriptionId);
        $response = $subscription->activateSubscriber($subscriberId);
        return $response;
    }
}