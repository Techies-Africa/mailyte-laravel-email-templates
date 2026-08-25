<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Mail\Concerns;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Mailyte\EmailTemplates\Listeners\RecordTemplateUsage;
use Mailyte\EmailTemplates\Rendering\RenderedEmail;
use Mailyte\EmailTemplates\Rendering\TemplateBuilder;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Use a catalog template as the body of your own Mailable.
 *
 * The point is that nothing else about the Mailable changes. Laravel's
 * `view:` and `markdown:` let you keep your class, its constructor, its
 * attachments and its queue configuration, and only say where the body comes
 * from. This does the same:
 *
 *     class AddressChanging extends Mailable
 *     {
 *         use Queueable, SerializesModels, UsesMailyteTemplate;
 *
 *         public function __construct(
 *             private User $user,
 *             private string $newAddress,
 *         ) {}
 *
 *         protected function mailyte(): TemplateBuilder
 *         {
 *             return Mailyte::template('email-changed')->with([
 *                 'old_email' => $this->user->email,
 *                 'new_email' => $this->newAddress,
 *                 'cancel_url' => route('account.email.cancel', $this->user),
 *             ]);
 *         }
 *     }
 *
 * Attachments, cc, bcc, `->onQueue()` and everything else behave exactly as
 * they do on any Mailable. Override `envelope()` yourself if you want a subject
 * the template does not supply.
 */
trait UsesMailyteTemplate
{
    private ?RenderedEmail $mailyteRendered = null;

    /**
     * The template and its data. Implement this in your Mailable.
     */
    abstract protected function mailyte(): TemplateBuilder;

    /**
     * Rendered once, then reused: envelope(), content(), headers() and build()
     * are each called separately by the mail manager, and rendering four times
     * would be four passes over the same template.
     */
    protected function mailyteEmail(): RenderedEmail
    {
        return $this->mailyteRendered ??= $this->mailyte()->render();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailyteEmail()->subject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->mailyteEmail()->html);
    }

    public function headers(): Headers
    {
        $email = $this->mailyteEmail();
        $headers = $email->suggestedHeaders;

        if ($email->slug !== '' && config('mailyte.usage.enabled', true)) {
            $headers[RecordTemplateUsage::HEADER] = $email->slug;
            $headers[RecordTemplateUsage::VERSION_HEADER] = $email->templateVersion;
        }

        return new Headers(text: $headers);
    }

    /**
     * Content::$text is a view name and has no raw-string equivalent, so the
     * rendered text part is attached to the Symfony message directly. An
     * HTML-only message is a deliverability liability, not just an
     * accessibility one, so this is not optional.
     */
    public function build(): self
    {
        return $this->withSymfonyMessage(function (SymfonyEmail $message): void {
            $text = $this->mailyteEmail()->text;

            if (trim($text) !== '') {
                $message->text($text);
            }
        });
    }
}
