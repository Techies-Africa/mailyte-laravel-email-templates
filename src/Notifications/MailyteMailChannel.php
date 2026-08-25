<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Notifications;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;
use Mailyte\EmailTemplates\MailyteManager;
use Mailyte\EmailTemplates\Rendering\RenderedEmail;

/**
 * Renders Laravel's own notification mail through Mailyte.
 *
 * This is the answer to "must I rewrite every notification?" -- no. Laravel
 * builds a `MailMessage` with a greeting, some lines, an action and a
 * salutation, then renders it through the `notifications::email` markdown view.
 * This channel replaces that last step and nothing else: the notification
 * classes, the `via()` methods, the queueing and the recipients are all
 * untouched, and every mail notification in the application comes out designed.
 *
 * It is deliberately conservative about when it steps in:
 *
 *  - A message with an explicit `view` is left alone. The developer chose a
 *    template; overriding that would be rude.
 *  - A message whose markdown view is not the framework default is left alone,
 *    for the same reason -- it is somebody's own markdown template.
 *  - Anything it cannot render falls back to Laravel's own rendering rather
 *    than failing the send, because a designed email is not worth a lost
 *    password reset.
 */
class MailyteMailChannel extends MailChannel
{
    public function __construct(
        MailFactory|Mailer $mailer,
        Markdown $markdown,
        private readonly MailyteManager $mailyte,
        private readonly ConfigRepository $config,
    ) {
        parent::__construct($mailer, $markdown);
    }

    /**
     * @param  MailMessage  $message
     * @return array<string, \Closure>|array<int, string>|string
     */
    protected function buildView($message)
    {
        if (! $this->shouldRender($message)) {
            return parent::buildView($message);
        }

        try {
            $email = $this->render($message);
        } catch (\Throwable $e) {
            // A styling failure must never cost a delivery. Laravel's own
            // rendering is right there, and the exception is worth reporting
            // rather than swallowing silently.
            report($e);

            return parent::buildView($message);
        }

        return [
            'html' => fn (): HtmlString => new HtmlString($email->html),
            'text' => fn (): HtmlString => new HtmlString($email->text),
        ];
    }

    private function shouldRender(MailMessage $message): bool
    {
        if (! $this->config->get('mailyte.notifications.enabled', false)) {
            return false;
        }

        // An explicit view, or somebody's own markdown template, is a decision
        // already made.
        if ($message->view) {
            return false;
        }

        return $message->markdown === 'notifications::email';
    }

    private function render(MailMessage $message): RenderedEmail
    {
        /** @var array<string, mixed> $data */
        $data = $message->data();
        $builder = $this->mailyte->template($this->template())->with($this->map($data));

        if (is_string($layout = $this->config->get('mailyte.notifications.layout'))) {
            $builder->layout($layout);
        }

        if (is_string($theme = $this->config->get('mailyte.notifications.theme'))) {
            $builder->theme($theme);
        }

        if (is_string($subject = $data['subject'] ?? null) && $subject !== '') {
            $builder->subject($subject);
        }

        return $builder->render();
    }

    /**
     * Which bundle receives the MailMessage content.
     *
     * One shell for everything, deliberately. Routing a specific notification
     * to one of the catalog's purpose-built designs sounds appealing until you
     * look at what those templates need: `password-reset` requires a
     * `reset_url`, an invoice needs line items, and a MailMessage carries none
     * of that -- only a greeting, some lines and one action. A per-notification
     * map here could only ever supply the shell's own variables, so it would
     * fall back for exactly the cases worth mapping.
     *
     * Laravel already has the right seam for that, and it is one line:
     *
     *     ResetPassword::toMailUsing(fn ($notifiable, $token) => Mailyte::template('password-reset')
     *         ->with(['reset_url' => url(route('password.reset', [...]))])
     *         ->toMailable());
     *
     * See docs/laravel-integration.md.
     */
    private function template(): string
    {
        return (string) $this->config->get('mailyte.notifications.template', 'laravel-notification');
    }

    /**
     * MailMessage's content model onto the shell's variables.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function map(array $data): array
    {
        return [
            'greeting' => $this->greeting($data),
            'lines' => $this->lines($data['introLines'] ?? []),
            'action_label' => $data['actionText'] ?? null,
            'action_url' => $data['actionUrl'] ?? null,
            'outro_lines' => $this->lines($data['outroLines'] ?? []),
            'salutation' => $this->salutation($data),
            'subcopy' => $this->subcopy($data),
            'level' => in_array($data['level'] ?? 'info', ['info', 'success', 'error'], true)
                ? $data['level']
                : 'info',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function greeting(array $data): string
    {
        if (is_string($greeting = $data['greeting'] ?? null) && $greeting !== '') {
            return $greeting;
        }

        // Laravel's own defaults, so switching this channel on does not change
        // what a message says -- only how it looks.
        return ($data['level'] ?? 'info') === 'error'
            ? (string) __('Whoops!')
            : (string) __('Hello!');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function salutation(array $data): string
    {
        if (is_string($salutation = $data['salutation'] ?? null) && $salutation !== '') {
            return $salutation;
        }

        return __('Regards,')."\n".(string) $this->config->get('app.name');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function subcopy(array $data): ?string
    {
        $actionText = $data['actionText'] ?? null;
        $url = $data['displayableActionUrl'] ?? $data['actionUrl'] ?? null;

        if (! is_string($actionText) || ! is_string($url) || $url === '') {
            return null;
        }

        return trim((string) __(
            "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below into your web browser:",
            ['actionText' => $actionText],
        ).' '.$url);
    }

    /**
     * Laravel's lines can be `Stringable`, and a `MailMessage` line is plain
     * text -- it must not arrive as markup.
     *
     * @param  mixed  $lines
     * @return array<int, string>
     */
    private function lines($lines): array
    {
        $out = [];

        foreach ((array) $lines as $line) {
            $text = trim((string) $line);

            if ($text !== '') {
                $out[] = $text;
            }
        }

        return $out;
    }
}
