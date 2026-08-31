<?php

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\CMS\User\User;

\defined('_JEXEC') or die;

class AccessHelper
{
    public static function isPrivileged(User $user): bool
    {
        // core.manage is required just to reach any admin view of the component
        // (enforced by Joomla's ComponentDispatcher), so the Hotel Manager group
        // must have it too and it cannot be used to distinguish privilege here.
        // core.create is what Hotel Manager deliberately lacks, so use that instead.
        return $user->authorise('core.admin') || $user->authorise('core.create', 'com_hotelbooking');
    }

    public static function canEditDestination(User $user, int $destinationManagerUserId): bool
    {
        return self::isPrivileged($user) || ($destinationManagerUserId > 0 && $destinationManagerUserId === (int) $user->id);
    }

    public static function canEditRoom(User $user, int $roomDestinationManagerUserId): bool
    {
        return self::canEditDestination($user, $roomDestinationManagerUserId);
    }
}
