<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Linting;

use Mailyte\EmailTemplates\Blocks\BlockRegistry;
use Mailyte\EmailTemplates\Templates\TemplateManifest;

/**
 * The house rules for a template bundle.
 *
 * Everything here is checkable without rendering: bundle structure, the
 * manifest against its schema, the markup's tokens against the declared ones
 * in both directions, and the content rules the catalog holds itself to.
 * Whether a template *renders* is a question for the test suite, which does
 * render every bundle in every layout against every sample.
 */
class TemplateLinter
{
    /**
     * Names Twig itself provides, which are not template variables.
     *
     * @var array<int, string>
     */
    private const TWIG = [
        'if', 'else', 'elseif', 'endif', 'for', 'endfor', 'in', 'set', 'endset',
        'and', 'or', 'not', 'is', 'defined', 'empty', 'null', 'none', 'true',
        'false', 'apply', 'endapply', 'verbatim', 'endverbatim', 'default',
        'join', 'length', 'upper', 'lower', 'title', 'capitalize', 'trim',
        'number_format', 'date', 'escape', 'e', 'first', 'last', 'slice',
        'sort', 'keys', 'merge', 'replace', 'split', 'striptags', 'nl2br',
        'round', 'abs', 'format', 'json_encode', 'reverse', 'url_encode',
        'max', 'min', 'same', 'divisible', 'by', 'even', 'odd', 'iterable',
        'starts', 'ends', 'with', 'matches', 'raw', 'loop',
    ];

    /**
     * Tokens every template may read because the package supplies them.
     *
     * @var array<int, string>
     */
    private const GLOBALS = [
        'product', 'company', 'support_url', 'unsubscribe_url',
        'preferences_url', 'theme',
    ];

    /**
     * @param  array<string, mixed>  $config  the `mailyte.lint.rules` block
     */
    public function __construct(
        private readonly BlockRegistry $blocks,
        private readonly SchemaValidator $schema,
        private readonly array $config = [],
    ) {}

