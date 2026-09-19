<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

final class ListBlock extends Block
{
    public function name(): string
    {
        return 'list';
    }

    public function schema(): array
    {
        return [
            'items' => Prop::items('Items', [
                'text' => Prop::text('Text', required: true),
                'detail' => Prop::text('Detail', 'A quieter second line under the item.'),
            ], required: true),
            'style' => Prop::enum('Marker', ['bullet', 'number', 'check', 'plain'], 'bullet'),
            'color' => Prop::color('Text colour'),
            'muted_color' => Prop::color('Detail colour'),
            'space_below' => Prop::spacing('Space below'),
        ];
    }

    public function normalize(array $props, Theme $theme): array
    {
        $items = [];

        foreach ($this->list($props, 'items') as $item) {
            if (is_scalar($item)) {
                $items[] = ['text' => (string) $item, 'detail' => ''];

                continue;
            }

            if (is_array($item)) {
                $items[] = [
                    'text' => isset($item['text']) && is_scalar($item['text']) ? (string) $item['text'] : '',
                    'detail' => isset($item['detail']) && is_scalar($item['detail']) ? (string) $item['detail'] : '',
                ];
            }
        }

        return [
            'items' => $items,
            'style' => $this->enum($props, 'style', ['bullet', 'number', 'check', 'plain'], 'bullet'),
            'type' => $theme->get('type.body', []),
            'color' => $this->string($props, 'color', (string) $theme->get('color.text')),
            'muted_color' => $this->string($props, 'muted_color', (string) $theme->get('color.text_muted')),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
