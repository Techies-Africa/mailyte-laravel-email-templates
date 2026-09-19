<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
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

    public function fullBleed(array $props = []): bool
    {
        return (bool) ($props['bleed'] ?? false);
    }

    public function schema(): array
    {
        return [
            'src' => Prop::image('Image', required: true),
            'alt' => Prop::text(
                'Alt text',
                'What the image says, for anyone whose client blocks images -- which is most of them, by default.',
                required: true,
            ),
            'href' => Prop::url('Links to', 'Optional. Makes the whole image clickable.'),
            'width' => Prop::length('Width', 'In pixels, without a unit. The content column is 552 wide.'),
            'height' => Prop::length('Height', 'In pixels. Best left empty so the image keeps its proportions.'),
            'align' => Prop::enum('Alignment', ['left', 'center', 'right'], 'center'),
            'radius' => Prop::length('Corner radius'),
            'bleed' => Prop::bool('Full width', description: 'Runs the image to the edges of the message rather than sitting in the gutter.'),
            'space_below' => Prop::spacing('Space below'),
        ];
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
