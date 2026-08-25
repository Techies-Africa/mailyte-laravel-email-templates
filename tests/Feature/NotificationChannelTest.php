<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Mailyte\EmailTemplates\EmailTemplatesServiceProvider;
use Mailyte\EmailTemplates\Facades\Mailyte;
use Mailyte\EmailTemplates\Linting\TemplateLinter;
use Mailyte\EmailTemplates\Sources\SourceChain;
use Symfony\Component\Mime\Email;

/**
 * The promise being tested: switch one flag and every mail notification in the
 * application is rendered by Mailyte, without a single notification class
 * changing. So the tests send real notifications and read the wire.
 */
class Recipient
{
    use Notifiable;

    public string $email = 'recipient@example.test';

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}

class OrdinaryNotification extends Notification
{
    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your export is ready')
            ->greeting('Hello Ada!')
            ->line('The export you asked for has finished.')
            ->action('Download it', 'https://acme.test/exports/8f2c')
            ->line('The link works for seven days.');
    }
}

class ExplicitViewNotification extends OrdinaryNotification
{
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Chosen view')->view('fixture::own', []);
    }
}

class OwnMarkdownNotification extends OrdinaryNotification
{
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Own markdown')->markdown('fixture::own', []);
    }
}

/**
 * The one message the array transport captured. `getMessage()` hands back a
 * prepared RawMessage; the Email object with the parts on it is the original.
 */
function sentEmail(): Email
{
    $messages = app('mailer')->getSymfonyTransport()->messages();

    expect($messages)->toHaveCount(1);

    $email = $messages[0]->getOriginalMessage();

    expect($email)->toBeInstanceOf(Email::class);

    return $email;
}

function sentHtml(): string
{
    return (string) sentEmail()->getHtmlBody();
}

function sentText(): string
{
    return (string) sentEmail()->getTextBody();
}

beforeEach(function (): void {
    // A view of the developer's own, to prove the channel steps aside for one.
    $dir = sys_get_temp_dir().'/mailyte-fixture-views';
    @mkdir($dir, 0777, true);
    file_put_contents($dir.'/own.blade.php', '<p>A view the developer chose.</p>');
    view()->addNamespace('fixture', $dir);

    config()->set('mail.default', 'array');
    config()->set('mail.from.address', 'sender@example.test');
    config()->set('mail.from.name', 'Acme');
    app('mailer')->getSymfonyTransport()->flush();
});

it('leaves Laravel alone until asked', function () {
    config()->set('mailyte.notifications.enabled', false);

    (new Recipient)->notify(new OrdinaryNotification);

    // Laravel's own markdown mail carries this wrapper class; Mailyte's does not.
    expect(sentHtml())->toContain('class="header"')
        ->and(sentHtml())->not->toContain('m-canvas');
});

