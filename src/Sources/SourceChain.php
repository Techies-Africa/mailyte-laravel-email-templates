<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Sources;

use Mailyte\EmailTemplates\Exceptions\TemplateNotFound;
use Mailyte\EmailTemplates\Templates\TemplateManifest;

/**
 * Resolution order, first hit wins.
 *
 * Mirrors Laravel's view path precedence: a bundle published or dropped into
 * the application beats the one the package ships under the same slug, so
 * forking a template means copying one folder and editing it.
 */
final class SourceChain
{
    /** @var array<int, TemplateSource> */
    private array $sources;

    public function __construct(TemplateSource ...$sources)
    {
        $this->sources = $sources;
    }

    public function push(TemplateSource $source): self
    {
        $this->sources[] = $source;

        return $this;
    }

    public function prepend(TemplateSource $source): self
    {
        array_unshift($this->sources, $source);

        return $this;
    }

    public function find(string $slug): ?TemplateManifest
    {
        foreach ($this->sources as $source) {
            if ($manifest = $source->find($slug)) {
                return $manifest;
            }
        }

        return null;
    }

    public function get(string $slug): TemplateManifest
    {
        return $this->find($slug) ?? throw new TemplateNotFound(
            "Template [{$slug}] not found. Looked in: ".implode(', ', array_map(
                static fn (TemplateSource $s): string => $s->name(),
                $this->sources
            )).'. Run `php artisan mailyte:list` to see what is available.'
        );
    }

    /**
     * @return array<string, TemplateManifest>
     */
    public function all(): array
    {
        $found = [];

        foreach ($this->sources as $source) {
            foreach ($source->all() as $slug => $manifest) {
                $found[$slug] ??= $manifest;
            }
        }

        ksort($found);

        return $found;
    }

    public function forget(): void
    {
        foreach ($this->sources as $source) {
            if ($source instanceof DirectorySource) {
                $source->forget();
            }
        }
    }
}
