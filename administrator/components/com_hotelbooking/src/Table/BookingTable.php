<?php

namespace Learn\Component\Hotelbooking\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

\defined('_JEXEC') or die;

class BookingTable extends Table
{
    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__hotelbooking_bookings', 'id', $db, $dispatcher);
    }

    public function check()
    {
        if ((int) $this->room_id <= 0) {
            $this->setError('Room is required.');

            return false;
        }

        if (trim($this->guest_name ?? '') === '' || trim($this->guest_email ?? '') === '') {
            $this->setError('Guest name and email are required.');

            return false;
        }

        if (strtotime($this->checkout_date) <= strtotime($this->checkin_date)) {
            $this->setError('Check-out date must be after the check-in date.');

            return false;
        }

        if (empty($this->created)) {
            $this->created = \Joomla\CMS\Factory::getDate()->toSql();
        }

        return true;
    }
}
