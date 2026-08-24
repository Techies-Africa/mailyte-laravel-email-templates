<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * The hidden line mail clients show next to the subject in the inbox list.
 *
 * Aim for 85-100 characters, front-loaded, complementing the subject rather
 * than repeating it. Trailing zero-width spaces stop clients from spilling
 * body copy into the preview when the text is short.
 */
final class PreheaderBlock extends Block
{
    public function name(): string
    {
        return 'preheader';
    }

    public function normalize(array $props, Theme $theme): array
    {
        return [
            'text' => $this->string($props, 'text'),
        ];
    }
}
