<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Learn\Component\Hotelbooking\Site\Helper\SubformHelper;

\defined('_JEXEC') or die;

/**
 * Read-only aggregate of everything the editor "Insert Hotel Booking Snippet"
 * modal (tmpl/snippets/modal.php) lets an author pick from: destinations, rooms,
 * and the offers/coupons nested inside each.
 */
class SnippetsModel extends BaseDatabaseModel
{
    public function getDestinations(): array
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'name']))
            ->from($db->quoteName('#__hotelbooking_destinations'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('name') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    public function getRooms(): array
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('r.id, r.name, r.price, d.name AS destination_name')
            ->from($db->quoteName('#__hotelbooking_rooms', 'r'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('r.destination_id'))
            ->where($db->quoteName('r.published') . ' = 1')
            ->order($db->quoteName('r.name') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    public function getOffers(): array
    {
        $offers = [];

        foreach (['destination', 'room'] as $entity) {
            $table = $entity === 'room' ? '#__hotelbooking_rooms' : '#__hotelbooking_destinations';

            $db    = $this->getDatabase();
            $query = $db->createQuery()
                ->select($db->quoteName(['id', 'name', 'offers']))
                ->from($db->quoteName($table))
                ->where($db->quoteName('published') . ' = 1')
                ->where($db->quoteName('offers') . ' IS NOT NULL')
                ->order($db->quoteName('name') . ' ASC');
            $db->setQuery($query);

            foreach ($db->loadObjectList() ?: [] as $row) {
                $rows = SubformHelper::decodeRows($row->offers, 'offer_item');

                foreach ($rows as $index => $offer) {
                    if (empty($offer['title'])) {
                        continue;
                    }

                    $offers[] = (object) [
                        'entity'      => $entity,
                        'id'          => (int) $row->id,
                        'index'       => $index,
                        'parent_name' => $row->name,
                        'title'       => $offer['title'],
                        'discount'    => $offer['discount'] ?? '',
                    ];
                }
            }
        }

        return $offers;
    }
}
