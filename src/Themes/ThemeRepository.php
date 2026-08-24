<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Themes;

use Illuminate\Contracts\Config\Repository as Config;
use Mailyte\EmailTemplates\Exceptions\ThemeNotFound;

/**
 * Loads themes from disk, resolving `extends` inheritance.
 *
 * Lookup order mirrors Laravel's view path precedence: a theme published into
 * the application wins over the one the package ships under the same name.
 */
final class ThemeRepository
{
    /** @var array<string, Theme> */
    private array $resolved = [];

    /** @var array<int, string> */
    private array $paths;

    public function __construct(private readonly Config $config)
    {
        $this->paths = array_values(array_filter([
            $this->publishedPath(),
            realpath(__DIR__.'/../../resources/themes') ?: null,
        ]));
    }

    public function default(): Theme
    {
        return $this->get((string) $this->config->get('mailyte.theme', 'neutral'));
    }

    public function get(string $name): Theme
    {
        return $this->resolved[$name] ??= $this->load($name, []);
    }

    public function exists(string $name): bool
    {
        return $this->locate($name) !== null;
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        $names = [];

        foreach ($this->paths as $path) {
            foreach (glob($path.'/*/theme.json') ?: [] as $file) {
                $names[] = basename(dirname($file));
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  array<int, string>  $seen  guards against an `extends` cycle
     */
    private function load(string $name, array $seen): Theme
    {
        if (in_array($name, $seen, true)) {
            $chain = implode(' -> ', [...$seen, $name]);

            throw new ThemeNotFound("Theme [{$name}] extends itself: {$chain}");
        }

        $file = $this->locate($name);

        if ($file === null) {
            $available = implode(', ', $this->names());

            throw new ThemeNotFound("Theme [{$name}] not found. Available themes: {$available}");
        }

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        $tokens = is_array($data['tokens'] ?? null) ? $data['tokens'] : [];

        if (is_string($data['extends'] ?? null)) {
            $parent = $this->load($data['extends'], [...$seen, $name]);
            $tokens = $this->mergeTokens($parent->toArray(), $tokens);
        }

        return Theme::make($name, $tokens);
    }

    /**
     * Deep merge where the child wins, but a child that overrides only the
     * light half of a colour pair does not silently drop the dark half.
     *
     * @param  array<string, mixed>  $parent
     * @param  array<string, mixed>  $child
     * @return array<string, mixed>
     */
    private function mergeTokens(array $parent, array $child): array
    {
        foreach ($child as $key => $value) {
            if (is_array($value) && isset($parent[$key]) && is_array($parent[$key]) && ! array_is_list($value)) {
                $parent[$key] = $this->mergeTokens($parent[$key], $value);

                continue;
            }

            $parent[$key] = $value;
        }

        return $parent;
    }

    private function locate(string $name): ?string
    {
        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $name) !== 1) {
            throw new ThemeNotFound("Theme name [{$name}] is not a valid slug.");
        }

        foreach ($this->paths as $path) {
            $candidate = $path.'/'.$name.'/theme.json';

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function publishedPath(): ?string
    {
        if (! function_exists('resource_path')) {
            return null;
        }

        $path = resource_path('views/vendor/mailyte/themes');

        return is_dir($path) ? $path : null;
    }
}
