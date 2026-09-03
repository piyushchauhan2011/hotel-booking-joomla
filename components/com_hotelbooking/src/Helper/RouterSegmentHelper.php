<?php

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class RouterSegmentHelper
{
    public static function slugFromAlias(?string $alias, int $id): string
    {
        $slug = $alias !== null && $alias !== '' ? OutputFilter::stringURLSafe($alias) : '';

        return $slug !== '' ? $slug : (string) $id;
    }

    public static function idFromLookup(?int $id, string $segment): int
    {
        return ($id ?? 0) > 0 ? (int) $id : (int) $segment;
    }

    public static function buildSegment(DatabaseInterface $db, string $table, int $id): string
    {
        if ($id <= 0) {
            return (string) $id;
        }

        $query = $db->createQuery()
            ->select($db->quoteName('alias'))
            ->from($db->quoteName($table))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $db->setQuery($query);

        return self::slugFromAlias((string) $db->loadResult(), $id);
    }

    public static function getIdFromSegment(DatabaseInterface $db, string $table, string $segment, string $language): int
    {
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName($table))
            ->where($db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $segment, ParameterType::STRING)
            ->whereIn($db->quoteName('language'), [$language, '*'], ParameterType::STRING)
            ->order('CASE ' . $db->quoteName('language') . ' WHEN ' . $db->quote($language) . ' THEN 0 ELSE 1 END');

        $db->setQuery($query, 0, 1);
        $result = $db->loadResult();

        return self::idFromLookup($result !== null && $result !== false ? (int) $result : null, $segment);
    }
}
