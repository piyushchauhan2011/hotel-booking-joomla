<?php

declare(strict_types=1);

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RouterSegmentHelperTest extends TestCase
{
    /**
     * @return DatabaseInterface&MockObject
     */
    private function databaseReturning(mixed $result): DatabaseInterface
    {
        $query = $this->createMock(QueryInterface::class);
        $query->method('select')->willReturnSelf();
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('whereIn')->willReturnSelf();
        $query->method('order')->willReturnSelf();
        $query->method('bind')->willReturnCallback(
            static function ($key, &$value) use ($query): QueryInterface {
                unset($key, $value);

                return $query;
            }
        );

        $db = $this->createMock(DatabaseInterface::class);
        $db->method('createQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(static fn (string $name): string => $name);
        $db->method('quote')->willReturnCallback(static fn (string $text): string => "'" . $text . "'");
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willReturn($result);

        return $db;
    }

    public function testSlugFromAliasUsesUrlSafeAlias(): void
    {
        $this->assertSame('tokyo-house', RouterSegmentHelper::slugFromAlias('Tokyo House', 9));
    }

    public function testSlugFromAliasFallsBackToId(): void
    {
        $this->assertSame('9', RouterSegmentHelper::slugFromAlias('', 9));
        $this->assertSame('9', RouterSegmentHelper::slugFromAlias(null, 9));
    }

    public function testIdFromLookupPrefersDatabaseId(): void
    {
        $this->assertSame(12, RouterSegmentHelper::idFromLookup(12, 'tokyo-house'));
    }

    public function testIdFromLookupFallsBackToNumericSegment(): void
    {
        $this->assertSame(12, RouterSegmentHelper::idFromLookup(null, '12'));
        $this->assertSame(0, RouterSegmentHelper::idFromLookup(0, 'tokyo-house'));
    }

    public function testBuildSegmentReturnsIdWhenNotPositive(): void
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->expects($this->never())->method('createQuery');

        $this->assertSame('0', RouterSegmentHelper::buildSegment($db, '#__hotelbooking_destinations', 0));
        $this->assertSame('-1', RouterSegmentHelper::buildSegment($db, '#__hotelbooking_destinations', -1));
    }

    public function testBuildSegmentUsesAliasFromDatabase(): void
    {
        $this->assertSame(
            'tokyo-house',
            RouterSegmentHelper::buildSegment(
                $this->databaseReturning('Tokyo House'),
                '#__hotelbooking_destinations',
                9
            )
        );
    }

    public function testBuildSegmentFallsBackWhenAliasIsEmpty(): void
    {
        $this->assertSame(
            '9',
            RouterSegmentHelper::buildSegment(
                $this->databaseReturning(''),
                '#__hotelbooking_destinations',
                9
            )
        );
    }

    public function testGetIdFromSegmentUsesLookupId(): void
    {
        $this->assertSame(
            12,
            RouterSegmentHelper::getIdFromSegment(
                $this->databaseReturning(12),
                '#__hotelbooking_destinations',
                'tokyo-house',
                'en-GB'
            )
        );
    }

    public function testGetIdFromSegmentFallsBackWhenLookupMisses(): void
    {
        $this->assertSame(
            12,
            RouterSegmentHelper::getIdFromSegment(
                $this->databaseReturning(null),
                '#__hotelbooking_destinations',
                '12',
                'en-GB'
            )
        );
    }
}
