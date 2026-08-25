<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * A band of content with its own background, running edge to edge of the
 * canvas.
 *
 * This is the block the catalog's best-looking templates are built from: a
 * tinted opening band, a white body, a dark closing band. Without it every
 * email is one flat surface with text stacked down it, which is precisely what
 * makes a template look like a wireframe rather than a design.
 *
 * The slot is rendered content, so blocks nest inside a section normally. The
 * section supplies its own inner gutter, because it has opted out of the one
 * BlockRegistry would otherwise apply.
 */
final class SectionBlock extends Block
{
    public function name(): string
    {
        return 'section';
    }

    public function hasSlot(): bool
    {
        return true;
    }

    public function fullBleed(array $props = []): bool
    {
        // A section is full-bleed unless it is explicitly asked to sit inside
        // the measure, which is occasionally what you want for an inset panel.
        return ! isset($props['inset']) || ! $props['inset'];
    }

    public function normalize(array $props, Theme $theme): array
    {
        $tone = $this->enum($props, 'tone', ['surface', 'alt', 'accent', 'dark', 'custom'], 'alt');

        $background = match ($tone) {
            'surface' => (string) $theme->get('color.surface'),
            'accent' => (string) $theme->get('color.primary'),
            'dark' => $this->string($props, 'background', '#15171A'),
            'custom' => $this->string($props, 'background', (string) $theme->get('color.surface_alt')),
            default => (string) $theme->get('color.surface_alt'),
        };

        // Text has to be legible on whatever the band turns out to be, and the
        // template author should not have to restate it on every child block.
        $ink = match ($tone) {
            'accent' => (string) $theme->get('color.primary_text'),
            'dark' => $this->string($props, 'text_color', '#FFFFFF'),
            default => (string) $theme->get('color.text'),
        };

        return [
            'slot' => $this->slot($props),
            'tone' => $tone,
            'background' => $background,
            'ink' => $ink,
            'padding_y' => $this->string($props, 'padding_y', (string) $theme->get('spacing.lg')),
            // Zero by default: the blocks inside the slot already carry the
            // layout gutter, so a section that added its own would inset them
            // twice and knock the band's content out of line with the footer.
            // A section wanting extra inset asks for it explicitly.
            'padding_x' => $this->string($props, 'padding_x', '0'),
            'align' => $this->enum($props, 'align', ['left', 'center'], 'left'),
            'radius' => $this->string($props, 'radius', '0'),
            'inset' => $this->bool($props, 'inset'),
            'border_color' => $this->string($props, 'border_color', ''),
            'space_above' => $this->string($props, 'space_above', '0'),
            'space_below' => $this->string($props, 'space_below', '0'),
        ];
    }
}
