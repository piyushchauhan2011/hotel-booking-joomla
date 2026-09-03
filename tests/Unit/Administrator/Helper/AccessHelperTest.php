<?php

declare(strict_types=1);

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\CMS\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AccessHelperTest extends TestCase
{
    /**
     * @return User&MockObject
     */
    private function userWithAuthorise(callable $authorise, int $id = 0): User
    {
        $user = $this->createMock(User::class);
        $user->method('authorise')->willReturnCallback($authorise);
        $user->id = $id;

        return $user;
    }

    public function testDestinationAssetName(): void
    {
        $this->assertSame('com_hotelbooking.destination.12', AccessHelper::destinationAsset(12));
    }

    public function testIsPrivilegedWhenCoreAdmin(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.admin'
        );

        $this->assertTrue(AccessHelper::isPrivileged($user));
    }

    public function testIsPrivilegedWhenCoreCreateOnComponent(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.create' && $asset === 'com_hotelbooking'
        );

        $this->assertTrue(AccessHelper::isPrivileged($user));
    }

    public function testIsNotPrivilegedWithOnlyManage(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.manage' && $asset === 'com_hotelbooking'
        );

        $this->assertFalse(AccessHelper::isPrivileged($user));
    }

    public function testCanEditDestinationWhenPrivileged(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.admin',
            99
        );

        $this->assertTrue(AccessHelper::canEditDestination($user, 1));
    }

    public function testCanEditDestinationWhenCoreEditOnAsset(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.edit' && $asset === 'com_hotelbooking.destination.5',
            7
        );

        $this->assertTrue(AccessHelper::canEditDestination($user, 5, 99));
    }

    public function testCanEditDestinationWhenEditOwnAndCreator(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.edit.own' && $asset === 'com_hotelbooking.destination.5',
            7
        );

        $this->assertTrue(AccessHelper::canEditDestination($user, 5, 7));
    }

    public function testCannotEditDestinationWhenEditOwnButDifferentCreator(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.edit.own',
            7
        );

        $this->assertFalse(AccessHelper::canEditDestination($user, 5, 9));
    }

    public function testCannotEditDestinationWhenIdIsZero(): void
    {
        $user = $this->userWithAuthorise(static fn (): bool => false, 3);

        $this->assertFalse(AccessHelper::canEditDestination($user, 0, 3));
    }

    public function testCanEditRoomFollowsDestinationAsset(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.edit' && $asset === 'com_hotelbooking.destination.4',
            2
        );

        $this->assertTrue(AccessHelper::canEditRoom($user, 4, 0));
        $this->assertFalse(AccessHelper::canEditRoom($user, 5, 0));
    }

    public function testFilterEditableDestinationIdsReturnsAllWhenPrivileged(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.admin'
        );

        $this->assertSame(
            [1, 2],
            AccessHelper::filterEditableDestinationIds($user, [
                ['id' => 1, 'created_by' => 9],
                ['id' => 2, 'created_by' => 8],
            ])
        );
    }

    public function testFilterEditableDestinationIdsKeepsAuthorisedOnly(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.edit' && $asset === 'com_hotelbooking.destination.2',
            4
        );

        $this->assertSame(
            [2],
            AccessHelper::filterEditableDestinationIds($user, [
                ['id' => 1, 'created_by' => 9],
                ['id' => 2, 'created_by' => 8],
            ])
        );
    }
}
