<?php

namespace Learn\Component\Hotelbooking\Site\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class HomeModel extends BaseDatabaseModel
{
    public function getFeaturedDestinations(int $limit = 6): array
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_destinations'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC')
            ->setLimit($limit);

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }
}
