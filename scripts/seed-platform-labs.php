#!/usr/bin/env php
<?php

/**
 * Enable platform-lab plugins, seed a destination Star rating field,
 * Schema.org rows, destination assets, and two hotel-manager demo groups.
 *
 * Idempotent. Re-run with:
 *
 *   ddev exec php scripts/seed-platform-labs.php
 */

const _JEXEC = 1;
const JOOMLA_MINIMUM_PHP = '8.3.0';

if (version_compare(PHP_VERSION, JOOMLA_MINIMUM_PHP, '<')) {
    fwrite(STDERR, 'PHP ' . JOOMLA_MINIMUM_PHP . ' or newer is required.' . PHP_EOL);
    exit(1);
}

$root = \dirname(__DIR__);

if (file_exists($root . '/defines.php')) {
    require_once $root . '/defines.php';
}

if (!\defined('_JDEFINES')) {
    \define('JPATH_BASE', $root);
    require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Learn\Component\Hotelbooking\Site\Helper\SchemaHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
    ->alias('session.web', 'session.cli')
    ->alias('session.web.site', 'session.cli')
    ->alias('session.web.administrator', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.cli');

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

/** @var AdministratorApplication $app */
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;
rebuildExtensionNamespaceMap();
$app->createExtensionNamespaceMap();

\Joomla\CMS\Plugin\PluginHelper::importPlugin('behaviour', null, true, $app->getDispatcher());
\Joomla\CMS\Plugin\PluginHelper::importPlugin('system', null, true, $app->getDispatcher());
\Joomla\CMS\Plugin\PluginHelper::importPlugin('fields', null, true, $app->getDispatcher());

$db = $container->get(DatabaseInterface::class);

$query = $db->createQuery()
    ->select($db->quoteName('user_id'))
    ->from($db->quoteName('#__user_usergroup_map'))
    ->where($db->quoteName('group_id') . ' = 8')
    ->setLimit(1);
$superUserId = (int) $db->setQuery($query)->loadResult();

if ($superUserId < 1) {
    fwrite(STDERR, "Could not find a Super User (group id 8).\n");
    exit(1);
}

$identity = $container->get(UserFactoryInterface::class)->loadUserById($superUserId);
$app->getSession()->set('user', $identity);
$app->loadIdentity($identity);
$app->loadLanguage();

if (!ComponentHelper::isEnabled('com_hotelbooking')) {
    fwrite(STDERR, "com_hotelbooking must be enabled.\n");
    exit(1);
}

$access = (int) $app->get('access', 1);

try {
    ensurePluginRow($db, 'schemaorg', 'lodging', 'plg_schemaorg_lodging');
    ensurePluginRow($db, 'privacy', 'hotelbooking', 'plg_privacy_hotelbooking');
    ensurePluginRow($db, 'finder', 'hotelbooking', 'plg_finder_hotelbooking');
    enablePlugin($db, 'schemaorg', 'lodging', []);
    enablePlugin($db, 'privacy', 'hotelbooking', []);
    enablePlugin($db, 'finder', 'hotelbooking', []);
    enablePlugin($db, 'system', 'schemaorg', []);
    enablePlugin($db, 'system', 'fields', []);
    rebuildExtensionNamespaceMap();
    cleanPluginCache();

    $app->setUserState('com_fields.fields.context', 'com_hotelbooking.destination');
    $app->setUserState('com_fields.groups.context', 'com_hotelbooking.destination');

    $fieldsFactory = $app->bootComponent('com_fields')->getMVCFactory();
    $fieldModel    = $fieldsFactory->createModel('Field', 'Administrator', ['ignore_request' => true]);

    $starFieldId = ensureField($fieldModel, $db, [
        'id'               => 0,
        'name'             => 'star-rating',
        'title'            => 'Star rating',
        'label'            => 'Star rating',
        'type'             => 'list',
        'description'      => '',
        'note'             => 'Learning example for Custom Fields on destinations.',
        'default_value'    => '',
        'ordering'         => 0,
        'state'            => 1,
        'language'         => '*',
        'access'           => $access,
        'context'          => 'com_hotelbooking.destination',
        'required'         => 0,
        'group_id'         => 0,
        'assigned_cat_ids' => [0],
        'params'           => [
            'hint'               => '',
            'class'              => '',
            'label_class'        => '',
            'show_on'            => '',
            'render_class'       => '',
            'showlabel'          => '1',
            'label_render_class' => '',
            'display'            => '0',
            'prefix'             => '',
            'suffix'             => '',
            'layout'             => '',
            'display_readonly'   => '2',
        ],
        'fieldparams' => [
            'multiple' => '0',
            'options'  => [
                'options0' => ['name' => '3 stars', 'value' => '3'],
                'options1' => ['name' => '4 stars', 'value' => '4'],
                'options2' => ['name' => '5 stars', 'value' => '5'],
            ],
        ],
    ]);

    $destinations = loadDemoDestinations($db);

    if ($destinations === []) {
        throw new \RuntimeException('No published destinations found to seed.');
    }

    $mvcFactory       = $app->bootComponent('com_hotelbooking')->getMVCFactory();
    $destinationTable = $mvcFactory->createTable('Destination', 'Administrator');
    $hotelManagerId   = findUsergroupId($db, 'Hotel Manager');

    if ($hotelManagerId < 1) {
        $installer = new \Learn\Component\Hotelbooking\Administrator\Extension\HotelbookingInstallerScript();
        $installer->install(null);
        $hotelManagerId = findUsergroupId($db, 'Hotel Manager');
    }

    $usersFactory = $app->bootComponent('com_users')->getMVCFactory();
    $userModel    = $usersFactory->createModel('User', 'Administrator', ['ignore_request' => true]);

    $ratings = ['5', '4'];
    $index   = 0;

    foreach ($destinations as $destination) {
        $id = (int) $destination->id;
        $destinationTable->reset();

        if (!$destinationTable->load($id)) {
            continue;
        }

        if ((int) $destinationTable->created_by < 1) {
            $destinationTable->created_by = $superUserId;
        }

        if (!$destinationTable->store()) {
            throw new \RuntimeException('Could not store destination #' . $id . ': ' . $destinationTable->getError());
        }

        echo "Ensured asset for destination {$destinationTable->name} (#{$id})\n";

        ensureSchemaorgRow($db, $id, 'com_hotelbooking.destination', 'LodgingBusiness', SchemaHelper::graphNode(
            SchemaHelper::forDestination($destinationTable, 'https://example.test/destination/' . $id),
        ));

        $room = loadFirstRoom($db, $id);

        if ($room) {
            ensureSchemaorgRow($db, (int) $room->id, 'com_hotelbooking.room', 'Product', SchemaHelper::graphNode(
                SchemaHelper::forRoom($room, 'https://example.test/room/' . (int) $room->id),
            ));
        }

        if (isset($ratings[$index])) {
            upsertFieldValue($db, $id, $starFieldId, $ratings[$index]);
        }

        $groupTitle = $destinationTable->name . ' Managers';
        $groupId    = ensureUsergroup($db, $groupTitle);
        grantAssetRule($db, 'com_hotelbooking.destination.' . $id, 'core.edit', $groupId);

        $username = ApplicationHelper::stringURLSafe($destinationTable->alias ?: $destinationTable->name) . '_manager';
        $username = $username !== '' ? $username : 'hotel_manager_' . $id;

        ensureUser($userModel, $db, [
            'id'        => 0,
            'name'      => $destinationTable->name . ' Manager',
            'username'  => $username,
            'password'  => 'ChangeMe123!',
            'password2' => 'ChangeMe123!',
            'email'     => $username . '@example.com',
            'groups'    => array_values(array_filter([$hotelManagerId, $groupId])),
            'block'     => 0,
        ]);

        $index++;
    }

    echo "Star rating field #{$starFieldId}. Plugins lodging/privacy/finder enabled.\n";
    echo "Destination assets, Schema.org rows, and manager groups are in place.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

/**
 * @return list<object>
 */
function loadDemoDestinations(DatabaseInterface $db): array
{
    $query = $db->createQuery()
        ->select('*')
        ->from($db->quoteName('#__hotelbooking_destinations'))
        ->where($db->quoteName('published') . ' = 1')
        ->whereIn($db->quoteName('language'), ['en-GB', '*'])
        ->order($db->quoteName('id') . ' ASC')
        ->setLimit(2);

    return $db->setQuery($query)->loadObjectList() ?: [];
}

function loadFirstRoom(DatabaseInterface $db, int $destinationId): ?object
{
    $query = $db->createQuery()
        ->select('*')
        ->from($db->quoteName('#__hotelbooking_rooms'))
        ->where($db->quoteName('destination_id') . ' = :destinationId')
        ->where($db->quoteName('published') . ' = 1')
        ->bind(':destinationId', $destinationId, ParameterType::INTEGER)
        ->order($db->quoteName('id') . ' ASC')
        ->setLimit(1);

    $room = $db->setQuery($query)->loadObject();

    return $room ?: null;
}

function ensureSchemaorgRow(DatabaseInterface $db, int $itemId, string $context, string $schemaType, array $schema): void
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__schemaorg'))
        ->where($db->quoteName('itemId') . ' = :itemId')
        ->where($db->quoteName('context') . ' = :context')
        ->bind(':itemId', $itemId, ParameterType::INTEGER)
        ->bind(':context', $context)
        ->setLimit(1);
    $id = (int) $db->setQuery($query)->loadResult();

    $encoded = json_encode($schema, JSON_UNESCAPED_SLASHES);

    if ($id > 0) {
        $update = $db->createQuery()
            ->update($db->quoteName('#__schemaorg'))
            ->set($db->quoteName('schemaType') . ' = :schemaType')
            ->set($db->quoteName('schema') . ' = :schema')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':schemaType', $schemaType)
            ->bind(':schema', $encoded)
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($update)->execute();

        return;
    }

    $row = (object) [
        'itemId'     => $itemId,
        'context'    => $context,
        'schemaType' => $schemaType,
        'schema'     => $encoded,
    ];
    $db->insertObject('#__schemaorg', $row);
    echo "Seeded schema {$schemaType} for {$context} #{$itemId}\n";
}

function ensureField($fieldModel, DatabaseInterface $db, array $data): int
{
    $name     = ApplicationHelper::stringURLSafe($data['name']);
    $existing = findFieldId($db, $name, $data['context']);

    if ($existing > 0) {
        echo "Field exists: {$name} (#{$existing})\n";

        return $existing;
    }

    if (!$fieldModel->save($data)) {
        throw new \RuntimeException('Could not save field "' . $data['name'] . '": ' . $fieldModel->getError());
    }

    $id = (int) $fieldModel->getItem()->id;
    echo "Created field: {$name} (#{$id})\n";

    return $id;
}

function findFieldId(DatabaseInterface $db, string $name, string $context): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__fields'))
        ->where($db->quoteName('context') . ' = :context')
        ->where($db->quoteName('name') . ' = :name')
        ->bind(':context', $context)
        ->bind(':name', $name)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function upsertFieldValue(DatabaseInterface $db, int $itemId, int $fieldId, string $value): void
{
    $query = $db->createQuery()
        ->select('COUNT(*)')
        ->from($db->quoteName('#__fields_values'))
        ->where($db->quoteName('item_id') . ' = :itemId')
        ->where($db->quoteName('field_id') . ' = :fieldId')
        ->bind(':itemId', $itemId, ParameterType::INTEGER)
        ->bind(':fieldId', $fieldId, ParameterType::INTEGER);

    if ((int) $db->setQuery($query)->loadResult() > 0) {
        $query = $db->createQuery()
            ->update($db->quoteName('#__fields_values'))
            ->set($db->quoteName('value') . ' = :value')
            ->where($db->quoteName('item_id') . ' = :itemId')
            ->where($db->quoteName('field_id') . ' = :fieldId')
            ->bind(':value', $value)
            ->bind(':itemId', $itemId, ParameterType::INTEGER)
            ->bind(':fieldId', $fieldId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();

        return;
    }

    $row = (object) [
        'item_id'  => $itemId,
        'field_id' => $fieldId,
        'value'    => $value,
    ];
    $db->insertObject('#__fields_values', $row);
}

function findUsergroupId(DatabaseInterface $db, string $title): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__usergroups'))
        ->where($db->quoteName('title') . ' = :title')
        ->bind(':title', $title)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function ensureUsergroup(DatabaseInterface $db, string $title): int
{
    $existing = findUsergroupId($db, $title);

    if ($existing > 0) {
        echo "User group exists: {$title} (#{$existing})\n";

        return $existing;
    }

    /** @var \Joomla\CMS\Table\Usergroup $group */
    $group = Table::getInstance('Usergroup');
    $group->title     = $title;
    $group->parent_id = 1;

    if (!$group->store()) {
        throw new \RuntimeException('Could not create user group "' . $title . '": ' . $group->getError());
    }

    echo "Created user group: {$title} (#{$group->id})\n";

    return (int) $group->id;
}

function grantAssetRule(DatabaseInterface $db, string $assetName, string $action, int $groupId): void
{
    /** @var \Joomla\CMS\Table\Asset $asset */
    $asset = Table::getInstance('Asset');

    if (!$asset->loadByName($assetName) || empty($asset->id)) {
        echo "Asset {$assetName} not found; skip ACL grant.\n";

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

function findUserId(DatabaseInterface $db, string $username): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__users'))
        ->where($db->quoteName('username') . ' = :username')
        ->bind(':username', $username)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function ensureUser($userModel, DatabaseInterface $db, array $data): int
{
    $existing = findUserId($db, $data['username']);

    if ($existing > 0) {
        echo "User exists: {$data['username']} (#{$existing})\n";

        return $existing;
    }

    if (!$userModel->save($data)) {
        throw new \RuntimeException('Could not save user "' . $data['username'] . '": ' . $userModel->getError());
    }

    $id = (int) $userModel->getState('user.id');
    echo "Created user: {$data['username']} (#{$id})\n";

    return $id;
}

function ensurePluginRow(DatabaseInterface $db, string $folder, string $element, string $name): void
{
    $query = $db->createQuery()
        ->select($db->quoteName('extension_id'))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = :folder')
        ->where($db->quoteName('element') . ' = :element')
        ->bind(':folder', $folder)
        ->bind(':element', $element)
        ->setLimit(1);

    if ((int) $db->setQuery($query)->loadResult() > 0) {
        return;
    }

    $row = (object) [
        'package_id'     => 0,
        'name'           => $name,
        'type'           => 'plugin',
        'element'        => $element,
        'folder'         => $folder,
        'client_id'      => 0,
        'enabled'        => 1,
        'access'         => 1,
        'protected'      => 0,
        'locked'         => 0,
        'manifest_cache' => '{}',
        'params'         => '{}',
        'custom_data'    => '',
        'ordering'       => 0,
        'state'          => 0,
    ];
    $db->insertObject('#__extensions', $row);
    echo "Registered plugin {$folder}/{$element}\n";
}

function enablePlugin(DatabaseInterface $db, string $folder, string $element, array $params): void
{
    $query = $db->createQuery()
        ->select($db->quoteName(['extension_id', 'enabled', 'params']))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = :folder')
        ->where($db->quoteName('element') . ' = :element')
        ->bind(':folder', $folder)
        ->bind(':element', $element)
        ->setLimit(1);

    $row = $db->setQuery($query)->loadObject();

    if (!$row) {
        throw new \RuntimeException("Plugin {$folder}/{$element} is not installed.");
    }

    $registry = new Registry($row->params);

    foreach ($params as $key => $value) {
        $registry->set($key, $value);
    }

    $encoded = $registry->toString();
    $id      = (int) $row->extension_id;
    $enabled = 1;

    $query = $db->createQuery()
        ->update($db->quoteName('#__extensions'))
        ->set($db->quoteName('enabled') . ' = :enabled')
        ->set($db->quoteName('params') . ' = :params')
        ->where($db->quoteName('extension_id') . ' = :id')
        ->bind(':enabled', $enabled, ParameterType::INTEGER)
        ->bind(':params', $encoded)
        ->bind(':id', $id, ParameterType::INTEGER);
    $db->setQuery($query)->execute();

    echo "Enabled plugin {$folder}/{$element} (#{$id})\n";
}

function cleanPluginCache(): void
{
    $factory = Factory::getContainer()->get(CacheControllerFactoryInterface::class);

    foreach (['com_plugins', '_system'] as $group) {
        $factory->createCacheController('callback', ['defaultgroup' => $group])->clean();
    }
}

function rebuildExtensionNamespaceMap(): void
{
    \JLoader::register('JNamespacePsr4Map', JPATH_LIBRARIES . '/namespacemap.php');
    (new \JNamespacePsr4Map())->create();
}
