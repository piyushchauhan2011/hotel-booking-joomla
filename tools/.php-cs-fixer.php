<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$finder = PhpCsFixer\Finder::create()
    ->in([
        $root . '/administrator/components/com_hotelbooking/src',
        $root . '/administrator/components/com_hotelbooking/services',
        $root . '/components/com_hotelbooking/src',
        $root . '/components/com_hotelbooking/services',
        $root . '/plugins/content/hotelbooking/src',
        $root . '/plugins/content/hotelbooking/services',
        $root . '/plugins/editors-xtd/hotelbooking/src',
        $root . '/plugins/editors-xtd/hotelbooking/services',
        $root . '/plugins/system/hbconsent/src',
        $root . '/plugins/system/hbconsent/services',
        $root . '/modules/mod_hoteldetails/src',
        $root . '/modules/mod_hoteldetails/services',
        $root . '/modules/mod_hotelhero/src',
        $root . '/modules/mod_hotelhero/services',
        $root . '/modules/mod_hotelrooms/src',
        $root . '/modules/mod_hotelrooms/services',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS2.0' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
