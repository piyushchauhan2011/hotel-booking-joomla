#!/usr/bin/env php
<?php

/**
 * Enable platform-lab plugins, seed a destination Star rating field,
 * Schema.org rows, destination assets, two hotel-manager demo groups,
 * a Smart Search filter, and Search menu items.
 *
 * Idempotent. Re-run with:
 *
 *   ddev exec php scripts/seed-platform-labs.php
 *   ddev exec php cli/joomla.php finder:index
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
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Finder\Administrator\Table\FilterTable;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Learn\Component\Hotelbooking\Administrator\Helper\PartnerNotificationHelper;
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
    ensurePluginRow($db, 'system', 'hotelbooking', 'plg_system_hotelbooking');
    enablePlugin($db, 'schemaorg', 'lodging', []);
    enablePlugin($db, 'privacy', 'hotelbooking', []);
    enablePlugin($db, 'finder', 'hotelbooking', []);
    enablePlugin($db, 'system', 'hotelbooking', []);
    enablePlugin($db, 'system', 'schemaorg', []);
    enablePlugin($db, 'system', 'fields', []);
    if (PartnerNotificationHelper::ensureRegisteredMailTemplate()) {
        echo "Registered mail template com_hotelbooking.partner_notify\n";
    }
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
    $installer = new \Learn\Component\Hotelbooking\Administrator\Extension\HotelbookingInstallerScript();
    $installer->install(null);
    $hotelManagerId = findUsergroupId($db, 'Hotel Manager');

    if ($hotelManagerId < 1) {
        throw new \RuntimeException('Could not create the Hotel Manager user group.');
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

    $filterId = ensureFinderFilter($db, $identity, $superUserId);
    $searchIds = ensureSmartSearchMenus($app, $db, $superUserId, $access, $filterId);
    ensureMenuAssociations($db, $searchIds);

    restrictCpanelArticleModules($db);
    cleanModuleCache();
    cleanMenuCache();

    echo "Star rating field #{$starFieldId}. Plugins lodging/privacy/finder/system hotelbooking enabled.\n";
    echo "Destination assets, Schema.org rows, and manager groups are in place.\n";
    echo "Smart Search filter Hotel Booking (#{$filterId}); Search menu EN #{$searchIds['en-GB']}, TH #{$searchIds['th-TH']}.\n";
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

function cleanModuleCache(): void
{
    $factory = Factory::getContainer()->get(CacheControllerFactoryInterface::class);
    $factory->createCacheController('callback', ['defaultgroup' => 'com_modules'])->clean();
}

function cleanMenuCache(): void
{
    $factory = Factory::getContainer()->get(CacheControllerFactoryInterface::class);

    foreach (['com_menus', 'mod_menu'] as $group) {
        $factory->createCacheController('callback', ['defaultgroup' => $group])->clean();
    }
}

/**
 * Popular / Recently Added articles are site-wide Blog posts, not hotel records.
 * They sit on the Home Dashboard with Special access, which Hotel Manager is in
 * (so the admin toolbar renders). Point those two modules at Super Users instead.
 */
function restrictCpanelArticleModules(DatabaseInterface $db): void
{
    $title = 'Super Users';
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__viewlevels'))
        ->where($db->quoteName('title') . ' = :title')
        ->bind(':title', $title)
        ->setLimit(1);
    $accessId = (int) $db->setQuery($query)->loadResult();

    if ($accessId < 1) {
        echo "No Super Users view level; skip cpanel article module restriction.\n";

        return;
    }

    $clientId = 1;
    $position = 'cpanel';
    $popular  = 'mod_popular';
    $latest   = 'mod_latest';
    $query    = $db->createQuery()
        ->update($db->quoteName('#__modules'))
        ->set($db->quoteName('access') . ' = :access')
        ->where($db->quoteName('client_id') . ' = :clientId')
        ->where($db->quoteName('position') . ' = :position')
        ->where($db->quoteName('module') . ' IN (' . $db->quote($popular) . ', ' . $db->quote($latest) . ')')
        ->bind(':access', $accessId, ParameterType::INTEGER)
        ->bind(':clientId', $clientId, ParameterType::INTEGER)
        ->bind(':position', $position);
    $db->setQuery($query)->execute();

    echo "Restricted Popular/Recently Added dashboard modules to Super Users.\n";
}