    /**
     * A rule's configured threshold, or the built-in default.
     */
    private function threshold(string $rule, string $key, float $default): float
    {
        $value = $this->config[$rule][$key] ?? $default;

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * @return array<int, Issue>
     */
    public function lint(TemplateManifest $manifest): array
    {
        $issues = array_merge(
            $this->checkStructure($manifest),
            $this->checkTokens($manifest),
            $this->checkContent($manifest),
            $this->checkCompliance($manifest),
        );

        $waivers = $manifest->lintWaivers();

        return array_map(
            static fn (Issue $issue): Issue => isset($waivers[$issue->rule])
                ? $issue->waive($waivers[$issue->rule])
                : $issue,
            $issues,
        );
    }

    /**
     * @return array<int, Issue>
     */
    private function checkStructure(TemplateManifest $manifest): array
    {
        $slug = $manifest->slug;
        $issues = [];

        if (! $manifest->has('email.html')) {
            return [Issue::error($slug, 'MT001', 'the bundle has no email.html')];
        }

        foreach ($this->schema->validate($manifest->read('template.json') ?? '') as $violation) {
            $issues[] = Issue::error($slug, 'MT003', 'manifest does not match the schema -- '.$violation);
        }

        if (! $manifest->has('design.json')) {
            $issues[] = Issue::error($slug, 'MT004', 'no design.json -- every template carries its own design tokens');
        } else {
            $issues = array_merge($issues, $this->checkDesign($manifest));
        }

        if (! $manifest->has('sample.json')) {
            $issues[] = Issue::error($slug, 'MT007', 'no sample.json -- the preview gallery and the render tests need sample data');
        } elseif (count($manifest->samples()) < 2) {
            $issues[] = Issue::warning($slug, 'MT008', 'only one sample -- ship a default plus an edge case, which is what catches layout breakage');
        }

        return $issues;
    }

    /**
     * @return array<int, Issue>
     */
    private function checkDesign(TemplateManifest $manifest): array
    {
        try {
            $design = $manifest->design();
        } catch (\JsonException $e) {
            return [Issue::error($manifest->slug, 'MT005', 'design.json is not valid JSON -- '.$e->getMessage())];
        }

        $issues = [];
        $padding = (string) ($design['layout.outer_padding'] ?? '36px');
        $pixels = (int) preg_replace('/[^0-9]/', '', $padding);
        $minimum = (int) $this->threshold('MT006', 'min_outer_padding', 32);

        if ($pixels > 0 && $pixels < $minimum) {
            $issues[] = Issue::warning(
                $manifest->slug,
                'MT006',
                "layout.outer_padding is {$padding}; below {$minimum}px the message crowds the client chrome",
            );
        }

        return array_merge($issues, $this->checkSchemes($manifest->slug, $design));
    }

    /**
     * Both schemes have to be real schemes.
     *
     * A design can be dark by intention, but then it is dark in *dark* mode and
     * has a light counterpart -- a template whose light scheme is also dark has
     * one mode wearing two names, and a recipient reading in daylight gets the
     * wrong one. A dark band inside a light message is a different thing and is
     * not what this looks at: this is the surface the body copy sits on.
     *
     * @param  array<string, mixed>  $design
     * @return array<int, Issue>
     */
    private function checkSchemes(string $slug, array $design): array
    {
        $issues = [];

        foreach (['color.surface', 'color.bg'] as $token) {
            $pair = $design[$token] ?? null;

            if (! is_array($pair) || ! isset($pair['light'], $pair['dark'])) {
                continue;
            }

            $light = $this->luminance((string) $pair['light']);
            $dark = $this->luminance((string) $pair['dark']);

            if ($light === null || $dark === null) {
                continue;
            }

            if ($light < $this->threshold('MT015', 'max_light_luminance', 0.30)) {
                $issues[] = Issue::error($slug, 'MT015', "{$token}.light is {$pair['light']}, which is a dark colour -- the template has no light scheme, only a dark one named twice");
            }

            if ($dark > $this->threshold('MT016', 'min_dark_luminance', 0.35)) {
                $issues[] = Issue::error($slug, 'MT016', "{$token}.dark is {$pair['dark']}, which is a light colour -- the template has no dark scheme");
            }
        }

        return $issues;
    }

    /**
     * Relative luminance per WCAG 2.1, or null for anything that is not a hex
     * colour -- a token may legitimately hold a gradient or a keyword.
     */
    private function luminance(string $color): ?float
    {
        $hex = ltrim(trim($color), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            return null;
        }

        $channel = static function (int $value): float {
            $c = $value / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel((int) hexdec(substr($hex, 0, 2)))
            + 0.7152 * $channel((int) hexdec(substr($hex, 2, 2)))
            + 0.0722 * $channel((int) hexdec(substr($hex, 4, 2)));
    }

    /**
     * Declared variables against the ones the markup reads, both directions.
     *
     * An undeclared token is an error: it renders empty and nobody finds out
     * until a recipient does. A declared token nothing uses is a warning --
     * usually a leftover, occasionally deliberate.
     *
     * @return array<int, Issue>
     */
    private function checkTokens(TemplateManifest $manifest): array
    {
        $body = $manifest->html();

        if ($body === '') {
            return [];
        }

        $known = array_merge(
            self::TWIG,
            self::GLOBALS,
            $this->blocks->names(),
            $this->localNames($body),
        );

        $declared = [];

        foreach (array_keys($manifest->variables()) as $name) {
            $declared[] = explode('.', (string) $name)[0];
        }

        $issues = [];
        $used = [];

        foreach ($this->identifiers($body) as $identifier) {
            $root = explode('.', $identifier)[0];
            $used[$root] = true;

            if (in_array($root, $known, true) || in_array($root, $declared, true)) {
                continue;
            }

            $issues[] = Issue::error(
                $manifest->slug,
                'MT042',
                "the markup reads '{$identifier}', which the manifest does not declare",
            );
        }

        foreach (array_keys($manifest->variables()) as $name) {
            if (! isset($used[explode('.', (string) $name)[0]])) {
                $issues[] = Issue::warning(
                    $manifest->slug,
                    'MT040',
                    "declares '{$name}' but the markup never reads it",
                );
            }
        }

        return $this->unique($issues);
    }

    /**
     * @return array<int, Issue>
     */
    private function checkContent(TemplateManifest $manifest): array
    {
        $slug = $manifest->slug;
        $body = $manifest->html();
        $manifestJson = $manifest->read('template.json') ?? '';
        $issues = [];

        if ($manifest->preheader() === '') {
            $issues[] = Issue::warning($slug, 'MT010', 'no preheader -- the inbox preview falls back to whatever text comes first');
        }

        // Tokens shrink when they render, so measure the subject with each one
        // replaced by a nominal value rather than counting the raw source.
        $subject = (string) preg_replace('/\{\{.*?\}\}/', str_repeat('x', 8), $manifest->subject());

        $maxSubject = (int) $this->threshold('MT011', 'max_subject_chars', 65);

        if (mb_strlen($subject) > $maxSubject) {
            $issues[] = Issue::warning(
                $slug,
                'MT011',
                'the subject renders to about '.mb_strlen($subject)." characters; mobile clients cut off around {$maxSubject}",
            );
        }

        $rendersHeading = str_contains($body, "level: '1'")
            || str_contains($body, 'level: "1"')
            || preg_match('/\b(split|banner|hero)\s*\(/', $body) === 1;

        if (! $rendersHeading) {
            $issues[] = Issue::warning($slug, 'MT012', 'nothing renders an h1 -- use a level 1 heading, or a split, banner or hero block');
        }

        if (preg_match('/[\x{1F300}-\x{1FAFF}\x{FE0F}]/u', $body.$manifestJson) === 1) {
            $issues[] = Issue::warning($slug, 'MT013', 'contains emoji, which render inconsistently across clients and read as noise in a transactional message');
        }

        return $issues;
    }

    /**
     * Rules that follow from the manifest's declared `type`.
     *
     * @return array<int, Issue>
     */
    private function checkCompliance(TemplateManifest $manifest): array
    {
        if ($manifest->type() !== 'marketing') {
            return [];
        }

        $slug = $manifest->slug;
        $headers = $manifest->suggestedHeaders();
        $design = $manifest->has('design.json') ? $manifest->design() : [];
        $issues = [];

        if (($design['footer.show_unsubscribe'] ?? true) === false) {
            $issues[] = Issue::error($slug, 'MT019', 'marketing mail with the footer unsubscribe link switched off in design.json');
        }

        if (! isset($headers['List-Unsubscribe'])) {
            $issues[] = Issue::error($slug, 'MT020', 'marketing mail without a suggested List-Unsubscribe header');
        }

        if (($design['footer.show_address'] ?? true) === false) {
            $issues[] = Issue::error($slug, 'MT021', 'marketing mail with the postal address switched off; CAN-SPAM requires one');
        }

        return $issues;
    }

    /**
     * Names the template binds itself: loop variables and `{% set %}` slots.
     *
     * @return array<int, string>
     */
    private function localNames(string $body): array
    {
        $names = [];

        preg_match_all('/\{%\s*for\s+([A-Za-z_][A-Za-z0-9_]*)(?:\s*,\s*([A-Za-z_][A-Za-z0-9_]*))?/', $body, $loops);
        preg_match_all('/\{%\s*set\s+([A-Za-z_][A-Za-z0-9_]*)/', $body, $sets);

        foreach ([$loops[1], $loops[2], $sets[1]] as $group) {
            foreach ($group as $name) {
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        return $names;
    }

    /**
     * Every dotted identifier the markup reads, with string literals and block
     * property keys removed so `button(label: 'Open')` contributes nothing.
     *
     * @return array<int, string>
     */
    private function identifiers(string $body): array
    {
        $scrubbed = (string) preg_replace('/\{#.*?#\}/s', ' ', $body);
        $scrubbed = (string) preg_replace('/\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"/', "''", $scrubbed);
        $scrubbed = (string) preg_replace('/[A-Za-z_][A-Za-z0-9_]*\s*:/', ' ', $scrubbed);

        preg_match_all('/[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*/', $scrubbed, $matches);

        return $matches[0];
    }

    /**
     * @param  array<int, Issue>  $issues
     * @return array<int, Issue>
     */
    private function unique(array $issues): array
    {
        $seen = [];
        $unique = [];

        foreach ($issues as $issue) {
            $key = $issue->rule.'|'.$issue->message;

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $issue;
            }
        }

        return $unique;
    }
}
