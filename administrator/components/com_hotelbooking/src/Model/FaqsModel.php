<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

class FaqsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'question', 'scope', 'published', 'ordering'];
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

        $scope = $app->getUserStateFromRequest($this->context . '.filter.scope', 'filter_scope', '', 'string');
        $this->setState('filter.scope', $scope);

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
                    $db->quoteName('a.question'),
                    $db->quoteName('a.scope'),
                    $db->quoteName('a.published'),
                    $db->quoteName('a.ordering'),
                ]
            )
        )
            ->from($db->quoteName('#__hotelbooking_faqs', 'a'));

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . str_replace(' ', '%', trim($search)) . '%';
            $query->where($db->quoteName('a.question') . ' LIKE :search')
                ->bind(':search', $search);
        }

        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        $scope = $this->getState('filter.scope');

        if (!empty($scope)) {
            $query->where($db->quoteName('a.scope') . ' = :scope')
                ->bind(':scope', $scope);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
