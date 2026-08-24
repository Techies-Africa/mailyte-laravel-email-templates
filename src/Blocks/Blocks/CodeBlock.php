<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * One-time code / OTP display.
 *
 * Live text in a tinted card, never an image -- an image is unreadable to
 * screen readers, unselectable, and blocked by default in several clients,
 * which is a poor fate for the one string the reader actually needs.
 */
final class CodeBlock extends Block
{
    public function name(): string
    {
        return 'code';
    }

    public function normalize(array $props, Theme $theme): array
    {
        return [
            'code' => $this->string($props, 'code'),
            'label' => $this->string($props, 'label'),
            'note' => $this->string($props, 'note'),
            'background' => $this->string($props, 'background', (string) $theme->get('color.surface_alt')),
            'color' => $this->string($props, 'color', (string) $theme->get('color.text')),
            'radius' => (string) $theme->get('radius.lg'),
            'type' => $theme->get('type.code', ['size' => '32px', 'line_height' => '40px', 'weight' => '700', 'letter_spacing' => '0.18em']),
            'space_above' => $this->string($props, 'space_above', (string) $theme->get('spacing.md')),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
