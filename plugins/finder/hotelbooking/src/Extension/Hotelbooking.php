<?php

namespace Learn\Plugin\Finder\Hotelbooking\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

final class Hotelbooking extends Adapter implements SubscriberInterface
{
    use DatabaseAwareTrait;

    public const ROOM_ID_OFFSET = 1000000;

    protected $context = 'Hotelbooking';

    protected $extension = 'com_hotelbooking';

    protected $layout = 'destination';

    protected $type_title = 'Hotel Booking';

    protected $table = '#__hotelbooking_destinations';

    protected $state_field = 'published';

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return array_merge(parent::getSubscribedEvents(), [
            'onFinderAfterDelete' => 'onFinderAfterDelete',
            'onFinderAfterSave'   => 'onFinderAfterSave',
            'onFinderBeforeSave'  => 'onFinderBeforeSave',
            'onFinderChangeState' => 'onFinderChangeState',
        ]);
    }

    public function onFinderAfterDelete(FinderEvent\AfterDeleteEvent $event): void
    {
        $context = $event->getContext();
        $table   = $event->getItem();

        if ($context === 'com_hotelbooking.destination') {
            $this->remove((string) (int) $table->id);
        } elseif ($context === 'com_hotelbooking.room') {
            $this->remove((string) ((int) $table->id + self::ROOM_ID_OFFSET));
        } elseif ($context === 'com_finder.index') {
            $this->remove((string) (int) $table->link_id);
        }
    }

    public function onFinderAfterSave(FinderEvent\AfterSaveEvent $event): void
    {
        $context = $event->getContext();
        $row     = $event->getItem();

        if ($context === 'com_hotelbooking.destination') {
            $this->reindex((int) $row->id);
        } elseif ($context === 'com_hotelbooking.room') {
            $this->reindex((int) $row->id + self::ROOM_ID_OFFSET);
        }
    }

    public function onFinderBeforeSave(FinderEvent\BeforeSaveEvent $event): void
    {
        // Destinations and rooms have no access column to snapshot.
    }

    public function onFinderChangeState(FinderEvent\AfterChangeStateEvent $event): void
    {
        $context = $event->getContext();
        $pks     = $event->getPks();
        $value   = $event->getValue();

        if ($context === 'com_hotelbooking.destination') {
            $this->itemStateChange($pks, $value);
        } elseif ($context === 'com_hotelbooking.room') {
            $ids = [];

            foreach ($pks as $pk) {
                $ids[] = (int) $pk + self::ROOM_ID_OFFSET;
            }

            $this->itemStateChange($ids, $value);
        }

        if ($context === 'com_plugins.plugin' && $value === 0) {
            $this->pluginDisable($pks);
        }
    }

    protected function index(Result $item)
    {
        if (ComponentHelper::isEnabled($this->extension) === false) {
            return true;
        }

        $item->setLanguage();

        $kind   = (string) ($item->getElement('item_kind') ?? 'destination');
        $view   = $kind === 'room' ? 'room' : 'destination';
        $realId = $kind === 'room'
            ? ((int) $item->getElement('id') - self::ROOM_ID_OFFSET)
            : (int) $item->getElement('id');

        $item->url    = $this->getUrl($realId, $this->extension, $view);
        $item->route  = $item->url;
        $item->setElement('layout', $view);

        $image = $item->getElement('image');

        if (!empty($image)) {
            $item->setElement('imageUrl', $image);
            $item->setElement('imageAlt', $item->title);
        }

        $item->addTaxonomy('Type', $kind === 'room' ? 'Room' : 'Destination');
        $item->addTaxonomy('Language', $item->language);

        $destinationName = $item->getElement('destination_name');

        if ($kind === 'room' && !empty($destinationName)) {
            $item->addTaxonomy('Destination', $destinationName);
        }

        Helper::getContentExtras($item);

        $this->indexer->index($item);

        return true;
    }

    protected function setup()
    {
        return true;
    }

    protected function getListQuery($query = null)
    {
        $db = $this->getDatabase();

        if ($query instanceof QueryInterface) {
            return $query;
        }

        $dummyDate = '1970-01-01 00:00:00';

        $destinations = $db->createQuery()
            ->select([
                $db->quoteName('d.id', 'id'),
                $db->quoteName('d.name', 'title'),
                $db->quoteName('d.alias', 'alias'),
                $db->quoteName('d.description', 'summary'),
                $db->quoteName('d.image', 'image'),
                $db->quoteName('d.published', 'state'),
                $db->quoteName('d.language', 'language'),
                $db->quote('destination') . ' AS ' . $db->quoteName('item_kind'),
                '1 AS ' . $db->quoteName('access'),
                '1 AS ' . $db->quoteName('cat_state'),
                '1 AS ' . $db->quoteName('cat_access'),
                $db->quote($dummyDate) . ' AS ' . $db->quoteName('start_date'),
                'CAST(NULL AS CHAR(255)) AS ' . $db->quoteName('destination_name'),
            ])
            ->from($db->quoteName('#__hotelbooking_destinations', 'd'));

        $rooms = $db->createQuery()
            ->select([
                '(' . $db->quoteName('r.id') . ' + ' . self::ROOM_ID_OFFSET . ') AS ' . $db->quoteName('id'),
                $db->quoteName('r.name', 'title'),
                $db->quoteName('r.alias', 'alias'),
                $db->quoteName('r.description', 'summary'),
                $db->quoteName('r.image', 'image'),
                $db->quoteName('r.published', 'state'),
                $db->quoteName('r.language', 'language'),
                $db->quote('room') . ' AS ' . $db->quoteName('item_kind'),
                '1 AS ' . $db->quoteName('access'),
                '1 AS ' . $db->quoteName('cat_state'),
                '1 AS ' . $db->quoteName('cat_access'),
                $db->quote($dummyDate) . ' AS ' . $db->quoteName('start_date'),
                $db->quoteName('d.name', 'destination_name'),
            ])
            ->from($db->quoteName('#__hotelbooking_rooms', 'r'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('r.destination_id'));

        $inner = $destinations->__toString() . ' UNION ALL ' . $rooms->__toString();

        return $db->createQuery()
            ->select('a.*')
            ->from('(' . $inner . ') AS a');
    }

    protected function getStateQuery()
    {
        return $this->getListQuery();
    }

    protected function getUrl($id, $extension, $view)
    {
        $id = (int) $id;

        if ($id >= self::ROOM_ID_OFFSET) {
            return 'index.php?option=' . $extension . '&view=room&id=' . ($id - self::ROOM_ID_OFFSET);
        }

        if ($view === 'room') {
            return 'index.php?option=' . $extension . '&view=room&id=' . $id;
        }

        return 'index.php?option=' . $extension . '&view=destination&id=' . $id;
    }

    protected function getUpdateQueryByTime($time)
    {
        return $this->db->createQuery()->where('0 = 1');
    }
}
