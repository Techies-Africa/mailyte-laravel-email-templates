<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Themes;

use Illuminate\Support\Arr;

/**
 * An immutable bag of design tokens.
 *
 * A colour token may be either a flat string or a {"light": ..., "dark": ...}
 * pair. Light values are what get inlined; dark values are emitted only into
 * media queries and Outlook.com's [data-ogsc] hooks, never inlined, because
 * inlining them would break every client that does not honour the query.
 */
final class Theme
{
    /**
     * @param  array<string, mixed>  $tokens
     */
    public function __construct(
        public readonly string $name,
        private readonly array $tokens,
    ) {}

    /**
     * @param  array<string, mixed>  $tokens
     */
    public static function make(string $name, array $tokens): self
    {
        return new self($name, $tokens);
    }

    /**
     * Resolve a token by dot path, e.g. "color.primary" or "type.h1.size".
     *
     * Colour pairs collapse to their value for the requested scheme, so callers
     * that don't care about dark mode get a usable string either way.
     */
    public function get(string $path, mixed $default = null, string $scheme = 'light'): mixed
    {
        $value = Arr::get($this->tokens, $path, $default);

        if (is_array($value) && (isset($value['light']) || isset($value['dark']))) {
            return $value[$scheme] ?? $value['light'] ?? $value['dark'] ?? $default;
        }

        return $value;
    }

    /**
     * True when this token differs between light and dark, and therefore needs
     * a media-query rule rather than just an inline style.
     */
    public function hasDarkVariant(string $path): bool
    {
        $value = Arr::get($this->tokens, $path);

        return is_array($value)
            && isset($value['light'], $value['dark'])
            && $value['light'] !== $value['dark'];
    }

    /**
     * Every token path that carries a distinct dark value.
     *
     * @return array<string, array{light: string, dark: string}>
     */
    public function darkVariants(): array
    {
        $found = [];

        $walk = function (array $node, string $prefix) use (&$walk, &$found): void {
            foreach ($node as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

                if (! is_array($value)) {
                    continue;
                }

                if (isset($value['light'], $value['dark'])) {
                    if ($value['light'] !== $value['dark']) {
                        $found[$path] = ['light' => (string) $value['light'], 'dark' => (string) $value['dark']];
                    }

                    continue;
                }

                $walk($value, $path);
            }
        };

        $walk($this->tokens, '');

        return $found;
    }

    /**
     * Apply dot-path overrides, returning a new Theme.
     *
     * This is how per-tenant branding reaches a template at send time without
     * mutating the shared theme instance.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function merge(array $overrides): self
    {
        $tokens = $this->tokens;

        foreach ($overrides as $path => $value) {
            // Overriding a light/dark pair with a bare string keeps the dark
            // variant rather than silently dropping it: a tenant setting a
            // brand colour should not lose dark-mode handling as a side effect.
            $existing = Arr::get($tokens, $path);

            if (is_string($value) && is_array($existing) && isset($existing['light'], $existing['dark'])) {
                $value = ['light' => $value, 'dark' => $value];
            }

            Arr::set($tokens, $path, $value);
        }

        return new self($this->name, $tokens);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->tokens;
    }

    /**
     * A flattened light-scheme view, which is what templates and blocks read.
     *
     * @return array<string, mixed>
     */
    public function flat(string $scheme = 'light'): array
    {
        $flat = [];

        $walk = function (array $node, string $prefix) use (&$walk, &$flat, $scheme): void {
            foreach ($node as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

                if (is_array($value) && isset($value['light'])) {
                    $flat[$path] = $value[$scheme] ?? $value['light'];

                    continue;
                }

                if (is_array($value) && ! array_is_list($value)) {
                    $walk($value, $path);

                    continue;
                }

                $flat[$path] = $value;
            }
        };

        $walk($this->tokens, '');

        return $flat;
    }

    public function fingerprint(): string
    {
        return substr(hash('xxh128', json_encode($this->tokens, JSON_THROW_ON_ERROR)), 0, 16);
    }
}
