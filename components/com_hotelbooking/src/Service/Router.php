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
        DatabaseInterface $db,
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

        $hotel = new RouterViewConfiguration('hotel');
        $hotel->setKey('id');
        $this->registerView($hotel);

        $this->registerView(new RouterViewConfiguration('cityguide'));

        parent::__construct($app, $menu);

        $this->attachRule(new RoomParentRule($room, $this->db));
        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    public function getDestinationSegment($id, $query)
    {
        return [(int) $id => $this->buildSegment('#__hotelbooking_destinations', (int) $id)];
    }

    public function getDestinationId($segment, $query)
    {
        return $this->getIdFromSegment('#__hotelbooking_destinations', $segment);
    }

    public function getHotelSegment($id, $query)
    {
        return $this->getDestinationSegment($id, $query);
    }

    public function getHotelId($segment, $query)
    {
        return $this->getDestinationId($segment, $query);
    }

    public function getRoomSegment($id, $query)
    {
        return [(int) $id => $this->buildSegment('#__hotelbooking_rooms', (int) $id)];
    }

    public function getRoomId($segment, $query)
    {
        return $this->getIdFromSegment('#__hotelbooking_rooms', $segment);
    }

    private function buildSegment(string $table, int $id): string
    {
        if ($id <= 0) {
            return (string) $id;
        }

        $query = $this->db->createQuery()
            ->select($this->db->quoteName('alias'))
            ->from($this->db->quoteName($table))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->db->setQuery($query);

        $value = (string) $this->db->loadResult();
        $slug  = $value !== '' ? OutputFilter::stringURLSafe($value) : '';

        return $slug !== '' ? $slug : (string) $id;
    }

    private function getIdFromSegment(string $table, string $segment): int
    {
        $language = $this->app->getLanguage()->getTag();

        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName($table))
            ->where($this->db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $segment, ParameterType::STRING)
            ->whereIn($this->db->quoteName('language'), [$language, '*'], ParameterType::STRING)
            ->order('CASE ' . $this->db->quoteName('language') . ' WHEN ' . $this->db->quote($language) . ' THEN 0 ELSE 1 END');

        $this->db->setQuery($query, 0, 1);

        $id = (int) $this->db->loadResult();

        // Alias not found for any language: fall back to treating the segment as a raw id
        // (covers legacy id-alias links and menu-item links built with a bare id).
        return $id > 0 ? $id : (int) $segment;
    }
}