function rebuildExtensionNamespaceMap(): void
{
    \JLoader::register('JNamespacePsr4Map', JPATH_LIBRARIES . '/namespacemap.php');
    (new \JNamespacePsr4Map())->create();
}

/**
 * @return list<int>
 */
function finderHotelTypeNodeIds(DatabaseInterface $db): array
{
    $paths = ['type/destination', 'type/room'];
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__finder_taxonomy'))
        ->whereIn($db->quoteName('path'), $paths, ParameterType::STRING)
        ->order($db->quoteName('lft') . ' ASC');

    return array_map('intval', $db->setQuery($query)->loadColumn() ?: []);
}

function ensureFinderFilter(DatabaseInterface $db, User $identity, int $userId): int
{
    $alias = 'hotel-booking';
    $nodes = finderHotelTypeNodeIds($db);
    $table = new FilterTable($db);
    $table->setCurrentUser($identity);

    if ($table->load(['alias' => $alias])) {
        $table->title = 'Hotel Booking';
        $table->state = 1;
        $table->data  = $nodes;
        $table->params = [];

        if (!$table->store()) {
            throw new \RuntimeException('Could not update Smart Search filter: ' . $table->getError());
        }

        echo 'Updated Smart Search filter Hotel Booking (#' . (int) $table->filter_id . ', ' . \count($nodes) . " type maps)\n";

        return (int) $table->filter_id;
    }

    $table->title          = 'Hotel Booking';
    $table->alias          = $alias;
    $table->state          = 1;
    $table->created_by     = $userId;
    $table->created_by_alias = '';
    $table->data           = $nodes;
    $table->params         = [];

    if (!$table->store()) {
        throw new \RuntimeException('Could not create Smart Search filter: ' . $table->getError());
    }

    echo 'Created Smart Search filter Hotel Booking (#' . (int) $table->filter_id . ', ' . \count($nodes) . " type maps)\n";

    if ($nodes === []) {
        echo "Hotel Booking filter has no Type maps yet. Run finder:index, then re-run this seed.\n";
    }

    return (int) $table->filter_id;
}

function findComponentId(DatabaseInterface $db, string $element): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('extension_id'))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
        ->where($db->quoteName('element') . ' = :element')
        ->bind(':element', $element)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function findMenuItem(DatabaseInterface $db, string $alias, string $language): array
{
    $query = $db->createQuery()
        ->select($db->quoteName(['id', 'menutype', 'parent_id', 'language', 'client_id', 'link']))
        ->from($db->quoteName('#__menu'))
        ->where($db->quoteName('alias') . ' = :alias')
        ->where($db->quoteName('language') . ' = :language')
        ->where($db->quoteName('client_id') . ' = 0')
        ->bind(':alias', $alias)
        ->bind(':language', $language)
        ->setLimit(1);

    $row = $db->setQuery($query)->loadAssoc();

    return \is_array($row) ? $row : [];
}

function ensureMenuItem($menuItemModel, DatabaseInterface $db, int $userId, int $access, array $data): int
{
    $existing = findMenuItem($db, $data['alias'], $data['language']);

    if (!empty($existing['id'])) {
        $id   = (int) $existing['id'];
        $link = (string) $data['link'];

        if (($existing['link'] ?? '') !== $link) {
            $query = $db->createQuery()
                ->update($db->quoteName('#__menu'))
                ->set($db->quoteName('link') . ' = :link')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':link', $link)
                ->bind(':id', $id, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
            echo "Updated menu item link: {$data['title']} (#{$id})\n";
        } else {
            echo "Menu item exists: {$data['title']} (#{$id})\n";
        }

        return $id;
    }

    $menuItemModel->setState('item.id', 0);

    $data += [
        'id'                => 0,
        'created_user_id'   => $userId,
        'note'              => '',
        'img'               => '',
        'associations'      => [],
        'client_id'         => 0,
        'level'             => 1,
        'home'              => 0,
        'browserNav'        => 0,
        'template_style_id' => 0,
        'access'            => $data['access'] ?? $access,
    ];

    if (!$menuItemModel->save($data)) {
        throw new \RuntimeException('Could not save menu "' . $data['title'] . '": ' . $menuItemModel->getError());
    }

    $id = (int) $menuItemModel->getState('item.id');
    echo "Created menu item: {$data['title']} (#{$id})\n";

    return $id;
}

