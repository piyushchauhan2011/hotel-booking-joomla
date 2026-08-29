<?php

namespace Learn\Component\Hotelbooking\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

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
        return false;
    }

    public function submitBooking(array $data): int
    {
        $roomId = (int) ($data['room_id'] ?? 0);

        if ($roomId <= 0) {
            throw new \RuntimeException(Text::_('COM_HOTELBOOKING_ERROR_INVALID_ROOM'));
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select(['id', 'capacity'])
            ->from($db->quoteName('#__hotelbooking_rooms'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $roomId, ParameterType::INTEGER);
        $db->setQuery($query);
        $room = $db->loadObject();

        if (!$room) {
            throw new \RuntimeException(Text::_('COM_HOTELBOOKING_ERROR_INVALID_ROOM'));
        }

        $guestName  = trim((string) ($data['guest_name'] ?? ''));
        $guestEmail = trim((string) ($data['guest_email'] ?? ''));

        if ($guestName === '' || $guestEmail === '') {
            throw new \RuntimeException(Text::_('COM_HOTELBOOKING_ERROR_REQUIRED_FIELDS'));
        }

        $checkin  = $data['checkin_date'] ?? '';
        $checkout = $data['checkout_date'] ?? '';
        $today    = Factory::getDate()->format('Y-m-d');

        if (
            !$checkin || !$checkout
            || strtotime($checkout) <= strtotime($checkin)
            || strtotime($checkin) < strtotime($today)
        ) {
            throw new \RuntimeException(Text::_('COM_HOTELBOOKING_ERROR_INVALID_DATES'));
        }

        $guests = (int) ($data['guests'] ?? 1);

        if ($guests > (int) $room->capacity) {
            throw new \RuntimeException(Text::sprintf('COM_HOTELBOOKING_ERROR_TOO_MANY_GUESTS', (int) $room->capacity));
        }

        $table = $this->getTable();
        $table->reset();
        $table->room_id       = $roomId;
        $table->guest_name    = $guestName;
        $table->guest_email   = $guestEmail;
        $table->checkin_date  = $checkin;
        $table->checkout_date = $checkout;
        $table->guests        = $guests;
        $table->status        = 'pending';
        $table->created        = Factory::getDate()->toSql();

        if (!$table->store()) {
            throw new \RuntimeException($table->getError());
        }

        return (int) $table->id;
    }
}
