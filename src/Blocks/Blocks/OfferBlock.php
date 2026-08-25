<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * The promotional panel: a large claim, an optional copyable code, a CTA and
 * the terms.
 *
 * `expires` and `terms` are separate props rather than one blob because an
 * offer email that hides its expiry is the kind of email that gets a brand
 * reported. The deadline renders in the panel; the small print renders under
 * it, and both are plain text so neither disappears when images are blocked.
 *
 * The dark tone is a solid colour, not a gradient. Outlook drops the gradient
 * and Gmail's dark-mode invert recolours it unpredictably; a flat panel looks
 * the same everywhere and keeps the code legible, which is the entire job.
 */
final class OfferBlock extends Block
{
    public function name(): string
    {
        return 'offer';
    }

    public function fullBleed(array $props = []): bool
    {
        return (bool) ($props['bleed'] ?? false);
    }

    public function normalize(array $props, Theme $theme): array
    {
        $tone = $this->enum($props, 'tone', ['accent', 'dark', 'light'], 'accent');

        $background = match ($tone) {
            'dark' => $this->string($props, 'background', '#14161A'),
            'light' => $this->string($props, 'background', (string) $theme->get('color.surface_alt')),
            default => $this->string($props, 'background', (string) $theme->get('color.primary')),
        };

        $ink = match ($tone) {
            'light' => (string) $theme->get('color.text'),
            'dark' => '#FFFFFF',
            default => (string) $theme->get('color.primary_text'),
        };

        return [
            'tone' => $tone,
            'eyebrow' => $this->string($props, 'eyebrow'),
            'headline' => $this->string($props, 'headline'),
            'text' => $this->string($props, 'text'),
            'code' => $this->string($props, 'code'),
            'code_label' => $this->string($props, 'code_label', 'Use code'),
            'expires' => $this->string($props, 'expires'),
            'terms' => $this->string($props, 'terms'),
            'button_label' => $this->string($props, 'button_label'),
            'button_url' => $this->url($props, 'button_url'),
            'background' => $background,
            'ink' => $ink,
            'button_bg' => $this->string($props, 'button_background', $tone === 'light' ? (string) $theme->get('color.primary') : $ink),
            'button_ink' => $this->string($props, 'button_color', $tone === 'light' ? (string) $theme->get('color.primary_text') : $background),
            'chip_border' => $tone === 'light' ? (string) $theme->get('color.border') : 'rgba(255,255,255,.45)',
            'muted_ink' => $tone === 'light' ? (string) $theme->get('color.text_muted') : 'rgba(255,255,255,.78)',
            'mono_font' => (string) $theme->get('font.mono'),
            'radius' => (string) $theme->get('radius.lg'),
            'button_radius' => (string) $theme->get('radius.md'),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
