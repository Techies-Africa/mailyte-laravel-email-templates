<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Facades\Mailyte;

/**
 * A logo and the company name, together.
 *
 * Worth knowing before reading the rest: with a logo, the name is ALREADY the
 * image's alt text, so a recipient with images blocked does see it. What the
 * flag adds is the name as real, styled text rather than a browser's alt
 * rendering -- which several clients show as nothing at all.
 *
 * The header was strictly either/or: the logo when there was one, the name in
 * text when there was not. A sensible default, because a logo usually contains
 * the name -- but not always, and logo-only has a real failure mode: most
 * clients block images by default, so the header arrives as an empty box with
 * nothing saying who sent it. Text survives that.
 *
 * Off unless a theme asks for it, so nothing already sent changes shape.
 *
 * Everything here counts <img> TAGS rather than searching for class names: the
 * dark-mode stylesheet mentions `m-logo-light` whether or not any image uses
 * it, and asserting on the substring passes for the wrong reason.
 */
const NAME = 'Northwind Trading';

function renderWelcome(array $theme = []): string
{
    return Mailyte::template('welcome')
        ->with([
            'first_name' => 'Ada',
            'action_url' => 'https://example.com/start',
            'product' => ['name' => NAME, 'url' => 'https://example.com'],
        ])
        ->layout('branded')
        ->theme($theme)
        ->html();
}

// `header` is a PHP builtin, hence renderWelcome.

/**
 * How many times the name is DRAWN as header text.
 *
 * Not a substring search: with a logo the name is already in its alt
 * attribute -- which is the right thing for blocked images, and would make a
 * naive `toContain` pass for entirely the wrong reason. The title, preheader
 * and footer carry it too.
 */
function headerNameCount(string $html): int
{
    return preg_match_all(
        '~<span style="font-family:[^"]*font-size:17px;[^"]*">\s*'.preg_quote(NAME, '~').'~',
        $html,
    );
}

function logoTags(string $html): int
{
    return preg_match_all('~<img[^>]*class="[^"]*m-logo~', $html);
}

it('shows the logo alone by default', function () {
    $html = renderWelcome(['logo.url' => 'https://cdn.example/logo.png']);

    expect(logoTags($html))->toBe(1);
    expect(headerNameCount($html))->toBe(0);
});

it('shows the name beside the logo when the theme asks', function () {
    $html = renderWelcome(['logo.url' => 'https://cdn.example/logo.png', 'header.show_name' => true]);

    expect(logoTags($html))->toBe(1);
    expect(headerNameCount($html))->toBe(1);
});

/** The old behaviour, untouched: no logo means the name carries the header. */
it('still shows the name alone when there is no logo', function () {
    $html = renderWelcome();

    expect(logoTags($html))->toBe(0);
    expect(headerNameCount($html))->toBe(1);
});

/** Asking for the name without having a logo must not print it twice. */
it('does not print the name twice when there is no logo', function () {
    $html = renderWelcome(['header.show_name' => true]);

    expect(headerNameCount($html))->toBe(1);
});
