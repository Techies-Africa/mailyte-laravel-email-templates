<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

final class DividerBlock extends Block
{
    public function name(): string
    {
        return 'divider';
    }

    public function schema(): array
    {
        return [
            'style' => Prop::enum('Style', ['solid', 'dotted', 'dashed', 'double', 'thick'], 'solid'),
            'width' => Prop::length('Width', 'How far across it runs -- "100%" or a fixed length.'),
            'align' => Prop::enum('Alignment', ['left', 'center'], 'left'),
            'color' => Prop::color('Colour'),
            'space_above' => Prop::spacing('Space above'),
            'space_below' => Prop::spacing('Space below'),
        ];
    }

    public function normalize(array $props, Theme $theme): array
    {
        return [
            'color' => $this->string($props, 'color', (string) $theme->get('color.border')),
            'style' => $this->enum($props, 'style', ['solid', 'dotted', 'dashed', 'double', 'thick'], 'solid'),
            'width' => $this->string($props, 'width', '100%'),
            'align' => $this->enum($props, 'align', ['left', 'center'], 'left'),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
