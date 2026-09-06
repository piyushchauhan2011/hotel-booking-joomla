<?php

namespace Learn\Component\Hotelbooking\Administrator\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Learn\Component\Hotelbooking\Administrator\Helper\PartnerNotificationHelper;

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
        if (\in_array($type, ['install', 'update', 'discover_install'], true)) {
            PartnerNotificationHelper::ensureRegisteredMailTemplate();
        }

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

        if ($groupId < 1) {
            /** @var \Joomla\CMS\Table\Usergroup $group */
            $group = Table::getInstance('Usergroup');
            $group->title     = $title;
            $group->parent_id = 1;

            if (!$group->store()) {
                return;
            }

            $groupId = (int) $group->id;
        }

        $this->grantRule(1, 'core.login.admin', $groupId, true);
        // core.manage is required by Joomla's ComponentDispatcher just to reach
        // any admin view of the component at all - it is not optional here.
        // AccessHelper::isPrivileged() deliberately does not treat core.manage as
        // "privileged" for this reason; it checks core.create instead, which stays
        // withheld below. Do not grant component-level core.edit: destination
        // assets inherit it and a hotel manager would then edit every hotel.
        $this->grantRule('com_hotelbooking', 'core.manage', $groupId, false);
        $this->revokeRule('com_hotelbooking', 'core.create', $groupId, false);
        $this->revokeRule('com_hotelbooking', 'core.edit', $groupId, false);
        $this->grantRule('com_hotelbooking', 'core.edit.own', $groupId, false);

        $this->addGroupToSpecialViewLevel($groupId);
        $this->restrictFaqsAdminMenu();
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

    /**
     * Site-wide FAQs are not hotel-scoped. CssMenu ignores #__menu.access, so
     * Super Users view level alone does not hide the item. Also set
     * menu-permission (honoured by mod_submenu) and let plg_system_hotelbooking
     * drop the sidebar link for anyone without core.create.
     */
    private function restrictFaqsAdminMenu(): void
    {
        $db    = Factory::getDbo();
        $title = 'Super Users';

        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__viewlevels'))
            ->where($db->quoteName('title') . ' = :title')
            ->bind(':title', $title, ParameterType::STRING);
        $db->setQuery($query);
        $accessId = (int) $db->loadResult();

        $clientId = 1;
        $link     = '%view=faqs%';
        $option   = '%option=com_hotelbooking%';
        $query    = $db->createQuery()
            ->select([$db->quoteName('id'), $db->quoteName('params')])
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('client_id') . ' = :clientId')
            ->where($db->quoteName('link') . ' LIKE :option')
            ->where($db->quoteName('link') . ' LIKE :link')
            ->bind(':clientId', $clientId, ParameterType::INTEGER)
            ->bind(':option', $option)
            ->bind(':link', $link);

        foreach ($db->setQuery($query)->loadObjectList() ?: [] as $row) {
            $params = new Registry($row->params);
            $params->set('menu-permission', 'core.create;com_hotelbooking');
            $encoded = $params->toString();
            $id      = (int) $row->id;

            $update = $db->createQuery()
                ->update($db->quoteName('#__menu'))
                ->set($db->quoteName('params') . ' = :params')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':params', $encoded)
                ->bind(':id', $id, ParameterType::INTEGER);

            if ($accessId > 0) {
                $update->set($db->quoteName('access') . ' = :access')
                    ->bind(':access', $accessId, ParameterType::INTEGER);
            }

            $db->setQuery($update)->execute();
        }
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

    private function revokeRule($assetIdentifier, string $action, int $groupId, bool $byId): void
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

        $groupKey = (string) $groupId;

        if (!\is_array($rules) || !isset($rules[$action]) || !\is_array($rules[$action])) {
            return;
        }

        if (!\array_key_exists($groupKey, $rules[$action]) && !\array_key_exists($groupId, $rules[$action])) {
            return;
        }

        unset($rules[$action][$groupKey], $rules[$action][$groupId]);

        if ($rules[$action] === []) {
            unset($rules[$action]);
        }

        $asset->rules = json_encode($rules);
        $asset->store();
    }
}
