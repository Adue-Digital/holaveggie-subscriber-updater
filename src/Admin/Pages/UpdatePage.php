<?php

namespace Adue\WordPressPlugin\Admin\Pages;

use Adue\Mobbex\Mobbex;
use Adue\WordPressBasePlugin\Base\Config;
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
            $logName = $this->preProcess();
            $this->view()->set('logName', $logName);
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
            $logName = 'Log-'.date('Y-m-d_H-i-s').'.csv';
            $fp = fopen(__DIR__.'/../../../storage/logs/'.$logName, 'w');
            while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
                $linea = [];
                if($fila == 0) {
                    $linea = ['Suscriptor', 'Acción', 'Dato adicional', 'Resultado', 'Mensaje'];
                    fputcsv($fp, $linea);
                    $fila++;
                    continue;
                }
                $linea[] = $datos[0]; //Suscriptor
                $linea[] = $datos[1]; //Acción
                $subscriber = $this->getSubscriberData($datos[0]);
                if (!$subscriber) {
                    $linea[] = ''; //Dato adicional
                    $linea[] = 'Error'; //Dato adicional
                    $linea[] = "No existe suscriptor";
                    fputcsv($fp, $linea);
                    $fila++;
                    continue;
                }
                $aditionalData = '';
                switch ($datos[1]) {
                    case "Suspender":
                        $response = $this->suspend($subscriber['uid'], $subscriber['subscription']['uid']);
                        $aditionalData = '';
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

                $linea[] = $aditionalData;
                $linea[] = $response['result'] ? 'éxito' : 'error';
                $linea[] = $response['result'] ? '' : "Código de error: ".$response['code'] . " - Error: " .$response['error'];
                fputcsv($fp, $linea);
                $fila++;
            }
            fclose($gestor);
        }

        return $logName;
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