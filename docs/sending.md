# Sending a template

Three ways in, depending on how much of the message is yours.

## 1. From the command line

```bash
php artisan mailyte:send-test email-changed --to=you@example.com
```

Sends that one template through your configured mailer, using the sample data
the bundle ships. It is how you check a template in a real client rather than in
the preview gallery.

```bash
# a different sample, theme or layout
php artisan mailyte:send-test email-changed --to=you@example.com --sample=already-changed --layout=plain

# every template in the catalog, once each
php artisan mailyte:send-test --to=you@example.com
```

## 2. One call, in application code

```php
use Mailyte\EmailTemplates\Facades\Mailyte;

Mailyte::template('email-changed')
    ->with([
        'old_email'       => $user->email,
        'new_email'       => $request->string('email'),
        'cancel_url'      => URL::signedRoute('account.email.cancel', $user),
        'cancel_deadline' => now()->addDays(7)->toFormattedDayDateString(),
    ])
    ->send($user);          // anything Mail::to() accepts
```

`->queue($user)` does the same thing later. It still renders now, on purpose:
a template that would fail on missing data fails in the request that queued it,
rather than silently on a worker at three in the morning.

Per-tenant branding, if you need it:

```php
->theme(['color.primary' => $org->brand_colour, 'logo.url' => $org->logo_url])
```

## 3. Inside your own Mailable

When you want the rest of what a Mailable gives you — a constructor, attachments,
cc and bcc, queue configuration — keep the class and let Mailyte supply the body.
This is the same swap as `view:` or `markdown:`, one line in, everything else
untouched:

```php
use Illuminate\Mail\Mailable;
use Mailyte\EmailTemplates\Facades\Mailyte;
use Mailyte\EmailTemplates\Mail\Concerns\UsesMailyteTemplate;
use Mailyte\EmailTemplates\Rendering\TemplateBuilder;

class AddressChanging extends Mailable
{
    use Queueable, SerializesModels, UsesMailyteTemplate;

    public function __construct(
        private User $user,
        private string $newAddress,
    ) {}

    protected function mailyte(): TemplateBuilder
    {
        return Mailyte::template('email-changed')->with([
            'old_email'  => $this->user->email,
            'new_email'  => $this->newAddress,
            'cancel_url' => URL::signedRoute('account.email.cancel', $this->user),
        ]);
    }

    public function attachments(): array
    {
        return [Attachment::fromPath($this->receiptPath)];
    }
}
```

```php
Mail::to($user)->send(new AddressChanging($user, $request->string('email')));
```

The trait fills in `envelope()`, `content()`, `headers()` and the plain-text
alternative from the template. Override `envelope()` yourself if you want a
subject the template does not supply. Everything else about the Mailable is
untouched.

## What a template expects

Each bundle declares its own variables, with a description, a type and either a
default or `required`. The dashboard lists them for any template, and so does:

```bash
php artisan mailyte:list email-changed
```

`email-changed`, for example, requires `old_email` and `new_email`; everything
else — the headline, the deadline, the cancel link, the closing note — has a
default you can leave alone or override.

Missing required data fails loudly at render time rather than sending a message
with a hole in it.

## Where the shared parts come from

Your logo, social accounts, postal address and footer settings are not passed per
send. They live in `config/mailyte.php` under `brand`, and apply to every
template. See [theming](theming.md).

## Making a template your own

Copy one into your application and edit it:

```bash
php artisan mailyte:publish-template email-changed
```

That writes the four files into `resources/views/vendor/mailyte/templates/email-changed/`:

| File | What you would change it for |
|---|---|
| `template.json` | the subject, the copy defaults, the variables it accepts |
| `design.json` | its palette, type scale, spacing and measure |
| `email.html` | the composition — which blocks, in what order |
| `sample.json` | the data the preview and `send-test` use |

Your copy takes precedence over the packaged one **everywhere `email-changed` is
already used**, including code you have already written. Nothing to re-point.

Publishing is one template at a time on purpose. Copying the catalog would fork
fifty bundles you never intended to maintain, and every one of them would stop
receiving fixes.

### Keeping the original as well

```bash
php artisan mailyte:publish-template email-changed --as=address-change-notice
```

The packaged template stays where it is, and your copy is a new slug:

```php
Mailyte::template('address-change-notice')->with([...])->send($user);
```

A renamed copy is marked `tier: community` with an `origin` block recording
which catalog template it came from — so whoever reads it in a year can find
what it diverged from.

`--force` overwrites a copy you have already published. It is not the default,
because overwriting is how local edits disappear.

To restyle the mail Laravel already sends — its password reset, its email
verification, and every notification of your own — see
[Laravel integration](laravel-integration.md).
