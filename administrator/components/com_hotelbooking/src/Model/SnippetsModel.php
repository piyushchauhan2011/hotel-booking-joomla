<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Learn\Component\Hotelbooking\Site\Helper\SubformHelper;

\defined('_JEXEC') or die;

/**
 * Paged picker for the editor "Insert Hotel Booking Snippet" modal:
 * one type (destination, room, or offer) and one page of published rows.
 */
class SnippetsModel extends ListModel
{
    /**
     * @var    string
     */
    protected $filterFormName = 'filter_snippets';

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'name', 'type', 'search', 'destination_id', 'entity'];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.name', $direction = 'ASC')
    {
        $app = Factory::getApplication();

        if ($layout = $app->getInput()->get('layout')) {
            $this->context .= '.' . $layout;
        }

        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $type = $app->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', 'destination', 'cmd');

        if (!\in_array($type, ['destination', 'room', 'offer'], true)) {
            $type = 'destination';
        }

        $this->setState('filter.type', $type);

        $destinationId = $app->getUserStateFromRequest($this->context . '.filter.destination_id', 'filter_destination_id', '', 'string');
        $this->setState('filter.destination_id', $destinationId);

        $entity = $app->getUserStateFromRequest($this->context . '.filter.entity', 'filter_entity', '', 'cmd');

        if ($entity !== 'destination' && $entity !== 'room') {
            $entity = '';
        }

        $this->setState('filter.entity', $entity);

		parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.type');
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.destination_id');
        $id .= ':' . $this->getState('filter.entity');

        return parent::getStoreId($id);
    }

    public function getItems()
    {
        if ($this->getState('filter.type') !== 'offer') {
            return parent::getItems();
        }

        $store = $this->getStoreId();

        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        $offers = $this->loadFlattenedOffers();
        $start  = $this->getStart();
        $limit  = (int) $this->getState('list.limit');

        $this->cache[$store] = $limit > 0
            ? \array_slice($offers, $start, $limit)
            : $offers;

        return $this->cache[$store];
    }

    public function getTotal()
    {
        if ($this->getState('filter.type') !== 'offer') {
            return parent::getTotal();
        }

        $store = $this->getStoreId('getTotal');

        if (!isset($this->cache[$store])) {
            $this->cache[$store] = \count($this->loadFlattenedOffers());
        }

        return $this->cache[$store];
    }

    protected function getListQuery(): QueryInterface
    {
        $type = (string) $this->getState('filter.type', 'destination');

        if ($type === 'room') {
            return $this->getRoomsQuery();
        }

        if ($type === 'offer') {
            $db = $this->getDatabase();

            return $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__hotelbooking_destinations'))
                ->where('1 = 0');
        }

        return $this->getDestinationsQuery();
    }

    private function getDestinationsQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['a.id', 'a.name']))
            ->from($db->quoteName('#__hotelbooking_destinations', 'a'))
            ->where($db->quoteName('a.published') . ' = 1')
            ->order($db->quoteName('a.name') . ' ASC');

        $this->bindNameSearch($query, 'a.name');

        return $query;
    }

    private function getRoomsQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('a.id, a.name, a.price, d.name AS destination_name')
            ->from($db->quoteName('#__hotelbooking_rooms', 'a'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('a.destination_id'))
            ->where($db->quoteName('a.published') . ' = 1')
            ->order($db->quoteName('a.name') . ' ASC');

        $this->bindNameSearch($query, 'a.name');

        $destinationId = $this->getState('filter.destination_id');

        if ($destinationId !== '' && $destinationId !== null) {
            $destinationId = (int) $destinationId;
            $query->where($db->quoteName('a.destination_id') . ' = :destinationId')
                ->bind(':destinationId', $destinationId, ParameterType::INTEGER);
        }

        return $query;
    }

    private function bindNameSearch(QueryInterface $query, string $column): void
    {
        $search = trim((string) $this->getState('filter.search', ''));

        if ($search === '') {
            return;
        }

        $db     = $this->getDatabase();
        $search = '%' . str_replace(' ', '%', $search) . '%';
        $query->where($db->quoteName($column) . ' LIKE :search')
            ->bind(':search', $search);
    }

    /**
     * Offers are JSON subform rows, so SQL LIMIT on parent hotels would page
     * the wrong thing. Flatten matching parents, then slice in getItems().
     *
     * @return  list<object>
     */
    private function loadFlattenedOffers(): array
    {
        $store = md5($this->context . ':flattenedOffers:' . $this->getState('filter.search') . ':' . $this->getState('filter.entity'));

        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        $entityFilter = (string) $this->getState('filter.entity', '');
        $entities     = ['destination', 'room'];

        if ($entityFilter === 'destination' || $entityFilter === 'room') {
            $entities = [$entityFilter];
        }

        $needle = mb_strtolower(trim((string) $this->getState('filter.search', '')));
        $offers = [];

        foreach ($entities as $entity) {
            $table = $entity === 'room' ? '#__hotelbooking_rooms' : '#__hotelbooking_destinations';
            $db    = $this->getDatabase();
            $query = $db->createQuery()
                ->select($db->quoteName(['id', 'name', 'offers']))
                ->from($db->quoteName($table))
                ->where($db->quoteName('published') . ' = 1')
                ->where($db->quoteName('offers') . ' IS NOT NULL')
                ->where($db->quoteName('offers') . ' != ' . $db->quote(''))
                ->where($db->quoteName('offers') . ' != ' . $db->quote('{}'))
                ->where($db->quoteName('offers') . ' != ' . $db->quote('[]'))
                ->order($db->quoteName('name') . ' ASC');

            if ($needle !== '') {
                $like = '%' . str_replace(' ', '%', $needle) . '%';
                $query->where(
                    '(' . $db->quoteName('name') . ' LIKE :offerSearchName OR ' . $db->quoteName('offers') . ' LIKE :offerSearchJson)'
                )
                    ->bind(':offerSearchName', $like)
                    ->bind(':offerSearchJson', $like);
            }

            foreach ($db->setQuery($query)->loadObjectList() ?: [] as $row) {
                foreach (SubformHelper::decodeRows($row->offers, 'offer_item') as $index => $offer) {
                    if (empty($offer['title'])) {
                        continue;
                    }

                    $title      = (string) $offer['title'];
                    $parentName = (string) $row->name;

                    if ($needle !== ''
                        && !str_contains(mb_strtolower($title), $needle)
                        && !str_contains(mb_strtolower($parentName), $needle)
                    ) {
                        continue;
                    }

                    $offers[] = (object) [
                        'entity'      => $entity,
                        'id'          => (int) $row->id,
                        'index'       => $index,
                        'parent_name' => $parentName,
                        'title'       => $title,
                        'discount'    => $offer['discount'] ?? '',
                    ];
                }
            }
        }

        $this->cache[$store] = $offers;

        return $offers;
    }
}
