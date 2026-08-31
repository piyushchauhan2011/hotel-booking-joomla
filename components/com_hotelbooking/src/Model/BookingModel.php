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
            ->select([
                $db->quoteName('r.id'),
                $db->quoteName('r.capacity'),
                $db->quoteName('r.price'),
                $db->quoteName('d.commission_rate'),
            ])
            ->from($db->quoteName('#__hotelbooking_rooms', 'r'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('r.destination_id'))
            ->where($db->quoteName('r.published') . ' = 1')
            ->where($db->quoteName('r.id') . ' = :id')
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

        $nights = (int) ((strtotime($checkout) - strtotime($checkin)) / 86400);

        $table = $this->getTable();
        $table->reset();
        $table->room_id         = $roomId;
        $table->guest_name      = $guestName;
        $table->guest_email     = $guestEmail;
        $table->checkin_date    = $checkin;
        $table->checkout_date   = $checkout;
        $table->guests          = $guests;
        $table->total_price     = round((float) $room->price * $nights, 2);
        $table->commission_rate = (float) $room->commission_rate;
        $table->status          = 'pending';
        $table->partner_status  = 'awaiting_hotel_check';
        $table->created          = Factory::getDate()->toSql();

        if (!$table->check() || !$table->store()) {
            throw new \RuntimeException($table->getError());
        }

        return (int) $table->id;
    }
}
