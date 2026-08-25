<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Deliverability\DeliverabilityAudit;
use Mailyte\EmailTemplates\Deliverability\EmlWriter;
use Mailyte\EmailTemplates\Facades\Mailyte;
use Mailyte\EmailTemplates\Linting\Issue;
use Mailyte\EmailTemplates\Rendering\RenderedEmail;
use Mailyte\EmailTemplates\Templates\TemplateManifest;

/**
 * An audit that never fires is worse than no audit, because it reads like
 * coverage. Every rule below is shown catching the thing it claims to catch,
 * and shown not firing on the honest case.
 */

/**
 * @param  array<string, mixed>  $overrides
 */
function fakeEmail(array $overrides = []): RenderedEmail
{
    return new RenderedEmail(
        html: $overrides['html'] ?? '<html><body><p>'.str_repeat('Real sentences carry the message. ', 12).'</p></body></html>',
        text: $overrides['text'] ?? str_repeat('Real sentences carry the message. ', 12),
        subject: $overrides['subject'] ?? 'Your receipt from Acme',
        preheader: $overrides['preheader'] ?? 'Paid in full, nothing owing.',
        suggestedHeaders: $overrides['headers'] ?? [],
        slug: 'fixture',
    );
}

/**
 * A template's own default sample, which is what makes it renderable.
 *
 * @return array<string, mixed>
 */
function sample(string $slug): array
{
    $samples = Mailyte::catalog()[$slug]->samples();

    return $samples['default'] ?? reset($samples) ?: [];
}

function fakeManifest(string $type = 'transactional'): TemplateManifest
{
    return new TemplateManifest('fixture', sys_get_temp_dir(), [
        'slug' => 'fixture',
        'type' => $type,
        'variables' => [],
    ], 'test');
}

/**
 * @param  array<int, Issue>  $issues
 * @return array<int, string>
 */
function codes(array $issues): array
{
    return array_values(array_map(static fn (Issue $i): string => $i->rule, $issues));
}

beforeEach(function (): void {
    $this->audit = app(DeliverabilityAudit::class);
});

it('passes every template in the catalog with no errors', function (string $slug) {
    $manifest = Mailyte::catalog()[$slug];
    $audit = app(DeliverabilityAudit::class);

    $samples = $manifest->samples();
    $data = $samples['default'] ?? reset($samples) ?: [];

    foreach ($manifest->supportedLayouts() as $layout) {
        $email = Mailyte::template($slug)->with($data)->layout($layout)->render();
        $errors = array_filter($audit->audit($email, $manifest), static fn (Issue $i): bool => $i->isError());

        expect($errors)->toBe([], "{$slug}/{$layout}: ".implode('; ', array_map('strval', $errors)));
    }
})->with('catalog');

it('accepts an ordinary transactional message', function () {
    expect(codes($this->audit->audit(fakeEmail(), fakeManifest())))->toBe([]);
});

it('catches HTML past the size Gmail clips at', function () {
    $filler = '<p>'.str_repeat('Padding that pushes this message over the threshold. ', 2200).'</p>';
    $issues = $this->audit->audit(fakeEmail(['html' => "<html><body>{$filler}</body></html>"]), fakeManifest());

    expect(codes($issues))->toContain('MT050')
        ->and((string) $issues[0])->toContain('Gmail clips');
});

it('catches a message with no plain-text part', function () {
    expect(codes($this->audit->audit(fakeEmail(['text' => '']), fakeManifest())))->toContain('MT052');
});

it('catches a text part that does not say what the HTML says', function () {
    $issues = $this->audit->audit(fakeEmail(['text' => 'View this email in your browser.']), fakeManifest());

    expect(codes($issues))->toContain('MT052');
});

it('catches a link whose label points somewhere else', function () {
    $issues = $this->audit->audit(fakeEmail([
        'html' => '<p>Sign in at <a href="https://evil.example.net/login">acme.com</a> to continue reading this message.</p>',
    ]), fakeManifest());

    expect(codes($issues))->toContain('MT054');
});

it('does not flag a label that matches its own host, or a subdomain of it', function () {
    $issues = $this->audit->audit(fakeEmail([
        'html' => '<p>'.str_repeat('Body copy so the message is not thin. ', 12)
            .'<a href="https://acme.com/x">acme.com</a> <a href="https://mail.acme.com/y">acme.com</a></p>',
    ]), fakeManifest());

    expect(codes($issues))->not->toContain('MT054');
});

it('catches plain http links but ignores a local development host', function () {
    $remote = $this->audit->audit(fakeEmail([
        'html' => '<p>'.str_repeat('Body copy. ', 20).'<a href="http://acme.com/x">Open</a></p>',
    ]), fakeManifest());

    $local = $this->audit->audit(fakeEmail([
        'html' => '<p>'.str_repeat('Body copy. ', 20).'<a href="http://127.0.0.1:8000/x">Open</a></p>',
    ]), fakeManifest());

    expect(codes($remote))->toContain('MT055')
        ->and(codes($local))->not->toContain('MT055');
});

