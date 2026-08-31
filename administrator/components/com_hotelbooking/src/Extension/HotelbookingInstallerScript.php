<?php

namespace Learn\Component\Hotelbooking\Administrator\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class HotelbookingInstallerScript
{
    public function install($parent): bool
    {
        $this->ensureHotelManagerGroup();

        return true;
    }

    public function update($parent): bool
    {
        $this->ensureHotelManagerGroup();

        return true;
    }

    public function uninstall($parent): bool
    {
        return true;
    }

    public function preflight($type, $parent): bool
    {
        return true;
    }

    public function postflight($type, $parent): bool
    {
        return true;
    }

    private function ensureHotelManagerGroup(): void
    {
        $db    = Factory::getDbo();
        $title = 'Hotel Manager';

        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = :title')
            ->bind(':title', $title, ParameterType::STRING);
        $db->setQuery($query);
        $groupId = (int) $db->loadResult();

        if ($groupId > 0) {
            return;
        }

        /** @var \Joomla\CMS\Table\Usergroup $group */
        $group = Table::getInstance('Usergroup');
        $group->title = $title;
        $group->setLocation(1, 'last-child');

        if (!$group->store()) {
            return;
        }

        $groupId = (int) $group->id;

        $this->grantRule(1, 'core.login.admin', $groupId, true);
        // core.manage is required by Joomla's ComponentDispatcher just to reach
        // any admin view of the component at all - it is not optional here.
        // AccessHelper::isPrivileged() deliberately does not treat core.manage as
        // "privileged" for this reason; it checks core.create instead, which stays
        // withheld below.
        $this->grantRule('com_hotelbooking', 'core.manage', $groupId, false);
        $this->grantRule('com_hotelbooking', 'core.edit', $groupId, false);
        $this->grantRule('com_hotelbooking', 'core.edit.own', $groupId, false);

        $this->addGroupToSpecialViewLevel($groupId);
    }

    /**
     * Core admin chrome (the toolbar module mod_toolbar, mod_menu, etc.) is only
     * rendered for user groups covered by the "Special" view access level - this
     * is separate from and in addition to component-level ACL rules, and without
     * it Hotel Manager could reach admin views but would see no Save/Apply
     * buttons at all. Add the new group to that access level's group list.
     */
    private function addGroupToSpecialViewLevel(int $groupId): void
    {
        $db    = Factory::getDbo();
        $title = 'Special';

        $query = $db->createQuery()
            ->select([$db->quoteName('id'), $db->quoteName('rules')])
            ->from($db->quoteName('#__viewlevels'))
            ->where($db->quoteName('title') . ' = :title')
            ->bind(':title', $title, ParameterType::STRING);
        $db->setQuery($query);
        $row = $db->loadObject();

        if (!$row) {
            return;
        }

        $rules = json_decode($row->rules ?: '[]', true);

        if (!\is_array($rules)) {
            $rules = [];
        }

        if (\in_array($groupId, $rules, false)) {
            return;
        }

        $rules[] = $groupId;

        $updateQuery = $db->createQuery()
            ->update($db->quoteName('#__viewlevels'))
            ->set($db->quoteName('rules') . ' = :rules')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':rules', json_encode(array_values($rules)), ParameterType::STRING)
            ->bind(':id', $row->id, ParameterType::INTEGER);
        $db->setQuery($updateQuery)->execute();
    }

    private function grantRule($assetIdentifier, string $action, int $groupId, bool $byId): void
    {
        /** @var \Joomla\CMS\Table\Asset $asset */
        $asset = Table::getInstance('Asset');

        if ($byId) {
            $asset->load((int) $assetIdentifier);
        } else {
            $asset->loadByName((string) $assetIdentifier);
        }

        if (empty($asset->id)) {
            return;
        }

        $rules = json_decode($asset->rules ?: '{}', true);

        if (!\is_array($rules)) {
            $rules = [];
        }

        if (!isset($rules[$action]) || !\is_array($rules[$action])) {
            $rules[$action] = [];
        }

        $rules[$action][(string) $groupId] = 1;

        $asset->rules = json_encode($rules);
        $asset->store();
    }
}
