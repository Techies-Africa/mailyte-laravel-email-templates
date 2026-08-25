<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Dashboard;
use Mailyte\EmailTemplates\Usage\UsageRecorder;

/**
 * The usage page totals every recorded send, but only ever listed rows for
 * templates in the catalog. Anything sending from outside it -- the notification
 * shell, or a template renamed after it had already sent -- was counted in the
 * headline figures and invisible in the table, so the two never reconciled.
 */
beforeEach(function (): void {
    config()->set('cache.default', 'array');
    config()->set('mailyte.usage.enabled', true);
    config()->set('mailyte.usage.driver', 'cache');
    app()->forgetInstance(UsageRecorder::class);
    Dashboard::auth(fn () => true);
});

afterEach(function (): void {
    Dashboard::$authUsing = null;
});

it('lists a template that sends but is not in the catalog', function () {
    $recorder = app(UsageRecorder::class);
    $recorder->flush();
    $recorder->record('laravel-notification', '1.0.0');
    $recorder->record('laravel-notification', '1.0.0');
    $recorder->record('invoice', '1.0.0');

    $this->get('/mailyte/usage')
        ->assertOk()
        // the catalog template, as before
        ->assertSee('invoice', false)
        // and the shell, which the catalog does not list
        ->assertSee('laravel-notification', false)
        ->assertSee('unlisted', false);
});

it('reconciles the headline count with the rows it shows', function () {
    $recorder = app(UsageRecorder::class);
    $recorder->flush();
    $recorder->record('laravel-notification', '1.0.0');
    $recorder->record('invoice', '1.0.0');

    $response = $this->get('/mailyte/usage')->assertOk();
    $html = $response->getContent();

    // Two sends, two templates used, and both must appear as rows.
    expect($html)->toContain('>2</b><span>Sends recorded')
        ->and($html)->toContain('>2</b><span>Templates used at least once')
        ->and(substr_count($html, 'laravel-notification'))->toBeGreaterThan(0)
        ->and(substr_count($html, '>invoice</code>'))->toBeGreaterThan(0);
});

it('names a template that is no longer installed at all', function () {
    $recorder = app(UsageRecorder::class);
    $recorder->flush();
    $recorder->record('a-template-since-deleted', '1.0.0');

    $this->get('/mailyte/usage')
        ->assertOk()
        ->assertSee('a-template-since-deleted', false)
        ->assertSee('not installed', false);
});

it('shows no extra rows when everything that sent is in the catalog', function () {
    $recorder = app(UsageRecorder::class);
    $recorder->flush();
    $recorder->record('invoice', '1.0.0');

    $html = $this->get('/mailyte/usage')->assertOk()->getContent();

    expect($html)->not->toContain('unlisted')
        ->and($html)->not->toContain('not installed')
        ->and($html)->not->toContain('Sending, but not in the catalog');
});
