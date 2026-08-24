<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Tests;

use Mailyte\EmailTemplates\EmailTemplatesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [EmailTemplatesServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('mailyte.theme', 'neutral');
        $app['config']->set('mailyte.layout', 'branded');
        $app['config']->set('mailyte.render.base_url', 'https://example.test');
        $app['config']->set('mailyte.globals.product.name', 'Acme');
        $app['config']->set('mailyte.globals.product.url', 'https://example.test');
        $app['config']->set('mailyte.globals.company.address', '1 Example Way, Springfield');
    }
}
