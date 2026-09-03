<?php

namespace Learn\Component\Hotelbooking\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Learn\Component\Hotelbooking\Site\Helper\RouterSegmentHelper;

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
        return RouterSegmentHelper::buildSegment($this->db, $table, $id);
    }

    private function getIdFromSegment(string $table, string $segment): int
    {
        return RouterSegmentHelper::getIdFromSegment(
            $this->db,
            $table,
            $segment,
            $this->app->getLanguage()->getTag(),
        );
    }
}
