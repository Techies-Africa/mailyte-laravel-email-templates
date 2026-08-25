<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Console;

use Illuminate\Console\Command;
use Mailyte\EmailTemplates\Linting\Issue;
use Mailyte\EmailTemplates\Linting\TemplateLinter;
use Mailyte\EmailTemplates\MailyteManager;

class LintCommand extends Command
{
    protected $signature = 'mailyte:lint
        {slug?* : Templates to check; omit to check every template this application can resolve}
        {--strict : Fail on warnings as well as errors}
        {--show-waived : Include issues a manifest has waived, with the stated reason}';

    protected $description = 'Check template bundles against the schema and the catalog house rules';

    public function handle(MailyteManager $mailyte, TemplateLinter $linter): int
    {
        $catalog = $mailyte->catalog();

        /** @var array<int, string> $requested */
        $requested = (array) $this->argument('slug');

        if ($requested !== []) {
            // Named explicitly, so resolve by slug rather than from the
            // catalog: a hidden bundle such as the notification shell is still
            // a real email somebody receives, and has to be checkable.
            $resolved = [];
            $unknown = [];

            foreach ($requested as $slug) {
                $manifest = $mailyte->sources()->find($slug);

                $manifest === null ? $unknown[] = $slug : $resolved[$slug] = $manifest;
            }

            if ($unknown !== []) {
                $this->components->error('Unknown template: '.implode(', ', $unknown));

                return self::FAILURE;
            }

            $catalog = $resolved;
        }

        if ($catalog === []) {
            $this->components->warn('No templates to check.');

            return self::SUCCESS;
        }

        $errors = 0;
        $warnings = 0;
        $waived = 0;
        $clean = 0;

        foreach ($catalog as $slug => $manifest) {
            $issues = $linter->lint($manifest);

            if (! $this->option('show-waived')) {
                $issues = array_filter($issues, static fn (Issue $i): bool => ! $i->isWaived());
            }

            if ($issues === []) {
                $clean++;

                continue;
            }

            $this->newLine();
            $this->line("  <options=bold>{$slug}</>");

            foreach ($issues as $issue) {
                if ($issue->isWaived()) {
                    $waived++;
                    $this->line("    <fg=gray>waived {$issue->rule}  {$issue->message}</>");
                    $this->line("    <fg=gray>        because {$issue->waivedBecause}</>");

                    continue;
                }

                if ($issue->severity === Issue::ERROR) {
                    $errors++;
                    $this->line("    <fg=red>error   {$issue->rule}</>  {$issue->message}");

                    continue;
                }

                $warnings++;
                $this->line("    <fg=yellow>warning {$issue->rule}</>  {$issue->message}");
            }
        }

        $this->newLine();

        $summary = count($catalog).' checked, '.$errors.' '.str('error')->plural($errors)
            .', '.$warnings.' '.str('warning')->plural($warnings);

        if ($waived > 0) {
            $summary .= ', '.$waived.' waived';
        }

        if ($errors > 0 || ($this->option('strict') && $warnings > 0)) {
            $this->components->error($summary);

            return self::FAILURE;
        }

        $this->components->info($clean === count($catalog) ? $summary : $summary.' -- nothing blocking');

        return self::SUCCESS;
    }
}
