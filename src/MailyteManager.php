<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates;

use Illuminate\Contracts\Container\Container;
use Mailyte\EmailTemplates\Blocks\BlockRegistry;
use Mailyte\EmailTemplates\Rendering\RenderPipeline;
use Mailyte\EmailTemplates\Rendering\TemplateBuilder;
use Mailyte\EmailTemplates\Sources\SourceChain;
use Mailyte\EmailTemplates\Templates\TemplateManifest;
use Mailyte\EmailTemplates\Themes\ThemeRepository;
use Mailyte\EmailTemplates\Themes\TokenSanitizer;

final class MailyteManager
{
    public function __construct(private readonly Container $app) {}

    public function template(string $slug): TemplateBuilder
    {
        return new TemplateBuilder(
            slug: $slug,
            sources: $this->sources(),
            themes: $this->themes(),
            pipeline: $this->app->make(RenderPipeline::class),
            sanitizer: $this->app->make(TokenSanitizer::class),
            config: $this->app->make('config'),
        );
    }

    public function sources(): SourceChain
    {
        return $this->app->make(SourceChain::class);
    }

    public function themes(): ThemeRepository
    {
        return $this->app->make(ThemeRepository::class);
    }

    public function blocks(): BlockRegistry
    {
        return $this->app->make(BlockRegistry::class);
    }

    /**
     * @return array<string, TemplateManifest>
     */
    public function catalog(): array
    {
        return $this->sources()->all();
    }

    public function has(string $slug): bool
    {
        return $this->sources()->find($slug) !== null;
    }
}
