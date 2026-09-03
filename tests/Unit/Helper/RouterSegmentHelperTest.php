<?php

declare(strict_types=1);

namespace Learn\Component\Hotelbooking\Site\Helper;

use PHPUnit\Framework\TestCase;

final class RouterSegmentHelperTest extends TestCase
{
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
}
