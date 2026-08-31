<?php

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

/**
 * Builds schema.org JSON-LD graphs for Destination and Room pages. The core
 * Joomla Schema plugin only covers com_content Articles, so the component's
 * own Destination/Room pages need their own structured data.
 */
class SchemaHelper
{
    public static function forDestination(object $item): array
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'LodgingBusiness',
            'name'        => $item->name,
            'description' => trim(strip_tags((string) ($item->description ?? ''))),
            'url'         => Uri::current(),
        ];

        if (!empty($item->image)) {
            $schema['image'] = self::absoluteUrl($item->image);
        }

        $amenities = self::amenityFeatures((array) ($item->amenities ?? []));

        if ($amenities) {
            $schema['amenityFeature'] = $amenities;
        }

        return $schema;
    }

    public static function forRoom(object $item): array
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $item->name,
            'description' => trim(strip_tags((string) ($item->description ?? ''))),
            'url'         => Uri::current(),
        ];

        if (!empty($item->image)) {
            $schema['image'] = self::absoluteUrl($item->image);
        }

        if (isset($item->price)) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => number_format((float) $item->price, 2, '.', ''),
                // No currency field exists in the data model; USD is a fixed placeholder.
                'priceCurrency' => 'USD',
                'availability'  => 'https://schema.org/InStock',
                'url'           => Uri::current(),
            ];
        }

        $amenities = self::amenityFeatures((array) ($item->amenities ?? []));

        if ($amenities) {
            $schema['amenityFeature'] = $amenities;
        }

        return $schema;
    }

    private static function amenityFeatures(array $amenities): array
    {
        $features = [];

        foreach ($amenities as $amenity) {
            if ($amenity === '') {
                continue;
            }

            $features[] = [
                '@type' => 'LocationFeatureSpecification',
                'name'  => Text::_('COM_HOTELBOOKING_AMENITY_' . strtoupper($amenity)),
                'value' => true,
            ];
        }

        return $features;
    }

    private static function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(Uri::root(), '/') . '/' . ltrim($path, '/');
    }
}
