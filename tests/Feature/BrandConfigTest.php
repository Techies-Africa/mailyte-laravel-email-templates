<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Facades\Mailyte;

/**
 * The brand block is the one place an application sets what every message has
 * in common. If it does not reach the rendered mail, it is decoration.
 */
function brand(array $brand): string
{
    foreach ($brand as $key => $value) {
        config()->set("mailyte.brand.{$key}", $value);
    }

    return Mailyte::template('welcome')
        ->with(['action_url' => 'https://example.test/app'])
        ->layout('branded')
        ->render()->html;
}

it('drives the logo, social accounts and footer from config', function () {
    $html = brand([
        'logo' => [
            'url' => 'https://cdn.example.test/logo.png',
            'dark_url' => 'https://cdn.example.test/logo-light.png',
            'alt' => 'Acme Corp',
            'width' => '180',
            'align' => 'center',
        ],
        'social' => [
            ['name' => 'X', 'url' => 'https://x.test/acme'],
            ['name' => 'LinkedIn', 'url' => 'https://li.test/acme'],
        ],
        'social_icons' => ['base_url' => 'https://cdn.example.test/social', 'style' => 'round', 'size' => '24'],
        'footer' => ['address' => 'Acme Ltd, 4 Config Street', 'reason' => 'You have an Acme account.'],
    ]);

    expect($html)->toContain('https://cdn.example.test/logo.png')
        ->and($html)->toContain('https://cdn.example.test/logo-light.png')
        ->and($html)->toContain('Acme Corp')
        ->and($html)->toContain('https://x.test/acme')
        ->and($html)->toContain('https://li.test/acme')
        ->and($html)->toContain('https://cdn.example.test/social/x-')
        ->and($html)->toContain('Acme Ltd, 4 Config Street')
        ->and($html)->toContain('You have an Acme account.');
});

it('lets a per-send override beat the brand config', function () {
    config()->set('mailyte.brand.logo.url', 'https://cdn.example.test/house.png');

    $html = Mailyte::template('welcome')
        ->with(['action_url' => 'https://example.test/app'])
        ->theme(['logo.url' => 'https://cdn.example.test/tenant.png'])
        ->layout('branded')
        ->render()->html;

    // Per-tenant branding is the whole reason the order is theme -> design ->
    // brand -> per-send.
    expect($html)->toContain('tenant.png')
        ->and($html)->not->toContain('house.png');
});

it('overrides a template design, because a template does not own the brand', function () {
    // trial-started sets its own footer tokens; the application still decides
    // whether a social row appears.
    config()->set('mailyte.brand.footer.show_social', false);
    config()->set('mailyte.brand.social', [['name' => 'X', 'url' => 'https://x.test/acme']]);

    $html = Mailyte::template('trial-started')
        ->with(['trial_ends_at' => 'soon', 'action_url' => 'https://example.test/app'])
        ->layout('branded')
        ->render()->html;

    expect($html)->not->toContain('https://x.test/acme');
});

it('leaves the theme alone for anything left null', function () {
    config()->set('mailyte.brand.logo.url', null);
    config()->set('mailyte.brand.footer.address', null);

    $html = Mailyte::template('welcome')
        ->with(['action_url' => 'https://example.test/app'])
        ->layout('branded')
        ->render()->html;

    // Falls back to the address configured in globals.
    expect($html)->toContain('1 Example Way, Springfield');
});
