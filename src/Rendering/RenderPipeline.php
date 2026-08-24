<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Rendering;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Mailyte\EmailTemplates\Blocks\BlockRegistry;
use Mailyte\EmailTemplates\Exceptions\RenderFailed;
use Mailyte\EmailTemplates\Templates\TemplateManifest;
use Mailyte\EmailTemplates\Themes\Theme;
use Mailyte\EmailTemplates\Themes\ThemeCompiler;
use Mailyte\EmailTemplates\Twig\SandboxFactory;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Twig\Environment;
use Twig\Error\Error as TwigError;

final class RenderPipeline
{
    public function __construct(
        private readonly SandboxFactory $sandbox,
        private readonly BlockRegistry $blocks,
        private readonly ThemeCompiler $compiler,
        private readonly ViewFactory $views,
        private readonly Config $config,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $warnings
     */
    public function render(
        TemplateManifest $manifest,
        Theme $theme,
        string $layout,
        array $data,
        string $locale = 'en',
        array $warnings = [],
        ?string $forceScheme = null,
    ): RenderedEmail {
        $twig = $this->sandbox->make($theme, $layout);

        $body = $this->renderSource($twig, $manifest, $manifest->html(), $data, 'email.html');
        $subject = $this->renderInline($twig, $manifest, (string) ($data['subject'] ?? $manifest->subject()), 'subject', $data);
        $preheader = $this->renderInline($twig, $manifest, (string) ($data['preheader'] ?? $manifest->preheader()), 'preheader', $data);

        $css = $this->compiler->compile($theme, $forceScheme);

        if ($styles = $manifest->styles()) {
            $css .= "\n".$styles;
        }

        $html = $this->views->make($this->layoutView($layout), [
            'content' => $body,
            'css' => $css,
            'theme' => $theme,
            't' => $theme->flat(),
            'globals' => $this->globals($data),
            'title' => $subject,
            'locale' => $locale,
            'preheaderHtml' => $preheader === ''
                ? ''
                : $this->blocks->render('preheader', ['text' => $preheader], $theme, $layout),
        ])->render();

        $html = $this->inlineCss($html);
        $html = (new PostProcessor((string) $this->config->get('mailyte.render.base_url')))->process($html);

        $this->enforceOutputLimit($html, $manifest);

        $text = $manifest->text() !== null
            ? html_entity_decode(
                $this->renderSource($twig, $manifest, (string) $manifest->text(), $data, 'email.txt'),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
            : (new TextPartGenerator((int) $this->config->get('mailyte.render.text.wrap_at', 78)))->fromHtml($html);

        return new RenderedEmail(
            html: $html,
            text: trim($text),
            subject: $subject,
            preheader: $preheader,
            suggestedHeaders: $manifest->suggestedHeaders(),
            warnings: $warnings,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderSource(
        Environment $twig,
        TemplateManifest $manifest,
        string $source,
        array $data,
        string $file,
    ): string {
        try {
            return $twig->createTemplate($source, $manifest->slug.'/'.$file)->render($data);
        } catch (TwigError $e) {
            throw new RenderFailed(
                sprintf(
                    'Failed rendering %s of template [%s] at line %d: %s',
                    $file,
                    $manifest->slug,
                    $e->getTemplateLine(),
                    $e->getRawMessage(),
                ),
                0,
                $e,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderInline(Environment $twig, TemplateManifest $manifest, string $source, string $what, array $data = []): string
    {
        if ($source === '' || ! str_contains($source, '{')) {
            return $source;
        }

        // Subject and preheader are plain text in the inbox list, so escaped
        // entities would show up literally there.
        return html_entity_decode(
            $this->renderSource($twig, $manifest, $source, $data, $what),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    private function inlineCss(string $html): string
    {
        // Inline what can be inlined, but keep the <style> block: media queries
        // and dark-mode overrides cannot be inlined by definition, and the
        // whole dark-mode strategy depends on them surviving.
        preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $matches);

        $inlinable = '';

        foreach ($matches[1] as $block) {
            // Strip at-rules before inlining; the inliner would otherwise try
            // to apply media-query and Outlook-hook declarations universally.
            $inlinable .= preg_replace('/@(media|supports)[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/is', '', $block) ?? '';
        }

        if (trim($inlinable) === '') {
            return $html;
        }

        return (new CssToInlineStyles)->convert($html, $inlinable);
    }

    private function enforceOutputLimit(string $html, TemplateManifest $manifest): void
    {
        $limit = (int) $this->config->get('mailyte.render.limits.output_bytes', 2 * 1024 * 1024);

        if (strlen($html) > $limit) {
            throw new RenderFailed(sprintf(
                'Template [%s] produced %d bytes, over the %d byte cap. The sandbox stops a template from '
                .'running code but not from being expensive, so this limit is the backstop -- check for an '
                .'unbounded loop over caller data.',
                $manifest->slug,
                strlen($html),
                $limit,
            ));
        }
    }

    private function layoutView(string $layout): string
    {
        $view = 'mailyte::html.layouts.'.$layout;

        if (! $this->views->exists($view)) {
            throw new RenderFailed("Unknown layout preset [{$layout}]. Available: plain, minimal, branded, editorial.");
        }

        return $view;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function globals(array $data): array
    {
        /** @var array<string, mixed> $configured */
        $configured = $this->config->get('mailyte.globals', []);

        return array_replace_recursive($configured, array_intersect_key($data, $configured));
    }
}
