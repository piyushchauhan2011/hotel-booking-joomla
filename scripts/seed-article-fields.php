#!/usr/bin/env php
<?php

/**
 * Seed two Field Groups, four article Fields, and two Blog articles so the
 * Custom Fields UI can be learned on this site.
 *
 * Idempotent: skips groups/fields/articles that already exist (matched by
 * title or name). Re-run with:
 *
 *   ddev exec php scripts/seed-article-fields.php
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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

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

$user = $container->get(UserFactoryInterface::class)->loadUserById($superUserId);
$app->getSession()->set('user', $user);
$app->loadIdentity($user);

if (!ComponentHelper::isEnabled('com_fields')) {
    fwrite(STDERR, "com_fields is not enabled.\n");
    exit(1);
}

$app->setUserState('com_fields.fields.context', 'com_content.article');
$app->setUserState('com_fields.groups.context', 'com_content.article');

try {
    $blogCatId = findCategoryId($db, 'Blog');

if ($blogCatId < 1) {
    fwrite(STDERR, "Could not find a com_content category titled \"Blog\".\n");
    exit(1);
}

$fieldsFactory = $app->bootComponent('com_fields')->getMVCFactory();
$contentFactory = $app->bootComponent('com_content')->getMVCFactory();

$groupModel = $fieldsFactory->createModel('Group', 'Administrator', ['ignore_request' => true]);
$fieldModel = $fieldsFactory->createModel('Field', 'Administrator', ['ignore_request' => true]);
$articleTable = $contentFactory->createTable('Article', 'Administrator');

$access = (int) $app->get('access', 1);

$tripGroupId = ensureGroup($groupModel, $db, [
    'title'           => 'Trip facts',
    'id'              => 0,
    'published'       => 1,
    'ordering'        => 1,
    'note'            => 'Learning example: fields in this group are rendered by the article template override.',
    'state'           => 1,
    'access'          => $access,
    'created_user_id' => $superUserId,
    'context'         => 'com_content.article',
    'description'     => '<p>Typed extras for a travel article (neighborhood, season, official site). Automatic Display is off — the layout prints them.</p>',
    'language'        => '*',
    'params'          => '{"display_readonly":"1"}',
]);

$authorGroupId = ensureGroup($groupModel, $db, [
    'title'           => 'Author note',
    'id'              => 0,
    'published'       => 1,
    'ordering'        => 2,
    'note'            => 'Learning example: this group uses Automatic Display after the article body.',
    'state'           => 1,
    'access'          => $access,
    'created_user_id' => $superUserId,
    'context'         => 'com_content.article',
    'description'     => '<p>Shown automatically after the article because the field’s Automatic Display is “After Display Content”.</p>',
    'language'        => '*',
    'params'          => '{"display_readonly":"1"}',
]);

$fieldDefaults = [
    'id'            => 0,
    'description'   => '',
    'note'          => '',
    'default_value' => '',
    'ordering'      => 0,
    'state'         => 1,
    'language'      => '*',
    'access'        => $access,
    'context'       => 'com_content.article',
    'required'      => 0,
];

$displayNone = [
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
];

$displayAfter = $displayNone;
$displayAfter['display'] = '3';

$neighborhoodId = ensureField($fieldModel, $db, $fieldDefaults + [
    'name'             => 'trip-neighborhood',
    'title'            => 'Neighborhood',
    'label'            => 'Neighborhood',
    'type'             => 'text',
    'group_id'         => $tripGroupId,
    'assigned_cat_ids' => [$blogCatId],
    'params'           => $displayNone,
    'fieldparams'      => ['filter' => 'string', 'maxlength' => ''],
]);

$seasonId = ensureField($fieldModel, $db, $fieldDefaults + [
    'name'             => 'trip-best-season',
    'title'            => 'Best season',
    'label'            => 'Best season',
    'type'             => 'list',
    'group_id'         => $tripGroupId,
    'assigned_cat_ids' => [$blogCatId],
    'params'           => $displayNone,
    'fieldparams'      => [
        'multiple' => '0',
        'options'  => [
            'options0' => ['name' => 'Spring', 'value' => 'spring'],
            'options1' => ['name' => 'Summer', 'value' => 'summer'],
            'options2' => ['name' => 'Autumn', 'value' => 'autumn'],
            'options3' => ['name' => 'Winter', 'value' => 'winter'],
        ],
    ],
]);

$siteId = ensureField($fieldModel, $db, $fieldDefaults + [
    'name'             => 'trip-official-site',
    'title'            => 'Official site',
    'label'            => 'Official site',
    'type'             => 'url',
    'group_id'         => $tripGroupId,
    'assigned_cat_ids' => [$blogCatId],
    'params'           => $displayNone,
    'fieldparams'      => [
        'schemes'  => ['http', 'https'],
        'relative' => '0',
        'show_url' => '1',
    ],
]);

$bioId = ensureField($fieldModel, $db, $fieldDefaults + [
    'name'             => 'author-bio',
    'title'            => 'About the author',
    'label'            => 'About the author',
    'type'             => 'textarea',
    'group_id'         => $authorGroupId,
    'assigned_cat_ids' => [$blogCatId],
    'params'           => $displayAfter,
    'fieldparams'      => [
        'rows'      => 3,
        'cols'      => 80,
        'maxlength' => 400,
        'filter'    => '',
    ],
]);

$articles = [
    [
        'title'     => 'A weekend in Kyoto',
        'alias'     => 'a-weekend-in-kyoto',
        'introtext' => '<p>This demo article uses Joomla Custom Fields. Open it under Content → Articles and look for the <strong>Trip facts</strong> and <strong>Author note</strong> tabs — those are Field Groups, not the Metadata tab.</p>',
        'fulltext'  => '<h2>Gion after dark</h2><p>Stay near the Shirakawa canal if you want lanterns and quiet streets a few minutes from the crossing at Shijo. The Trip facts card above this article is rendered by the template from three fields whose Automatic Display is off.</p><h2>When to go</h2><p>Autumn colour in the temple gardens is the easy answer. The Best season field is a list custom field — change it on this article and reload the page.</p>',
        'fields'    => [
            $neighborhoodId => 'Gion',
            $seasonId       => 'autumn',
            $siteId         => 'https://www.kyoto.travel/',
            $bioId          => 'A desk-bound traveller who packs one extra pair of walking shoes and always checks the last train.',
        ],
    ],
    [
        'title'     => 'Shoulder season in Tokyo',
        'alias'     => 'shoulder-season-in-tokyo',
        'introtext' => '<p>Same Field Groups as the Kyoto article, different values. That is the point: fields are extra data on <em>this</em> article, not a module you duplicate per page.</p>',
        'fulltext'  => '<h2>Shibuya as a base</h2><p>The Yamanote loop makes most day trips simple. Edit this article and change Neighborhood — the facts card updates because the override reads <code>jcfields</code>.</p><h2>Author note</h2><p>The biography under the body is dumped by Joomla because that field’s Automatic Display is “After Display Content”. You did not have to print it in PHP.</p>',
        'fields'    => [
            $neighborhoodId => 'Shibuya',
            $seasonId       => 'spring',
            $siteId         => 'https://www.gotokyo.org/',
            $bioId          => 'Writes about rail maps, convenience-store breakfasts, and why you should not try to see every ward in one trip.',
        ],
    ],
];

foreach ($articles as $article) {
    $articleId = ensureArticle($articleTable, $db, $superUserId, [
        'id'              => 0,
        'title'           => $article['title'],
        'alias'           => $article['alias'],
        'introtext'       => $article['introtext'],
        'fulltext'        => $article['fulltext'],
        'catid'           => $blogCatId,
        'state'           => 1,
        'featured'        => 0,
        'language'        => '*',
        'access'          => $access,
        'created_user_id' => $superUserId,
        'created_by_alias'=> '',
        'ordering'        => 0,
        'associations'    => [],
        'metakey'         => '',
        'metadesc'        => '',
        'images'          => '',
        'urls'            => '',
        'attribs'         => '',
        'metadata'        => '',
        'note'            => 'Learning example for Custom Fields / Field Groups.',
    ]);

    foreach ($article['fields'] as $fieldId => $value) {
        upsertFieldValue($db, $articleId, (int) $fieldId, $value);
    }

    echo "Article #{$articleId}: {$article['title']}\n";
}

echo "Field groups: Trip facts (#{$tripGroupId}), Author note (#{$authorGroupId})\n";
echo "Fields: trip-neighborhood (#{$neighborhoodId}), trip-best-season (#{$seasonId}), trip-official-site (#{$siteId}), author-bio (#{$bioId})\n";
echo "Done. Open Content → Field Groups, Content → Fields, then edit either new Blog article.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

function findCategoryId(DatabaseInterface $db, string $title): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__categories'))
        ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
        ->where($db->quoteName('title') . ' = :title')
        ->bind(':title', $title)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function findGroupId(DatabaseInterface $db, string $title): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__fields_groups'))
        ->where($db->quoteName('context') . ' = ' . $db->quote('com_content.article'))
        ->where($db->quoteName('title') . ' = :title')
        ->bind(':title', $title)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function findFieldId(DatabaseInterface $db, string $name): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__fields'))
        ->where($db->quoteName('context') . ' = ' . $db->quote('com_content.article'))
        ->where($db->quoteName('name') . ' = :name')
        ->bind(':name', $name)
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

function ensureGroup($groupModel, DatabaseInterface $db, array $data): int
{
    $existing = findGroupId($db, $data['title']);

    if ($existing > 0) {
        echo "Field group exists: {$data['title']} (#{$existing})\n";

        return $existing;
    }

    if (!$groupModel->save($data)) {
        throw new \RuntimeException('Could not save field group "' . $data['title'] . '": ' . $groupModel->getError());
    }

    $id = (int) $groupModel->getItem()->id;
    echo "Created field group: {$data['title']} (#{$id})\n";

    return $id;
}

function ensureField($fieldModel, DatabaseInterface $db, array $data): int
{
    $name     = ApplicationHelper::stringURLSafe($data['name']);
    $existing = findFieldId($db, $name);

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

    unset($data['created_user_id'], $data['associations']);

    $articleTable->reset();
    $articleTable->id = 0;

    if (!$articleTable->bind($data) || !$articleTable->check() || !$articleTable->store()) {
        throw new \RuntimeException('Could not save article "' . $data['title'] . '": ' . $articleTable->getError());
    }

    return (int) $articleTable->id;
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

    $exists = (int) $db->setQuery($query)->loadResult() > 0;

    if ($exists) {
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
