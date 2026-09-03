#!/usr/bin/env php
<?php

/**
 * Seed a Privacy Policy article, a hidden menu item, and enable consent
 * plugins so Users → Privacy → Consents gets rows for:
 *
 *   - Logged-in users (core System – Privacy Consent, subject key)
 *   - Guest Contact-form ticks (plg_system_hbconsent, subject "Contact form")
 *   - Cookie-banner Accept (plg_system_hbconsent, subject "Cookie banner")
 *
 * Guest rows use user_id 0. Idempotent. Re-run with:
 *
 *   ddev exec php scripts/seed-privacy-consent.php
 *
 * Do not pre-insert a consent for maya — the lesson is the redirect after login.
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
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

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

if (!ComponentHelper::isEnabled('com_content') || !ComponentHelper::isEnabled('com_privacy')) {
    fwrite(STDERR, "com_content and com_privacy must be enabled.\n");
    exit(1);
}

$access         = (int) $app->get('access', 1);
$contentFactory = $app->bootComponent('com_content')->getMVCFactory();
$menusFactory   = $app->bootComponent('com_menus')->getMVCFactory();
$articleTable   = $contentFactory->createTable('Article', 'Administrator');
$menuItemModel  = $menusFactory->createModel('Item', 'Administrator', ['ignore_request' => true]);

try {
    $blogCatId = findCategoryId($db, 'Blog', 'com_content');

    if ($blogCatId < 1) {
        throw new \RuntimeException('Could not find a com_content category titled "Blog".');
    }

    $articleId = ensureArticle($articleTable, $db, $superUserId, [
        'id'               => 0,
        'title'            => 'Privacy Policy',
        'alias'            => 'privacy-policy',
        'introtext'        => '<p>This demo site stores the information you give us so we can run bookings, contacts, and your account.</p>',
        'fulltext'         => '<h2>When you agree on My Profile</h2><p>If you are logged in, Joomla records a <strong>Privacy Policy</strong> consent. That row lives under Users → Privacy → Consents. It stores your user account, the time you agreed, your IP address, and your browser user-agent. Admin can invalidate it; the next login will ask again.</p><h2>When you send the Contact form as a guest</h2><p>The checkbox on that form is only a gate. Core does not write a Consent row for anonymous visitors — there is no user account to attach it to.</p><h2>What this is not</h2><p>This is one policy agreement, not a per-feature flag (marketing vs partners). Cookie banners are a different tool.</p>',
        'catid'            => $blogCatId,
        'state'            => 1,
        'featured'         => 0,
        'language'         => '*',
        'access'           => $access,
        'ordering'         => 0,
        'note'             => 'Learning example for Privacy Consents.',
    ]);

    ensureWorkflowAssociation($db, $articleId);

    $contentComponentId = findComponentId($db, 'com_content');

    if ($contentComponentId < 1) {
        throw new \RuntimeException('Could not resolve com_content extension id.');
    }

    $hiddenMenutype = findMenutype($db, 'hidden') ? 'hidden' : 'mainmenu';

    ensureMenuItem($menuItemModel, $db, $superUserId, $access, [
        'title'        => 'Privacy Policy',
        'alias'        => 'privacy',
        'link'         => 'index.php?option=com_content&view=article&id=' . $articleId,
        'type'         => 'component',
        'component_id' => $contentComponentId,
        'menutype'     => $hiddenMenutype,
        'parent_id'    => 1,
        'language'     => '*',
        'access'       => $access,
        'published'    => 1,
        'params'       => [
            'show_page_heading' => '1',
            'page_heading'      => 'Privacy Policy',
            'menu_text'         => 0,
            'menu_show'         => 0,
        ],
    ]);

    enablePlugin($db, 'system', 'privacyconsent', [
        'privacy_type'    => 'article',
        'privacy_article' => (string) $articleId,
    ]);

    enablePlugin($db, 'content', 'confirmconsent', [
        'privacy_type'    => 'article',
        'privacy_article' => (string) $articleId,
    ]);

    ensurePluginRow($db, 'system', 'hbconsent', 'plg_system_hbconsent');
    enablePlugin($db, 'system', 'hbconsent', [
        'privacy_article' => (string) $articleId,
    ]);
    rebuildExtensionNamespaceMap();

    cleanPluginCache();

    echo "Privacy article #{$articleId}. Plugins Privacy Consent, Confirm Consent, and Hotel Booking Consents are enabled.\n";
    echo "Logged-in: maya agrees on Profile edit (Privacy Policy). Guest Contact tick and cookie-banner Accept write Consents with user id 0.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

function findCategoryId(DatabaseInterface $db, string $title, string $extension): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__categories'))
        ->where($db->quoteName('extension') . ' = :extension')
        ->where($db->quoteName('title') . ' = :title')
        ->bind(':extension', $extension)
        ->bind(':title', $title)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function findArticleId(DatabaseInterface $db, string $alias): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__content'))
        ->where($db->quoteName('alias') . ' = :alias')
        ->bind(':alias', $alias)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function ensureArticle($articleTable, DatabaseInterface $db, int $userId, array $data): int
{
    $existing = findArticleId($db, $data['alias']);

    if ($existing > 0) {
        echo "Article exists: {$data['title']} (#{$existing})\n";

        return $existing;
    }

    $now = Factory::getDate()->toSql();

    $data['created']     = $now;
    $data['modified']    = $now;
    $data['publish_up']  = $now;
    $data['hits']        = 0;
    $data['version']     = 1;
    $data['images']      = '{}';
    $data['urls']        = '{}';
    $data['attribs']     = '{}';
    $data['metadata']    = '{}';
    $data['metakey']     = '';
    $data['metadesc']    = '';
    $data['xreference']  = '';
    $data['created_by']  = $userId;
    $data['modified_by'] = $userId;
    $data['created_by_alias'] = '';

    unset($data['created_user_id'], $data['associations']);

    $articleTable->reset();
    $articleTable->id = 0;

    if (!$articleTable->bind($data) || !$articleTable->check() || !$articleTable->store()) {
        throw new \RuntimeException('Could not save article "' . $data['title'] . '": ' . $articleTable->getError());
    }

    $id = (int) $articleTable->id;
    echo "Created article: {$data['title']} (#{$id})\n";

    return $id;
}

function ensureWorkflowAssociation(DatabaseInterface $db, int $articleId): void
{
    $extension = 'com_content.article';

    $query = $db->createQuery()
        ->select('COUNT(*)')
        ->from($db->quoteName('#__workflow_associations'))
        ->where($db->quoteName('item_id') . ' = :itemId')
        ->where($db->quoteName('extension') . ' = :extension')
        ->bind(':itemId', $articleId, ParameterType::INTEGER)
        ->bind(':extension', $extension);

    if ((int) $db->setQuery($query)->loadResult() > 0) {
        return;
    }

    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__workflow_stages'))
        ->where($db->quoteName('default') . ' = 1')
        ->setLimit(1);
    $stageId = (int) $db->setQuery($query)->loadResult();

    if ($stageId < 1) {
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__workflow_stages'))
            ->order($db->quoteName('id') . ' ASC')
            ->setLimit(1);
        $stageId = (int) $db->setQuery($query)->loadResult();
    }

    if ($stageId < 1) {
        return;
    }

    $row = (object) [
        'item_id'   => $articleId,
        'stage_id'  => $stageId,
        'extension' => $extension,
    ];
    $db->insertObject('#__workflow_associations', $row);
}

function findMenutype(DatabaseInterface $db, string $menutype): bool
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__menu_types'))
        ->where($db->quoteName('menutype') . ' = :menutype')
        ->bind(':menutype', $menutype)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult() > 0;
}

function findMenuItem(DatabaseInterface $db, string $alias): array
{
    $query = $db->createQuery()
        ->select($db->quoteName(['id', 'menutype', 'parent_id', 'language', 'client_id']))
        ->from($db->quoteName('#__menu'))
        ->where($db->quoteName('alias') . ' = :alias')
        ->where($db->quoteName('client_id') . ' = 0')
        ->bind(':alias', $alias)
        ->setLimit(1);

    $row = $db->setQuery($query)->loadAssoc();

    return \is_array($row) ? $row : [];
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

function ensureMenuItem($menuItemModel, DatabaseInterface $db, int $userId, int $access, array $data): int
{
    $existing = findMenuItem($db, $data['alias']);

    if (!empty($existing['id'])) {
        echo "Menu item exists: {$data['title']} (#{$existing['id']})\n";

        return (int) $existing['id'];
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
        'package_id'      => 0,
        'name'            => $name,
        'type'            => 'plugin',
        'element'         => $element,
        'folder'          => $folder,
        'client_id'       => 0,
        'enabled'         => 1,
        'access'          => 1,
        'protected'       => 0,
        'locked'          => 0,
        'manifest_cache'  => '{}',
        'params'          => '{}',
        'custom_data'     => '',
        'ordering'        => 0,
        'state'           => 0,
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

/**
 * Rescan extension XML into administrator/cache/autoload_psr4.php.
 * createExtensionNamespaceMap() only loads that file; it does not rebuild it.
 */
function rebuildExtensionNamespaceMap(): void
{
    \JLoader::register('JNamespacePsr4Map', JPATH_LIBRARIES . '/namespacemap.php');
    (new \JNamespacePsr4Map())->create();
}
