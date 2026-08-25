<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Themes;

use Mailyte\EmailTemplates\Exceptions\InvalidThemeOverride;

/**
 * Validates runtime theme overrides.
 *
 * Per-tenant branding is attacker-adjacent input: it typically originates from
 * a form in someone's settings page. A colour token ends up inside a style
 * attribute and a logo token inside src, so both are sanitized before they can
 * reach the rendered document.
 */
final class TokenSanitizer
{
    private const COLOR_PATTERN = '/^(#[0-9a-fA-F]{3,8}|rgba?\(\s*[\d.\s,%\/]+\)|hsla?\(\s*[\d.\s,%\/deg]+\)|transparent|inherit)$/';

    /** Tokens whose values are rendered as URLs rather than CSS. */
    private const URL_TOKENS = ['logo.url', 'logo.dark_url', 'header.logo_url'];

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{tokens: array<string, mixed>, warnings: array<int, string>}
     */
    public function sanitize(array $overrides): array
    {
        $clean = [];
        $warnings = [];

        foreach ($overrides as $path => $value) {
            if ($path === '') {
                throw new InvalidThemeOverride('Theme override keys must be dot-path strings.');
            }

            if (str_starts_with($path, 'color.') || str_ends_with($path, '_color')) {
                $clean[$path] = $this->color($path, $value, $warnings);

                continue;
            }

            if (in_array($path, self::URL_TOKENS, true) || str_ends_with($path, '.url')) {
                $clean[$path] = $this->url($path, $value);

                continue;
            }

            if (is_scalar($value) || is_array($value) || $value === null) {
                $clean[$path] = $value;

                continue;
            }

            throw new InvalidThemeOverride(
                "Theme override [{$path}] must be a scalar, array or null, ".get_debug_type($value).' given.'
            );
        }

        return ['tokens' => $clean, 'warnings' => $warnings];
    }

    /**
     * Loopback and developer TLDs, which cannot resolve for a recipient.
     */
    private function isLocalHost(string $host): bool
    {
        $host = strtolower($host);

        return in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.localhost')
            || preg_match('/^(?:10|127)\./', $host) === 1
            || preg_match('/^192\.168\./', $host) === 1;
    }

    /**
     * @param  array<int, string>  $warnings
     * @return string|array<string, string>
     */
    private function color(string $path, mixed $value, array &$warnings): string|array
    {
        if (is_array($value)) {
            $pair = [];

            foreach (['light', 'dark'] as $scheme) {
                if (isset($value[$scheme])) {
                    $pair[$scheme] = $this->color($path.'.'.$scheme, $value[$scheme], $warnings);
                }
            }

            return $pair;
        }

        if (! is_string($value) || preg_match(self::COLOR_PATTERN, trim($value)) !== 1) {
            throw new InvalidThemeOverride(
                "Theme override [{$path}] is not a valid CSS colour: ".var_export($value, true)
            );
        }

        $value = trim($value);

        // Outlook's forced inversion targets pure white most aggressively and
        // tends to leave mid-tones alone, so pure #fff/#000 are the values most
        // likely to come back recoloured in ways nobody intended.
        $normalized = strtolower($value);

        if (in_array($normalized, ['#fff', '#ffffff', '#000', '#000000'], true)) {
            $suggestion = str_starts_with($normalized, '#f') ? '#FAFAFA' : '#1A1A1A';
            $warnings[] = "Theme override [{$path}] uses pure {$value}. Outlook's dark-mode inversion "
                ."targets pure white and black most aggressively; {$suggestion} survives it far better.";
        }

        return $value;
    }

    private function url(string $path, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidThemeOverride("Theme override [{$path}] must be a URL string.");
        }

        $parts = parse_url($value);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new InvalidThemeOverride(
                "Theme override [{$path}] must be an absolute URL. Mail clients have no base URL, "
                .'so a relative asset path simply will not load.'
            );
        }

        // A loopback or `.test` host over http is somebody previewing against
        // their own machine, which is the normal way to check a logo before it
        // is hosted anywhere. It can never reach a real recipient, so there is
        // nothing to protect them from -- and blocking it meant the render threw
        // and fell back to Laravel's own rendering, which looks like adoption
        // silently not working. The deliverability audit already exempts these
        // hosts from its own http rule; this matches it.
        if (strtolower($parts['scheme']) !== 'https' && ! $this->isLocalHost($parts['host'])) {
            throw new InvalidThemeOverride(
                "Theme override [{$path}] must use https. Plain http assets are blocked or flagged "
                .'by most mail clients.'
            );
        }

        return $value;
    }
}
