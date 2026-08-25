<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * A pull quote or customer testimonial, with the attribution attached to it.
 *
 * Rendered as a real <blockquote> with a left rule rather than decorative
 * quotation-mark images: the rule survives image blocking, and screen readers
 * announce the quotation rather than reading a stray glyph.
 */
final class QuoteBlock extends Block
{
    public function name(): string
    {
        return 'quote';
    }

    public function normalize(array $props, Theme $theme): array
    {
        return [
            'text' => $this->string($props, 'text'),
            'author' => $this->string($props, 'author'),
            'role' => $this->string($props, 'role'),
            'avatar' => $this->url($props, 'avatar'),
            'align' => $this->enum($props, 'align', ['left', 'center'], 'left'),
            'size' => $this->enum($props, 'size', ['regular', 'large'], 'regular'),
            'accent_color' => $this->string($props, 'accent_color', (string) $theme->get('color.primary')),
            'text_color' => $this->string($props, 'text_color', (string) $theme->get('color.text')),
            'muted_color' => $this->string($props, 'muted_color', (string) $theme->get('color.text_muted')),
            'background' => $this->string($props, 'background', 'transparent'),
            'radius' => (string) $theme->get('radius.lg'),
            'font_heading' => (string) $theme->get('font.heading'),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
