<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$phpunit = __DIR__ . '/vendor/bin/phpunit';
$config = $root . '/phpunit.xml.dist';

if (!is_file($phpunit)) {
    fwrite(STDERR, "PHPUnit not found. Run composer install --working-dir=tools first.\n");
    exit(1);
}

$command = [
    PHP_BINARY,
    '-d', 'pcov.directory=' . $root,
    $phpunit,
    '-c', $config,
    '--coverage-clover', $root . '/build/coverage/clover.xml',
    '--coverage-html', $root . '/build/coverage/html',
    '--coverage-text',
];

passthru(implode(' ', array_map('escapeshellarg', $command)), $exitCode);

exit($exitCode);
