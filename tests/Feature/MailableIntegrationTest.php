<?php

declare(strict_types=1);

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Mailyte\EmailTemplates\Facades\Mailyte;
use Mailyte\EmailTemplates\Mail\Concerns\UsesMailyteTemplate;
use Mailyte\EmailTemplates\Rendering\TemplateBuilder;

/**
 * A user's own Mailable, exactly as they would write it against `view:` or
 * `markdown:` — constructor, queueing, attachments and all.
 */
class AddressChanging extends Mailable
{
    use Queueable, SerializesModels, UsesMailyteTemplate;

    public function __construct(
        private readonly string $oldAddress,
        private readonly string $newAddress,
    ) {}

    protected function mailyte(): TemplateBuilder
    {
        return Mailyte::template('email-changed')->with([
            'old_email' => $this->oldAddress,
            'new_email' => $this->newAddress,
            'cancel_url' => 'https://example.test/cancel/8f2c1d',
            'cancel_deadline' => '31 August 2026',
        ]);
    }
}

it('drops into an ordinary Mailable the way a view does', function () {
    Mail::fake();

    Mail::to('ada@example.com')->send(new AddressChanging('ada@old.test', 'ada@new.test'));

    Mail::assertSent(AddressChanging::class, function (AddressChanging $mail): bool {
        $rendered = $mail->render();

        return $mail->hasTo('ada@example.com')
            && str_contains($rendered, 'ada@new.test')
            && str_contains($rendered, 'ada@old.test');
    });
});

it('carries the template subject and a real text/plain part', function () {
    // The array transport keeps the actual Symfony message, which is the only
    // way to prove the text alternative was attached — Content::$text takes a
    // view name, so the rendered text is set on the message itself.
    config()->set('mail.default', 'array');

    $mail = new AddressChanging('ada@old.test', 'ada@new.test');

    Mail::to('ada@example.com')->send($mail);

    $messages = app('mailer')->getSymfonyTransport()->messages();

    expect($messages)->toHaveCount(1);

    $sent = $messages[0]->getOriginalMessage();

    expect($sent->getSubject())->toContain('sign-in address is changing')
        ->and($sent->getHtmlBody())->toContain('ada@new.test')
        ->and($sent->getTextBody())->toContain('ada@new.test')
        ->and($sent->getTextBody())->not->toContain('<td');
});

it('still queues like any other Mailable', function () {
    Mail::fake();

    Mail::to('ada@example.com')->queue(
        (new AddressChanging('ada@old.test', 'ada@new.test'))->onQueue('emails')
    );

    Mail::assertQueued(AddressChanging::class, fn (AddressChanging $m): bool => $m->queue === 'emails');
});
