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

/**
 * Wraps a rendered email as a normal Laravel Mailable.
 *
 * Laravel's mail channel already accepts a Mailable, so notifications need
 * nothing special -- return this from toMail() and everything downstream
 * (queueing, the `mail.*` config, Mailtrap, failover transports) behaves the
 * way it always does. That is why this package ships no notification channel
 * of its own.
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
        return new Content(
            htmlString: $this->email->html,
            textString: $this->email->text,
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->email->suggestedHeaders);
    }
}
