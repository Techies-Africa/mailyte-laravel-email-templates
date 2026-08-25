<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

final class DividerBlock extends Block
{
    public function name(): string
    {
        return 'divider';
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
