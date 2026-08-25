<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Rendering;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Mailyte\EmailTemplates\Exceptions\RenderFailed;
use Mailyte\EmailTemplates\Mail\TemplateMailable;
use Mailyte\EmailTemplates\Sources\SourceChain;
use Mailyte\EmailTemplates\Templates\TemplateManifest;
use Mailyte\EmailTemplates\Themes\BrandTokens;
use Mailyte\EmailTemplates\Themes\Theme;
use Mailyte\EmailTemplates\Themes\ThemeRepository;
use Mailyte\EmailTemplates\Themes\TokenSanitizer;

/**
 * The fluent front door:
 *
 *     Mailyte::template('welcome')
 *         ->with(['user' => $user, 'action_url' => $url])
 *         ->theme(['color.primary' => $org->brand_color])
 *         ->layout('minimal')
 *         ->toMailable();
 */
final class TemplateBuilder
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, mixed> */
    private array $themeOverrides = [];

    private ?string $themeName = null;

    private ?string $layoutName = null;

    private string $locale = 'en';

    private ?string $forceScheme = null;

    public function __construct(
        private readonly string $slug,
        private readonly SourceChain $sources,
        private readonly ThemeRepository $themes,
        private readonly RenderPipeline $pipeline,
        private readonly TokenSanitizer $sanitizer,
        private readonly Config $config,
        private readonly BrandTokens $brand,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function with(array $data): self
    {
        $this->data = array_replace_recursive($this->data, $data);

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->data['subject'] = $subject;

        return $this;
    }

    public function preheader(string $preheader): self
    {
        $this->data['preheader'] = $preheader;

        return $this;
    }

    /**
     * Per-send branding. Pass a theme name, or dot-path token overrides, or
     * both -- this is how one application sends on behalf of many tenants
     * without maintaining a template per tenant.
     *
     * @param  string|array<string, mixed>  $theme
     */
    public function theme(string|array $theme): self
    {
        if (is_string($theme)) {
            $this->themeName = $theme;

            return $this;
        }

        $this->themeOverrides = array_merge($this->themeOverrides, $theme);

        return $this;
    }

    public function layout(string $layout): self
    {
        $this->layoutName = $layout;

        return $this;
    }

    public function locale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Force a colour scheme. For previewing only -- a real send leaves this
     * alone so dark values stay inside the media query where they belong.
     */
    public function forceScheme(?string $scheme): self
    {
        $this->forceScheme = in_array($scheme, ['light', 'dark'], true) ? $scheme : null;

        return $this;
    }

    public function manifest(): TemplateManifest
    {
        return $this->sources->get($this->slug);
    }

    /**
     * The theme this template would render with, after every layer of
     * precedence has been applied.
     *
     * Exposed because more than rendering needs it: `mailyte:adopt` compiles a
     * stylesheet for Laravel's markdown mailables from exactly these tokens,
     * and it has to be the resolved set rather than the raw design.json, or the
     * application's own brand would be missing from it.
     */
    public function resolvedTheme(): Theme
    {
        return $this->buildTheme(
            $this->manifest(),
            $this->sanitizer->sanitize($this->themeOverrides),
        );
    }

    /**
     * Precedence, innermost last: the theme sets the house style, the bundle's
     * design.json makes this template look like itself, the application's brand
     * config supplies the assets and links that are the same in every message,
     * and a per-send override still beats all three.
     *
     * A community bundle's design is sanitised on the same path as tenant input
     * -- it is a stranger's file too.
     *
     * @param  array<string, mixed>  $sanitized
     */
    private function buildTheme(TemplateManifest $manifest, array $sanitized): Theme
    {
        $design = $this->sanitizer->sanitize($manifest->design());
        $brand = $this->sanitizer->sanitize($this->brand->toTokens());

        return $this->resolveTheme()
            ->merge($design['tokens'])
            ->merge($brand['tokens'])
            ->merge($sanitized['tokens']);
    }

    public function render(): RenderedEmail
    {
        $manifest = $this->manifest();
        $layout = $this->resolveLayout($manifest);

        $sanitized = $this->sanitizer->sanitize($this->themeOverrides);
        $theme = $this->buildTheme($manifest, $sanitized);

        $data = $this->resolveData($manifest);

        $this->assertRequired($manifest, $data);

        return $this->pipeline->render(
            manifest: $manifest,
            theme: $theme,
            layout: $layout,
            data: $data,
            locale: $this->locale,
            warnings: $sanitized['warnings'],
            forceScheme: $this->forceScheme,
        );
    }

    /**
     * Render and send in one step.
     *
     * The common case is one template, some data and one recipient, so it
     * should read that way. Anything Laravel's `Mail::to()` accepts works
     * here -- a string, an array, a Mailable-aware user model, a collection.
     *
     * Reach for `toMailable()` instead when you need the mailable itself: to
     * attach a file, add a cc, or hand it to a queue with your own options.
     *
     * @param  mixed  $to
     */
    public function send($to): RenderedEmail
    {
        $email = $this->render();

        Mail::to($to)->send($email->toMailableFrom());

        return $email;
    }

    /**
     * Render now, deliver later.
     *
     * Rendering happens here rather than in the job, so a template that would
     * fail on missing data fails in the request that queued it, not silently
     * on a worker at three in the morning.
     *
     * @param  mixed  $to
     */
    public function queue($to, ?string $queue = null): RenderedEmail
    {
        $email = $this->render();
        $mailable = $email->toMailableFrom();

        if ($queue !== null) {
            $mailable->onQueue($queue);
        }

        Mail::to($to)->queue($mailable);

        return $email;
    }

    public function toMailable(): TemplateMailable
    {
        return new TemplateMailable($this->render());
    }

    public function html(): string
    {
        return $this->render()->html;
    }

    public function text(): string
    {
        return $this->render()->text;
    }

    private function resolveTheme(): Theme
    {
        return $this->themeName !== null
            ? $this->themes->get($this->themeName)
            : $this->themes->default();
    }

    private function resolveLayout(TemplateManifest $manifest): string
    {
        $layout = $this->layoutName ?? (string) $this->config->get('mailyte.layout', 'branded');

        if ($manifest->supportsLayout($layout)) {
            return $layout;
        }

        if ($this->layoutName !== null) {
            throw new RenderFailed(sprintf(
                'Template [%s] does not support the [%s] layout. It declares: %s.',
                $manifest->slug,
                $layout,
                implode(', ', $manifest->supportedLayouts()),
            ));
        }

        // The configured default does not fit this template, which is normal --
        // a receipt has no business in an editorial layout. Fall back to what
        // the bundle actually declares rather than failing the send.
        return $manifest->supportedLayouts()[0] ?? 'branded';
    }

    /**
     * Manifest defaults, then config globals, then caller data.
     *
     * @return array<string, mixed>
     */
    private function resolveData(TemplateManifest $manifest): array
    {
        /** @var array<string, mixed> $globals */
        $globals = $this->config->get('mailyte.globals', []);

        return $this->normalize(array_replace_recursive(
            $manifest->defaults(),
            $globals,
            $this->data,
        ));
    }

    /**
     * Deep-convert models and objects to arrays *before* they reach the
     * sandbox. The security policy forbids method and property access
     * outright, so anything that arrives as an object is unreachable from a
     * template -- converting here is what makes `{{ user.name }}` work at all.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->normalizeValue($value);
        }

        return $data;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            return $this->normalize($value->toArray());
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item), $value);
        }

        if (is_object($value)) {
            if ($value instanceof \JsonSerializable) {
                $encoded = json_decode((string) json_encode($value), true);

                return is_array($encoded) ? $this->normalize($encoded) : $encoded;
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return $this->normalize(get_object_vars($value));
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertRequired(TemplateManifest $manifest, array $data): void
    {
        $missing = [];

        foreach ($manifest->requiredVariables() as $name) {
            $value = Arr::get($data, $name);

            if ($value === null || $value === '') {
                $missing[] = $name;
            }
        }

        if ($missing !== []) {
            throw new RenderFailed(sprintf(
                'Template [%s] is missing required data: %s. Pass it with ->with([...]) or give the '
                .'variable a default in the manifest.',
                $manifest->slug,
                implode(', ', $missing),
            ));
        }
    }
}
