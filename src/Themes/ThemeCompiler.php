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
        /* The gutter is applied per block, so a block nested inside a card or a
           quote is wrapped a second time and its measure collapses -- under
           100px of usable width on a 320px screen. Nested gutters are zeroed;
           the container supplies the inset. */
        .m-card .m-gutter, .m-quote .m-gutter, .m-nested .m-gutter { padding-left:0 !important; padding-right:0 !important; }
        /* A URL or an address has no spaces to wrap at, so one long value forces
           the whole table wider than the screen. break-word breaks only what
           cannot fit, unlike break-all, which would chop ordinary prose. */
        p, h1, h2, h3, td, li, blockquote, a { overflow-wrap:break-word; word-break:break-word; }
        CSS;
    }

    /**
     * Mobile size for a display face.
     *
     * A 40px headline set for a 600px canvas is roughly seven words wide; at
     * 320px it is one or two, and the result is a column of single words. The
     * scale is applied proportionally with a floor, so a theme that already
     * uses restrained headings is left alone while a poster-sized one is
     * brought back to something readable. A theme can override any of these
     * outright with `type.<step>.mobile_size`.
     */
    private function mobileType(Theme $theme, string $step, float $scale, int $floor): string
    {
        $explicit = $theme->get("type.{$step}.mobile_size");

        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $size = (float) str_replace('px', '', (string) $theme->get("type.{$step}.size", '16px'));

        if ($size <= $floor) {
            return '';
        }

        return (string) max($floor, (int) round($size * $scale)).'px';
    }

    private function responsive(Theme $theme): string
    {
        $gutter = (string) $theme->get('layout.gutter', '24px');

        $steps = [
            // class,     token,     scale, floor px
            ['m-h1', 'h1', 0.68, 26],
            ['m-h2', 'h2', 0.80, 20],
            ['m-display', 'display', 0.60, 30],
        ];

        // Statistic values are sized by the block rather than by a type token,
        // so they get a fixed pair of caps instead of a scale.
        $statRules = '.m-stat, .m-stat * { font-size:32px !important; line-height:38px !important; }'
            ."\n            .m-stat-display, .m-stat-display * { font-size:38px !important; line-height:44px !important; }";

        $typeRules = '';

        foreach ($steps as [$class, $token, $scale, $floor]) {
            $size = $this->mobileType($theme, $token, $scale, $floor);

            if ($size === '') {
                continue;
            }

            // Line height follows the size rather than staying at the desktop
            // value, or a shrunk headline sits in a box built for a taller one.
            $leading = (string) (int) round((float) str_replace('px', '', $size) * 1.18).'px';
            $typeRules .= "            .{$class}, .{$class} * { font-size:{$size} !important; line-height:{$leading} !important; }\n";
        }

        // The placeholder sits inside an indented heredoc, so the first line
        // is indented by the template and the rest carry their own.
        $typeRules = ltrim(rtrim($typeRules, "\n"));

        // 480px, not the 600px canvas width itself: no real device is 600px
        // wide, but a desktop mail client rendering the canvas at its full
        // 600px design width is common, and that view must stay desktop --
        // multi-column layouts included -- rather than collapsing to mobile
        // just because the two numbers happen to be close.
        return <<<CSS
        @media only screen and (max-width:480px) {
            .m-canvas { width:100% !important; max-width:100% !important; }
            .m-gutter { padding-left:{$gutter} !important; padding-right:{$gutter} !important; }
            /* ...and the mobile rule above would otherwise re-apply the full
               gutter to those same nested wrappers. */
            .m-card .m-gutter, .m-quote .m-gutter, .m-nested .m-gutter { padding-left:0 !important; padding-right:0 !important; }
            /* A table cell is border-box; the moment .m-stack makes it a block it
               becomes content-box, and any horizontal padding it carries is then
               added *outside* the 100% width. That is the overflow you see as a
               sideways scrollbar on a stacked column. */
            .m-stack { display:block !important; width:100% !important; max-width:100% !important; box-sizing:border-box !important; }
            .m-center { text-align:center !important; }
            /* An image sized for its desktop column would otherwise keep that
               width once the column stacks, leaving a gap beside it. */
            .m-img-fill { width:100% !important; max-width:100% !important; height:auto !important; }
            .m-hide-sm { display:none !important; }
            /* iOS zooms body copy under 16px, which reflows the whole layout. */
            .m-body-copy { font-size:16px !important; line-height:26px !important; }
            {$typeRules}
            {$statRules}
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
        $buttonPlate = (string) $theme->get('color.primary', null, 'dark');
        $dangerPlate = (string) $theme->get('color.danger', null, 'dark');

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
            ['h1, h2, h3, p, td, span, li, blockquote', "color:{$text} !important;", '[data-ogsc] h1, [data-ogsc] h2, [data-ogsc] h3, [data-ogsc] p, [data-ogsc] td, [data-ogsc] span, [data-ogsc] li, [data-ogsc] blockquote'],
            ['.m-muted, .m-muted *', "color:{$muted} !important;", '[data-ogsc] .m-muted'],
            ['a', "color:{$link} !important;", '[data-ogsc] a'],
            // Restated after .m-muted and `a`: a band authored dark already
            // carries its own light text, and equal-specificity rules are
            // settled by source order.
            ['.m-hold, .m-hold *', 'color:inherit !important;', '[data-ogsc] .m-hold, [data-ogsc] .m-hold *'],
            ['.m-divider', "background-color:{$border} !important;", '[data-ogsb] .m-divider'],
            // Restated last so CTA labels keep contrast against the button fill
            // instead of inheriting the broad body-text colour above.
            ['.m-btn, .m-btn *', "color:{$buttonText} !important;", '[data-ogsc] .m-btn'],
            // The label above is repainted in the dark scheme's button ink, so
            // the plate beneath it has to move too or the pair loses contrast.
            ['.m-btn-plate', "background-color:{$buttonPlate} !important;", '[data-ogsb] .m-btn-plate'],
            ['.m-btn-danger', "background-color:{$dangerPlate} !important;", '[data-ogsb] .m-btn-danger'],
            // Swap a dark-ink mark for a light one. Only clients honouring the
            // media query act on it, which is why the default mark still has to
            // be legible on both grounds.
            ['.m-logo-light', 'display:none !important;', '[data-ogsb] .m-logo-light'],
            ['.m-logo-dark', 'display:block !important;', '[data-ogsb] .m-logo-dark'],
            // Same swap for the social marks, so they invert with the footer
            // they sit on rather than disappearing into it.
            ['.m-social-light', 'display:none !important;', '[data-ogsb] .m-social-light'],
            ['.m-social-dark', 'display:block !important;', '[data-ogsb] .m-social-dark'],
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
