<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * Two to four numbers shown large, for a summary or a year-in-review.
 *
 * Distinct from KeyValueBlock, which is a reference table: this is for figures
 * meant to be read at a glance and remembered, so the number leads and the
 * label supports it. A caption line is available for the comparison that makes
 * a number mean something ("up from 812 last month").
 */
final class StatRowBlock extends Block
{
    public function name(): string
    {
        return 'stat_row';
    }

    public function schema(): array
    {
        return [
            'items' => Prop::items('Figures', [
                'value' => Prop::text('Figure', 'The number itself, formatted -- "1,204", "98.2%".', required: true),
                'label' => Prop::text('Label', required: true),
                'caption' => Prop::text('Caption', 'The comparison that makes the figure mean something.'),
            ], 'At most four. A fifth is dropped rather than squeezed.', required: true),
            'size' => Prop::enum('Size', ['regular', 'display'], 'regular'),
            'align' => Prop::enum('Alignment', ['left', 'center'], 'left'),
            'boxed' => Prop::bool('In a panel', description: 'Sets the figures on their own background.'),
            'background' => Prop::color('Panel background'),
            'value_color' => Prop::color('Figure colour'),
            'label_color' => Prop::color('Label colour', 'Worth setting when this sits inside a dark band: the slot is rendered before the band wraps it, so it cannot inherit the band\'s text colour.'),
            'caption_color' => Prop::color('Caption colour'),
            'space_above' => Prop::spacing('Space above'),
            'space_below' => Prop::spacing('Space below'),
        ];
    }

    public function normalize(array $props, Theme $theme): array
    {
        $items = [];
        foreach ($this->list($props, 'items') as $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[] = [
                'value' => isset($item['value']) && is_scalar($item['value']) ? (string) $item['value'] : '',
                'label' => isset($item['label']) && is_scalar($item['label']) ? (string) $item['label'] : '',
                'caption' => isset($item['caption']) && is_scalar($item['caption']) ? (string) $item['caption'] : '',
            ];
        }

        $items = array_slice($items, 0, 4);
        $count = max(1, count($items));

        $width = (int) str_replace('px', '', (string) $theme->get('layout.width', '600px'));
        $gutter = (int) str_replace('px', '', (string) $theme->get('layout.gutter', '24px'));

        return [
            'items' => $items,
            'count' => $count,
            'width_percent' => (int) floor(100 / $count),
            'ghost_width' => (int) floor(($width - 2 * $gutter) / $count),
            'value_size' => $this->enum($props, 'size', ['regular', 'display'], 'regular') === 'display'
                ? ($count > 1 ? '40px' : '56px')
                : ($count >= 4 ? '24px' : ($count === 3 ? '28px' : '32px')),
            'boxed' => $this->bool($props, 'boxed'),
            'background' => $this->string($props, 'background', (string) $theme->get('color.surface_alt')),
            'border_color' => (string) $theme->get('color.border'),
            'value_color' => $this->string($props, 'value_color', (string) $theme->get('color.text')),
            // Overridable because a block dropped into a dark section cannot
            // inherit that section's ink -- the slot is already rendered by the
            // time the section wraps it, so colour has to arrive as a prop.
            'label_color' => $this->string($props, 'label_color', (string) $theme->get('color.text_muted')),
            'caption_color' => $this->string($props, 'caption_color', (string) $theme->get('color.text_muted')),
            'radius' => (string) $theme->get('radius.md'),
            'align' => $this->enum($props, 'align', ['left', 'center'], 'left'),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
