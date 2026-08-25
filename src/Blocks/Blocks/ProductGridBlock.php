<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * Two or three product cards in a row: picture, name, a line of meta, a price
 * and a link.
 *
 * Cells are real table cells with an Outlook ghost table, the same construction
 * as ColumnsBlock -- see the note there for why inline-block loses. Images are
 * given explicit pixel widths because a card whose picture has no dimensions
 * jumps the moment images load, and the card next to it jumps with it.
 */
final class ProductGridBlock extends Block
{
    public function name(): string
    {
        return 'product_grid';
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
                'image' => isset($item['image']) && is_scalar($item['image']) ? (string) $item['image'] : '',
                'title' => isset($item['title']) && is_scalar($item['title']) ? (string) $item['title'] : '',
                'meta' => isset($item['meta']) && is_scalar($item['meta']) ? (string) $item['meta'] : '',
                'price' => isset($item['price']) && is_scalar($item['price']) ? (string) $item['price'] : '',
                'was_price' => isset($item['was_price']) && is_scalar($item['was_price']) ? (string) $item['was_price'] : '',
                'badge' => isset($item['badge']) && is_scalar($item['badge']) ? (string) $item['badge'] : '',
                'url' => isset($item['url']) && is_scalar($item['url']) ? (string) $item['url'] : '',
            ];
        }

        $width = (int) str_replace('px', '', (string) $theme->get('layout.width', '600px'));
        $gutter = (int) str_replace('px', '', (string) $theme->get('layout.gutter', '24px'));
        $cell = (int) floor(($width - 2 * $gutter) / $count);

        $items = array_slice($items, 0, $count);

        return [
            'count' => $count,
            'items' => $items,
            'has_badges' => array_filter(array_column($items, 'badge')) !== [],
            'width_percent' => (int) floor(100 / $count),
            'ghost_width' => $cell,
            'image_width' => $cell - 14,
            'image_height' => (int) round(($cell - 14) * 0.75),
            'gutter' => '14px',
            'link_label' => $this->string($props, 'link_label', 'View'),
            'show_links' => $this->bool($props, 'show_links', true),
            'title_color' => (string) $theme->get('color.text'),
            'meta_color' => (string) $theme->get('color.text_muted'),
            'price_color' => (string) $theme->get('color.text'),
            'was_color' => (string) $theme->get('color.text_muted'),
            'accent_color' => (string) $theme->get('color.primary'),
            'badge_bg' => (string) $theme->get('color.surface_alt'),
            'border_color' => (string) $theme->get('color.border'),
            'radius' => (string) $theme->get('radius.md'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
