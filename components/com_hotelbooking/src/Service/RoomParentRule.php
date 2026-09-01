<?php

namespace Learn\Component\Hotelbooking\Site\Service;

use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\RulesInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Backfills destination_id when a room link only passes id= (e.g. Route::_() calls
 * in tmpl/room/default.php). Joomla core's PreprocessRules does the same job but
 * expects the parent key to be resolvable directly from the segment; here the parent
 * (destination_id) has to be looked up by the room's own id first.
 */
class RoomParentRule implements RulesInterface
{
    public function __construct(private RouterViewConfiguration $view, private DatabaseInterface $db)
    {
    }

    public function preprocess(&$query)
    {
        if (!isset($query['view']) || $query['view'] !== $this->view->name) {
            return;
        }

        $key       = $this->view->key;
        $parentKey = $this->view->parent_key;

        if (!isset($query[$key]) || !$parentKey || isset($query[$parentKey])) {
            return;
        }

        $id = (int) $query[$key];

        $dbQuery = $this->db->createQuery()
            ->select($this->db->quoteName($parentKey))
            ->from($this->db->quoteName('#__hotelbooking_rooms'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $value = $this->db->setQuery($dbQuery)->loadResult();

        if ($value !== null) {
            $query[$parentKey] = $value;
        }
    }

    public function parse(&$segments, &$vars)
    {
    }

    public function build(&$query, &$segments)
    {
    }
}
