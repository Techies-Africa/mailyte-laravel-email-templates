<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * A hero split down the middle: words on one side, picture on the other, both
 * sitting on a colour field that runs to the edges.
 *
 * The alternative -- headline over a photograph -- needs a scrim and still
 * gambles on contrast. This construction never has that problem, which is why
 * so much product email uses it for the opening statement.
 */
final class SplitBlock extends Block
{
    public function name(): string
    {
        return 'split';
    }

    public function fullBleed(array $props = []): bool
    {
        return ! isset($props['inset']) || ! $props['inset'];
    }

    public function normalize(array $props, Theme $theme): array
    {
        $tone = $this->enum($props, 'tone', ['alt', 'accent', 'dark', 'custom'], 'alt');

        $background = match ($tone) {
            'accent' => (string) $theme->get('color.primary'),
            'dark' => $this->string($props, 'background', '#15171A'),
            'custom' => $this->string($props, 'background', (string) $theme->get('color.surface_alt')),
            default => (string) $theme->get('color.surface_alt'),
        };

        $ink = match ($tone) {
            'accent' => (string) $theme->get('color.primary_text'),
            'dark' => $this->string($props, 'text_color', '#FFFFFF'),
            default => (string) $theme->get('color.text'),
        };

        $width = (int) str_replace('px', '', (string) $theme->get('layout.width', '600px'));
        $imagePercent = max(30, min(60, (int) ($props['image_percent'] ?? 45)));

        return [
            'eyebrow' => $this->string($props, 'eyebrow'),
            'title' => $this->string($props, 'title'),
            'text' => $this->string($props, 'text'),
            'image' => $this->url($props, 'image'),
            'image_alt' => $this->string($props, 'image_alt'),
            'button_label' => $this->string($props, 'button_label'),
            'button_url' => $this->url($props, 'button_url'),
            'reverse' => $this->bool($props, 'reverse'),
            'tone' => $tone,
            'background' => $background,
            'ink' => $ink,
            'muted_ink' => $tone === 'alt' ? (string) $theme->get('color.text_muted') : $ink,
            'image_percent' => $imagePercent,
            'text_percent' => 100 - $imagePercent,
            'image_cell' => (int) floor($width * $imagePercent / 100),
            'text_cell' => $width - (int) floor($width * $imagePercent / 100),
            'padding' => $this->string($props, 'padding', (string) $theme->get('layout.gutter', '24px')),
            'button_bg' => $this->string($props, 'button_background', $tone === 'alt' ? (string) $theme->get('color.primary') : $ink),
            'button_ink' => $this->string($props, 'button_color', $tone === 'alt' ? (string) $theme->get('color.primary_text') : $background),
            'button_radius' => (string) $theme->get('radius.pill', '999px'),
            'h1' => $theme->get('type.h1', []),
            'space_below' => $this->string($props, 'space_below', '0'),
        ];
    }
}