it('catches a link shortener', function () {
    $issues = $this->audit->audit(fakeEmail([
        'html' => '<p>'.str_repeat('Body copy. ', 20).'<a href="https://bit.ly/3xYz">Open</a></p>',
    ]), fakeManifest());

    expect(codes($issues))->toContain('MT056');
});

it('catches an image with no alt attribute', function () {
    $issues = $this->audit->audit(fakeEmail([
        'html' => '<p>'.str_repeat('Body copy. ', 20).'</p><img src="https://acme.com/a.png" width="600">',
    ]), fakeManifest());

    expect(codes($issues))->toContain('MT057');
});

it('does not count icons or the hidden half of a light/dark pair as content images', function () {
    $icons = str_repeat('<img class="m-social-light" src="https://a.test/i.png" alt="X" width="22" height="22">'
        .'<img class="m-social-dark" src="https://a.test/i2.png" alt="X" width="22" height="22">', 5);
    $issues = $this->audit->audit(fakeEmail([
        'html' => '<p>'.str_repeat('Real body copy that carries the message. ', 6).'</p>'.$icons
            .'<img class="m-logo m-logo-light" src="https://a.test/l.png" alt="Acme" width="190">'
            .'<img class="m-logo m-logo-dark" src="https://a.test/ld.png" alt="Acme" width="190">',
        'text' => str_repeat('Real body copy that carries the message. ', 6),
    ]), fakeManifest());

    // Ten icons and a light/dark logo pair resolve to one content image, so the
    // image-heavy rule stays quiet.
    expect(codes($issues))->not->toContain('MT058');
});

it('catches a message that is an image with nothing to read', function () {
    $issues = $this->audit->audit(fakeEmail([
        'html' => '<img src="https://acme.com/whole-email.png" alt="Sale" width="600">',
        'text' => 'Sale',
    ]), fakeManifest());

    expect(codes($issues))->toContain('MT058');
});

it('catches spam-trigger phrases stacked into a short message', function () {
    $issues = $this->audit->audit(fakeEmail([
        'html' => '<p>ACT NOW! Free gift, no obligation, risk free. Limited time offer expires. Order now, winner!</p>',
        'text' => 'ACT NOW! Free gift, no obligation, risk free. Limited time offer expires. Order now, winner!',
    ]), fakeManifest('marketing'));

    expect(codes($issues))->toContain('MT059');
});

it('tolerates a single trigger phrase in real copy', function () {
    $issues = $this->audit->audit(fakeEmail([
        'html' => '<p>'.str_repeat('Your trial gives you the whole product for two weeks. ', 8)
            .'There is no obligation, and nothing is charged until you choose a plan.</p>',
        'text' => str_repeat('Your trial gives you the whole product for two weeks. ', 8),
    ]), fakeManifest());

    expect(codes($issues))->not->toContain('MT059');
});

it('catches a shouting subject line', function () {
    expect(codes($this->audit->audit(fakeEmail(['subject' => 'URGENT ACTION REQUIRED']), fakeManifest())))
        ->toContain('MT060');
    expect(codes($this->audit->audit(fakeEmail(['subject' => 'Big news!!!']), fakeManifest())))
        ->toContain('MT060');
});

it('catches markup every client strips', function () {
    foreach (['<script>alert(1)</script>', '<iframe src="x"></iframe>', '<form action="x"></form>'] as $markup) {
        $issues = $this->audit->audit(fakeEmail([
            'html' => '<p>'.str_repeat('Body copy. ', 20).'</p>'.$markup,
        ]), fakeManifest());

        expect(codes($issues))->toContain('MT061');
    }
});

it('catches an inline event handler', function () {
    $issues = $this->audit->audit(fakeEmail([
        'html' => '<p onclick="go()">'.str_repeat('Body copy. ', 20).'</p>',
    ]), fakeManifest());

    expect(codes($issues))->toContain('MT061');
});

it('catches an empty subject', function () {
    expect(codes($this->audit->audit(fakeEmail(['subject' => '']), fakeManifest())))->toContain('MT062');
});

it('grades the missing-unsubscribe rule by compliance class, as the schema promises', function () {
    // marketing: legally required, so an error. notification: good practice,
    // so a warning -- otherwise a clean install fails --strict before the
    // application has configured an unsubscribe URL at all.
    $marketing = $this->audit->audit(fakeEmail(), fakeManifest('marketing'));
    $notification = $this->audit->audit(fakeEmail(), fakeManifest('notification'));

    $pick = fn (array $issues) => array_values(array_filter(
        $issues, static fn (Issue $i): bool => $i->rule === 'MT063'
    ));

    expect($pick($marketing))->toHaveCount(1)
        ->and($pick($marketing)[0]->severity)->toBe(Issue::ERROR)
        ->and($pick($notification))->toHaveCount(1)
        ->and($pick($notification)[0]->severity)->toBe(Issue::WARNING)
        ->and($pick($notification)[0]->isError())->toBeFalse();
});

