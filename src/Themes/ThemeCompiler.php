<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Themes;

/**
 * Turns theme tokens into the <style> block that ships in the document head.
 *
 * Two things live here and nowhere else:
 *
 * 1. Responsive rules. Blocks inline their own light-mode styling, so the only
 *    thing the stylesheet has to do is stack and resize on narrow viewports.
 *
 * 2. Dark mode. Dark token values are deliberately *never* inlined -- an
 *    inlined dark colour would apply in every client, including the majority
 *    that do not honour prefers-color-scheme. They are emitted here as
 *    overrides instead.
 *
 * On dark-mode reach, be clear-eyed: prefers-color-scheme is honoured by Apple
 * Mail and little else -- Gmail strips it on web, iOS and Android. Since Apple
 * is roughly half of all opens that is still worth doing, but the more
 * load-bearing defence is in the palette itself: the shipped themes avoid pure
 * #FFFFFF and #000000, which is what Outlook's forced inversion attacks hardest.
 */
final class ThemeCompiler
{
    /**
     * @param  string|null  $forceScheme  preview-only: 'light' drops the dark rules,
     *                                    'dark' applies them unconditionally so a
     *                                    reviewer can see dark mode without changing
     *                                    their OS setting. Sends always pass null.
     */
    public function compile(Theme $theme, ?string $forceScheme = null): string
    {
        if ($forceScheme === 'light') {
            return implode("\n", array_filter([
                $this->base($theme),
                $this->responsive($theme),
            ]));
        }

        return implode("\n", array_filter([
            $this->base($theme),
            $this->responsive($theme),
            $this->dark($theme, $forceScheme === 'dark'),
        ]));
    }

    private function base(Theme $theme): string
    {
        return <<<CSS
        body { margin:0; padding:0; width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
        table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
        a { color:{$theme->get('color.link')}; }
        .m-body { background-color:{$theme->get('color.bg')}; }
        .m-canvas { background-color:{$theme->get('color.surface')}; }
        CSS;
    }

    private function responsive(Theme $theme): string
    {
        $gutter = (string) $theme->get('layout.gutter', '24px');

        return <<<CSS
        @media only screen and (max-width:600px) {
            .m-canvas { width:100% !important; max-width:100% !important; }
            .m-gutter { padding-left:{$gutter} !important; padding-right:{$gutter} !important; }
            .m-stack { display:block !important; width:100% !important; max-width:100% !important; }
            .m-center { text-align:center !important; }
            .m-hide-sm { display:none !important; }
            /* iOS zooms body copy under 16px, which reflows the whole layout. */
            .m-body-copy { font-size:16px !important; line-height:26px !important; }
        }
        CSS;
    }

    private function dark(Theme $theme, bool $force = false): string
    {
        $bg = (string) $theme->get('color.bg', null, 'dark');
        $surface = (string) $theme->get('color.surface', null, 'dark');
        $surfaceAlt = (string) $theme->get('color.surface_alt', null, 'dark');
        $text = (string) $theme->get('color.text', null, 'dark');
        $muted = (string) $theme->get('color.text_muted', null, 'dark');
        $link = (string) $theme->get('color.link', null, 'dark');
        $border = (string) $theme->get('color.border', null, 'dark');

        $buttonText = (string) $theme->get('color.primary_text', null, 'dark');

        // Two selector sets over the same declarations.
        //
        // The media query reaches Apple Mail. The [data-ogsb]/[data-ogsc] hooks
        // reach Outlook.com, which injects those attributes onto elements whose
        // colours it rewrote -- so these rules are inert everywhere else and
        // cost nothing. Note Outlook.com's parser accepts only `[attr]` or
        // `E[attr]`: a `.m-card[data-ogsb]` selector silently fails, which is
        // why the hook has to sit on an ancestor and reach down.
        $declarations = [
            ['body, .m-body', "background-color:{$bg} !important;", '[data-ogsb] .m-body'],
            ['.m-canvas', "background-color:{$surface} !important;", '[data-ogsb] .m-canvas'],
            ['.m-card, .m-alt', "background-color:{$surfaceAlt} !important;", '[data-ogsb] .m-card, [data-ogsb] .m-alt'],
            ['h1, h2, h3, p, td, span, li', "color:{$text} !important;", '[data-ogsc] h1, [data-ogsc] h2, [data-ogsc] h3, [data-ogsc] p, [data-ogsc] td, [data-ogsc] span, [data-ogsc] li'],
            ['.m-muted, .m-muted *', "color:{$muted} !important;", '[data-ogsc] .m-muted'],
            ['a', "color:{$link} !important;", '[data-ogsc] a'],
            ['.m-divider', "background-color:{$border} !important;", '[data-ogsb] .m-divider'],
            // Restated last so CTA labels keep contrast against the button fill
            // instead of inheriting the broad body-text colour above.
            ['.m-btn, .m-btn *', "color:{$buttonText} !important;", '[data-ogsc] .m-btn'],
        ];

        $media = '';
        $outlook = '';

        foreach ($declarations as [$selector, $body, $ogscSelector]) {
            $media .= "    {$selector} { {$body} }\n";
            $outlook .= "{$ogscSelector} { {$body} }\n";
        }

        if ($force) {
            // Preview only: same declarations, no media query, so the reviewer
            // sees dark mode regardless of what their operating system prefers.
            return str_replace('    ', '', $media);
        }

        return "@media (prefers-color-scheme: dark) {\n{$media}}\n{$outlook}";
    }
}
