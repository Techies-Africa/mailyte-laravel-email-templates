<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
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

    public function schema(): array
    {
        return [
            'code' => Prop::text('Code', 'The verification code or reference, set large and spaced out.', required: true),
            'label' => Prop::text('Label', 'A line above the code saying what it is.'),
            'note' => Prop::text('Note', 'A quieter line below -- when it expires, what to do if it was not you.'),
            'align' => Prop::enum('Alignment', ['left', 'center'], 'center'),
            'background' => Prop::color('Background'),
            'color' => Prop::color('Code colour'),
            'muted_color' => Prop::color('Note colour'),
            'space_above' => Prop::spacing('Space above'),
            'space_below' => Prop::spacing('Space below'),
        ];
    }

    public function normalize(array $props, Theme $theme): array
    {
        return [
            'code' => $this->string($props, 'code'),
            'align' => $this->enum($props, 'align', ['left', 'center'], 'center'),
            'label' => $this->string($props, 'label'),
            'note' => $this->string($props, 'note'),
            'background' => $this->string($props, 'background', (string) $theme->get('color.surface_alt')),
            'color' => $this->string($props, 'color', (string) $theme->get('color.text')),
            'muted_color' => $this->string($props, 'muted_color', (string) $theme->get('color.text_muted')),
            'radius' => (string) $theme->get('radius.lg'),
            'type' => $theme->get('type.code', ['size' => '32px', 'line_height' => '40px', 'weight' => '700', 'letter_spacing' => '0.18em']),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
