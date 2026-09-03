<?php

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

/**
 * Builds schema.org JSON-LD graphs for Destination and Room pages.
 * The lodging Schema.org plugin and the system schemaorg plugin own page
 * emission; this helper is the shared graph builder.
 */
class SchemaHelper
{
    public static function forDestination(object $item, ?string $url = null): array
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'LodgingBusiness',
            'name'        => $item->name,
            'description' => trim(strip_tags((string) ($item->description ?? ''))),
            'url'         => $url ?? self::currentUrl(),
        ];

        if (!empty($item->image)) {
            $schema['image'] = self::absoluteUrl((string) $item->image);
        }

        $amenities = self::amenityFeatures(self::normaliseAmenities($item->amenities ?? []));

        if ($amenities) {
            $schema['amenityFeature'] = $amenities;
        }

        return $schema;
    }

    public static function forRoom(object $item, ?string $url = null): array
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $item->name,
            'description' => trim(strip_tags((string) ($item->description ?? ''))),
            'url'         => $url ?? self::currentUrl(),
        ];

        if (!empty($item->image)) {
            $schema['image'] = self::absoluteUrl((string) $item->image);
        }

        if (isset($item->price)) {
            $pageUrl = $url ?? self::currentUrl();
            $schema['offers'] = [
                '@type'         => 'Offer',
                'price'         => number_format((float) $item->price, 2, '.', ''),
                'priceCurrency' => 'USD',
                'availability'  => 'https://schema.org/InStock',
                'url'           => $pageUrl,
            ];
        }

        $amenities = self::amenityFeatures(self::normaliseAmenities($item->amenities ?? []));

        if ($amenities) {
            $schema['amenityFeature'] = $amenities;
        }

        return $schema;
    }

    public static function graphNode(array $schema): array
    {
        unset($schema['@context']);

        return $schema;
    }

    /**
     * @param  mixed  $amenities
     *
     * @return list<string>
     */
    public static function normaliseAmenities(mixed $amenities): array
    {
        if (\is_string($amenities)) {
            $amenities = $amenities === '' ? [] : explode(',', $amenities);
        }

        if (!\is_array($amenities)) {
            return [];
        }

        $values = [];

        foreach ($amenities as $amenity) {
            $amenity = trim((string) $amenity);

            if ($amenity !== '') {
                $values[] = $amenity;
            }
        }

        return $values;
    }

    /**
     * @param  list<string>  $amenities
     *
     * @return list<array<string, mixed>>
     */
    private static function amenityFeatures(array $amenities): array
    {
        $features = [];

        foreach ($amenities as $amenity) {
            $features[] = [
                '@type' => 'LocationFeatureSpecification',
                'name'  => self::amenityLabel($amenity),
                'value' => true,
            ];
        }

        return $features;
    }

    private static function amenityLabel(string $amenity): string
    {
        $key = 'COM_HOTELBOOKING_AMENITY_' . strtoupper($amenity);

        try {
            $label = Text::_($key);
        } catch (\Throwable) {
            return $amenity;
        }

        return $label !== '' ? $label : $amenity;
    }

    private static function currentUrl(): string
    {
        try {
            return Uri::current();
        } catch (\Throwable) {
            return '';
        }
    }

    private static function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        try {
            $root = rtrim(Uri::root(), '/');
        } catch (\Throwable) {
            $root = '';
        }

        return $root . '/' . ltrim($path, '/');
    }
}
