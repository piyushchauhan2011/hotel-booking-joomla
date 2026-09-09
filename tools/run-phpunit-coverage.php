<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$phpunit = __DIR__ . '/vendor/bin/phpunit';
$config = $root . '/phpunit.xml.dist';

if (!is_file($phpunit)) {
    fwrite(STDERR, "PHPUnit not found. Run composer install --working-dir=tools first.\n");
    exit(1);
}

$extensions = array_map('strtolower', get_loaded_extensions());
$hasPcov    = in_array('pcov', $extensions, true);
$hasXdebug  = in_array('xdebug', $extensions, true);

if (!$hasPcov && !$hasXdebug) {
    fwrite(STDERR, <<<'TXT'
No code coverage driver available.

Install php-pcov in DDEV (see .ddev/config.yaml webimage_extra_packages), then:

  ddev restart
  ddev exec composer test:coverage --working-dir=tools

Or enable Xdebug for one run:

  ddev xdebug on
  ddev exec bash -lc 'export XDEBUG_MODE=coverage; composer test:coverage --working-dir=tools'
  ddev xdebug off

TXT);
    exit(1);
}

$command = [PHP_BINARY];

if ($hasPcov) {
    $command[] = '-d';
    $command[] = 'pcov.enabled=1';
    $command[] = '-d';
    $command[] = 'pcov.directory=' . $root;
} else {
    $command[] = '-d';
    $command[] = 'xdebug.mode=coverage';
}

$command[] = $phpunit;
$command[] = '-c';
$command[] = $config;
$command[] = '--coverage-clover';
$command[] = $root . '/build/coverage/clover.xml';
$command[] = '--coverage-html';
$command[] = $root . '/build/coverage/html';
$command[] = '--coverage-text';

passthru(implode(' ', array_map('escapeshellarg', $command)), $exitCode);

exit($exitCode);
