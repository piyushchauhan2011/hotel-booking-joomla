<?php

namespace Learn\Component\Hotelbooking\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

\defined('_JEXEC') or die;

class RoomTable extends Table
{
    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__hotelbooking_rooms', 'id', $db, $dispatcher);
    }

    public function check()
    {
        if (trim($this->name) === '') {
            $this->setError('Name is required.');

            return false;
        }

        if ((int) $this->destination_id <= 0) {
            $this->setError('Destination is required.');

            return false;
        }

        return true;
    }
}
