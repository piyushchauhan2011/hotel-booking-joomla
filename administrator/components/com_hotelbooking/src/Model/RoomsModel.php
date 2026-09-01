<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

class RoomsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'name', 'published', 'ordering', 'destination_id', 'language'];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        $app = Factory::getApplication();

        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', $published);

        $destinationId = $app->getUserStateFromRequest($this->context . '.filter.destination_id', 'filter_destination_id', '', 'string');
        $this->setState('filter.destination_id', $destinationId);

        $language = $app->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string');
        $this->setState('filter.language', $language);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.name'),
                    $db->quoteName('a.destination_id'),
                    $db->quoteName('a.price'),
                    $db->quoteName('a.capacity'),
                    $db->quoteName('a.published'),
                    $db->quoteName('a.ordering'),
                    $db->quoteName('a.language'),
                    $db->quoteName('l.title', 'language_title'),
                    $db->quoteName('l.image', 'language_image'),
                    $db->quoteName('d.name', 'destination_name'),
                ]
            )
        )
            ->from($db->quoteName('#__hotelbooking_rooms', 'a'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('a.destination_id'))
            ->join('LEFT', $db->quoteName('#__languages', 'l') . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . str_replace(' ', '%', trim($search)) . '%';
            $query->where($db->quoteName('a.name') . ' LIKE :search')
                ->bind(':search', $search);
        }

        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        $destinationId = $this->getState('filter.destination_id');

        if (!empty($destinationId)) {
            $destinationId = (int) $destinationId;
            $query->where($db->quoteName('a.destination_id') . ' = :destinationId')
                ->bind(':destinationId', $destinationId, ParameterType::INTEGER);
        }

        $language = $this->getState('filter.language');

        if (!empty($language)) {
            $query->where($db->quoteName('a.language') . ' = :language')
                ->bind(':language', $language);
        }

        $user = $this->getCurrentUser();

        if (!AccessHelper::isPrivileged($user)) {
            $query->where($db->quoteName('d.manager_user_id') . ' = :scopedUserId')
                ->bind(':scopedUserId', $user->id, ParameterType::INTEGER);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
