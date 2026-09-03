<?php

namespace Learn\Module\Hotelrooms\Site\Helper;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Learn\Component\Hotelbooking\Site\Helper\DestinationContextHelper;

\defined('_JEXEC') or die;

class HotelroomsHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    public function getRooms(Registry $params, CMSApplicationInterface $app): array
    {
        $destinationId = DestinationContextHelper::getDestinationId($params, $app);
        $limit         = max(1, (int) $params->get('count', 6));

        if ($destinationId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_rooms'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('destination_id') . ' = :destinationId')
            ->bind(':destinationId', $destinationId, ParameterType::INTEGER)
            ->order($db->quoteName('ordering') . ' ASC')
            ->setLimit($limit);

        if (Multilanguage::isEnabled()) {
            $query->whereIn(
                $db->quoteName('language'),
                [$app->getLanguage()->getTag(), '*'],
                ParameterType::STRING
            );
        }

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }
}
