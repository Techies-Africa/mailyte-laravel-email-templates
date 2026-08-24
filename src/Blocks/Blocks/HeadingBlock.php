<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

final class HeadingBlock extends Block
{
    public function name(): string
    {
        return 'heading';
    }

    public function normalize(array $props, Theme $theme): array
    {
        $level = $this->enum($props, 'level', ['1', '2', '3'], '1');

        return [
            'text' => $this->string($props, 'text'),
            'level' => $level,
            'align' => $this->enum($props, 'align', ['left', 'center', 'right'], 'left'),
            'type' => $theme->get("type.h{$level}", []),
            'color' => $this->string($props, 'color', (string) $theme->get('color.text')),
            'space_above' => $this->string($props, 'space_above', (string) $theme->get('spacing.md')),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.sm')),
        ];
    }
}
