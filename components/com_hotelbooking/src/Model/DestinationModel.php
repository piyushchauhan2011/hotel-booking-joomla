<?php

namespace Learn\Component\Hotelbooking\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class DestinationModel extends ItemModel
{
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_destinations'))
            ->where($db->quoteName('published') . ' = 1');

        $id = (int) $this->getState('destination.id');

        if ($id > 0) {
            $query->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
        }

        if (Multilanguage::isEnabled()) {
            $query->whereIn(
                $db->quoteName('language'),
                [Factory::getApplication()->getLanguage()->getTag(), '*'],
                ParameterType::STRING,
            );
        }

        return $query;
    }

    protected function populateState()
    {
        $id = \Joomla\CMS\Factory::getApplication()->getInput()->getInt('id', 0);
        $this->setState('destination.id', $id);
    }

    public function getItem($pk = null)
    {
        $db    = $this->getDatabase();
        $query = $this->getListQuery();
        $db->setQuery($query);

        $item = $db->loadObject();

        return $item ?: null;
    }

    public function getRooms(int $destinationId): array
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_rooms'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('destination_id') . ' = :destinationId')
            ->bind(':destinationId', $destinationId, ParameterType::INTEGER)
            ->order($db->quoteName('ordering') . ' ASC');

        if (Multilanguage::isEnabled()) {
            $query->whereIn(
                $db->quoteName('language'),
                [Factory::getApplication()->getLanguage()->getTag(), '*'],
                ParameterType::STRING,
            );
        }

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }
}
