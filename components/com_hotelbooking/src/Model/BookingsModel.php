<?php

namespace Learn\Component\Hotelbooking\Site\Model;

use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class BookingsModel extends ItemModel
{
    protected function populateState()
    {
        $id = \Joomla\CMS\Factory::getApplication()->getInput()->getInt('id', 0);
        $this->setState('booking.id', $id);
    }

    public function getItem($pk = null)
    {
        $id = (int) ($pk ?: $this->getState('booking.id'));

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('b.*, r.name AS room_name')
            ->from($db->quoteName('#__hotelbooking_bookings', 'b'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_rooms', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('b.room_id'))
            ->where($db->quoteName('b.id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }
}
