<?php

namespace Learn\Component\Hotelbooking\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Learn\Component\Hotelbooking\Site\Model\DestinationsModel;

\defined('_JEXEC') or die;

class DestinationsController extends BaseController
{
    public function suggest()
    {
        $app  = $this->app;
        $term = $app->getInput()->getString('term', '');

        $results = [];

        if (mb_strlen(trim($term)) >= 2) {
            /** @var DestinationsModel $model */
            $model = $this->getModel('Destinations', 'Site', ['ignore_request' => true]);
            $model->setState('filter.search', $term);
            $model->setState('list.limit', 6);
            $model->setState('list.ordering', 'a.ordering');
            $model->setState('list.direction', 'ASC');

            foreach ($model->getItems() as $destination) {
                $results[] = [
                    'id'   => (int) $destination->id,
                    'name' => $destination->name,
                    'url'  => Route::_('index.php?option=com_hotelbooking&view=destination&id=' . (int) $destination->id, false),
                ];
            }
        }

        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();
        echo json_encode($results);
        $app->close();
    }
}
