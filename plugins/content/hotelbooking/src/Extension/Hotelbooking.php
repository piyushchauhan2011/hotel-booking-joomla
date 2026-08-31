<?php

namespace Learn\Plugin\Content\Hotelbooking\Extension;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Learn\Component\Hotelbooking\Site\Helper\SubformHelper;

\defined('_JEXEC') or die;

/**
 * Renders {hotelbooking ...} tags dropped into article text into room, destination,
 * and offer/coupon promo cards. Companion to plg_editors-xtd_hotelbooking, which lets
 * authors insert the tags from a toolbar button instead of typing them by hand.
 */
final class Hotelbooking extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    private const TAG_REGEX = '/{hotelbooking\s+([^}]*)}/i';

    public static function getSubscribedEvents(): array
    {
        return ['onContentPrepare' => 'onContentPrepare'];
    }

    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        $article = $event->getItem();

        if (!\is_object($article) || !property_exists($article, 'text') || $article->text === null) {
            return;
        }

        if (!str_contains($article->text, '{hotelbooking ')) {
            return;
        }

        // Strip the tags entirely rather than resolving them while indexing for search.
        if ($event->getContext() === 'com_finder.indexer') {
            $article->text = preg_replace(self::TAG_REGEX, '', $article->text);

            return;
        }

        $this->loadLanguage();

        $app = Factory::getApplication();
        $app->getLanguage()->load('com_hotelbooking', JPATH_SITE . '/components/com_hotelbooking');

        // The component's own views enqueue this per-view; a snippet dropped into
        // unrelated content (e.g. a com_content article) needs it enqueued explicitly,
        // and com_hotelbooking's asset registry file isn't auto-loaded outside its own pages.
        if ($app->isClient('site')) {
            $wa = $app->getDocument()->getWebAssetManager();
            $wa->getRegistry()->addExtensionRegistryFile('com_hotelbooking');
            $wa->useStyle('com_hotelbooking.site');
        }

        $article->text = preg_replace_callback(
            self::TAG_REGEX,
            fn (array $match) => $this->renderSnippet($this->parseAttributes($match[1])),
            $article->text
        );
    }

    private function parseAttributes(string $raw): array
    {
        $attributes = [];

        if (preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[strtolower($match[1])] = $match[2];
            }
        }

        return $attributes;
    }

    private function renderSnippet(array $attributes): string
    {
        $type = strtolower($attributes['type'] ?? '');
        $id   = (int) ($attributes['id'] ?? 0);

        if ($id < 1) {
            return '';
        }

        return match ($type) {
            'room'        => $this->renderRoom($id),
            'destination' => $this->renderDestination($id),
            'offer'       => $this->renderOffer(strtolower($attributes['entity'] ?? ''), $id, (int) ($attributes['index'] ?? -1)),
            default       => '',
        };
    }

    private function renderRoom(int $id): string
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('r.*, d.name AS destination_name')
            ->from($db->quoteName('#__hotelbooking_rooms', 'r'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('r.destination_id'))
            ->where($db->quoteName('r.published') . ' = 1')
            ->where($db->quoteName('r.id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        $room = $db->loadObject();

        if (!$room) {
            return '';
        }

        return LayoutHelper::render('snippets.room', ['room' => $room], JPATH_ROOT . '/components/com_hotelbooking/layouts');
    }

    private function renderDestination(int $id): string
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_destinations'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        $destination = $db->loadObject();

        if (!$destination) {
            return '';
        }

        return LayoutHelper::render('snippets.destination', ['destination' => $destination], JPATH_ROOT . '/components/com_hotelbooking/layouts');
    }

    private function renderOffer(string $entity, int $id, int $index): string
    {
        if ($index < 0 || !\in_array($entity, ['room', 'destination'], true)) {
            return '';
        }

        $table = $entity === 'room' ? '#__hotelbooking_rooms' : '#__hotelbooking_destinations';
        $view  = $entity === 'room' ? 'room' : 'destination';

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select([$db->quoteName('id'), $db->quoteName('name'), $db->quoteName('offers')])
            ->from($db->quoteName($table))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        $row = $db->loadObject();

        if (!$row) {
            return '';
        }

        $offers = SubformHelper::decodeRows($row->offers, 'offer_item');

        if (!isset($offers[$index]) || empty($offers[$index]['title'])) {
            return '';
        }

        return LayoutHelper::render('snippets.offer', [
            'offer'      => $offers[$index],
            'parentName' => $row->name,
            'link'       => Route::_('index.php?option=com_hotelbooking&view=' . $view . '&id=' . $id),
        ], JPATH_ROOT . '/components/com_hotelbooking/layouts');
    }
}
