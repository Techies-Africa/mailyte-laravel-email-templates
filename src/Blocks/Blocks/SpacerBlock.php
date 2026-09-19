<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

final class SpacerBlock extends Block
{
    public function name(): string
    {
        return 'spacer';
    }

    public function schema(): array
    {
        return [
            'height' => Prop::spacing('Height'),
        ];
    }

    public function normalize(array $props, Theme $theme): array
    {
        return [
            'height' => $this->string($props, 'height', (string) $theme->get('spacing.md')),
        ];
    }
}
