#!/usr/bin/env php
<?php

/**
 * Seed Contact/User Field Groups, a demo user, organisation + team contacts,
 * and About / Contact / My Profile menu items.
 *
 * Idempotent. Re-run with:
 *
 *   ddev exec php scripts/seed-people-fields.php
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
\Joomla\CMS\Plugin\PluginHelper::importPlugin('user', null, true, $app->getDispatcher());

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

if (!ComponentHelper::isEnabled('com_fields') || !ComponentHelper::isEnabled('com_contact')) {
    fwrite(STDERR, "com_fields and com_contact must be enabled.\n");
    exit(1);
}

$access = (int) $app->get('access', 1);

$app->getInput()->set('extension', 'com_contact');

$fieldsFactory   = $app->bootComponent('com_fields')->getMVCFactory();
$contactFactory  = $app->bootComponent('com_contact')->getMVCFactory();
$usersFactory    = $app->bootComponent('com_users')->getMVCFactory();
$categoryFactory = $app->bootComponent('com_categories')->getMVCFactory();
$menusFactory    = $app->bootComponent('com_menus')->getMVCFactory();

$groupModel    = $fieldsFactory->createModel('Group', 'Administrator', ['ignore_request' => true]);
$fieldModel    = $fieldsFactory->createModel('Field', 'Administrator', ['ignore_request' => true]);
$userModel     = $usersFactory->createModel('User', 'Administrator', ['ignore_request' => true]);
$categoryModel = $categoryFactory->createModel('Category', 'Administrator', ['ignore_request' => true]);
$menuItemModel = $menusFactory->createModel('Item', 'Administrator', ['ignore_request' => true]);

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
$displayAfter            = $displayNone;
$displayAfter['display'] = '3';

try {
    $orgCatId  = ensureCategory($categoryModel, $db, 'Organisation', 'organisation', 'com_contact', $superUserId, $access);
    $teamCatId = ensureCategory($categoryModel, $db, 'Team', 'team', 'com_contact', $superUserId, $access);

    $app->setUserState('com_fields.fields.context', 'com_contact.contact');
    $app->setUserState('com_fields.groups.context', 'com_contact.contact');

    $roleGroupId = ensureGroup($groupModel, $db, 'com_contact.contact', [
        'title'           => 'Role',
        'id'              => 0,
        'published'       => 1,
        'ordering'        => 1,
        'note'            => 'Learning example: printed by the contact template override.',
        'state'           => 1,
        'access'          => $access,
        'created_user_id' => $superUserId,
        'context'         => 'com_contact.contact',
        'description'     => '<p>Job title and department on a public Contact. Automatic Display is off.</p>',
        'language'        => '*',
        'params'          => '{"display_readonly":"1"}',
    ]);

    $linksGroupId = ensureGroup($groupModel, $db, 'com_contact.contact', [
        'title'           => 'Public links',
        'id'              => 0,
        'published'       => 1,
        'ordering'        => 2,
        'note'            => 'Learning example: Automatic Display after the contact body.',
        'state'           => 1,
        'access'          => $access,
        'created_user_id' => $superUserId,
        'context'         => 'com_contact.contact',
        'description'     => '<p>Shown automatically after the contact because Automatic Display is After Display Content.</p>',
        'language'        => '*',
        'params'          => '{"display_readonly":"1"}',
    ]);

    $contactFieldDefaults = [
        'id'            => 0,
        'description'   => '',
        'note'          => '',
        'default_value' => '',
        'ordering'      => 0,
        'state'         => 1,
        'language'      => '*',
        'access'        => $access,
        'context'       => 'com_contact.contact',
        'required'      => 0,
    ];

    $jobTitleId = ensureField($fieldModel, $db, 'com_contact.contact', $contactFieldDefaults + [
        'name'             => 'job-title',
        'title'            => 'Job title',
        'label'            => 'Job title',
        'type'             => 'text',
        'group_id'         => $roleGroupId,
        'assigned_cat_ids' => [0],
        'params'           => $displayNone,
        'fieldparams'      => ['filter' => 'string', 'maxlength' => ''],
    ]);

    $departmentId = ensureField($fieldModel, $db, 'com_contact.contact', $contactFieldDefaults + [
        'name'             => 'department',
        'title'            => 'Department',
        'label'            => 'Department',
        'type'             => 'list',
        'group_id'         => $roleGroupId,
        'assigned_cat_ids' => [0],
        'params'           => $displayNone,
        'fieldparams'      => [
            'multiple' => '0',
            'options'  => [
                'options0' => ['name' => 'Leadership', 'value' => 'leadership'],
                'options1' => ['name' => 'Concierge', 'value' => 'concierge'],
                'options2' => ['name' => 'Partnerships', 'value' => 'partnerships'],
            ],
        ],
    ]);

    $publicSiteId = ensureField($fieldModel, $db, 'com_contact.contact', $contactFieldDefaults + [
        'name'             => 'public-site',
        'title'            => 'Public site',
        'label'            => 'Public site',
        'type'             => 'url',
        'group_id'         => $linksGroupId,
        'assigned_cat_ids' => [0],
        'params'           => $displayAfter,
        'fieldparams'      => [
            'schemes'  => ['http', 'https'],
            'relative' => '0',
            'show_url' => '1',
        ],
    ]);

    $app->setUserState('com_fields.fields.context', 'com_users.user');
    $app->setUserState('com_fields.groups.context', 'com_users.user');

    $workGroupId = ensureGroup($groupModel, $db, 'com_users.user', [
        'title'           => 'Work',
        'id'              => 0,
        'published'       => 1,
        'ordering'        => 1,
        'note'            => 'Learning example: User fields on My Profile (login account).',
        'state'           => 1,
        'access'          => $access,
        'created_user_id' => $superUserId,
        'context'         => 'com_users.user',
        'description'     => '<p>Internal work details on a User account — not the public Contact record.</p>',
        'language'        => '*',
        'params'          => '{"display_readonly":"1"}',
    ]);

    $prefsGroupId = ensureGroup($groupModel, $db, 'com_users.user', [
        'title'           => 'Preferences',
        'id'              => 0,
        'published'       => 1,
        'ordering'        => 2,
        'note'            => 'Learning example: a second User Field Group.',
        'state'           => 1,
        'access'          => $access,
        'created_user_id' => $superUserId,
        'context'         => 'com_users.user',
        'description'     => '<p>Shown on My Profile. Also appears on Maya’s public Contact because that contact is linked to this user.</p>',
        'language'        => '*',
        'params'          => '{"display_readonly":"1"}',
    ]);

    $userFieldDefaults = [
        'id'               => 0,
        'description'      => '',
        'note'             => '',
        'default_value'    => '',
        'ordering'         => 0,
        'state'            => 1,
        'language'         => '*',
        'access'           => $access,
        'context'          => 'com_users.user',
        'required'         => 0,
        'assigned_cat_ids' => [0],
        'params'           => $displayNone,
    ];

    $deskId = ensureField($fieldModel, $db, 'com_users.user', $userFieldDefaults + [
        'name'        => 'desk-location',
        'title'       => 'Desk location',
        'label'       => 'Desk location',
        'type'        => 'text',
        'group_id'    => $workGroupId,
        'fieldparams' => ['filter' => 'string', 'maxlength' => ''],
    ]);

    $hoursId = ensureField($fieldModel, $db, 'com_users.user', $userFieldDefaults + [
        'name'        => 'working-hours',
        'title'       => 'Working hours',
        'label'       => 'Working hours',
        'type'        => 'text',
        'group_id'    => $workGroupId,
        'fieldparams' => ['filter' => 'string', 'maxlength' => ''],
    ]);

    $preferredId = ensureField($fieldModel, $db, 'com_users.user', $userFieldDefaults + [
        'name'        => 'preferred-name',
        'title'       => 'Preferred name',
        'label'       => 'Preferred name',
        'type'        => 'text',
        'group_id'    => $prefsGroupId,
        'fieldparams' => ['filter' => 'string', 'maxlength' => ''],
    ]);

    $registeredGroupId = findUsergroupId($db, 'Registered');

    if ($registeredGroupId < 1) {
        $registeredGroupId = 2;
    }

    $mayaUserId = ensureUser($userModel, $db, [
        'id'        => 0,
        'name'      => 'Maya Chen',
        'username'  => 'maya',
        'password'  => 'ChangeMe123!',
        'password2' => 'ChangeMe123!',
        'email'     => 'maya@example.com',
        'groups'    => [$registeredGroupId],
        'block'     => 0,
    ]);

    upsertFieldValue($db, $mayaUserId, $deskId, 'Chiang Mai ops desk');
    upsertFieldValue($db, $mayaUserId, $hoursId, 'Tue–Sat 10:00–18:00 ICT');
    upsertFieldValue($db, $mayaUserId, $preferredId, 'Maya');

    $orgId = ensureContact($contactFactory, $db, $superUserId, [
        'name'        => 'Hotel Booking Demo',
        'alias'       => 'hotel-booking-demo',
        'catid'       => $orgCatId,
        'con_position'=> 'Reservations desk',
        'email_to'    => 'hello@example.com',
        'address'     => '12 Ping Riverside',
        'suburb'      => 'Chiang Mai',
        'state'       => 'Chiang Mai',
        'country'     => 'Thailand',
        'postcode'    => '50000',
        'telephone'   => '+66 53 000 000',
        'image'       => 'media/com_hotelbooking/images/people/org.svg',
        'misc'        => '<p>This Contact is the organisation. The form below is core com_contact mail — not a User profile. Team members are separate Contact records.</p>',
        'featured'    => 1,
        'user_id'     => 0,
        'params'      => [
            'show_email_form' => '1',
            'show_image'      => '1',
            'show_misc'       => '1',
            'show_name'       => '1',
        ],
        'fields'      => [
            $jobTitleId    => 'Reservations',
            $departmentId  => 'leadership',
            $publicSiteId  => 'https://joomla-hotel-booking.ddev.site/',
        ],
    ]);

    $mayaContactId = ensureContact($contactFactory, $db, $superUserId, [
        'name'        => 'Maya Chen',
        'alias'       => 'maya-chen',
        'catid'       => $teamCatId,
        'con_position'=> 'Guest Experience Lead',
        'email_to'    => 'maya@example.com',
        'address'     => '',
        'suburb'      => 'Chiang Mai',
        'country'     => 'Thailand',
        'image'       => 'media/com_hotelbooking/images/people/maya.svg',
        'misc'        => '<p>This is a public Contact. The Work / Preferences block below comes from the linked User account <strong>maya</strong> — log in as maya / ChangeMe123! and open My Profile to edit those User fields.</p>',
        'featured'    => 1,
        'user_id'     => $mayaUserId,
        'params'      => [
            'show_email_form'          => '0',
            'show_image'               => '1',
            'show_misc'                => '1',
            'show_user_custom_fields'  => ['-1'],
        ],
        'fields'      => [
            $jobTitleId   => 'Guest Experience Lead',
            $departmentId => 'leadership',
            $publicSiteId => 'https://www.gotokyo.org/',
        ],
    ]);

    ensureContact($contactFactory, $db, $superUserId, [
        'name'        => 'Luca Rossi',
        'alias'       => 'luca-rossi',
        'catid'       => $teamCatId,
        'con_position'=> 'Partnerships',
        'email_to'    => 'luca@example.com',
        'suburb'      => 'Rome',
        'country'     => 'Italy',
        'image'       => 'media/com_hotelbooking/images/people/luca.svg',
        'misc'        => '<p>Hotel partner liaison. Same Contact Field Groups as Maya — different values.</p>',
        'featured'    => 1,
        'user_id'     => 0,
        'params'      => [
            'show_email_form' => '0',
            'show_image'      => '1',
            'show_misc'       => '1',
        ],
        'fields'      => [
            $jobTitleId   => 'Partnerships manager',
            $departmentId => 'partnerships',
            $publicSiteId => 'https://www.italia.it/',
        ],
    ]);

    ensureContact($contactFactory, $db, $superUserId, [
        'name'        => 'Amina Diallo',
        'alias'       => 'amina-diallo',
        'catid'       => $teamCatId,
        'con_position'=> 'Concierge',
        'email_to'    => 'amina@example.com',
        'suburb'      => 'Paris',
        'country'     => 'France',
        'image'       => 'media/com_hotelbooking/images/people/amina.svg',
        'misc'        => '<p>On-the-ground concierge for city stays. No linked User — only Contact fields appear here.</p>',
        'featured'    => 1,
        'user_id'     => 0,
        'params'      => [
            'show_email_form' => '0',
            'show_image'      => '1',
            'show_misc'       => '1',
        ],
        'fields'      => [
            $jobTitleId   => 'Senior concierge',
            $departmentId => 'concierge',
            $publicSiteId => 'https://parisjetaime.com/',
        ],
    ]);

    $blogMenu = findMenuItem($db, 'blog');
    $menutype = $blogMenu['menutype'] ?? 'mainmenu';
    $language = $blogMenu['language'] ?? '*';
    $parentId = (int) ($blogMenu['parent_id'] ?? 1);

    // Hidden menu is for SEF only (not shown in the navbar). Team contacts
    // parent to this category item so URLs become /team/maya-chen.html.
    $hiddenMenutype = findMenutype($db, 'hidden') ? 'hidden' : $menutype;

    $contactComponentId = findComponentId($db, 'com_contact');
    $usersComponentId   = findComponentId($db, 'com_users');
    $registeredAccess   = findViewlevelId($db, 'Registered');

    if ($registeredAccess < 1) {
        $registeredAccess = 2;
    }

    if ($contactComponentId < 1 || $usersComponentId < 1) {
        throw new \RuntimeException('Could not resolve com_contact or com_users extension ids.');
    }

    ensureMenuItem($menuItemModel, $db, $superUserId, $access, [
        'title'      => 'About',
        'alias'      => 'about',
        'link'       => 'index.php?option=com_contact&view=featured',
        'type'       => 'component',
        'component_id' => $contactComponentId,
        'menutype'   => $menutype,
        'parent_id'  => $parentId,
        'language'   => $language,
        'access'     => $access,
        'published'  => 1,
        'params'     => [
            'show_page_heading' => '1',
            'page_heading'      => 'About the team',
            'show_pagination'   => '0',
        ],
    ]);

    ensureMenuItem($menuItemModel, $db, $superUserId, $access, [
        'title'        => 'Contact',
        'alias'        => 'contact',
        'link'         => 'index.php?option=com_contact&view=contact&id=' . $orgId,
        'type'         => 'component',
        'component_id' => $contactComponentId,
        'menutype'     => $menutype,
        'parent_id'    => $parentId,
        'language'     => $language,
        'access'       => $access,
        'published'    => 1,
        'params'       => [
            'show_page_heading' => '1',
            'page_heading'      => 'Write to us',
            'show_email_form'   => '1',
            'show_name'         => '1',
            'show_image'        => '1',
            'show_misc'         => '1',
        ],
    ]);

    ensureMenuItem($menuItemModel, $db, $superUserId, $access, [
        'title'        => 'Team',
        'alias'        => 'team',
        'link'         => 'index.php?option=com_contact&view=category&id=' . $teamCatId,
        'type'         => 'component',
        'component_id' => $contactComponentId,
        'menutype'     => $hiddenMenutype,
        'parent_id'    => 1,
        'language'     => $language,
        'access'       => $access,
        'published'    => 1,
        'params'       => [
            'show_page_heading' => '0',
            'menu_text'         => 0,
            'menu_show'         => 0,
        ],
    ]);

    ensureMenuItem($menuItemModel, $db, $superUserId, $access, [
        'title'        => 'My Profile',
        'alias'        => 'my-profile',
        'link'         => 'index.php?option=com_users&view=profile',
        'type'         => 'component',
        'component_id' => $usersComponentId,
        'menutype'     => $menutype,
        'parent_id'    => $parentId,
        'language'     => $language,
        'access'       => $registeredAccess,
        'published'    => 1,
        'params'       => [
            'show_page_heading' => '1',
            'page_heading'      => 'My Profile',
        ],
    ]);

    echo "User maya (#{$mayaUserId}) password ChangeMe123!\n";
    echo "Contacts: org #{$orgId}, Maya #{$mayaContactId}\n";
    echo "Done. Open About, Contact, then log in as maya to see My Profile.\n";
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

function ensureCategory($categoryModel, DatabaseInterface $db, string $title, string $alias, string $extension, int $userId, int $access): int
{
    $existing = findCategoryId($db, $title, $extension);

    if ($existing > 0) {
        echo "Category exists: {$title} (#{$existing})\n";

        return $existing;
    }

    $data = [
        'id'              => 0,
        'title'           => $title,
        'alias'           => $alias,
        'parent_id'       => 1,
        'extension'       => $extension,
        'published'       => 1,
        'access'          => $access,
        'language'        => '*',
        'created_user_id' => $userId,
        'params'          => '{}',
        'metadata'        => '{}',
        'description'     => '',
    ];

    if (!$categoryModel->save($data)) {
        throw new \RuntimeException('Could not save category "' . $title . '": ' . $categoryModel->getError());
    }

    $id = (int) $categoryModel->getState('category.id');
    echo "Created category: {$title} (#{$id})\n";

    return $id;
}

function findGroupId(DatabaseInterface $db, string $context, string $title): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__fields_groups'))
        ->where($db->quoteName('context') . ' = :context')
        ->where($db->quoteName('title') . ' = :title')
        ->bind(':context', $context)
        ->bind(':title', $title)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function findFieldId(DatabaseInterface $db, string $context, string $name): int
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

function ensureGroup($groupModel, DatabaseInterface $db, string $context, array $data): int
{
    $existing = findGroupId($db, $context, $data['title']);

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

function ensureField($fieldModel, DatabaseInterface $db, string $context, array $data): int
{
    $name     = ApplicationHelper::stringURLSafe($data['name']);
    $existing = findFieldId($db, $context, $name);

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

function findContactId(DatabaseInterface $db, string $alias): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__contact_details'))
        ->where($db->quoteName('alias') . ' = :alias')
        ->bind(':alias', $alias)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
}

function ensureContact($contactFactory, DatabaseInterface $db, int $userId, array $data): int
{
    $fields = $data['fields'] ?? [];
    unset($data['fields']);

    $existing = findContactId($db, $data['alias']);

    if ($existing > 0) {
        echo "Contact exists: {$data['name']} (#{$existing})\n";

        foreach ($fields as $fieldId => $value) {
            upsertFieldValue($db, $existing, (int) $fieldId, $value);
        }

        return $existing;
    }

    $now = Factory::getDate()->toSql();

    $data += [
        'published'         => 1,
        'access'            => 1,
        'language'          => '*',
        'ordering'          => 0,
        'created'           => $now,
        'created_by'        => $userId,
        'created_by_alias'  => '',
        'modified'          => $now,
        'metadata'          => '{}',
        'metakey'           => '',
        'metadesc'          => '',
        'sortname1'         => '',
        'sortname2'         => '',
        'sortname3'         => '',
        'webpage'           => '',
        'fax'               => '',
        'mobile'            => '',
        'default_con'       => 0,
        'checked_out'       => 0,
        'version'           => 1,
        'hits'              => 0,
    ];

    $contactTable = $contactFactory->createTable('Contact', 'Administrator');
    $contactTable->reset();
    $contactTable->id = 0;

    if (!$contactTable->bind($data) || !$contactTable->check() || !$contactTable->store()) {
        throw new \RuntimeException('Could not save contact "' . $data['name'] . '": ' . $contactTable->getError());
    }

    $id = (int) $contactTable->id;
    echo "Created contact: {$data['name']} (#{$id})\n";

    foreach ($fields as $fieldId => $value) {
        upsertFieldValue($db, $id, (int) $fieldId, $value);
    }

    return $id;
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

function findViewlevelId(DatabaseInterface $db, string $title): int
{
    $query = $db->createQuery()
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__viewlevels'))
        ->where($db->quoteName('title') . ' = :title')
        ->bind(':title', $title)
        ->setLimit(1);

    return (int) $db->setQuery($query)->loadResult();
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
        'id'              => 0,
        'created_user_id' => $userId,
        'note'            => '',
        'img'             => '',
        'associations'    => [],
        'client_id'       => 0,
        'level'           => 1,
        'home'            => 0,
        'browserNav'      => 0,
        'template_style_id' => 0,
        'access'          => $data['access'] ?? $access,
    ];

    if (!$menuItemModel->save($data)) {
        throw new \RuntimeException('Could not save menu "' . $data['title'] . '": ' . $menuItemModel->getError());
    }

    $id = (int) $menuItemModel->getState('item.id');
    echo "Created menu item: {$data['title']} (#{$id})\n";

    return $id;
}
