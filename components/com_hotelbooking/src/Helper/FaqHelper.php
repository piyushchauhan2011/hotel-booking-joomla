<?php

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class FaqHelper
{
    public static function getPublished(DatabaseInterface $db, string $scope, ?string $language = null): array
    {
        $query = $db->createQuery()
            ->select([$db->quoteName('question'), $db->quoteName('answer')])
            ->from($db->quoteName('#__hotelbooking_faqs'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('scope') . ' = :scope')
            ->bind(':scope', $scope, ParameterType::STRING)
            ->order($db->quoteName('ordering') . ' ASC');

        if ($language) {
            $query->whereIn($db->quoteName('language'), [$language, '*'], ParameterType::STRING);
        }

        $db->setQuery($query);

        return $db->loadAssocList() ?: [];
    }
}
