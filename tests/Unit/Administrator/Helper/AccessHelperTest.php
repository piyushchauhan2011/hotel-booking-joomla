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
    private function userWithAuthorise(callable $authorise): User
    {
        $user = $this->createMock(User::class);
        $user->method('authorise')->willReturnCallback($authorise);

        return $user;
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

    public function testIsNotPrivilegedWithoutAdminOrCreate(): void
    {
        $user = $this->userWithAuthorise(static fn (): bool => false);

        $this->assertFalse(AccessHelper::isPrivileged($user));
    }

    public function testCanEditDestinationWhenPrivileged(): void
    {
        $user = $this->userWithAuthorise(
            static fn (string $action, ?string $asset = null): bool => $action === 'core.admin'
        );
        $user->id = 99;

        $this->assertTrue(AccessHelper::canEditDestination($user, 1));
    }

    public function testCanEditDestinationWhenAssignedManager(): void
    {
        $user = $this->userWithAuthorise(static fn (): bool => false);
        $user->id = 7;

        $this->assertTrue(AccessHelper::canEditDestination($user, 7));
    }

    public function testCannotEditDestinationWhenManagerIdIsZero(): void
    {
        $user = $this->userWithAuthorise(static fn (): bool => false);
        $user->id = 0;

        $this->assertFalse(AccessHelper::canEditDestination($user, 0));
    }

    public function testCannotEditDestinationWhenAnotherManagerOwnsIt(): void
    {
        $user = $this->userWithAuthorise(static fn (): bool => false);
        $user->id = 3;

        $this->assertFalse(AccessHelper::canEditDestination($user, 8));
    }

    public function testCanEditRoomFollowsDestinationRules(): void
    {
        $user = $this->userWithAuthorise(static fn (): bool => false);
        $user->id = 4;

        $this->assertTrue(AccessHelper::canEditRoom($user, 4));
        $this->assertFalse(AccessHelper::canEditRoom($user, 5));
    }
}
