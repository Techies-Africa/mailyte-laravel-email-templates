<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mailyte\EmailTemplates\Blocks\BlockRegistry;
use Mailyte\EmailTemplates\Console\AdoptCommand;
use Mailyte\EmailTemplates\Console\DeliverabilityCommand;
use Mailyte\EmailTemplates\Console\LintCommand;
use Mailyte\EmailTemplates\Console\ListCommand;
use Mailyte\EmailTemplates\Console\PublishTemplateCommand;
use Mailyte\EmailTemplates\Console\SendTestCommand;
use Mailyte\EmailTemplates\Console\UsageCommand;
use Mailyte\EmailTemplates\Deliverability\DeliverabilityAudit;
use Mailyte\EmailTemplates\Http\Middleware\Authorize;
use Mailyte\EmailTemplates\Linting\SchemaValidator;
use Mailyte\EmailTemplates\Linting\TemplateLinter;
use Mailyte\EmailTemplates\Listeners\RecordTemplateUsage;
use Mailyte\EmailTemplates\Notifications\MailyteMailChannel;
use Mailyte\EmailTemplates\Rendering\RenderPipeline;
use Mailyte\EmailTemplates\Sources\DirectorySource;
use Mailyte\EmailTemplates\Sources\ShellSource;
use Mailyte\EmailTemplates\Sources\SourceChain;
use Mailyte\EmailTemplates\Themes\ThemeCompiler;
use Mailyte\EmailTemplates\Themes\ThemeRepository;
use Mailyte\EmailTemplates\Themes\TokenSanitizer;
use Mailyte\EmailTemplates\Twig\SandboxFactory;
use Mailyte\EmailTemplates\Usage\CacheUsageRecorder;
use Mailyte\EmailTemplates\Usage\DatabaseUsageRecorder;
use Mailyte\EmailTemplates\Usage\NullUsageRecorder;
use Mailyte\EmailTemplates\Usage\UsageRecorder;

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

        $this->app->singleton(DeliverabilityAudit::class, fn ($app) => new DeliverabilityAudit(
            (array) $app['config']->get('mailyte.lint.rules', []),
        ));

        $this->app->singleton(TemplateLinter::class, fn ($app) => new TemplateLinter(
            $app->make(BlockRegistry::class),
            $app->make(SchemaValidator::class),
            (array) $app['config']->get('mailyte.lint.rules', []),
        ));

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

        $this->app->singleton(UsageRecorder::class, function ($app): UsageRecorder {
            $config = $app['config'];

            if (! $config->get('mailyte.usage.enabled', true)) {
                return new NullUsageRecorder;
            }

            return match ($config->get('mailyte.usage.driver', 'cache')) {
                'database' => new DatabaseUsageRecorder(
                    $app['db']->connection(),
                    (string) $config->get('mailyte.usage.table', 'mailyte_template_usage'),
                ),
                'null' => new NullUsageRecorder,
                default => new CacheUsageRecorder($app['cache']->store()),
            };
        });
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

        // Resolvable by slug, deliberately absent from the catalog: the
        // notification shell is plumbing, not one of the designed fifty.
        $chain->push(new ShellSource(__DIR__.'/../resources/shells', 'shells'));

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

        Event::listen(MessageSending::class, RecordTemplateUsage::class);

        $this->registerNotificationChannel();
        $this->registerMarkdownTheme();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();

            $this->commands([
                AdoptCommand::class,
                DeliverabilityCommand::class,
                LintCommand::class,
                ListCommand::class,
                PublishTemplateCommand::class,
                SendTestCommand::class,
                UsageCommand::class,
            ]);
        }
    }

    /**
     * Replace the rendering half of Laravel's own mail notification channel.
     *
     * Off unless asked for: switching it on changes how every notification in
     * the application looks, which is not a decision a package gets to make on
     * installation. Everything else about the channel -- recipients, queueing,
     * `via()`, attachments -- is Laravel's and stays Laravel's.
     */
    protected function registerNotificationChannel(): void
    {
        if (! $this->app['config']->get('mailyte.notifications.enabled', false)) {
            return;
        }

        $this->callAfterResolving(ChannelManager::class, function (ChannelManager $manager): void {
            $manager->extend('mail', fn ($app) => new MailyteMailChannel(
                $app->make(Factory::class),
                $app->make(Markdown::class),
                $app->make(MailyteManager::class),
                $app['config'],
            ));
        });
    }

    /**
     * Point Laravel's markdown mailables at the stylesheet `mailyte:adopt`
     * generated, if there is one.
     *
     * Markdown mailables are a separate mechanism from notifications -- they
     * render through Laravel's own `mail::` components -- and the only global
     * lever on them is that one CSS file, which is looked up as a view. Setting
     * the theme here rather than editing the application's config/mail.php
     * keeps the change reversible by the same flag that turned it on.
     */
    protected function registerMarkdownTheme(): void
    {
        $config = $this->app['config'];

        if (! $config->get('mailyte.notifications.enabled', false)) {
            return;
        }

        // Never override a theme the application chose for itself.
        if ($config->get('mail.markdown.theme', 'default') !== 'default') {
            return;
        }

        if (is_file(resource_path('views/vendor/mail/html/themes/mailyte.css'))) {
            $config->set('mail.markdown.theme', 'mailyte');
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

        // Social icons have to be served from a public URL the recipient's mail
        // client can reach; a package path cannot be. Publishing copies them
        // into the application's own public directory, and
        // `footer.social_icon_base` points the footer at wherever they land.
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/mailyte'),
        ], 'mailyte-assets');

        $this->publishes([
            __DIR__.'/../resources/views/html' => resource_path('views/vendor/mailyte/html'),
            __DIR__.'/../resources/views/text' => resource_path('views/vendor/mailyte/text'),
        ], 'mailyte-mail-blocks');

        $this->publishes([
            __DIR__.'/../resources/themes' => resource_path('views/vendor/mailyte/themes'),
        ], 'mailyte-mail-themes');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'mailyte-migrations');

        // Publishing the shell moves it into the application's own published
        // directory, which is a listed source -- so it starts appearing in the
        // catalog, because from then on it is the application's template.
        $this->publishes([
            __DIR__.'/../resources/shells/laravel-notification' => config('mailyte.sources.published').'/laravel-notification',
        ], 'mailyte-notification-shell');
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [MailyteManager::class, 'mailyte'];
    }
}
