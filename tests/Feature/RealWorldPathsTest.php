<?php

declare(strict_types=1);

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use Mailyte\EmailTemplates\EmailTemplatesServiceProvider;
use Mailyte\EmailTemplates\Exceptions\TemplateNotFound;
use Mailyte\EmailTemplates\Facades\Mailyte;
use Mailyte\EmailTemplates\Mail\TemplateMailable;
use Symfony\Component\Mime\Email;

/**
 * The paths a real application takes that unit-level tests miss: queues,
 * attachments, faked mail, other locales, cached config, path safety.
 */
class ProbePerson
{
    use Notifiable;

    public string $email = 'probe@example.test';

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}

class QueuedNote extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($n): array
    {
        return ['mail'];
    }

    public function toMail($n): MailMessage
    {
        return (new MailMessage)->subject('Queued')->greeting('Hello!')
            ->line('Sent from a queue worker.')->action('Open', 'https://acme.test/x');
    }
}

class RichNote extends Notification
{
    public function via($n): array
    {
        return ['mail'];
    }

    public function toMail($n): MailMessage
    {
        return (new MailMessage)->subject('Rich')->greeting('Hello!')
            ->line('With extras.')
            ->cc('cc@example.test')->bcc('bcc@example.test')->replyTo('reply@example.test')
            ->attachData('a,b', 'data.csv', ['mime' => 'text/csv']);
    }
}

function adopt(): void
{
    config()->set('mailyte.notifications.enabled', true);
    config()->set('mail.default', 'array');
    app()->forgetInstance(ChannelManager::class);
    NotificationFacade::clearResolvedInstances();
    (new EmailTemplatesServiceProvider(app()))->boot();
    app('mailer')->getSymfonyTransport()->flush();
}

function firstEmail(): Email
{
    return app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage();
}

it('a queued notification still renders through Mailyte', function () {
    adopt();
    Queue::fake();
    (new ProbePerson)->notify(new QueuedNote);
    Queue::assertPushed(SendQueuedNotifications::class);
});

it('a queued notification survives being serialised and run', function () {
    adopt();
    (new ProbePerson)->notifyNow(new QueuedNote);
    expect((string) firstEmail()->getHtmlBody())->toContain('m-canvas')
        ->and((string) firstEmail()->getHtmlBody())->toContain('Sent from a queue worker.');
});

it('cc, bcc, reply-to and attachments survive adoption', function () {
    adopt();
    (new ProbePerson)->notify(new RichNote);
    $e = firstEmail();

    expect(array_map(fn ($a) => $a->getAddress(), $e->getCc()))->toContain('cc@example.test')
        ->and(array_map(fn ($a) => $a->getAddress(), $e->getBcc()))->toContain('bcc@example.test')
        ->and(array_map(fn ($a) => $a->getAddress(), $e->getReplyTo()))->toContain('reply@example.test')
        ->and($e->getAttachments())->toHaveCount(1);
});

it('Mail::fake in an application test suite sees a Mailyte send', function () {
    Mail::fake();

    Mailyte::template('welcome')
        ->with(Mailyte::catalog()['welcome']->samples()['default'] ?? [])
        ->send('someone@example.test');

    Mail::assertSent(TemplateMailable::class);
});

it('rendering works with a non-English locale', function () {
    app()->setLocale('fr');
    $email = Mailyte::template('welcome')
        ->with(Mailyte::catalog()['welcome']->samples()['default'] ?? [])
        ->locale('fr')
        ->render();
    expect($email->html)->toContain('lang="fr"');
});

it('a direct send can be queued', function () {
    config()->set('mail.default', 'array');
    Queue::fake();
    Mailyte::template('welcome')
        ->with(Mailyte::catalog()['welcome']->samples()['default'] ?? [])
        ->queue('someone@example.test');
    Queue::assertPushed(SendQueuedMailable::class);
});

it('works with a cached config, as production runs', function () {
    // config:cache serialises every config file; a closure anywhere in the
    // package's own config would make the whole app uncacheable.
    $config = require __DIR__.'/../../config/mailyte.php';

    $closures = [];
    $walk = function (array $a, string $path) use (&$walk, &$closures): void {
        foreach ($a as $k => $v) {
            if ($v instanceof Closure) {
                $closures[] = "{$path}.{$k}";
            }
            if (is_array($v)) {
                $walk($v, "{$path}.{$k}");
            }
        }
    };
    $walk($config, 'mailyte');

    expect($closures)->toBe([])
        ->and(serialize($config))->toBeString();
});

it('every layout table is marked presentational for screen readers', function () {
    $email = Mailyte::template('invoice')
        ->with(Mailyte::catalog()['invoice']->samples()['default'] ?? [])
        ->render();

    preg_match_all('/<table\b[^>]*>/i', $email->html, $tables);

    $unmarked = array_values(array_filter(
        $tables[0],
        fn (string $t): bool => stripos($t, 'role=') === false
    ));

    expect($unmarked)->toBe([], count($unmarked).' of '.count($tables[0]).' tables carry no role');
});

it('the html declares a direction and a charset', function () {
    $email = Mailyte::template('welcome')
        ->with(Mailyte::catalog()['welcome']->samples()['default'] ?? [])
        ->render();

    expect($email->html)->toContain('charset=')
        ->and($email->html)->toMatch('/<html[^>]*dir="ltr"/i');

    $rtl = Mailyte::template('welcome')
        ->with(Mailyte::catalog()['welcome']->samples()['default'] ?? [])
        ->locale('ar')
        ->render();

    expect($rtl->html)->toMatch('/<html[^>]*dir="rtl"/i')
        ->and($rtl->html)->toContain('lang="ar"');
});

it('a slug from user input cannot escape the templates directory', function () {
    foreach (['../../etc/passwd', '..%2f..%2fetc', 'welcome/../../../secret'] as $evil) {
        // `toThrow` with one string argument matches the *message*, so name
        // the class explicitly or the assertion silently checks the wrong thing.
        expect(fn () => Mailyte::template($evil)->render())
            ->toThrow(TemplateNotFound::class);
    }
});
