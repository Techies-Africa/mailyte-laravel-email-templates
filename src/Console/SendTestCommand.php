<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Mailyte\EmailTemplates\MailyteManager;

class SendTestCommand extends Command
{
    protected $signature = 'mailyte:send-test
        {slug? : Template slug. Omit to send every template in the catalog.}
        {--to= : Recipient address}
        {--theme= : Theme name}
        {--layout= : Layout preset}
        {--sample=default : Which sample-data variant to use}';

    protected $description = 'Send one template, or the whole catalog, through the configured mailer';

    public function handle(MailyteManager $mailyte): int
    {
        $to = (string) ($this->option('to') ?: $this->ask('Send to which address?'));

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->components->error("[{$to}] is not a valid email address.");

            return self::FAILURE;
        }

        $slugs = $this->argument('slug') !== null
            ? [(string) $this->argument('slug')]
            : array_keys($mailyte->catalog());

        $this->components->info(sprintf(
            'Sending %d template(s) to %s via the [%s] mailer.',
            count($slugs),
            $to,
            (string) config('mail.default'),
        ));

        $failed = 0;

        foreach ($slugs as $slug) {
            try {
                $manifest = $mailyte->sources()->get($slug);
                $samples = $manifest->samples();
                $key = (string) $this->option('sample');
                $data = $samples[$key] ?? $samples[array_key_first($samples)] ?? [];

                $builder = $mailyte->template($slug)->with($data);

                if ($theme = $this->option('theme')) {
                    $builder->theme((string) $theme);
                }

                $layout = $this->option('layout');

                if (is_string($layout) && $layout !== '') {
                    $builder->layout($layout);
                }

                $rendered = $builder->render();

                Mail::to($to)->send($rendered->toMailableFrom());

                $this->components->twoColumnDetail(
                    $slug,
                    sprintf('%.1f KB%s', $rendered->bytes() / 1024, $rendered->willBeClippedByGmail() ? ' CLIPPED BY GMAIL' : '')
                );
            } catch (\Throwable $e) {
                $failed++;
                $this->components->twoColumnDetail($slug, '<fg=red>'.$e->getMessage().'</>');
            }
        }

        if ($failed > 0) {
            $this->newLine();
            $this->components->error("{$failed} template(s) failed to send.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
