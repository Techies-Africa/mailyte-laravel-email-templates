<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * Picture on one side, words on the other -- the row newsletters are built out
 * of, alternating sides down the page.
 *
 * `reverse` swaps the sides, and on mobile the cells stack in source order --
 * so a reversed row leads with its text rather than its picture. That is the
 * honest consequence of stacking and it stays consistent within a row, which
 * matters more than forcing every row to lead the same way.
 */
final class MediaBlock extends Block
{
    public function name(): string
    {
        return 'media';
    }

    public function normalize(array $props, Theme $theme): array
    {
        $width = (int) str_replace('px', '', (string) $theme->get('layout.width', '600px'));
        $gutter = (int) str_replace('px', '', (string) $theme->get('layout.gutter', '24px'));
        $inner = $width - 2 * $gutter;
        $imagePercent = max(30, min(60, (int) ($props['image_percent'] ?? 42)));
        $imageCell = (int) floor($inner * $imagePercent / 100);

        return [
            'image' => $this->url($props, 'image'),
            'image_alt' => $this->string($props, 'image_alt'),
            'eyebrow' => $this->string($props, 'eyebrow'),
            'title' => $this->string($props, 'title'),
            'text' => $this->string($props, 'text'),
            'link_label' => $this->string($props, 'link_label'),
            'link_url' => $this->url($props, 'link_url'),
            'reverse' => $this->bool($props, 'reverse'),
            'image_percent' => $imagePercent,
            'text_percent' => 100 - $imagePercent,
            'image_cell' => $imageCell,
            'image_width' => $imageCell - 16,
            'text_cell' => $inner - $imageCell,
            'radius' => (string) $theme->get('radius.md'),
            'title_color' => (string) $theme->get('color.text'),
            'text_color' => (string) $theme->get('color.text_muted'),
            'accent_color' => (string) $theme->get('color.primary'),
            'muted_color' => (string) $theme->get('color.text_subtle', $theme->get('color.text_muted')),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
