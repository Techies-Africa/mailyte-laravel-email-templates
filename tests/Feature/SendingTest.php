<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Mailyte\EmailTemplates\Facades\Mailyte;
use Mailyte\EmailTemplates\Mail\TemplateMailable;

it('sends a template in one call', function () {
    Mail::fake();

    Mailyte::template('email-changed')
        ->with([
            'old_email' => 'ada@example.com',
            'new_email' => 'ada@newdomain.com',
            'cancel_url' => 'https://example.test/cancel/8f2c1d',
            'cancel_deadline' => '31 August 2026',
        ])
        ->send('ada@example.com');

    Mail::assertSent(TemplateMailable::class, function (TemplateMailable $mail): bool {
        return $mail->hasTo('ada@example.com')
            && str_contains($mail->email->html, 'ada@newdomain.com')
            && str_contains((string) $mail->email->subject, 'sign-in address is changing');
    });
});

it('renders before queueing, so bad data fails where it was queued', function () {
    Mail::fake();

    Mailyte::template('email-changed')
        ->with(['old_email' => 'a@example.com', 'new_email' => 'b@example.com'])
        ->queue('ada@example.com');

    Mail::assertQueued(TemplateMailable::class);
});
