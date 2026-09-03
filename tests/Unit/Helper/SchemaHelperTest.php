<?php

declare(strict_types=1);

namespace Learn\Component\Hotelbooking\Site\Helper;

use PHPUnit\Framework\TestCase;

final class SchemaHelperTest extends TestCase
{
    public function testForDestinationBuildsLodgingBusiness(): void
    {
        $item = (object) [
            'name'        => 'Tokyo House',
            'description' => '<p>Quiet rooms near the station.</p>',
            'image'       => 'https://cdn.example/tokyo.jpg',
            'amenities'   => 'wifi,pool',
        ];

        $schema = SchemaHelper::forDestination($item, 'https://example.test/tokyo');

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('LodgingBusiness', $schema['@type']);
        $this->assertSame('Tokyo House', $schema['name']);
        $this->assertSame('Quiet rooms near the station.', $schema['description']);
        $this->assertSame('https://example.test/tokyo', $schema['url']);
        $this->assertSame('https://cdn.example/tokyo.jpg', $schema['image']);
        $this->assertCount(2, $schema['amenityFeature']);
        $this->assertSame('LocationFeatureSpecification', $schema['amenityFeature'][0]['@type']);
        $this->assertTrue($schema['amenityFeature'][0]['value']);
    }

    public function testForRoomBuildsProductOffer(): void
    {
        $item = (object) [
            'name'        => 'Deluxe King',
            'description' => 'A large room',
            'image'       => 'https://cdn.example/room.jpg',
            'price'       => 120.5,
            'amenities'   => ['wifi'],
        ];

        $schema = SchemaHelper::forRoom($item, 'https://example.test/room/1');

        $this->assertSame('Product', $schema['@type']);
        $this->assertSame('Deluxe King', $schema['name']);
        $this->assertSame('https://example.test/room/1', $schema['url']);
        $this->assertSame('https://cdn.example/room.jpg', $schema['image']);
        $this->assertSame('120.50', $schema['offers']['price']);
        $this->assertSame('USD', $schema['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $schema['offers']['availability']);
        $this->assertSame('https://example.test/room/1', $schema['offers']['url']);
    }

    public function testGraphNodeStripsContext(): void
    {
        $node = SchemaHelper::graphNode([
            '@context' => 'https://schema.org',
            '@type'    => 'LodgingBusiness',
            'name'     => 'Bali',
        ]);

        $this->assertArrayNotHasKey('@context', $node);
        $this->assertSame('LodgingBusiness', $node['@type']);
    }

    public function testNormaliseAmenitiesAcceptsStringAndArray(): void
    {
        $this->assertSame(['wifi', 'pool'], SchemaHelper::normaliseAmenities('wifi,pool'));
        $this->assertSame(['wifi'], SchemaHelper::normaliseAmenities([' wifi ', '']));
        $this->assertSame([], SchemaHelper::normaliseAmenities(null));
    }
}
