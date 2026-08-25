<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Facades\Mailyte;

it('does not inset blocks twice when they sit inside a section', function () {
    // The gutter is applied per block. A section that also applied one would
    // push its band content out of line with the header and footer, which is
    // what a stacked-cell overflow and a ragged left edge both come from.
    $theme = Mailyte::themes()->default();

    $html = Mailyte::blocks()->render('section', [
        'slot' => 'content',
        'padding_y' => '30px',
    ], $theme);

    expect($html)->toContain('padding:30px 0');

    // ...and a section that genuinely wants an inset can still ask for one.
    $inset = Mailyte::blocks()->render('section', [
        'slot' => 'content',
        'padding_y' => '30px',
        'padding_x' => '18px',
    ], $theme);

    expect($inset)->toContain('padding:30px 18px');
});

it('scales display headings down on narrow screens', function () {
    // A 40px headline is about seven words wide at 600px and one word wide at
    // 320px. Without a mobile size it renders as a column of single words.
    $html = Mailyte::template('newsletter')
        ->with(['issue_title' => 'A headline long enough to wrap', 'cover_url' => 'https://example.test/a'])
        ->render()->html;

    expect($html)->toContain('.m-h1')
        ->and($html)->toContain('class="m-h1"')
        ->and($html)->toMatch('/\.m-h1[^{]*\{\s*font-size:(2[0-9]|3[0-5])px !important/');
});

it('leaves an already-restrained heading scale alone', function () {
    // invoice sets a 20px document title; there is nothing to shrink, so no
    // mobile override should be emitted for it.
    $html = Mailyte::template('invoice')
        ->with([
            'invoice' => ['number' => 'INV-1', 'due_date' => 'today', 'total' => '$1.00'],
            'pay_url' => 'https://example.test/pay',
        ])
        ->render()->html;

    expect($html)->not->toMatch('/\.m-h1[^{]*\{\s*font-size/');
});

it('keeps big figures readable on mobile', function () {
    $html = Mailyte::template('trial-ending')
        ->with([
            'days_left' => 3,
            'trial_ends_at' => '8 September',
            'action_url' => 'https://example.test/plans',
        ])
        ->render()->html;

    expect($html)->toContain('m-stat-display')
        ->and($html)->toMatch('/\.m-stat-display[^{]*\{\s*font-size:\d+px !important/');
});

it('makes stacked cells border-box so their padding cannot overflow', function () {
    // A td is border-box; .m-stack turns it into a block, which is content-box.
    // Without this the cell's horizontal padding is added outside its 100%
    // width and the message gets a sideways scrollbar on a phone.
    $css = Mailyte::template('welcome')
        ->with(['action_url' => 'https://example.test/a'])
        ->render()->html;

    expect($css)->toMatch('/\.m-stack\s*\{[^}]*box-sizing:border-box\s*!important/');
});

it('lets a stacked image fill the column it lands in', function () {
    // An image sized for a 42% desktop column keeps that pixel width once the
    // column stacks, leaving a gap beside it.
    $html = Mailyte::template('promotion')
        ->with([
            'offer_headline' => 'Sale',
            'expires_text' => 'Friday',
            'action_url' => 'https://example.test/sale',
            'hero_image' => 'https://example.test/hero.jpg',
        ])
        ->render()->html;

    expect($html)->toMatch('/\.m-img-fill\s*\{[^}]*max-width:100%\s*!important/')
        ->and($html)->toContain('class="m-img-fill"');
});

it('gives every template room above and below the card', function (string $slug) {
    // The card butting against the top of the viewport reads as a rendering
    // fault rather than a design, so nothing ships with no outer padding.
    $manifest = Mailyte::catalog()[$slug];
    $design = $manifest->design();

    $padding = (int) preg_replace('/[^0-9]/', '', (string) ($design['layout.outer_padding'] ?? '32px'));

    expect($padding)->toBeGreaterThanOrEqual(32, "{$slug} has too little room above and below");
})->with('catalog');

it('moves the button plate with its label in dark mode', function () {
    // The dark scheme repaints the button label. Without a matching rule for
    // the plate underneath, the pair loses contrast — dark ink on a light
    // accent — which is invisible in exactly the clients that force dark mode.
    $html = Mailyte::template('getting-started')
        ->with(['action_url' => 'https://example.test/a'])
        ->render()->html;

    expect($html)->toMatch('/<a[^>]*class="[^"]*\bm-btn-plate\b/')
        ->and($html)->toMatch('/\.m-btn-plate\s*\{\s*background-color:/');
});

it('keeps authored-dark bands from being repainted', function () {
    // A band that ships dark already carries light text. The blanket dark-mode
    // colour rules would undo that, so .m-hold restores it — and has to be
    // declared after .m-muted, since equal specificity is settled by order.
    $css = Mailyte::template('first-milestone')
        ->with(['milestone_verb' => 'shipped', 'action_url' => 'https://example.test/a'])
        ->render()->html;

    $holdAt = strpos($css, '.m-hold, .m-hold *');
    $mutedAt = strpos($css, '.m-muted, .m-muted *');

    expect($holdAt)->toBeGreaterThan($mutedAt);
});

it('gives the plain layout footer the same gutter as its body', function () {
    // The gutter lives on the blocks. The footer is not a block, so a layout
    // that drops it into an unpadded cell leaves the footer flush against the
    // canvas while every line above it sits inside the measure.
    $html = Mailyte::template('verify-email-typeset')
        ->with(['verification_code' => '1'])
        ->layout('plain')
        ->render()->html;

    // The footer's own wrapper carries the gutter class.
    expect(substr_count($html, 'class="m-gutter"'))->toBeGreaterThan(1);
});

it('keeps a full-width button inside the measure', function () {
    // An inline-block is content-box, so width:100% plus horizontal padding
    // renders wider than its column — the button pushes the whole message
    // sideways and the client shows a horizontal scrollbar.
    //
    // Assert on the element, not on a CSS string: the inliner re-serialises
    // every style attribute (spaces after colons, declarations reordered), so
    // matching the source text is testing the inliner rather than the button.
    $html = Mailyte::template('verify-email-link')
        ->with(['action_url' => 'https://example.test/verify/abc'])
        ->render()->html;

    preg_match_all('/<a\b[^>]*style="([^"]*)"[^>]*>/i', $html, $anchors);

    $fullWidth = array_values(array_filter(
        $anchors[1],
        static fn (string $style): bool => (bool) preg_match('/width:\s*100%/', $style)
    ));

    expect($fullWidth)->not->toBeEmpty();

    foreach ($fullWidth as $style) {
        expect($style)->toMatch('/box-sizing:\s*border-box/');
    }
});
