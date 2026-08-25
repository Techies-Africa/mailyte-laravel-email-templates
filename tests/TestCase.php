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
        // Any test that makes an HTTP request goes through the `web` middleware
        // group, which encrypts cookies and so needs a key. A developer's
        // testbench .env usually supplies one; a clean checkout does not, which
        // is why the dashboard tests passed locally and failed in CI.
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('m', 32)));
        $app['config']->set('app.cipher', 'AES-256-CBC');

        $app['config']->set('mailyte.theme', 'neutral');
        $app['config']->set('mailyte.layout', 'branded');
        $app['config']->set('mailyte.render.base_url', 'https://example.test');
        $app['config']->set('mailyte.globals.product.name', 'Acme');
        $app['config']->set('mailyte.globals.product.url', 'https://example.test');
        $app['config']->set('mailyte.globals.company.address', '1 Example Way, Springfield');
        $app['config']->set('mailyte.globals.unsubscribe_url', 'https://unsubscribe.test/one-click');
    }
}
