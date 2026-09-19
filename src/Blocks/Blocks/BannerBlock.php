<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks\Blocks;

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * Full-bleed image with the headline sitting on top of it.
 *
 * This is the one layout that genuinely needs VML. Outlook's Word engine
 * ignores CSS background-image entirely, so without a VML rect the text lands
 * on bare white and the design collapses. The rect is drawn behind the same
 * markup every other client renders, so there is one copy of the content, not
 * two.
 *
 * Text over a photo is a contrast gamble, so a scrim is applied by default and
 * `overlay: 'none'` has to be asked for. The scrim is a solid tinted band
 * rather than a gradient: Outlook cannot render a gradient overlay, and a band
 * that exists everywhere beats a gradient that exists in half the clients.
 */
final class BannerBlock extends Block
{
    public function name(): string
    {
        return 'banner';
    }

    public function fullBleed(array $props = []): bool
    {
        return (bool) ($props['bleed'] ?? false);
    }

    public function schema(): array
    {
        return [
            'image' => Prop::image('Background image', required: true),
            'image_alt' => Prop::text('Image description', 'What the image says, for anyone whose client blocks it.'),
            'eyebrow' => Prop::text('Eyebrow', 'A short line above the title.'),
            'title' => Prop::text('Title', required: true),
            'subtitle' => Prop::richtext('Subtitle'),
            'button_label' => Prop::text('Button label'),
            'button_url' => Prop::url('Button links to'),
            'overlay' => Prop::enum('Overlay', ['dark', 'light', 'none'], 'dark', 'A wash over the image so the words stay readable on it.'),
            'align' => Prop::enum('Alignment', ['left', 'center'], 'center'),
            'height' => Prop::number('Height', 120, 600, 'In pixels.'),
            'bleed' => Prop::bool('Full width', description: 'Runs the banner to the edges of the message rather than sitting in the gutter.'),
            'text_color' => Prop::color('Text colour', 'Used when there is no overlay.'),
            'fallback_color' => Prop::color('Fallback colour', 'Shown where the image does not load -- which is most inboxes, by default.'),
            'button_background' => Prop::color('Button colour'),
            'space_below' => Prop::spacing('Space below'),
        ];
    }

    public function normalize(array $props, Theme $theme): array
    {
        $overlay = $this->enum($props, 'overlay', ['dark', 'light', 'none'], 'dark');

        $textColor = match ($overlay) {
            'light' => (string) $theme->get('color.text'),
            'none' => $this->string($props, 'text_color', '#FFFFFF'),
            default => '#FFFFFF',
        };

        return [
            'image' => $this->url($props, 'image'),
            'image_alt' => $this->string($props, 'image_alt'),
            'eyebrow' => $this->string($props, 'eyebrow'),
            'title' => $this->string($props, 'title'),
            'subtitle' => $this->string($props, 'subtitle'),
            'button_label' => $this->string($props, 'button_label'),
            'button_url' => $this->url($props, 'button_url'),
            'height' => (int) $this->string($props, 'height', '320'),
            'align' => $this->enum($props, 'align', ['left', 'center'], 'center'),
            'overlay' => $overlay,
            'scrim' => match ($overlay) {
                'light' => 'rgba(255,255,255,.78)',
                'none' => 'transparent',
                default => 'rgba(12,14,18,.48)',
            },
            'fallback_color' => $this->string($props, 'fallback_color', (string) $theme->get('color.surface_alt')),
            'text_color' => $textColor,
            'button_bg' => $this->string($props, 'button_background', (string) $theme->get('color.primary')),
            'button_color' => (string) $theme->get('color.primary_text'),
            'width' => (int) str_replace('px', '', (string) $theme->get('layout.width', '600px')),
            'gutter' => (string) $theme->get('layout.gutter', '24px'),
            'radius' => (string) $theme->get('radius.lg'),
            'h1' => $theme->get('type.h1', []),
            'button_radius' => (string) $theme->get('radius.md'),
            'space_below' => $this->string($props, 'space_below', (string) $theme->get('spacing.lg')),
        ];
    }
}
