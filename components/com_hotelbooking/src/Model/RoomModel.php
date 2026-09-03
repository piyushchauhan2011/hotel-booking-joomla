<?php

namespace Learn\Component\Hotelbooking\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class RoomModel extends ItemModel
{
    protected function populateState()
    {
        $id = Factory::getApplication()->getInput()->getInt('id', 0);
        $this->setState('room.id', $id);
    }

    public function getItem($pk = null)
    {
        $id = (int) ($pk ?: $this->getState('room.id'));

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('r.*, d.name AS destination_name, d.id AS destination_id')
            ->from($db->quoteName('#__hotelbooking_rooms', 'r'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('r.destination_id'))
            ->where($db->quoteName('r.published') . ' = 1')
            ->where($db->quoteName('r.id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        if (Multilanguage::isEnabled()) {
            $query->whereIn(
                $db->quoteName('r.language'),
                [Factory::getApplication()->getLanguage()->getTag(), '*'],
                ParameterType::STRING,
            );
        }

        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }
}
