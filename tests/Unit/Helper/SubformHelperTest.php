<?php

declare(strict_types=1);

namespace Learn\Component\Hotelbooking\Site\Helper;

use PHPUnit\Framework\TestCase;

final class SubformHelperTest extends TestCase
{
    public function testNullJsonReturnsEmptyList(): void
    {
        $this->assertSame([], SubformHelper::decodeRows(null, 'gallery_item'));
    }

    public function testEmptyJsonReturnsEmptyList(): void
    {
        $this->assertSame([], SubformHelper::decodeRows('', 'gallery_item'));
    }

    public function testInvalidJsonReturnsEmptyList(): void
    {
        $this->assertSame([], SubformHelper::decodeRows('{not-json', 'gallery_item'));
    }

    public function testJsonScalarReturnsEmptyList(): void
    {
        $this->assertSame([], SubformHelper::decodeRows('"just a string"', 'gallery_item'));
    }

    public function testDecodesNestedSubformRows(): void
    {
        $json = json_encode([
            'gallery0' => [
                'gallery_item' => [
                    'image'   => 'bali.jpg',
                    'caption' => 'Beach',
                ],
            ],
            'gallery1' => [
                'gallery_item' => [
                    'image'   => 'rome.jpg',
                    'caption' => 'Forum',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                ['image' => 'bali.jpg', 'caption' => 'Beach'],
                ['image' => 'rome.jpg', 'caption' => 'Forum'],
            ],
            SubformHelper::decodeRows($json, 'gallery_item')
        );
    }

    public function testSkipsRowsWithoutTheItemKey(): void
    {
        $json = json_encode([
            'gallery0' => [
                'gallery_item' => ['image' => 'ok.jpg'],
            ],
            'gallery1' => [
                'other' => ['image' => 'skip.jpg'],
            ],
            'gallery2' => 'not-an-array',
        ], JSON_THROW_ON_ERROR);

        $this->assertSame(
            [['image' => 'ok.jpg']],
            SubformHelper::decodeRows($json, 'gallery_item')
        );
    }
}
