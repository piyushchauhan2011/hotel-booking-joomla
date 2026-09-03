<?php

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\CMS\User\User;

\defined('_JEXEC') or die;

class AccessHelper
{
    public static function destinationAsset(int $destinationId): string
    {
        return 'com_hotelbooking.destination.' . $destinationId;
    }

    public static function isPrivileged(User $user): bool
    {
        // core.manage is required just to reach any admin view of the component
        // (enforced by Joomla's ComponentDispatcher), so the Hotel Manager group
        // must have it too and it cannot be used to distinguish privilege here.
        // core.create is what Hotel Manager deliberately lacks, so use that instead.
        return $user->authorise('core.admin') || $user->authorise('core.create', 'com_hotelbooking');
    }

    public static function canEditDestination(User $user, int $destinationId, int $createdBy = 0): bool
    {
        if (self::isPrivileged($user)) {
            return true;
        }

        if ($destinationId < 1) {
            return false;
        }

        $asset = self::destinationAsset($destinationId);

        if ($user->authorise('core.edit', $asset)) {
            return true;
        }

        return $createdBy > 0
            && (int) $user->id === $createdBy
            && $user->authorise('core.edit.own', $asset);
    }

    public static function canEditRoom(User $user, int $destinationId, int $createdBy = 0): bool
    {
        return self::canEditDestination($user, $destinationId, $createdBy);
    }

    /**
     * @param  list<array{id:int,created_by:int}>  $rows
     *
     * @return list<int>
     */
    public static function filterEditableDestinationIds(User $user, array $rows): array
    {
        if (self::isPrivileged($user)) {
            return array_map(static fn(array $row): int => (int) $row['id'], $rows);
        }

        $ids = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id > 0 && self::canEditDestination($user, $id, (int) ($row['created_by'] ?? 0))) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
