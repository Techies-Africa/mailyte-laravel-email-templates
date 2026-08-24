<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * Explicit width and height are required rather than optional: Outlook sizes
 * images badly without them, and clients that block images by default reserve
 * the wrong space, which shoves the rest of the layout around.
 */
final class ImageBlock extends Block
{
    public function name(): string
    {
        return 'image';
    }

    public function normalize(array $props, Theme $theme): array
    {
        return [
            'src' => $this->url($props, 'src'),
            'alt' => $this->string($props, 'alt'),
            'width' => $this->string($props, 'width', '552'),
            'height' => $this->string($props, 'height'),
            'href' => $this->url($props, 'href'),
            'align' => $this->enum($props, 'align', ['left', 'center', 'right'], 'center'),
            'radius' => $this->string($props, 'radius', (string) $theme->get('radius.lg')),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
