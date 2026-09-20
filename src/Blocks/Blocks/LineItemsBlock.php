<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * An order summary: thumbnail, name, a line of meta, a price, one row per item.
 *
 * Distinct from ProductGridBlock, which sells things across a row. This lists
 * things already bought, so it reads down the page, the prices right-align on
 * tabular numerals, and the thumbnail is small enough that a blocked image
 * leaves the row legible rather than gutted.
 */
final class LineItemsBlock extends Block
{
    public function name(): string
    {
        return 'line_items';
    }

    public function schema(): array
    {
        return [
            'items' => Prop::items('Line items', [
                'title' => Prop::text('Name', required: true),
                'meta' => Prop::text('Detail', 'Quantity, size, variant -- whatever sits under the name.'),
                'price' => Prop::text('Price', 'Formatted and with its currency symbol: the block does no arithmetic.'),
                'image' => Prop::image('Thumbnail'),
                'url' => Prop::url('Links to'),
            ], required: true),
            'show_thumbs' => Prop::bool('Show thumbnails', true, 'Ignored when no item has an image.'),
            'thumb_size' => Prop::number('Thumbnail size', 24, 96, 'In pixels, square.'),
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
                'image' => isset($item['image']) && is_scalar($item['image']) ? (string) $item['image'] : '',
                'title' => isset($item['title']) && is_scalar($item['title']) ? (string) $item['title'] : '',
                'meta' => isset($item['meta']) && is_scalar($item['meta']) ? (string) $item['meta'] : '',
                'price' => isset($item['price']) && is_scalar($item['price']) ? (string) $item['price'] : '',
                'url' => isset($item['url']) && is_scalar($item['url']) ? (string) $item['url'] : '',
            ];
        }

        return [
            'items' => $items,
            'thumb_size' => (int) $this->string($props, 'thumb_size', '56'),
            'show_thumbs' => $this->bool($props, 'show_thumbs', true)
                && array_filter(array_column($items, 'image')) !== [],
            'title_color' => (string) $theme->get('color.text'),
            'meta_color' => (string) $theme->get('color.text_muted'),
            'border_color' => (string) $theme->get('color.border'),
            'radius' => (string) $theme->get('radius.sm'),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
