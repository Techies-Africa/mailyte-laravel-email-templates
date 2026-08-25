<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * Bulletproof CTA button.
 *
 * Square corners are accepted in classic Outlook rather than reaching for VML
 * RoundRect: VML is proprietary, awkward to maintain and hurts accessibility,
 * and a square button is a perfectly reasonable degradation.
 *
 * The fallback URL line is optional on purpose. Shipped emails split into two
 * camps -- button-first senders repeat the raw URL underneath, while code-first
 * senders ship no button at all -- so forcing one convention would be wrong.
 */
final class ButtonBlock extends Block
{
    public function name(): string
    {
        return 'button';
    }

    public function normalize(array $props, Theme $theme): array
    {
        $variant = $this->enum($props, 'variant', ['primary', 'secondary', 'danger', 'outline', 'link'], 'primary');
        $shape = $this->enum($props, 'shape', ['default', 'square', 'pill'], 'default');

        $background = match ($variant) {
            'secondary' => (string) $theme->get('color.surface_alt'),
            'danger' => (string) $theme->get('color.danger'),
            'outline', 'link' => 'transparent',
            default => (string) $theme->get('color.primary'),
        };

        $color = $this->string($props, 'color') ?: match ($variant) {
            'secondary' => (string) $theme->get('color.text'),
            'outline', 'link' => (string) $theme->get('color.primary'),
            default => (string) $theme->get('color.primary_text'),
        };

        return [
            'label' => $this->string($props, 'label', 'Continue'),
            'url' => $this->url($props, 'url'),
            'variant' => $variant,
            'background' => $background,
            'color' => $color,
            'align' => $this->enum($props, 'align', ['left', 'center', 'right'], 'center'),
            'full_width' => $this->bool($props, 'full_width'),
            'shape' => $shape,
            'border' => $variant === 'outline'
                ? ($this->string($props, 'border_color') ?: ($this->string($props, 'color') ?: (string) $theme->get('color.primary')))
                : '',
            'underline' => $variant === 'link',
            'radius' => match ($shape) {
                'square' => '0',
                'pill' => (string) $theme->get('radius.pill', '999px'),
                default => (string) $theme->get('radius.md'),
            },
            'type' => $theme->get('type.button', []),
            'padding_y' => (string) $theme->get('button.padding_y', '14px'),
            'padding_x' => (string) $theme->get('button.padding_x', '28px'),
            'shadow' => $variant === 'primary' ? (string) $theme->get('shadow.button', '') : '',
            'bare' => in_array($variant, ['outline', 'link'], true),
            'fallback_text' => $this->string($props, 'fallback_text'),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
