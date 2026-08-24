<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Exceptions\RenderFailed;
use Mailyte\EmailTemplates\Facades\Mailyte;

it('renders a bundle end to end', function () {
    $email = Mailyte::template('verify-email')
        ->with([
            'user' => ['first_name' => 'Ada'],
            'verification_code' => '294 501',
            'action_url' => 'https://example.test/verify/abc',
        ])
        ->render();

    expect($email->subject)->toBe('294 501 is your Acme verification code')
        ->and($email->html)->toContain('294 501')
        ->and($email->html)->toContain('https://example.test/verify/abc')
        ->and($email->html)->toContain('Confirm your email address')
        ->and($email->text)->toContain('Verification code: 294 501');
});

it('falls back to manifest defaults so a template renders with almost no data', function () {
    $email = Mailyte::template('verify-email')
        ->with(['verification_code' => '111 222'])
        ->render();

    expect($email->html)->toContain('This code expires in 10 minutes.')
        ->and($email->html)->toContain('you can safely ignore this email');
});

it('lets every visible string be overridden without touching markup', function () {
    $email = Mailyte::template('verify-email')
        ->with([
            'verification_code' => '111 222',
            'heading_text' => 'One last step',
            'body' => 'Pop this code in to finish up.',
        ])
        ->render();

    expect($email->html)->toContain('One last step')
        ->and($email->html)->toContain('Pop this code in to finish up.')
        // The default heading is gone from the body. It still appears in the
        // preheader, which has its own default -- so assert on the heading tag.
        ->and($email->html)->not->toContain('>Confirm your email address<');
});

it('drops the CTA when no action url is given', function () {
    $withLink = Mailyte::template('verify-email')
        ->with(['verification_code' => '1', 'action_url' => 'https://example.test/v'])
        ->render();

    $codeOnly = Mailyte::template('verify-email')
        ->with(['verification_code' => '1', 'action_url' => ''])
        ->render();

    expect($withLink->html)->toContain('Verify email address')
        ->and($codeOnly->html)->not->toContain('Verify email address');
});

it('fails loudly when required data is missing', function () {
    Mailyte::template('verify-email')->render();
})->throws(RenderFailed::class, 'missing required data');

it('applies per-tenant branding at send time', function () {
    $email = Mailyte::template('verify-email')
        ->with(['verification_code' => '1', 'action_url' => 'https://example.test/v'])
        ->theme(['color.primary' => '#FF00AA'])
        ->render();

    expect($email->html)->toContain('#FF00AA');
});

it('renders every layout the bundle declares', function (string $layout) {
    $email = Mailyte::template('verify-email')
        ->with(['verification_code' => '1'])
        ->layout($layout)
        ->render();

    expect($email->html)->toContain('<!DOCTYPE')->and($email->html)->toContain('1');
})->with(['plain', 'minimal', 'branded']);

it('refuses a layout the bundle does not support', function () {
    Mailyte::template('verify-email')->with(['verification_code' => '1'])->layout('editorial')->render();
})->throws(RenderFailed::class, 'does not support');
