# Using this with the mail Laravel already sends

Three levels, from "one email" to "every email in the application". Most teams
end up using all three.

| You want | Do this | Code to change |
|---|---|---|
| One email designed | `Mailyte::template('invoice')->send($user)` | The one call site |
| Your existing Mailable restyled | Add the `UsesMailyteTemplate` trait | One line per Mailable |
| **Every notification designed, including Laravel's own** | Set `mailyte.notifications.enabled` | **Nothing** |

---

## One command

> Is there a way to say "use this design" and have all my emails just use it,
> instead of editing each one?

```bash
php artisan mailyte:adopt email-changed
```

That is the whole thing. It publishes a notification shell carrying
`email-changed`'s design, writes a stylesheet so Laravel's markdown mailables
match, and switches adoption on. Every email the application already sends now
wears that design — and nothing about how you send it changes.

```
Notification shell ......... published with the email-changed design
Markdown mail theme .......... written from the email-changed design
Environment ....................... MAILYTE_ADOPT_NOTIFICATIONS=true
```

Options: `--layout=minimal`, `--theme=neutral`, `--no-markdown`, `--no-env`,
`--force` to overwrite files it wrote before. Run it with no argument and it
lists the catalog to choose from.

**To back out:**

```bash
php artisan mailyte:adopt --reset
```

Laravel renders its own mail again. The command removes what it wrote, turns the
flag off, and keeps anything you edited — a shell whose markup you changed is
left in place (inert, since adoption is off) unless you add `--force`.

Turning the flag off by itself is also enough: with adoption off the published
files are simply never consulted.

### The one thing it cannot do, and why

Adopting `email-changed` means adopting its **design** — palette, type scale,
fonts, spacing, radii — not its composition. It cannot render your password
reset *through* the `email-changed` template, because that template wants an old
address, a new address and a cancellation URL, and Laravel's password reset
notification has none of those. What Laravel hands over is a greeting, some
lines and one action.

So the design travels and the layout receives arbitrary content. That is the
honest version of "make everything look like this", and it is what you actually
want: consistent design, whatever the message happens to say.

**What travels** — verified by comparing the adopted shell against the source
template's own render, property by property:

| | |
|---|---|
| Page and card background | ✓ |
| Heading font, size, weight, letter-spacing | ✓ |
| Body font, size, line height, colour | ✓ |
| Button fill, ink, font size | ✓ |
| Button **shape** (pill, square) and **variant** (filled, outline) | ✓ — inferred from the source's markup, since design.json does not hold them |
| Radii, spacing scale, content width, gutters | ✓ |
| Logo, social row, footer sections | ✓ from your brand config |
| Light **and** dark schemes | ✓ |

**What cannot travel**, because it is composition rather than design: a tinted
band behind the opening, a 56px amount due, a 42px verification code, a pull
quote, a checklist, a dark masthead. Those exist because a specific template had
a specific fact to present. A notification carries a greeting, some lines and one
action, so there is nothing to put in them.

Compare `verify-email` against a notification wearing it: same monospace
headings, same cool grey ground, same green outline button — and no code panel,
because there is no code.

### Doing it by hand instead

If you would rather not have a command write files, the flag alone is enough —
you just get the shell's own default design rather than one of the fifty:

```php
// config/mailyte.php
'notifications' => [
    'enabled' => true,
],
```

or

```dotenv
MAILYTE_ADOPT_NOTIFICATIONS=true
``` Every mail notification in the application — yours and
the framework's — now renders through Mailyte: designed, responsive, dark-mode
aware, with your logo, your social links and your footer from
`config('mailyte.brand')`.

**No notification class changes.** Not the `via()` methods, not `toMail()`, not
the queueing, not the recipients. Laravel still builds the `MailMessage`; only
the rendering step is replaced.

### What this covers

Anything that builds a `MailMessage`, which is almost everything:

- **`Illuminate\Auth\Notifications\ResetPassword`** — Laravel's password reset
- **`Illuminate\Auth\Notifications\VerifyEmail`** — Laravel's email verification
- Every `Notification` of your own whose `toMail()` returns a `MailMessage`
- Fortify, Jetstream and Breeze notifications, which are `MailMessage`-based
- Anything a package sends through the `mail` channel

### How the content maps

Laravel's `MailMessage` has a fixed shape, and the shipped shell receives it
one-for-one:

| MailMessage | Shell variable | Rendered as |
|---|---|---|
| `->greeting('Hello Ada!')` | `greeting` | The `h1` — or a danger band on `->error()` |
| `->line('...')` before `action()` | `lines` | Paragraphs |
| `->action('Label', $url)` | `action_label`, `action_url` | The button, red on `->error()` |
| `->line('...')` after `action()` | `outro_lines` | Paragraphs below the button |
| `->salutation('...')` | `salutation` | Sign-off |
| `->error()` / `->success()` | `level` | Button colour, and the banded heading |
| — | `subcopy` | The "trouble clicking the button" URL fallback, demoted |

Nothing is dropped and nothing is invented: switch it on and your emails say
exactly what they said before.

### What it deliberately leaves alone

- **A `MailMessage` with an explicit `->view(...)`.** You chose a template.
- **A `MailMessage` with its own `->markdown(...)`.** Also your template. Only
  the framework's default `notifications::email` is intercepted.
- **A `toMail()` that returns a `Mailable`.** That is a different code path
  entirely, and yours.

### Adopted mail still counts