/**
 * @return array<string, int>
 */
function ensureSmartSearchMenus(AdministratorApplication $app, DatabaseInterface $db, int $userId, int $access, int $filterId): array
{
    if (!ComponentHelper::isEnabled('com_finder')) {
        throw new \RuntimeException('com_finder must be enabled.');
    }

    $finderComponentId = findComponentId($db, 'com_finder');

    if ($finderComponentId < 1) {
        throw new \RuntimeException('Could not resolve com_finder extension id.');
    }

    $menusFactory  = $app->bootComponent('com_menus')->getMVCFactory();
    $menuItemModel = $menusFactory->createModel('Item', 'Administrator', ['ignore_request' => true]);
    $link          = 'index.php?option=com_finder&view=search';

    if ($filterId > 0) {
        $link .= '&f=' . $filterId;
    }

    $enId = ensureMenuItem($menuItemModel, $db, $userId, $access, [
        'title'        => 'Search',
        'alias'        => 'search',
        'link'         => $link,
        'type'         => 'component',
        'component_id' => $finderComponentId,
        'menutype'     => 'mainmenu',
        'parent_id'    => 1,
        'language'     => 'en-GB',
        'access'       => $access,
        'published'    => 1,
        'params'       => [
            'show_page_heading' => '1',
            'page_heading'      => 'Search',
            'show_advanced'     => '1',
            'show_description'  => '1',
            'show_date'         => '0',
        ],
    ]);

    $thId = ensureMenuItem($menuItemModel, $db, $userId, $access, [
        'title'        => 'ค้นหา',
        'alias'        => 'search',
        'link'         => $link,
        'type'         => 'component',
        'component_id' => $finderComponentId,
        'menutype'     => 'mainmenu',
        'parent_id'    => 1,
        'language'     => 'th-TH',
        'access'       => $access,
        'published'    => 1,
        'params'       => [
            'show_page_heading' => '1',
            'page_heading'      => 'ค้นหา',
            'show_advanced'     => '1',
            'show_description'  => '1',
            'show_date'         => '0',
        ],
    ]);

    return [
        'en-GB' => $enId,
        'th-TH' => $thId,
    ];
}

/**
 * @param  array<string, int>  $idsByLanguage
 */
function ensureMenuAssociations(DatabaseInterface $db, array $idsByLanguage): void
{
    ksort($idsByLanguage);

    $ids = array_values(array_filter(array_map('intval', $idsByLanguage)));

    if (\count($ids) < 2) {
        return;
    }

    $context = 'com_menus.item';
    $key     = md5(json_encode($idsByLanguage));

    $query = $db->createQuery()
        ->select($db->quoteName(['id', 'key']))
        ->from($db->quoteName('#__associations'))
        ->where($db->quoteName('context') . ' = :context')
        ->whereIn($db->quoteName('id'), $ids)
        ->bind(':context', $context);
    $existing = $db->setQuery($query)->loadAssocList('id') ?: [];

    $sameKey = \count($existing) === \count($ids);

    foreach ($ids as $id) {
        if (!isset($existing[$id]) || $existing[$id]['key'] !== $key) {
            $sameKey = false;
            break;
        }
    }

    if ($sameKey) {
        return;
    }

    $query = $db->createQuery()
        ->delete($db->quoteName('#__associations'))
        ->where($db->quoteName('context') . ' = :context')
        ->whereIn($db->quoteName('id'), $ids)
        ->bind(':context', $context);
    $db->setQuery($query)->execute();

    foreach ($ids as $id) {
        $row = (object) [
            'id'      => $id,
            'context' => $context,
            'key'     => $key,
        ];
        $db->insertObject('#__associations', $row);
    }

    echo 'Associated Search menu items #' . implode(', #', $ids) . "\n";
}
