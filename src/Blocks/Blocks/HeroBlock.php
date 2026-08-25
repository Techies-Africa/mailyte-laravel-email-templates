<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * The opening banner: an eyebrow label, a large headline, an optional
 * sub-line, and either a photo or a tinted panel.
 *
 * Without an image it falls back to the theme's header gradient (with a solid
 * bgcolor fallback for Outlook, which ignores CSS gradients but honours the
 * attribute) rather than a plain flat colour, so a theme that defines one gets
 * a genuine banner treatment for free.
 */
final class HeroBlock extends Block
{
    public function name(): string
    {
        return 'hero';
    }

    public function normalize(array $props, Theme $theme): array
    {
        return [
            'eyebrow' => $this->string($props, 'eyebrow'),
            'title' => $this->string($props, 'title'),
            'subtitle' => $this->string($props, 'subtitle'),
            'image' => $this->url($props, 'image'),
            'image_alt' => $this->string($props, 'image_alt'),
            'align' => $this->enum($props, 'align', ['left', 'center'], 'left'),
            'gradient' => $theme->get('header.gradient'),
            'fallback_color' => (string) $theme->get('color.surface_alt'),
            'accent_color' => (string) $theme->get('color.primary'),
            'text_color' => (string) $theme->get('color.text'),
            'muted_color' => (string) $theme->get('color.text_muted'),
            'radius' => (string) $theme->get('radius.lg'),
            'h1' => $theme->get('type.h1', []),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.lg')),
        ];
    }
}
