<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * Two-column label/value table -- login provenance, incident facts, DNS
 * records, order details.
 *
 * Two columns rather than three or four because two survive a 320px viewport
 * with no media query at all, and horizontal scrolling does not exist in an
 * email client. Outlook also rounds percentage widths up and will drop a cell
 * onto a new row when several columns compete.
 */
final class KeyValueBlock extends Block
{
    public function name(): string
    {
        return 'key_value';
    }

    public function normalize(array $props, Theme $theme): array
    {
        $rows = [];

        foreach ($this->list($props, 'rows') as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = $row['label'] ?? $row[0] ?? null;
            $value = $row['value'] ?? $row[1] ?? null;

            if (! is_scalar($label) || ! is_scalar($value)) {
                continue;
            }

            $rows[] = [
                'label' => (string) $label,
                'value' => (string) $value,
                'mono' => isset($row['mono']) && (bool) $row['mono'],
            ];
        }

        return [
            'rows' => $rows,
            'figures' => $this->bool($props, 'figures'),
            'emphasise_last' => $this->bool($props, 'emphasise_last'),
            'label_width' => $this->string($props, 'label_width', '40%'),
            'label_color' => $this->string($props, 'label_color', (string) $theme->get('color.text_muted')),
            'value_color' => $this->string($props, 'value_color', (string) $theme->get('color.text')),
            'border_color' => $this->string($props, 'border_color', (string) $theme->get('color.border')),
            'mono_font' => (string) $theme->get('font.mono'),
            'type' => $theme->get('type.small', []),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.md')),
        ];
    }
}
