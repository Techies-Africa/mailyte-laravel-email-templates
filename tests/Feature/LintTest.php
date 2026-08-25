<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Facades\Mailyte;
use Mailyte\EmailTemplates\Linting\Issue;
use Mailyte\EmailTemplates\Linting\TemplateLinter;
use Mailyte\EmailTemplates\Sources\SourceChain;
use Mailyte\EmailTemplates\Templates\TemplateManifest;

/**
 * The linter is the contract community bundles are accepted under, so it needs
 * to be shown catching things -- a rule that fires on nothing is worse than no
 * rule, because it reads like coverage.
 */

/**
 * @param  array<string, mixed>  $manifest
 * @param  array<string, string>  $files
 */
function bundle(array $manifest, array $files = []): TemplateManifest
{
    $slug = $manifest['slug'] ?? 'fixture';
    $directory = sys_get_temp_dir().'/mailyte-lint/'.$slug.'-'.bin2hex(random_bytes(4));

    mkdir($directory, 0777, true);

    $files = array_merge([
        'email.html' => "{{ heading(text: 'Hello', level: '1') }}",
        'design.json' => '{"tokens":{}}',
        'sample.json' => '{"default":{},"edge":{}}',
    ], $files);

    $manifest = array_merge([
        'slug' => $slug,
        'name' => 'Fixture',
        'description' => 'A bundle that exists only to be linted.',
        'version' => '1.0.0',
        'engine' => '^1.0',
        'type' => 'transactional',
        'category' => 'system',
        'supported_layouts' => ['branded'],
        'license' => 'MIT',
        'subject' => 'Fixture',
        'preheader' => 'A fixture bundle, used by the linter tests.',
        'variables' => [
            'name' => ['type' => 'string', 'description' => 'Who it is for'],
        ],
    ], $manifest);

    file_put_contents($directory.'/template.json', json_encode($manifest, JSON_PRETTY_PRINT));

    foreach ($files as $file => $contents) {
        file_put_contents($directory.'/'.$file, $contents);
    }

    return new TemplateManifest($slug, $directory, $manifest, 'test');
}

/**
 * @param  array<int, Issue>  $issues
 * @return array<int, string>
 */
function rules(array $issues): array
{
    return array_values(array_map(static fn (Issue $i): string => $i->rule, $issues));
}

beforeEach(function (): void {
    $this->linter = app(TemplateLinter::class);
});

it('passes every template in the catalog with no errors', function (string $slug) {
    $issues = app(TemplateLinter::class)->lint(Mailyte::catalog()[$slug]);
    $errors = array_filter($issues, static fn (Issue $i): bool => $i->isError());

    expect($errors)->toBe([], implode("\n", array_map('strval', $errors)));
})->with('catalog');

it('catches a token the markup reads but the manifest never declares', function () {
    $issues = $this->linter->lint(bundle([], [
        'email.html' => "{{ text(body: name ~ ' ' ~ invoice.total) }}",
    ]));

    expect(rules($issues))->toContain('MT042')
        ->and((string) $issues[0])->toContain('invoice.total');
});

it('does not mistake block names, loop variables or string literals for tokens', function () {
    $issues = $this->linter->lint(bundle([
        'variables' => [
            'name' => ['type' => 'string', 'description' => 'Who it is for'],
            'items' => ['type' => 'array', 'description' => 'Line items'],
        ],
    ], [
        'email.html' => <<<'TWIG'
            {{ heading(text: 'Welcome', level: '1') }}
            {% for item in items %}
                {{ text(body: item.label, align: 'left') }}
            {% endfor %}
            {{ button(label: 'Open', url: product.url) }}
            {{ text(body: name) }}
            TWIG,
    ]));

    expect(rules($issues))->toBe([]);
});

it('warns about a variable nothing uses', function () {
    $issues = $this->linter->lint(bundle([
        'variables' => [
            'name' => ['type' => 'string', 'description' => 'Who it is for'],
            'leftover' => ['type' => 'string', 'description' => 'Nothing reads this'],
        ],
    ], ['email.html' => "{{ heading(text: name, level: '1') }}"]));

    expect(rules($issues))->toContain('MT040');
});

it('reports a manifest that does not match the schema', function () {
    $issues = $this->linter->lint(bundle(['category' => 'not-a-category']));

    expect(rules($issues))->toContain('MT003');
});

it('requires marketing mail to carry an unsubscribe affordance', function () {
    $issues = $this->linter->lint(bundle([
        'type' => 'marketing',
        'variables' => ['name' => ['type' => 'string', 'description' => 'Who it is for']],
    ], [
        'email.html' => "{{ heading(text: name, level: '1') }}",
        'design.json' => '{"tokens":{"footer":{"show_unsubscribe":false,"show_address":false}}}',
    ]));

    expect(rules($issues))->toContain('MT019')
        ->and(rules($issues))->toContain('MT020')
        ->and(rules($issues))->toContain('MT021');
});

it('exempts transactional mail from the marketing rules', function () {
    $issues = $this->linter->lint(bundle([], [
        'email.html' => "{{ heading(text: name, level: '1') }}",
        'design.json' => '{"tokens":{"footer":{"show_unsubscribe":false,"show_address":false}}}',
    ]));

    expect(rules($issues))->not->toContain('MT019')
        ->and(rules($issues))->not->toContain('MT021');
});

