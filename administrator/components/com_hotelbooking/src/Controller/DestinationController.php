<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;
use Learn\Component\Hotelbooking\Administrator\Table\DestinationTable;

\defined('_JEXEC') or die;

class DestinationController extends FormController
{
    protected $text_prefix = 'COM_HOTELBOOKING_DESTINATION';

    protected function allowEdit($data = [], $key = 'id')
    {
        $user = $this->app->getIdentity();

        if (AccessHelper::isPrivileged($user)) {
            return parent::allowEdit($data, $key);
        }

        $id = (int) ($data[$key] ?? 0);

        if ($id <= 0) {
            return false;
        }

        $table = new DestinationTable(Factory::getDbo());

        if (!$table->load($id)) {
            return false;
        }

        return AccessHelper::canEditDestination($user, $id, (int) $table->created_by);
    }
}
