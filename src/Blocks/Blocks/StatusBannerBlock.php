<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * Severity and status indicator that never relies on colour alone.
 *
 * Full-invert clients will happily recolour a red badge to cyan, images are
 * blocked by default in others, and the Email Markup Consortium found 16.91%
 * of emails convey meaning by colour alone. So severity is carried four ways
 * at once: an uppercase word, a real-text glyph (shapes differ, not just
 * hues), a left border weight, and the colour as the last of the four.
 */
final class StatusBannerBlock extends Block
{
    private const LEVELS = ['info', 'success', 'warning', 'danger', 'neutral'];

    public function name(): string
    {
        return 'status_banner';
    }

    public function normalize(array $props, Theme $theme): array
    {
        $level = $this->enum($props, 'level', self::LEVELS, 'info');

        $glyph = match ($level) {
            'success' => "\u{25CF}",
            'warning' => "\u{25B2}",
            'danger' => "\u{25A0}",
            'info' => "\u{25CF}",
            default => "\u{25CB}",
        };

        $accent = match ($level) {
            'success' => (string) $theme->get('color.success'),
            'warning' => (string) $theme->get('color.warning'),
            'danger' => (string) $theme->get('color.danger'),
            'info' => (string) $theme->get('color.info'),
            default => (string) $theme->get('color.text_muted'),
        };

        return [
            'level' => $level,
            'label' => strtoupper($this->string($props, 'label', strtoupper($level))),
            'text' => $this->string($props, 'text'),
            'glyph' => $this->string($props, 'glyph', $glyph),
            'accent' => $accent,
            'background' => $this->string($props, 'background', (string) $theme->get('color.surface_alt')),
            'color' => (string) $theme->get('color.text'),
            'type' => $theme->get('type.body', []),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