it('requires a marketing message to carry an unsubscribe route in the rendered output', function () {
    $bare = $this->audit->audit(fakeEmail(), fakeManifest('marketing'));
    $withLink = $this->audit->audit(fakeEmail([
        'html' => '<p>'.str_repeat('Body copy. ', 20).'</p><a href="https://acme.com/u">Unsubscribe</a>',
    ]), fakeManifest('marketing'));

    expect(codes($bare))->toContain('MT063')
        ->and(codes($withLink))->not->toContain('MT063');
});

it('exempts transactional mail from the unsubscribe rule', function () {
    expect(codes($this->audit->audit(fakeEmail(), fakeManifest())))->not->toContain('MT063');
});

it('reads its thresholds from config rather than hard-coding them', function () {
    $email = fakeEmail([
        'html' => '<p>'.str_repeat('Body copy. ', 20).'</p>'
            .str_repeat('<a href="https://acme.com/x">Open</a>', 8),
    ]);

    expect(codes($this->audit->audit($email, fakeManifest())))->not->toContain('MT053');

    config()->set('mailyte.lint.rules.MT053.max_links', 3);
    app()->forgetInstance(DeliverabilityAudit::class);

    expect(codes(app(DeliverabilityAudit::class)->audit($email, fakeManifest())))->toContain('MT053');
});

it('honours a per-template waiver', function () {
    $manifest = new TemplateManifest('fixture', sys_get_temp_dir(), [
        'slug' => 'fixture',
        'type' => 'transactional',
        'variables' => [],
        'lint' => ['ignore' => [[
            'rule' => 'MT052',
            'reason' => 'Ships a text part built by the sending application, not by the template.',
        ]]],
    ], 'test');

    $issues = $this->audit->audit(fakeEmail(['text' => '']), $manifest);
    $waived = array_values(array_filter($issues, static fn (Issue $i): bool => $i->rule === 'MT052'));

    expect($waived[0]->isWaived())->toBeTrue()
        ->and($waived[0]->isError())->toBeFalse();
});

/**
 * The bug this pins: suggested headers are tokenised, and were reaching the
 * message unrendered -- shipping `List-Unsubscribe: <{{ unsubscribe_url }}>`,
 * which is invalid and breaks the one-click unsubscribe that bulk senders are
 * now required to support.
 */
it('renders tokens in suggested headers rather than shipping them literally', function () {
    $email = Mailyte::template('promotion')
        ->with(sample('promotion') + ['unsubscribe_url' => 'https://acme.test/u/abc123'])
        ->render();

    expect($email->suggestedHeaders)->toHaveKey('List-Unsubscribe')
        ->and($email->suggestedHeaders['List-Unsubscribe'])->toBe('<https://acme.test/u/abc123>')
        ->and($email->suggestedHeaders['List-Unsubscribe'])->not->toContain('{{');
});

it('drops a suggested header whose token resolved to nothing', function () {
    $manifest = Mailyte::catalog()['promotion'];
    $email = Mailyte::template('promotion')->with(sample('promotion') + ['unsubscribe_url' => null])->render();

    expect($manifest->suggestedHeaders())->toHaveKey('List-Unsubscribe')
        ->and($email->suggestedHeaders)->not->toHaveKey('List-Unsubscribe');
});

it('writes an eml that a mail parser accepts', function () {
    $email = Mailyte::template('invoice')->with(sample('invoice'))->render();
    $eml = (new EmlWriter)->write($email);

    expect($eml)->toContain('MIME-Version: 1.0')
        ->and($eml)->toContain('Content-Type: multipart/alternative;')
        ->and($eml)->toContain('Content-Type: text/plain; charset=UTF-8')
        ->and($eml)->toContain('Content-Type: text/html; charset=UTF-8')
        ->and($eml)->toContain('X-Mailyte-Template: invoice@')
        // CRLF line endings, as the RFC requires.
        ->and(substr_count($eml, "\r\n"))->toBeGreaterThan(10);

    // A non-ASCII subject has to be encoded or the file is not a valid message.
    $accented = (new EmlWriter)->write(new RenderedEmail(
        html: '<p>x</p>', text: 'x', subject: 'Réservation confirmée — merci', preheader: '',
    ));

    expect($accented)->toContain('Subject: =?UTF-8?B?')
        ->and($accented)->not->toContain('Réservation confirmée');
});
