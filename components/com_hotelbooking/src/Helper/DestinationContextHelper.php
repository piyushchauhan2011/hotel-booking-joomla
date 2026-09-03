<?php

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Resolves the hotel (destination) shown on a Hotel Landing page from the URL,
 * with an optional module-parameter fallback.
 */
class DestinationContextHelper
{
    public static function getDestinationId(Registry $params, CMSApplicationInterface $app): int
    {
        if ((int) $params->get('use_url_hotel', 1) === 1) {
            $input = $app->getInput();

            if ($input->getCmd('option') === 'com_hotelbooking' && $input->getCmd('view') === 'hotel') {
                $id = $input->getInt('id', 0);

                if ($id > 0) {
                    return $id;
                }
            }
        }

        return (int) $params->get('destination_id', 0);
    }

    public static function getDestination(Registry $params, CMSApplicationInterface $app): ?object
    {
        $id = self::getDestinationId($params, $app);

        if ($id <= 0) {
            return null;
        }

        $db    = Factory::getDbo();
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_destinations'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        if (Multilanguage::isEnabled()) {
            $query->whereIn(
                $db->quoteName('language'),
                [$app->getLanguage()->getTag(), '*'],
                ParameterType::STRING,
            );
        }

        $db->setQuery($query, 0, 1);
        $item = $db->loadObject();

        if (!$item) {
            return null;
        }

        $item->amenities = $item->amenities ? array_values(array_filter(explode(',', (string) $item->amenities))) : [];
        $item->offers    = SubformHelper::decodeRows($item->offers ?? null, 'offer_item');

        return $item;
    }

    public static function imageUrl(?string $image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return '';
        }

        $cleaned = HTMLHelper::_('cleanImageURL', $image);
        $image   = $cleaned->url ?? $image;

        if ($image !== '' && !preg_match('#^(https?:)?//#i', $image)) {
            $image = Uri::root(true) . '/' . ltrim($image, '/');
        }

        return $image;
    }

    public static function plainExcerpt(?string $html, int $max = 180): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));

        if ($text === '') {
            return '';
        }

        if (\function_exists('mb_strimwidth')) {
            return mb_strimwidth($text, 0, $max, '...');
        }

        return strlen($text) > $max ? substr($text, 0, $max - 3) . '...' : $text;
    }
}
