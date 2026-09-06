<?php

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\CMS\Access\Access;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;

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

    /**
     * Site-wide FAQs live on com_hotelbooking view=faqs, not on a destination asset.
     */
    public static function isAdministratorFaqsLink(string $link): bool
    {
        if ($link === '' || !str_contains($link, 'option=com_hotelbooking')) {
            return false;
        }

        $query = [];
        parse_str((string) (parse_url($link, PHP_URL_QUERY) ?: ''), $query);
        $view = strtolower((string) ($query['view'] ?? ''));
        $task = strtolower((string) ($query['task'] ?? ''));

        return \in_array($view, ['faqs', 'faq'], true)
            || str_starts_with($task, 'faq.');
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

        if (self::allowsItemLevelEdit($user, $asset)) {
            return true;
        }

        return $createdBy > 0
            && (int) $user->id === $createdBy
            && $user->authorise('core.edit.own', $asset);
    }

    /**
     * Item-level core.edit only. Recursive authorise() would inherit
     * com_hotelbooking core.edit and let a hotel manager open every destination.
     */
    private static function allowsItemLevelEdit(User $user, string $asset): bool
    {
        $identities = $user->getAuthorisedGroups();

        if (!\is_array($identities) || $identities === []) {
            return $user->authorise('core.edit', $asset);
        }

        try {
            array_unshift($identities, (int) $user->id * -1);

            return Access::getAssetRules($asset, false, false)->allow('core.edit', $identities) === true;
        } catch (\Throwable) {
            return $user->authorise('core.edit', $asset);
        }
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

    /**
     * @return list<int>
     */
    public static function editableDestinationIds(User $user, DatabaseInterface $db): array
    {
        $query = $db->createQuery()
            ->select([$db->quoteName('id'), $db->quoteName('created_by')])
            ->from($db->quoteName('#__hotelbooking_destinations'));
        $rows = $db->setQuery($query)->loadAssocList() ?: [];

        return self::filterEditableDestinationIds($user, $rows);
    }
}