describe('with adoption switched on', function () {
    beforeEach(function (): void {
        config()->set('mailyte.notifications.enabled', true);
        // The provider registers the channel at boot, so the manager has to be
        // rebuilt for the flag to take effect inside a single test process.
        app()->forgetInstance(ChannelManager::class);
        NotificationFacade::clearResolvedInstances();
        (new EmailTemplatesServiceProvider(app()))->boot();
    });

    it('renders an ordinary notification through Mailyte', function () {
        (new Recipient)->notify(new OrdinaryNotification);

        $html = sentHtml();

        expect($html)->toContain('<!DOCTYPE')
            // Mailyte's own layout markers, absent from Laravel's mail views.
            ->and($html)->toContain('m-canvas')
            ->and($html)->toContain('prefers-color-scheme')
            // and the content Laravel put in the MailMessage, unchanged
            ->and($html)->toContain('Hello Ada!')
            ->and($html)->toContain('The export you asked for has finished.')
            ->and($html)->toContain('Download it')
            ->and($html)->toContain('https://acme.test/exports/8f2c')
            ->and($html)->toContain('The link works for seven days.');
    });

    it('keeps the subject the notification set', function () {
        (new Recipient)->notify(new OrdinaryNotification);

        expect(sentEmail()->getSubject())->toBe('Your export is ready');
    });

    it('ships a plain-text part derived from the rendered HTML', function () {
        (new Recipient)->notify(new OrdinaryNotification);

        expect(trim(sentText()))->not->toBe('')
            ->and(sentText())->toContain('The export you asked for has finished.')
            ->and(sentText())->not->toContain('<td');
    });

    it('puts lines added after the action below the button', function () {
        (new Recipient)->notify(new OrdinaryNotification);

        $html = sentHtml();
        expect(strpos($html, 'Download it'))->toBeLessThan(strpos($html, 'The link works for seven days.'));
    });

    it('respects a notification that chose its own view', function () {
        (new Recipient)->notify(new ExplicitViewNotification);

        expect(sentHtml())->not->toContain('m-canvas');
    });

    it('respects a notification that chose its own markdown template', function () {
        (new Recipient)->notify(new OwnMarkdownNotification);

        expect(sentHtml())->not->toContain('m-canvas');
    });

    it('steps aside for Laravel\'s own toMailUsing seam, which is how a designed template gets its data', function () {
        // Returning a Mailable from toMail() means addressing it yourself --
        // the notification channel hands off entirely at that point.
        ResetPassword::toMailUsing(fn ($notifiable, string $token) => Mailyte::template('password-reset')
            ->with(['reset_url' => 'https://acme.test/reset/'.$token, 'expires_in' => '60 minutes'])
            ->toMailable()
            ->to($notifiable->email));

        try {
            (new Recipient)->notify(new ResetPassword('a-token'));

            // The catalog's designed password-reset, with the data only the
            // application could have supplied.
            expect(sentHtml())->toContain('m-canvas')
                ->and(sentHtml())->toContain('https://acme.test/reset/a-token');
        } finally {
            ResetPassword::$toMailCallback = null;
        }
    });

    it('falls back to Laravel rather than losing the message when a template is missing', function () {
        config()->set('mailyte.notifications.template', 'no-such-template');

        (new Recipient)->notify(new OrdinaryNotification);

        // Still delivered, still says what it said, just not styled by us.
        expect(sentHtml())->toContain('The export you asked for has finished.')
            ->and(sentHtml())->not->toContain('m-canvas');
    });

    it('bands an error-level notification and keeps info-level plain', function () {
        $error = new class extends OrdinaryNotification
        {
            public function toMail(mixed $notifiable): MailMessage
            {
                return (new MailMessage)->error()
                    ->subject('Payment failed')
                    ->line('We could not charge the card on file.');
            }
        };

        (new Recipient)->notify($error);

        expect(sentHtml())->toContain('Whoops!');
    });
});

it('renders the shell in every layout against every sample', function () {
    $manifest = Mailyte::template('laravel-notification')->manifest();

    foreach ($manifest->supportedLayouts() as $layout) {
        foreach ($manifest->samples() as $name => $data) {
            $email = Mailyte::template('laravel-notification')->with($data)->layout($layout)->render();

            expect($email->html)->toContain('<!DOCTYPE')
                ->and(trim($email->text))->not->toBe('');
        }
    }
});

it('holds the shell to the same lint rules as the catalog', function () {
    $issues = array_filter(
        app(TemplateLinter::class)->lint(Mailyte::template('laravel-notification')->manifest()),
        static fn ($i): bool => $i->isError(),
    );

    expect($issues)->toBe([], implode("\n", array_map('strval', $issues)));
});

it('keeps the shell out of the catalog', function () {
    // Point published at an empty directory: this is about what the package
    // ships, and a shell someone published locally would otherwise fail it --
    // which is the documented behaviour, not a regression.
    config()->set('mailyte.sources.published', sys_get_temp_dir().'/mailyte-empty-'.bin2hex(random_bytes(4)));
    app()->forgetInstance(SourceChain::class);

    expect(Mailyte::catalog())->not->toHaveKey('laravel-notification')
        ->and(Mailyte::catalog())->toHaveCount(50)
        // ...but still resolvable, which is the whole point of a hidden source.
        ->and(Mailyte::has('laravel-notification'))->toBeTrue();
});

it('lets the commands reach the shell by name even though it is unlisted', function () {
    $this->artisan('mailyte:lint', ['slug' => ['laravel-notification'], '--strict' => true])
        ->assertExitCode(0);

    $this->artisan('mailyte:deliverability', ['slug' => ['laravel-notification'], '--strict' => true])
        ->assertExitCode(0);

    $this->artisan('mailyte:lint', ['slug' => ['no-such-thing']])
        ->assertExitCode(1);
});
