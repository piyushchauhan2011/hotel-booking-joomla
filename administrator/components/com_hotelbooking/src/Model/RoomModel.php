<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;
use Learn\Component\Hotelbooking\Administrator\Table\DestinationTable;

\defined('_JEXEC') or die;

class RoomModel extends AdminModel
{
    public $typeAlias = 'com_hotelbooking.room';

    public function getTable($name = 'Room', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_hotelbooking.room', 'room', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_hotelbooking.edit.room.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function save($data)
    {
        $user = $this->getCurrentUser();

        if (!AccessHelper::isPrivileged($user)) {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $table = $this->getTable();

            if (!$table->load($id) || !AccessHelper::canEditRoom($user, $this->getDestinationManagerId((int) $table->destination_id))) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $newDestinationId = (int) ($data['destination_id'] ?? $table->destination_id);

            if ($newDestinationId !== (int) $table->destination_id) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $data['published'] = 0;
        }

        return parent::save($data);
    }

    private function getDestinationManagerId(int $destinationId): int
    {
        if ($destinationId <= 0) {
            return 0;
        }

        $destinationTable = new DestinationTable($this->getDatabase());

        if (!$destinationTable->load($destinationId)) {
            return 0;
        }

        return (int) $destinationTable->manager_user_id;
    }
}
