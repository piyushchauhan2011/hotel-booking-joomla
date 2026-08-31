<?php

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class FaqHelper
{
    public static function getPublished(DatabaseInterface $db, string $scope): array
    {
        $query = $db->createQuery()
            ->select([$db->quoteName('question'), $db->quoteName('answer')])
            ->from($db->quoteName('#__hotelbooking_faqs'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('scope') . ' = :scope')
            ->bind(':scope', $scope, ParameterType::STRING)
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadAssocList() ?: [];
    }
}
