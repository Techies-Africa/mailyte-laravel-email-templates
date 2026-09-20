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

/**
 * Whether the mark and the name are SIDE BY SIDE -- sibling cells of one row.
 *
 * The arrangement needs asserting, not just the presence of both: the name sat
 * under the mark for a release, every count-based test here passed either way,
 * and nothing failed when it moved. Two `<td>`s in one `<tr>` is the only thing
 * that distinguishes a lockup from a stack in table markup.
 */
function nameIsBesideMark(string $html): bool
{
    return (bool) preg_match(
        '~<tr>\s*<td class="m-stack"[^>]*>.*?<img[^>]*class="[^"]*m-logo.*?</td>\s*'
        .'<td class="m-lockup-name"[^>]*>.*?'.preg_quote(NAME, '~').'.*?</td>\s*</tr>~s',
        $html,
    );
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
    expect(nameIsBesideMark($html))->toBeTrue();
});

/**
 * A lockup centres as a UNIT. A full-width table would centre each half in its
 * own column, putting the mark and the name at opposite ends of the header --
 * which looks like a bug rather than a brand.
 */
it('shrink-wraps the lockup so it aligns as one', function () {
    $html = renderWelcome([
        'logo.url' => 'https://cdn.example/logo.png',
        'header.show_name' => true,
        'logo.align' => 'center',
    ]);

    expect($html)->toMatch('~<table[^>]*align="center"[^>]*>\s*<tr>\s*<td class="m-stack"~');
    expect($html)->not->toMatch('~<table[^>]*width="100%"[^>]*align="center"[^>]*>\s*<tr>\s*<td class="m-stack"~');
});

/**
 * Below 480px a wide mark and a long name together run past the canvas, so the
 * name drops underneath. The rule has to actually ship in the stylesheet --
 * the markup alone would overflow.
 */
it('stacks the lockup on a narrow screen', function () {
    $html = renderWelcome(['logo.url' => 'https://cdn.example/logo.png', 'header.show_name' => true]);

    expect($html)->toContain('.m-lockup-name');
    // padding-left becomes padding-top: a stacked cell is content-box, so the
    // desktop gutter would be added outside its 100% width and scroll sideways.
    expect($html)->toMatch('~\.m-lockup-name\s*\{[^}]*padding:8px 0 0 0~');
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
