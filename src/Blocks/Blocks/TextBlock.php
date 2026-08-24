<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

final class TextBlock extends Block
{
    public function name(): string
    {
        return 'text';
    }

    public function hasSlot(): bool
    {
        return true;
    }

    public function normalize(array $props, Theme $theme): array
    {
        $size = $this->enum($props, 'size', ['body', 'small', 'footer'], 'body');

        return [
            'text' => $this->string($props, 'text'),
            'size' => $size,
            'type' => $theme->get("type.{$size}", []),
            'align' => $this->enum($props, 'align', ['left', 'center', 'right'], 'left'),
            'muted' => $this->bool($props, 'muted'),
            'color' => $this->string(
                $props,
                'color',
                (string) $theme->get($this->bool($props, 'muted') ? 'color.text_muted' : 'color.text')
            ),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.sm')),
        ];
    }
}
