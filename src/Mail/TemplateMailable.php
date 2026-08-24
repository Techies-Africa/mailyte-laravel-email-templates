<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Mailyte\EmailTemplates\Rendering\RenderedEmail;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Wraps a rendered email as a normal Laravel Mailable.
 *
 * Laravel's mail channel already accepts a Mailable, so notifications need
 * nothing special -- return this from toMail() and everything downstream
 * (queueing, the `mail.*` config, failover transports) behaves as it always
 * does. That is why this package ships no notification channel of its own.
 */
class TemplateMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly RenderedEmail $email) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->email->subject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->email->html);
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->email->suggestedHeaders);
    }

    /**
     * Attach the plain-text alternative directly to the Symfony message.
     *
     * Content::$text is a *view name*, and there is no raw-string equivalent,
     * so the already-rendered text part has to be set on the message itself.
     * Skipping it is not an option: an HTML-only message is a real
     * deliverability liability, not just an accessibility one.
     */
    public function build(): self
    {
        return $this->withSymfonyMessage(function (SymfonyEmail $message): void {
            if (trim($this->email->text) !== '') {
                $message->text($this->email->text);
            }
        });
    }
}
