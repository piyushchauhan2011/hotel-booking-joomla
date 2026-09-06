<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;
use Learn\Component\Hotelbooking\Administrator\Table\DestinationTable;
use Learn\Component\Hotelbooking\Administrator\Table\RoomTable;

\defined('_JEXEC') or die;

class BookingModel extends AdminModel
{
    public $typeAlias = 'com_hotelbooking.booking';

    public function getTable($name = 'Booking', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_hotelbooking.booking', 'booking', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_hotelbooking.edit.booking.data', []);

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

            if (!$table->load($id)) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $auth = $this->getDestinationAuth((int) $table->room_id);

            if (!AccessHelper::canEditDestination($user, $auth['id'], $auth['created_by'])) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $newRoomId = (int) ($data['room_id'] ?? $table->room_id);

            if ($newRoomId !== (int) $table->room_id) {
                $newAuth = $this->getDestinationAuth($newRoomId);

                if ($newAuth['id'] !== $auth['id'] || !AccessHelper::canEditDestination($user, $newAuth['id'], $newAuth['created_by'])) {
                    $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                    return false;
                }
            }

            unset($data['commission_rate'], $data['commission_paid'], $data['commission_paid_date']);
        }

        return parent::save($data);
    }

    /**
     * @return array{id:int,created_by:int}
     */
    private function getDestinationAuth(int $roomId): array
    {
        if ($roomId <= 0) {
            return ['id' => 0, 'created_by' => 0];
        }

        $roomTable = new RoomTable($this->getDatabase());

        if (!$roomTable->load($roomId)) {
            return ['id' => 0, 'created_by' => 0];
        }

        $destinationTable = new DestinationTable($this->getDatabase());

        if (!$destinationTable->load((int) $roomTable->destination_id)) {
            return ['id' => 0, 'created_by' => 0];
        }

        return [
            'id'         => (int) $destinationTable->id,
            'created_by' => (int) $destinationTable->created_by,
        ];
    }
}
