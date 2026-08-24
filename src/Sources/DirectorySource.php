<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Sources;

use Mailyte\EmailTemplates\Exceptions\InvalidManifest;
use Mailyte\EmailTemplates\Templates\TemplateManifest;

/**
 * Discovers template bundles by scanning a directory for template.json files.
 *
 * Discovery is purely filesystem-based and one level deep or two (the second
 * level covers `community/<vendor>/<slug>`), so plugging in a template is
 * literally copying its folder into place. Nothing to register, no cache to
 * rebuild, and removing the folder removes the template.
 */
class DirectorySource implements TemplateSource
{
    /** @var array<string, TemplateManifest>|null */
    private ?array $cache = null;

    public function __construct(
        private readonly string $directory,
        private readonly string $label = 'directory',
    ) {}

    public function name(): string
    {
        return $this->label;
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function find(string $slug): ?TemplateManifest
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * @return array<string, TemplateManifest>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $found = [];

        if (! is_dir($this->directory)) {
            return $this->cache = $found;
        }

        foreach ($this->manifestFiles() as $file) {
            $manifest = $this->parse($file);

            // First one wins, so a bundle sitting directly in the directory
            // takes precedence over the same slug nested a level deeper.
            $found[$manifest->slug] ??= $manifest;
        }

        return $this->cache = $found;
    }

    public function forget(): void
    {
        $this->cache = null;
    }

    /**
     * @return array<int, string>
     */
    private function manifestFiles(): array
    {
        return [
            ...(glob($this->directory.'/*/template.json') ?: []),
            ...(glob($this->directory.'/*/*/template.json') ?: []),
        ];
    }

    private function parse(string $file): TemplateManifest
    {
        $raw = (string) file_get_contents($file);

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidManifest(
                "Template manifest [{$file}] is not valid JSON: ".$e->getMessage(), 0, $e
            );
        }

        $directory = dirname($file);
        $slug = (string) ($data['slug'] ?? basename($directory));

        if ($slug !== basename($directory)) {
            throw new InvalidManifest(
                "Template [{$slug}] lives in a directory named [".basename($directory).'], but a bundle\'s '
                .'slug and folder name have to match so it can be found by name.'
            );
        }

        return new TemplateManifest($slug, $directory, $data, $this->label);
    }
}
