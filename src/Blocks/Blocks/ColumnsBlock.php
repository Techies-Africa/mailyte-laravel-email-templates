<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * A 2 or 3 column row, for feature highlights or step summaries.
 *
 * Built as fluid inline-block cells with an Outlook ghost table holding fixed
 * pixel widths -- Outlook's Word engine does not support inline-block sizing,
 * so without the ghost table the columns collapse to one on Windows desktop.
 * Stacks to one column under 600px via the .m-stack class in ThemeCompiler.
 */
final class ColumnsBlock extends Block
{
    public function name(): string
    {
        return 'columns';
    }

    public function schema(): array
    {
        return [
            'items' => Prop::items('Columns', [
                'icon' => Prop::text('Icon', 'A character or short symbol above the heading.'),
                'heading' => Prop::text('Heading', required: true),
                'text' => Prop::richtext('Text'),
            ], required: true),
            'count' => Prop::number('How many', 2, 3, 'Anything past the count is dropped: four columns are unreadable on a phone.', Prop::CONTENT),
            'space_below' => Prop::spacing('Space below'),
        ];
    }

    public function normalize(array $props, Theme $theme): array
    {
        $count = max(2, min(3, (int) ($props['count'] ?? 2)));

        $items = [];

        foreach ($this->list($props, 'items') as $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[] = [
                'icon' => isset($item['icon']) && is_scalar($item['icon']) ? (string) $item['icon'] : '',
                'heading' => isset($item['heading']) && is_scalar($item['heading']) ? (string) $item['heading'] : '',
                'text' => isset($item['text']) && is_scalar($item['text']) ? (string) $item['text'] : '',
            ];
        }

        return [
            'count' => $count,
            'items' => array_slice($items, 0, $count),
            'width_percent' => (int) floor(100 / $count),
            'ghost_width' => (int) floor((((int) str_replace('px', '', (string) $theme->get('layout.width', '600px'))) - 2 * ((int) str_replace('px', '', (string) $theme->get('layout.gutter', '24px')))) / $count),
            'gutter' => (string) $theme->get('spacing.sm'),
            'heading_color' => (string) $theme->get('color.text'),
            'text_color' => (string) $theme->get('color.text_muted'),
            'accent_color' => (string) $theme->get('color.primary'),
            'type_heading' => $theme->get('type.small', []),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
