<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

final class CardBlock extends Block
{
    public function name(): string
    {
        return 'card';
    }

    public function hasSlot(): bool
    {
        return true;
    }

    public function normalize(array $props, Theme $theme): array
    {
        return [
            'slot' => $this->slot($props),
            'background' => $this->string($props, 'background', (string) $theme->get('color.surface_alt')),
            'border_color' => $this->string($props, 'border_color', (string) $theme->get('color.border')),
            'padding' => $this->string($props, 'padding', (string) $theme->get('spacing.md')),
            'radius' => (string) $theme->get('radius.lg'),
            'space_above' => $this->string($props, 'space_above', (string) $theme->get('spacing.md')),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