it('honours a waiver, but keeps the issue and its stated reason', function () {
    $issues = $this->linter->lint(bundle([
        'type' => 'marketing',
        'headers' => ['suggested' => ['List-Unsubscribe' => '<{{ unsubscribe_url }}>']],
        'lint' => ['ignore' => [[
            'rule' => 'MT021',
            'reason' => 'Sender is outside CAN-SPAM; the address is set per-tenant at send time.',
        ]]],
    ], [
        'email.html' => "{{ heading(text: name, level: '1') }}",
        'design.json' => '{"tokens":{"footer":{"show_address":false}}}',
    ]));

    $waived = array_values(array_filter($issues, static fn (Issue $i): bool => $i->rule === 'MT021'));

    expect($waived)->toHaveCount(1)
        ->and($waived[0]->isWaived())->toBeTrue()
        ->and($waived[0]->isError())->toBeFalse()
        ->and($waived[0]->waivedBecause)->toContain('per-tenant');
});

it('flags a bundle with no design tokens of its own', function () {
    $bundle = bundle([], ['email.html' => "{{ heading(text: name, level: '1') }}"]);
    unlink($bundle->path('design.json'));

    expect(rules($this->linter->lint($bundle)))->toContain('MT004');
});

it('warns when the subject will be cut off on a phone', function () {
    $issues = $this->linter->lint(bundle([
        'subject' => 'Your monthly account summary, usage breakdown and upcoming renewal details',
    ], ['email.html' => "{{ heading(text: name, level: '1') }}"]));

    expect(rules($issues))->toContain('MT011');
});

it('exits non-zero from the console when a bundle is broken', function () {
    $broken = sys_get_temp_dir().'/mailyte-lint-cli-'.bin2hex(random_bytes(4));
    mkdir($broken.'/wrong', 0777, true);

    file_put_contents($broken.'/wrong/template.json', json_encode([
        'slug' => 'wrong',
        'name' => 'Wrong',
        'description' => 'Declares nothing it uses.',
        'version' => '1.0.0',
        'engine' => '^1.0',
        'type' => 'transactional',
        'category' => 'system',
        'supported_layouts' => ['branded'],
        'license' => 'MIT',
        'variables' => ['name' => ['type' => 'string', 'description' => 'Who it is for']],
    ]));
    file_put_contents($broken.'/wrong/email.html', '{{ text(body: undeclared.value) }}');
    file_put_contents($broken.'/wrong/design.json', '{"tokens":{}}');
    file_put_contents($broken.'/wrong/sample.json', '{"default":{},"edge":{}}');

    config()->set('mailyte.sources.paths', [$broken]);
    app()->forgetInstance(SourceChain::class);

    $this->artisan('mailyte:lint', ['slug' => ['wrong']])
        ->expectsOutputToContain('MT042')
        ->assertExitCode(1);
});

it('reports a clean catalog and exits zero', function () {
    $this->artisan('mailyte:lint', ['slug' => ['welcome']])->assertExitCode(0);
});

it('rejects a design whose light scheme is also dark', function () {
    $issues = $this->linter->lint(bundle([], [
        'email.html' => "{{ heading(text: name, level: '1') }}",
        'design.json' => '{"tokens":{"color":{
            "bg":{"light":"#0B0E12","dark":"#07090C"},
            "surface":{"light":"#11151B","dark":"#0E1116"}
        }}}',
    ]));

    expect(rules($issues))->toContain('MT015');
});

it('rejects a design whose dark scheme is not dark', function () {
    $issues = $this->linter->lint(bundle([], [
        'email.html' => "{{ heading(text: name, level: '1') }}",
        'design.json' => '{"tokens":{"color":{
            "surface":{"light":"#FFFFFF","dark":"#FAFAFA"}
        }}}',
    ]));

    expect(rules($issues))->toContain('MT016');
});

it('accepts a design that genuinely inverts', function () {
    $issues = $this->linter->lint(bundle([], [
        'email.html' => "{{ heading(text: name, level: '1') }}",
        'design.json' => '{"tokens":{"color":{
            "bg":{"light":"#EDF1F4","dark":"#07090C"},
            "surface":{"light":"#FFFFFF","dark":"#0E1116"}
        }}}',
    ]));

    expect(rules($issues))->not->toContain('MT015')
        ->and(rules($issues))->not->toContain('MT016');
});

it('ignores a token that is not a hex colour', function () {
    $issues = $this->linter->lint(bundle([], [
        'email.html' => "{{ heading(text: name, level: '1') }}",
        'design.json' => '{"tokens":{"color":{
            "surface":{"light":"linear-gradient(#fff,#eee)","dark":"transparent"}
        }}}',
    ]));

    expect(rules($issues))->not->toContain('MT015')
        ->and(rules($issues))->not->toContain('MT016');
});

it('reads its thresholds from config rather than hard-coding them', function () {
    $bundle = bundle([
        'subject' => 'A subject of moderate length, forty-eight chars',
    ], ['email.html' => "{{ heading(text: name, level: '1') }}"]);

    expect(rules(app(TemplateLinter::class)->lint($bundle)))->not->toContain('MT011');

    config()->set('mailyte.lint.rules.MT011.max_subject_chars', 20);
    app()->forgetInstance(TemplateLinter::class);

    expect(rules(app(TemplateLinter::class)->lint($bundle)))->toContain('MT011');
});
