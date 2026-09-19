<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * A one-line inset bar: a mark, then a sentence.
 *
 * The reference emails use this constantly -- the date of a webinar, a
 * compliance line, "reply to this email and we'll get back to you". It is a
 * quieter thing than a status banner, which shouts severity; this one just
 * holds a single fact where the eye will land on it.
 */
final class NoteBlock extends Block
{
    public function name(): string
    {
        return 'note';
    }

    public function schema(): array
    {
        return [
            'text' => Prop::richtext('Text', required: true),
            'strong_text' => Prop::text('Lead-in', 'Set bold at the start of the note -- "Heads up:".'),
            'mark' => Prop::text('Marker', 'A short word or symbol set in the accent colour beside the text.'),
            'icon_url' => Prop::image('Icon'),
            'tone' => Prop::enum('Treatment', ['soft', 'outline', 'plain'], 'soft', 'Soft fills the panel, outline rules it, plain does neither.'),
            'align' => Prop::enum('Alignment', ['left', 'center'], 'left'),
            'background' => Prop::color('Background', 'Only used by the soft treatment.'),
            'border_color' => Prop::color('Border colour', 'Ignored by the plain treatment, which has no border.'),
            'text_color' => Prop::color('Text colour'),
            'mark_color' => Prop::color('Marker colour'),
            'space_above' => Prop::spacing('Space above'),
            'space_below' => Prop::spacing('Space below'),
        ];
    }

    public function normalize(array $props, Theme $theme): array
    {
        $tone = $this->enum($props, 'tone', ['soft', 'outline', 'plain'], 'soft');

        return [
            'text' => $this->string($props, 'text'),
            'strong_text' => $this->string($props, 'strong_text'),
            'mark' => $this->string($props, 'mark'),
            'icon_url' => $this->url($props, 'icon_url'),
            'tone' => $tone,
            'align' => $this->enum($props, 'align', ['left', 'center'], 'left'),
            'background' => $tone === 'soft'
                ? $this->string($props, 'background', (string) $theme->get('color.surface_alt'))
                : 'transparent',
            'border_color' => $tone === 'plain' ? '' : $this->string($props, 'border_color', (string) $theme->get('color.border')),
            'text_color' => $this->string($props, 'text_color', (string) $theme->get('color.text')),
            'mark_color' => $this->string($props, 'mark_color', (string) $theme->get('color.primary')),
            'radius' => (string) $theme->get('radius.md'),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
