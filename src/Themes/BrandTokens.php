<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Themes;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * Turns `mailyte.brand` config into theme token overrides.
 *
 * The logo, the social accounts, the postal address and which footer sections
 * appear are facts about an application, not design decisions — so they belong
 * in config, where they are set once, rather than in every theme and every
 * template bundle.
 *
 * They are applied over the theme and over a template's own design, because a
 * template has no business deciding whose logo is at the top. Per-send
 * `->theme()` overrides still win over these, which is what makes per-tenant
 * branding work.
 */
final class BrandTokens
{
    public function __construct(private readonly Config $config) {}

    /**
     * @return array<string, mixed>
     */
    public function toTokens(): array
    {
        /** @var array<string, mixed> $brand */
        $brand = (array) $this->config->get('mailyte.brand', []);

        $tokens = [];

        $this->put($tokens, 'logo.url', $brand['logo']['url'] ?? null);
        $this->put($tokens, 'logo.dark_url', $brand['logo']['dark_url'] ?? null);
        $this->put($tokens, 'logo.alt', $brand['logo']['alt'] ?? null);
        $this->put($tokens, 'logo.width', $brand['logo']['width'] ?? null);
        $this->put($tokens, 'logo.align', $brand['logo']['align'] ?? null);

        // An empty array is a legitimate "no accounts", but it is also what an
        // untouched config looks like, so only a non-empty list overrides.
        if (! empty($brand['social'])) {
            $tokens['social'] = $brand['social'];
        }

        $this->put($tokens, 'footer.social_icon_base', $brand['social_icons']['base_url'] ?? null);
        $this->put($tokens, 'footer.social_style', $brand['social_icons']['style'] ?? null);
        $this->put($tokens, 'footer.social_icon_ink', $brand['social_icons']['ink'] ?? null);
        $this->put($tokens, 'footer.social_icon_size', $brand['social_icons']['size'] ?? null);

        foreach ([
            'address', 'legal', 'reason', 'copyright',
            'unsubscribe_text', 'preferences_text',
            'show_social', 'show_address', 'show_copyright', 'show_reason', 'show_unsubscribe',
        ] as $key) {
            $this->put($tokens, "footer.{$key}", $brand['footer'][$key] ?? null);
        }

        return $tokens;
    }

    /**
     * @param  array<string, mixed>  $tokens
     */
    private function put(array &$tokens, string $path, mixed $value): void
    {
        // null means "leave it to the theme", which is different from false.
        if ($value === null || $value === '') {
            return;
        }

        $tokens[$path] = $value;
    }
}
