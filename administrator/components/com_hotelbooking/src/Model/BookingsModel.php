<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Utilities\ArrayHelper;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;
use Learn\Component\Hotelbooking\Administrator\Helper\BookingWorkflowHelper;

\defined('_JEXEC') or die;

class BookingsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'guest_name', 'status', 'partner_status', 'created', 'stage'];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.created', $direction = 'DESC')
    {
        $app = Factory::getApplication();

        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $status = $app->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'string');
        $this->setState('filter.status', $status);

        $partnerStatus = $app->getUserStateFromRequest($this->context . '.filter.partner_status', 'filter_partner_status', '', 'string');
        $this->setState('filter.partner_status', $partnerStatus);

        $stage = $app->getUserStateFromRequest($this->context . '.filter.stage', 'filter_stage', '', 'string');
        $filter = $app->getInput()->get('filter', [], 'array');

        if (isset($filter['stage'])) {
            $stage = (string) $filter['stage'];
        }

        $this->setState('filter.stage', $stage);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();
        $select = [
            $db->quoteName('a.id'),
            $db->quoteName('a.room_id'),
            $db->quoteName('a.guest_name'),
            $db->quoteName('a.guest_email'),
            $db->quoteName('a.checkin_date'),
            $db->quoteName('a.checkout_date'),
            $db->quoteName('a.guests'),
            $db->quoteName('a.total_price'),
            $db->quoteName('a.commission_rate'),
            $db->quoteName('a.commission_amount'),
            $db->quoteName('a.commission_paid'),
            $db->quoteName('a.status'),
            $db->quoteName('a.partner_status'),
            $db->quoteName('a.hotel_notified_at'),
            $db->quoteName('a.created'),
            $db->quoteName('r.name', 'room_name'),
            $db->quoteName('d.name', 'destination_name'),
        ];

        $workflowEnabled = (bool) ComponentHelper::getParams('com_hotelbooking')->get('workflow_enabled');

        if ($workflowEnabled) {
            $select[] = $db->quoteName('wa.stage_id', 'stage_id');
            $select[] = $db->quoteName('ws.title', 'stage_title');
            $select[] = $db->quoteName('ws.workflow_id', 'workflow_id');
            $select[] = $db->quoteName('w.title', 'workflow_title');
        }

        $query->select($this->getState('list.select', $select))
            ->from($db->quoteName('#__hotelbooking_bookings', 'a'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_rooms', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('a.room_id'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('r.destination_id'));

        if ($workflowEnabled) {
            $extension = BookingWorkflowHelper::EXTENSION;
            $query->join(
                'LEFT',
                $db->quoteName('#__workflow_associations', 'wa'),
                $db->quoteName('wa.item_id') . ' = ' . $db->quoteName('a.id')
                . ' AND ' . $db->quoteName('wa.extension') . ' = :waExtension',
            )
                ->join('LEFT', $db->quoteName('#__workflow_stages', 'ws'), $db->quoteName('ws.id') . ' = ' . $db->quoteName('wa.stage_id'))
                ->join('LEFT', $db->quoteName('#__workflows', 'w'), $db->quoteName('w.id') . ' = ' . $db->quoteName('ws.workflow_id'))
                ->bind(':waExtension', $extension);

            $stage = $this->getState('filter.stage');

            if (is_numeric($stage)) {
                $stageId = (int) $stage;
                $query->where($db->quoteName('wa.stage_id') . ' = :stageId')
                    ->bind(':stageId', $stageId, ParameterType::INTEGER);
            }
        }

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . str_replace(' ', '%', trim($search)) . '%';
            $query->where('(' . $db->quoteName('a.guest_name') . ' LIKE :search1 OR ' . $db->quoteName('a.guest_email') . ' LIKE :search2)')
                ->bind(':search1', $search)
                ->bind(':search2', $search);
        }

        $status = $this->getState('filter.status');

        if (!empty($status)) {
            $query->where($db->quoteName('a.status') . ' = :status')
                ->bind(':status', $status);
        }

        $partnerStatus = $this->getState('filter.partner_status');

        if (!empty($partnerStatus)) {
            $query->where($db->quoteName('a.partner_status') . ' = :partnerStatus')
                ->bind(':partnerStatus', $partnerStatus);
        }

        $user = $this->getCurrentUser();

        if (!AccessHelper::isPrivileged($user)) {
            $ids = AccessHelper::editableDestinationIds($user, $db);

            if ($ids === []) {
                $query->where('0 = 1');
            } else {
                $query->whereIn($db->quoteName('r.destination_id'), $ids);
            }
        }

        $orderCol  = $this->state->get('list.ordering', 'a.created');
        $orderDirn = $this->state->get('list.direction', 'DESC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * @return list<array<string, mixed>>|false
     */
    public function getTransitions()
    {
        $store = $this->getStoreId('getTransitions');

        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        $this->cache[$store] = [];

        if (!ComponentHelper::getParams('com_hotelbooking')->get('workflow_enabled')) {
            return $this->cache[$store];
        }

        $items = $this->getItems();

        if ($items === false) {
            return false;
        }

        $stageIds    = array_values(array_unique(array_filter(ArrayHelper::toInteger(ArrayHelper::getColumn($items, 'stage_id')))));
        $workflowIds = array_values(array_unique(array_filter(ArrayHelper::toInteger(ArrayHelper::getColumn($items, 'workflow_id')))));

        if ($stageIds === [] && $workflowIds === []) {
            return $this->cache[$store];
        }

        $db   = $this->getDatabase();
        $user = $this->getCurrentUser();

        try {
            Factory::getLanguage()->load('com_workflow', JPATH_ADMINISTRATOR);

            $query = $db->createQuery()
                ->select([
                    $db->quoteName('t.id', 'value'),
                    $db->quoteName('t.title', 'text'),
                    $db->quoteName('t.from_stage_id'),
                    $db->quoteName('t.to_stage_id'),
                    $db->quoteName('t.workflow_id'),
                ])
                ->from($db->quoteName('#__workflow_transitions', 't'))
                ->innerJoin(
                    $db->quoteName('#__workflow_stages', 's'),
                    $db->quoteName('t.to_stage_id') . ' = ' . $db->quoteName('s.id'),
                )
                ->where([
                    $db->quoteName('t.published') . ' = 1',
                    $db->quoteName('s.published') . ' = 1',
                ])
                ->order($db->quoteName('t.ordering'));

            $where = [];

            if ($stageIds !== []) {
                $where[] = $db->quoteName('t.from_stage_id') . ' IN (' . implode(',', $query->bindArray($stageIds)) . ')';
            }

            if ($workflowIds !== []) {
                $where[] = '(' . $db->quoteName('t.from_stage_id') . ' = -1 AND ' . $db->quoteName('t.workflow_id')
                    . ' IN (' . implode(',', $query->bindArray($workflowIds)) . '))';
            }

            $query->where('((' . implode(') OR (', $where) . '))');

            $transitions = $db->setQuery($query)->loadAssocList() ?: [];

            foreach ($transitions as $key => $transition) {
                if (!$user->authorise('core.execute.transition', 'com_hotelbooking.transition.' . (int) $transition['value'])) {
                    unset($transitions[$key]);

                    continue;
                }

                $transitions[$key]['text'] = Text::_($transition['text']);
            }

            $this->cache[$store] = array_values($transitions);
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());

            return false;
        }

        return $this->cache[$store];
    }
}