`php artisan mailyte:usage` counts adopted notifications alongside direct
sends, so the figures cover everything the application sends rather than only
the calls that name a template. Counting is local and off-switchable
(`mailyte.usage.enabled`).

### If rendering fails, the email still sends

A missing template or a bad token falls back to Laravel's own rendering and
reports the exception. A styling problem is not worth a lost password reset.

### Making the shell yours

```bash
php artisan vendor:publish --tag=mailyte-notification-shell
```

Four files land in `resources/views/vendor/mailyte/templates/laravel-notification/`
— manifest, design tokens, composition, sample data. Edit any of them; your copy
takes precedence. Or point the config at a different bundle entirely:

```php
'notifications' => ['enabled' => true, 'template' => 'my-own-shell'],
```

A replacement must accept the shell's variables — `greeting`, `lines`,
`action_label`, `action_url`, `outro_lines`, `salutation`, `subcopy`, `level` —
because that is all a `MailMessage` carries.

You can also pin the layout and theme used for all notifications:

```php
'notifications' => [
    'enabled' => true,
    'layout' => 'minimal',   // plain | minimal | branded | editorial
    'theme' => 'neutral',
],
```

---

## When one notification deserves a designed template

The generic shell handles Laravel's password reset well. The catalog's
`password-reset` handles it *better* — but it needs a `reset_url`, and a
`MailMessage` has no such thing, only a generic action URL. So this is not
something the config can do for you: only your application knows the data.

Laravel has the right seam for it, and it is one line in a service provider:

```php
use Illuminate\Auth\Notifications\ResetPassword;
use Mailyte\EmailTemplates\Facades\Mailyte;

public function boot(): void
{
    ResetPassword::toMailUsing(fn ($notifiable, string $token) =>
        Mailyte::template('password-reset')
            ->with([
                'reset_url' => route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]),
                'expires_in' => config('auth.passwords.users.expire').' minutes',
            ])
            ->toMailable()
            ->to($notifiable->getEmailForPasswordReset())
    );
}
```

Same for email verification:

```php
use Illuminate\Auth\Notifications\VerifyEmail;

VerifyEmail::toMailUsing(fn ($notifiable, string $url) =>
    Mailyte::template('verify-email-link')
        ->with(['action_url' => $url])
        ->toMailable()
        ->to($notifiable->getEmailForVerification())
);
```

> **Watch the addressing.** When `toMail()` returns a `Mailable` rather than a
> `MailMessage`, the notification channel hands off completely — including the
> recipient. Call `->to(...)` yourself, or the send throws.

---

## Level 2: a Mailable you already have

If you have a `Mailable` using `view:` or `markdown:`, the swap is the trait:

```php
use Illuminate\Mail\Mailable;
use Mailyte\EmailTemplates\Mail\Concerns\UsesMailyteTemplate;
use Mailyte\EmailTemplates\Rendering\TemplateBuilder;

class OrderShipped extends Mailable
{
    use Queueable, SerializesModels, UsesMailyteTemplate;

    public function __construct(private Order $order) {}

    protected function mailyte(): TemplateBuilder
    {
        return Mailyte::template('receipt')->with([
            'order_number' => $this->order->number,
            'line_items' => $this->order->lineItemsForEmail(),
        ]);
    }
}
```

Delete the `content()`, `envelope()` and `build()` methods — the trait supplies
them, subject and text part included. Everything else about the class stays:
attachments, cc, bcc, queueing, `shouldQueue`.

There is also `MailyteMailable` to extend if you are starting fresh.

### Replacing `markdown:` mailables wholesale

Laravel's markdown mailables render through `mail::` Blade components, which is a
separate mechanism from notifications and is not intercepted. Two options:

1. **Convert them** — the trait above, one Mailable at a time. Recommended:
   you end up with a real template and its design tokens.
2. **Keep them and restyle them** — `mailyte:adopt` already did this. It writes
   `resources/views/vendor/mail/html/themes/mailyte.css` from the design you
   chose and points `mail.markdown.theme` at it, so every markdown mailable
   picks up the same palette, type scale and fonts, with a dark-mode block
   Laravel's own theme does not have.

   Be clear about what this is: Laravel's markup wearing a Mailyte design. The
   blocks are not there, so it will not match a real Mailyte template
   pixel-for-pixel. It does stop your markdown mail looking like the framework
   default, which for forty existing mailables is the trade worth making today.
   Regenerate it any time by re-running the command with `--force`.

---

## Level 1: one email at a time

```php
Mailyte::template('invoice')
    ->with(['invoice' => $invoice->toEmailArray(), 'pay_url' => $url])
    ->send($user);          // or ->queue($user)
```

See [sending.md](sending.md) for the full API — layouts, themes, per-tenant
branding, publishing a template to edit.

---

## Rolling it out safely

1. Turn adoption on in local first and look at the result:
   `php artisan mailyte:send-test laravel-notification --to=you@example.com`
2. Open `/mailyte` and check the shell in every layout, both colour schemes, at
   320px.
3. Trigger your real notifications against MailHog or Mailpit — a password
   reset, an invitation, whatever your app sends most.
4. Check the plain-text part. It is derived from the HTML, and it is what spam
   filters read: `php artisan mailyte:deliverability laravel-notification`.
5. Then enable it in production. To back out, unset one env var.

---

[← Back to the README](../README.md) · [Sending](sending.md) · [Theming](theming.md) · [Deliverability](deliverability.md)
