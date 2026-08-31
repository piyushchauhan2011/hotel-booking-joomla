<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;
use Learn\Component\Hotelbooking\Administrator\Table\DestinationTable;
use Learn\Component\Hotelbooking\Administrator\Table\RoomTable;

\defined('_JEXEC') or die;

class RoomController extends FormController
{
    protected $text_prefix = 'COM_HOTELBOOKING_ROOM';

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

        $db = Factory::getDbo();

        $roomTable = new RoomTable($db);

        if (!$roomTable->load($id)) {
            return false;
        }

        $destinationTable = new DestinationTable($db);

        if (!$destinationTable->load((int) $roomTable->destination_id)) {
            return false;
        }

        return AccessHelper::canEditRoom($user, (int) $destinationTable->manager_user_id);
    }
}
