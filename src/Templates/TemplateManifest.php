<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Templates;

use Illuminate\Support\Arr;

/**
 * A parsed template.json, paired with the directory it came from.
 *
 * Bundles are self-contained folders: everything a template needs -- manifest,
 * markup, plain-text part, styles, sample data, preview -- lives together, and
 * dropping the folder into a templates directory is the entire install step.
 * There is no registration call and no compiled index to rebuild.
 */
final class TemplateManifest
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $directory,
        public readonly array $data,
        public readonly string $source = 'package',
    ) {}

    public function name(): string
    {
        return (string) ($this->data['name'] ?? $this->slug);
    }

    public function description(): string
    {
        return (string) ($this->data['description'] ?? '');
    }

    public function version(): string
    {
        return (string) ($this->data['version'] ?? '0.0.0');
    }

    public function type(): string
    {
        return (string) ($this->data['type'] ?? 'transactional');
    }

    public function category(): string
    {
        return (string) ($this->data['category'] ?? 'notifications');
    }

    public function tier(): string
    {
        return (string) ($this->data['tier'] ?? 'community');
    }

    public function tone(): string
    {
        return (string) ($this->data['tone'] ?? 'neutral');
    }

    /**
     * @return array<int, string>
     */
    public function supportedLayouts(): array
    {
        $layouts = $this->data['supported_layouts'] ?? ['branded'];

        return is_array($layouts) ? array_values(array_map('strval', $layouts)) : ['branded'];
    }

    public function supportsLayout(string $layout): bool
    {
        return in_array($layout, $this->supportedLayouts(), true);
    }

    /**
     * @return array<int, string>
     */
    public function audience(): array
    {
        $audience = $this->data['audience'] ?? [];

        return is_array($audience) ? array_values(array_map('strval', $audience)) : [];
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        $tags = $this->data['tags'] ?? [];

        return is_array($tags) ? array_values(array_map('strval', $tags)) : [];
    }

    public function subject(): string
    {
        return (string) ($this->data['subject'] ?? '');
    }

    public function preheader(): string
    {
        return (string) ($this->data['preheader'] ?? '');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function variables(): array
    {
        $variables = $this->data['variables'] ?? [];

        return is_array($variables) ? $variables : [];
    }

    /**
     * Defaults declared in the manifest, which is what lets a template render
     * with no data at all. Every visible string is a variable with a default,
     * so rewording never means editing markup.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->variables() as $name => $spec) {
            if (is_array($spec) && array_key_exists('default', $spec)) {
                Arr::set($defaults, $name, $spec['default']);
            }
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    public function examples(): array
    {
        $examples = [];

        foreach ($this->variables() as $name => $spec) {
            if (! is_array($spec)) {
                continue;
            }

            if (array_key_exists('example', $spec)) {
                Arr::set($examples, $name, $spec['example']);
            } elseif (array_key_exists('default', $spec)) {
                Arr::set($examples, $name, $spec['default']);
            }
        }

        return $examples;
    }

    /**
     * @return array<int, string>
     */
    public function requiredVariables(): array
    {
        $required = [];

        foreach ($this->variables() as $name => $spec) {
            if (is_array($spec) && ($spec['required'] ?? false) === true) {
                $required[] = (string) $name;
            }
        }

        return $required;
    }

    /**
     * @return array<string, string>
     */
    public function suggestedHeaders(): array
    {
        $headers = Arr::get($this->data, 'headers.suggested', []);

        return is_array($headers) ? array_map('strval', $headers) : [];
    }

    /**
     * @return array<string, string> rule id => reason
     */
    public function lintWaivers(): array
    {
        $waivers = [];

        foreach ((array) Arr::get($this->data, 'lint.ignore', []) as $entry) {
            if (is_array($entry) && isset($entry['rule'], $entry['reason'])) {
                $waivers[(string) $entry['rule']] = (string) $entry['reason'];
            }
        }

        return $waivers;
    }

    public function isDeprecated(): bool
    {
        return ($this->data['status'] ?? 'active') === 'deprecated';
    }

    public function path(string $file): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.$file;
    }

    public function has(string $file): bool
    {
        return is_file($this->path($file));
    }

    public function read(string $file): ?string
    {
        return $this->has($file) ? (string) file_get_contents($this->path($file)) : null;
    }

    public function html(): string
    {
        return $this->read('email.html') ?? '';
    }

    public function text(): ?string
    {
        return $this->read('email.txt');
    }

    public function styles(): ?string
    {
        return $this->read('styles.css');
    }

    /**
     * Named sample-data variants, used by the preview gallery, the render
     * smoke tests and the snapshot suite. A bundle that ships only "default"
     * is fine; edge-case variants are what catch layout breakage.
     *
     * @return array<string, array<string, mixed>>
     */
    public function samples(): array
    {
        $raw = $this->read('sample.json');

        if ($raw === null) {
            return ['default' => $this->examples()];
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        // Either {"default": {...}, "long-name": {...}} or a bare data object.
        $looksLikeVariants = $decoded !== [] && array_reduce(
            array_values($decoded),
            static fn (bool $carry, mixed $value): bool => $carry && is_array($value),
            true
        );

        return $looksLikeVariants ? $decoded : ['default' => $decoded];
    }
}
