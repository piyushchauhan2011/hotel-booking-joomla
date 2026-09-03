<?php

declare(strict_types=1);

$cloverPath = $argv[1] ?? '';
$minimum    = (float) ($argv[2] ?? 80);

if ($cloverPath === '' || !is_readable($cloverPath)) {
    fwrite(STDERR, "Coverage file not found: {$cloverPath}\n");
    exit(1);
}

$clover = simplexml_load_file($cloverPath);

if ($clover === false) {
    fwrite(STDERR, "Could not parse coverage file: {$cloverPath}\n");
    exit(1);
}

$metrics = $clover->project->metrics ?? null;

if ($metrics === null) {
    fwrite(STDERR, "Clover file has no project metrics.\n");
    exit(1);
}

$statements = (int) $metrics['statements'];
$covered    = (int) $metrics['coveredstatements'];
$percent    = $statements === 0 ? 0.0 : ($covered / $statements) * 100;

printf("Helper coverage: %.1f%% (%d/%d statements)\n", $percent, $covered, $statements);

if ($percent < $minimum) {
    fwrite(STDERR, sprintf("Coverage %.1f%% is below the %.0f%% gate.\n", $percent, $minimum));
    exit(1);
}
