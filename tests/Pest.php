<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Security', 'Snapshots');

/**
 * Every template bundle on disk, by slug.
 *
 * Resolved from the filesystem rather than the container: datasets are built
 * before the application boots, so tests load the manifest themselves.
 */
dataset('catalog', function () {
    foreach (glob(__DIR__.'/../resources/templates/core/*/template.json') ?: [] as $file) {
        $slug = basename(dirname($file));

        yield $slug => [$slug];
    }
});
