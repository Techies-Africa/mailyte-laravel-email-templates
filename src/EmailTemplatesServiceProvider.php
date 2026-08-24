<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mailyte\EmailTemplates\Blocks\BlockRegistry;
use Mailyte\EmailTemplates\Console\ListCommand;
use Mailyte\EmailTemplates\Console\SendTestCommand;
use Mailyte\EmailTemplates\Http\Middleware\Authorize;
use Mailyte\EmailTemplates\Rendering\RenderPipeline;
use Mailyte\EmailTemplates\Sources\DirectorySource;
use Mailyte\EmailTemplates\Sources\SourceChain;
use Mailyte\EmailTemplates\Themes\ThemeCompiler;
use Mailyte\EmailTemplates\Themes\ThemeRepository;
use Mailyte\EmailTemplates\Themes\TokenSanitizer;
use Mailyte\EmailTemplates\Twig\SandboxFactory;

class EmailTemplatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailyte.php', 'mailyte');

        $this->app->singleton(MailyteManager::class, fn ($app) => new MailyteManager($app));
        $this->app->alias(MailyteManager::class, 'mailyte');

        $this->app->singleton(ThemeRepository::class, fn ($app) => new ThemeRepository($app['config']));
        $this->app->singleton(ThemeCompiler::class);
        $this->app->singleton(TokenSanitizer::class);

        $this->app->singleton(BlockRegistry::class, fn ($app) => new BlockRegistry($app['view']));
        $this->app->singleton(SandboxFactory::class, fn ($app) => new SandboxFactory($app->make(BlockRegistry::class)));

        $this->app->singleton(RenderPipeline::class, fn ($app) => new RenderPipeline(
            $app->make(SandboxFactory::class),
            $app->make(BlockRegistry::class),
            $app->make(ThemeCompiler::class),
            $app['view'],
            $app['config'],
        ));

        $this->app->singleton(SourceChain::class, fn ($app) => $this->buildSourceChain($app['config']));
    }

    /**
     * Resolution order, first hit wins, mirroring Laravel's view precedence.
     *
     * Every entry is a plain directory scan, which is what makes a template
     * bundle plug-and-play: copy the folder in and it resolves, delete it and
     * it is gone. Nothing to register, no index to rebuild.
     */
    protected function buildSourceChain(ConfigRepository $config): SourceChain
    {
        $chain = new SourceChain;

        if (is_string($published = $config->get('mailyte.sources.published'))) {
            $chain->push(new DirectorySource($published, 'published'));
        }

        foreach ((array) $config->get('mailyte.sources.paths', []) as $path) {
            if (is_string($path)) {
                $chain->push(new DirectorySource($path, 'path:'.basename($path)));
            }
        }

        $chain->push(new DirectorySource(__DIR__.'/../resources/templates/core', 'core'));

        if ((bool) $config->get('mailyte.include_community', false)) {
            $chain->push(new DirectorySource(__DIR__.'/../resources/templates/community', 'community'));
        }

        return $chain;
    }

    public function boot(): void
    {
        // Registering the package's views under the "mailyte" namespace is what
        // gives us Laravel's own path precedence for free: anything published
        // into resources/views/vendor/mailyte overrides what we ship, exactly
        // like the framework's markdown mail components.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mailyte');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();

            $this->commands([
                ListCommand::class,
                SendTestCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        $config = $this->app['config'];

        if (! $config->get('mailyte.dashboard.enabled', true)) {
            return;
        }

        Route::group([
            'domain' => $config->get('mailyte.dashboard.domain'),
            'prefix' => $config->get('mailyte.dashboard.path', 'mailyte'),
            'middleware' => array_merge(
                (array) $config->get('mailyte.dashboard.middleware', ['web']),
                [Authorize::class],
            ),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/dashboard.php');
        });
    }

    /**
     * Publish tags deliberately mirror Laravel's own `laravel-mail` tag.
     *
     * Note there is no "publish everything" tag for templates: like the
     * framework publishing mail *components* rather than your application's
     * emails, individual templates are published one slug at a time via
     * `mailyte:publish-template`.
     */
    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/mailyte.php' => config_path('mailyte.php'),
        ], 'mailyte-mail-config');

        $this->publishes([
            __DIR__.'/../resources/views/html' => resource_path('views/vendor/mailyte/html'),
            __DIR__.'/../resources/views/text' => resource_path('views/vendor/mailyte/text'),
        ], 'mailyte-mail-blocks');

        $this->publishes([
            __DIR__.'/../resources/themes' => resource_path('views/vendor/mailyte/themes'),
        ], 'mailyte-mail-themes');
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [MailyteManager::class, 'mailyte'];
    }
}
