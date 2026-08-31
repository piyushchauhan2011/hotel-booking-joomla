<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

class BookingsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'guest_name', 'status', 'partner_status', 'created'];
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
                ]
            )
        )
            ->from($db->quoteName('#__hotelbooking_bookings', 'a'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_rooms', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('a.room_id'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('r.destination_id'));

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

        $orderCol  = $this->state->get('list.ordering', 'a.created');
        $orderDirn = $this->state->get('list.direction', 'DESC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
