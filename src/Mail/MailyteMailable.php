<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Mailyte\EmailTemplates\Mail\Concerns\UsesMailyteTemplate;
use Mailyte\EmailTemplates\Rendering\TemplateBuilder;

/**
 * A Mailable whose body comes from the catalog.
 *
 * Extend this when the class is yours but the design is not:
 *
 *     class AddressChanging extends MailyteMailable
 *     {
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
 *             ]);
 *         }
 *     }
 *
 * Everything a Mailable can do, it still does — attachments, cc and bcc,
 * `->onQueue()`, `Mail::later()`. Only the body, subject and headers come from
 * the template, and `envelope()` can be overridden if you want a subject the
 * template does not supply.
 *
 * Already extending your own base Mailable? Use the
 * {@see UsesMailyteTemplate} trait directly instead; this class is only that
 * trait plus the two Laravel adds to a generated Mailable.
 */
abstract class MailyteMailable extends Mailable
{
    use Queueable;
    use SerializesModels;
    use UsesMailyteTemplate;

    /**
     * The template and the data it renders with.
     */
    abstract protected function mailyte(): TemplateBuilder;
}
