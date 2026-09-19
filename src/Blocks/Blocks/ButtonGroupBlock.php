<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * Two or three actions side by side, with the first carrying the weight.
 *
 * Real product email routinely offers a primary and a secondary path -- "Enable
 * now" beside "Learn more", "This was me" beside "Secure my account". Stacking
 * those as two separate buttons reads as two equal demands; setting them in a
 * row with different weights says which one you expect.
 *
 * The cells sit in one row and do not stack on mobile: two short labels fit at
 * 320px, and a stacked pair reintroduces the equal-weight problem.
 */
final class ButtonGroupBlock extends Block
{
    public function name(): string
    {
        return 'button_group';
    }

    public function schema(): array
    {
        return [
            'items' => Prop::items('Buttons', [
                'label' => Prop::text('Label', required: true),
                'url' => Prop::url('Links to', required: true),
                'variant' => Prop::enum('Style', ['primary', 'secondary', 'danger', 'outline'], 'outline', 'The first button defaults to primary, the rest to outline.'),
            ], 'At most three. Beyond that the row wraps badly on a phone and nothing gets clicked.', required: true),
            'align' => Prop::enum('Alignment', ['left', 'center'], 'left'),
            'shape' => Prop::enum('Shape', ['default', 'square', 'pill'], 'default'),
            'space_above' => Prop::spacing('Space above'),
            'space_below' => Prop::spacing('Space below'),
        ];
    }

    public function normalize(array $props, Theme $theme): array
    {
        $items = [];

        foreach ($this->list($props, 'items') as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $variant = isset($item['variant']) && is_string($item['variant'])
                ? $item['variant']
                : ($index === 0 ? 'primary' : 'outline');

            $items[] = [
                'label' => isset($item['label']) && is_scalar($item['label']) ? (string) $item['label'] : '',
                'url' => isset($item['url']) && is_scalar($item['url']) ? (string) $item['url'] : '',
                'variant' => in_array($variant, ['primary', 'secondary', 'danger', 'outline'], true) ? $variant : 'outline',
            ];
        }

        $shape = $this->enum($props, 'shape', ['default', 'square', 'pill'], 'default');

        return [
            'items' => array_slice($items, 0, 3),
            'align' => $this->enum($props, 'align', ['left', 'center'], 'left'),
            'shape' => $shape,
            'radius' => match ($shape) {
                'square' => '0',
                'pill' => (string) $theme->get('radius.pill', '999px'),
                default => (string) $theme->get('radius.md'),
            },
            'primary_bg' => (string) $theme->get('color.primary'),
            'primary_ink' => (string) $theme->get('color.primary_text'),
            'secondary_bg' => (string) $theme->get('color.surface_alt'),
            'danger_bg' => (string) $theme->get('color.danger'),
            'ink' => (string) $theme->get('color.text'),
            'border_color' => (string) $theme->get('color.border'),
            'type' => $theme->get('type.button', []),
            'padding_y' => (string) $theme->get('button.padding_y', '13px'),
            'padding_x' => (string) $theme->get('button.padding_x', '22px'),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
