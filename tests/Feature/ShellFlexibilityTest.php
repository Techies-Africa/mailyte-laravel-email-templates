<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Facades\Mailyte;

/**
 * The shell is what a brand is actually judged on, so its contract is tested
 * rather than assumed: which sections appear, where the logo sits, and what a
 * template may switch off.
 */
function render(array $tokens = [], string $layout = 'branded'): string
{
    return Mailyte::template('welcome')
        ->with(['action_url' => 'https://example.test/app'])
        ->theme($tokens)
        ->layout($layout)
        ->render()
        ->html;
}

it('always carries social, address and copyright in the branded footer', function () {
    $html = render([
        'social' => [
            ['name' => 'Mastodon', 'url' => 'https://example.test/@acme'],
            ['name' => 'LinkedIn', 'url' => 'https://example.test/linkedin'],
        ],
    ]);

    expect($html)->toContain('https://example.test/@acme')
        ->and($html)->toContain('1 Example Way, Springfield')
        ->and($html)->toContain('&copy;')
        ->and($html)->toContain((string) date('Y'));
});

it('places the logo left, centre or right on request', function (string $align) {
    $html = render([
        'logo.url' => 'https://example.test/logo.png',
        'header.align' => $align,
    ]);

    expect($html)->toContain('align="'.$align.'"')
        ->and($html)->toContain('https://example.test/logo.png');
})->with(['left', 'center', 'right']);

it('renders a banner with the logo laid over it', function () {
    $html = render([
        'logo.url' => 'https://example.test/logo.png',
        'header.banner_url' => 'https://example.test/banner.jpg',
        'header.logo_on_banner' => true,
    ]);

    // Match the declaration, not its formatting: the inliner re-serialises
    // every style attribute it touches, spacing and all.
    expect($html)->toMatch('/background-image:\s*url\(.https:\/\/example\.test\/banner\.jpg.\)/')
        ->and($html)->toContain('v:rect')
        ->and($html)->toContain('https://example.test/logo.png');
});

it('renders a banner with no logo at all', function () {
    $html = render([
        'header.banner_url' => 'https://example.test/banner.jpg',
        'header.show_logo' => false,
    ]);

    expect($html)->toContain('https://example.test/banner.jpg')
        ->and($html)->not->toContain('logo.png');
});

it('lets a template switch footer sections off', function () {
    $html = render([
        'social' => [['name' => 'Mastodon', 'url' => 'https://example.test/@acme']],
        'footer.show_social' => false,
        'footer.show_address' => false,
        'footer.show_copyright' => false,
    ]);

    expect($html)->not->toContain('https://example.test/@acme')
        ->and($html)->not->toContain('1 Example Way, Springfield')
        ->and($html)->not->toContain('&copy;');
});

it('keeps only the plain layout free of social chrome', function () {
    // `plain` is the deliverability-first layout and stays chrome-free.
    // `minimal` is an ordinary product email and carries the social row.
    // welcome does not declare `plain`, so the plain case uses one that does.
    $social = [['name' => 'Mastodon', 'url' => 'https://example.test/@acme']];

    $plain = Mailyte::template('getting-started')
        ->with(['action_url' => 'https://example.test/a'])
        ->theme(['social' => $social])
        ->layout('plain')
        ->render()->html;

    expect($plain)->not->toContain('https://example.test/@acme')
        ->and($plain)->toContain('1 Example Way, Springfield')
        ->and(render(['social' => $social], 'minimal'))->toContain('https://example.test/@acme');
});

it('keeps unfilled buttons out of the dark-mode colour swap', function () {
    // .m-btn repaints its label in the filled button's ink under forced dark
    // mode. On a transparent outline or link button that is invisible text.
    $outline = Mailyte::template('verify-email')       // outline CTA
        ->with(['verification_code' => '1', 'action_url' => 'https://example.test/v'])
        ->render()->html;

    $filled = Mailyte::template('getting-started')     // filled CTA
        ->with(['action_url' => 'https://example.test/app', 'button_label' => 'Start now'])
        ->render()->html;

    expect($filled)->toContain('Start now');

    // Match anchors only, not the class definitions in the compiled stylesheet.
    // Filled buttons also carry a plate class, so match the list, not the value.
    expect(preg_match('/<a[^>]*class="[^"]*\bm-btn\b/', $outline))->toBe(0)
        ->and(preg_match('/<a[^>]*class="[^"]*\bm-btn\b/', $filled))->toBe(1);
});

it('centres and right-aligns the logo with margins, not just cell alignment', function () {
    // align="center" centres inline content; a display:block image is not
    // inline, so only Chrome's legacy -webkit-center would save it.
    $logoStyle = function (string $align): string {
        $html = render([
            'logo.url' => 'https://example.test/logo.png',
            'header.align' => $align,
        ]);

        // The canvas table also uses `margin:0 auto`, so look at the logo only.
        preg_match('/<img[^>]+logo\.png[^>]*>/', $html, $m);

        return $m[0] ?? '';
    };

    expect($logoStyle('center'))->toMatch('/margin:\s*0 auto/')
        ->and($logoStyle('right'))->toMatch('/margin:\s*0 0 0 auto/')
        ->and($logoStyle('left'))->not->toMatch('/margin:\s*0 auto/');
});

it('swaps the logo for a light-ink mark in dark mode', function () {
    // logo.dark_url existed as a token but nothing read it, so a dark mark
    // stayed dark on an inverted canvas.
    $html = render([
        'logo.url' => 'https://example.test/logo-dark.png',
        'logo.dark_url' => 'https://example.test/logo-light.png',
    ]);

    expect($html)->toContain('logo-dark.png')
        ->and($html)->toContain('logo-light.png')
        ->and($html)->toMatch('/\.m-logo-dark\s*\{\s*display:block/')
        ->and($html)->toMatch('/\.m-logo-light\s*\{\s*display:none/');
});

it('puts the unsubscribe link last and drives it from a parameter', function () {
    // Unset: shown because a URL was supplied, and shown after the copyright.
    $auto = Mailyte::template('welcome')
        ->with(['action_url' => 'https://example.test/a'])
        ->theme(['footer.copyright' => 'COPYRIGHT-MARK'])
        ->render()->html;

    expect($auto)->toContain('unsubscribe.test')
        ->and(strpos($auto, 'unsubscribe.test'))->toBeGreaterThan(strpos($auto, 'COPYRIGHT-MARK'));

    // Explicitly off, even though a URL exists.
    $off = Mailyte::template('welcome')
        ->with(['action_url' => 'https://example.test/a'])
        ->theme(['footer.show_unsubscribe' => false])
        ->render()->html;

    expect($off)->not->toContain('unsubscribe.test');
});

it('shows no unsubscribe row when there is no url to link to', function () {
    config()->set('mailyte.globals.unsubscribe_url', null);
    config()->set('mailyte.globals.preferences_url', null);

    $html = Mailyte::template('welcome')
        ->with(['action_url' => 'https://example.test/a'])
        ->render()->html;

    expect($html)->not->toContain('Unsubscribe');
});
