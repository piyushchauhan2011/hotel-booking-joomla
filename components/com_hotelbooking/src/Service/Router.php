<?php

namespace Learn\Component\Hotelbooking\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class Router extends RouterView
{
    private DatabaseInterface $db;

    public function __construct(
        SiteApplication $app,
        AbstractMenu $menu,
        ?CategoryFactoryInterface $categoryFactory,
        DatabaseInterface $db
    ) {
        $this->db = $db;

        $this->registerView(new RouterViewConfiguration('home'));

        $destinations = new RouterViewConfiguration('destinations');
        $this->registerView($destinations);

        $destination = new RouterViewConfiguration('destination');
        $destination->setKey('id')->setParent($destinations);
        $this->registerView($destination);

        $room = new RouterViewConfiguration('room');
        $room->setKey('id')->setParent($destination, 'destination_id');
        $this->registerView($room);

        $this->registerView(new RouterViewConfiguration('bookings'));
        $this->registerView(new RouterViewConfiguration('faqs'));

        parent::__construct($app, $menu);

        $this->attachRule(new RoomParentRule($room, $this->db));
        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    public function getDestinationSegment($id, $query)
    {
        return [(int) $id => $this->buildSegment('#__hotelbooking_destinations', 'alias', (int) $id)];
    }

    public function getDestinationId($segment, $query)
    {
        return (int) $segment;
    }

    public function getRoomSegment($id, $query)
    {
        return [(int) $id => $this->buildSegment('#__hotelbooking_rooms', 'name', (int) $id)];
    }

    public function getRoomId($segment, $query)
    {
        return (int) $segment;
    }

    private function buildSegment(string $table, string $column, int $id): string
    {
        if ($id <= 0) {
            return (string) $id;
        }

        $query = $this->db->createQuery()
            ->select($this->db->quoteName($column))
            ->from($this->db->quoteName($table))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->db->setQuery($query);

        $value = (string) $this->db->loadResult();
        $slug  = $value !== '' ? OutputFilter::stringURLSafe($value) : '';

        return $slug !== '' ? $id . '-' . $slug : (string) $id;
    }
}
