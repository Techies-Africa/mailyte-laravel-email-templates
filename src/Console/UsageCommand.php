<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Console;

use Illuminate\Console\Command;
use Mailyte\EmailTemplates\Usage\UsageRecorder;
use Mailyte\EmailTemplates\Usage\UsageReport;

class UsageCommand extends Command
{
    protected $signature = 'mailyte:usage
        {--share : Build the anonymous report that would be shared with template authors}
        {--dry-run : With --share, print the exact payload instead of sending it}
        {--flush : Reset the local counters}';

    protected $description = 'Show how often each template has been sent';

    public function handle(UsageRecorder $recorder, UsageReport $report): int
    {
        if ($this->option('flush')) {
            $recorder->flush();
            $this->components->info('Usage counters reset.');

            return self::SUCCESS;
        }

        $usage = $recorder->all();

        if ($usage === []) {
            $this->components->warn('No sends recorded yet.');

            return self::SUCCESS;
        }

        if (! $this->option('share')) {
            uasort($usage, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

            $this->table(
                ['Template', 'Version', 'Sent', 'Last used'],
                array_map(static fn (array $row): array => [
                    $row['slug'],
                    $row['version'],
                    number_format($row['count']),
                    $row['last_used_at'] ?? '-',
                ], array_values($usage)),
            );

            $this->newLine();
            $this->line('  <fg=gray>Counted locally. Nothing is transmitted anywhere.</>');
            $this->line('  <fg=gray>To share these numbers with template authors: mailyte:usage --share --dry-run</>');

            return self::SUCCESS;
        }

        $payload = $report->build($usage);

        $this->components->info('This is the complete payload. Every field is listed in config mailyte.usage.share.fields.');
        $this->newLine();
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->newLine();

        $this->line('  <fg=gray>Not included, and never will be: recipients, subjects, message bodies,</>');
        $this->line('  <fg=gray>your app name, your domain, or any customer data.</>');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->components->warn('Dry run. Nothing was sent.');

            return self::SUCCESS;
        }

        if (! config('mailyte.usage.share.enabled', false)) {
            $this->components->error(
                'Sharing is disabled. Set MAILYTE_USAGE_SHARE=true to opt in, once you are happy with the payload above.'
            );

            return self::FAILURE;
        }

        $sent = $report->send($payload);

        $sent
            ? $this->components->info('Usage shared. Thank you -- this is how template authors learn their work is useful.')
            : $this->components->error('Could not reach the registry. Nothing was retried or queued.');

        return $sent ? self::SUCCESS : self::FAILURE;
    }
}
