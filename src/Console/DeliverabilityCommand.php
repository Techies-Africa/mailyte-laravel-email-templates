<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Console;

use Illuminate\Console\Command;
use Mailyte\EmailTemplates\Deliverability\DeliverabilityAudit;
use Mailyte\EmailTemplates\Deliverability\EmlWriter;
use Mailyte\EmailTemplates\Linting\Issue;
use Mailyte\EmailTemplates\MailyteManager;

class DeliverabilityCommand extends Command
{
    protected $signature = 'mailyte:deliverability
        {slug?* : Templates to check; omit to check every template this application can resolve}
        {--layout= : Only this layout, instead of every layout the template declares}
        {--sample=default : Which sample data to render with}
        {--eml= : Also write each rendered message as an .eml file into this directory}
        {--strict : Fail on warnings as well as errors}';

    protected $description = 'Check what the rendered message itself can do about landing in the inbox';

    public function handle(MailyteManager $mailyte, DeliverabilityAudit $audit): int
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

        $emlDir = $this->option('eml');

        if (is_string($emlDir) && ! is_dir($emlDir) && ! mkdir($emlDir, 0755, true) && ! is_dir($emlDir)) {
            $this->components->error("Could not create {$emlDir}");

            return self::FAILURE;
        }

        $errors = 0;
        $warnings = 0;
        $checked = 0;
        $written = 0;

        foreach ($catalog as $slug => $manifest) {
            $layouts = is_string($only = $this->option('layout'))
                ? array_values(array_filter($manifest->supportedLayouts(), fn (string $l): bool => $l === $only))
                : $manifest->supportedLayouts();

            if ($layouts === []) {
                $this->components->warn("{$slug} does not support the {$only} layout, skipping.");

                continue;
            }

            foreach ($layouts as $layout) {
                $samples = $manifest->samples();
                $sample = (string) $this->option('sample');
                $data = $samples[$sample] ?? reset($samples) ?: [];

                $rendered = $mailyte->template($slug)->with($data)->layout($layout)->render();
                $checked++;

                if (is_string($emlDir)) {
                    $path = rtrim($emlDir, '/')."/{$slug}-{$layout}.eml";
                    file_put_contents($path, (new EmlWriter)->write($rendered));
                    $written++;
                }

                $issues = array_filter($audit->audit($rendered, $manifest), static fn (Issue $i): bool => ! $i->isWaived());

                if ($issues === []) {
                    continue;
                }

                $this->newLine();
                $this->line("  <options=bold>{$slug}</> <fg=gray>{$layout}</> <fg=gray>".$this->weight($rendered->bytes()).'</>');

                foreach ($issues as $issue) {
                    if ($issue->severity === Issue::ERROR) {
                        $errors++;
                        $this->line("    <fg=red>error   {$issue->rule}</>  {$issue->message}");

                        continue;
                    }

                    $warnings++;
                    $this->line("    <fg=yellow>warning {$issue->rule}</>  {$issue->message}");
                }
            }
        }

        $this->newLine();

        if ($written > 0) {
            $this->components->info("Wrote {$written} .eml files to {$emlDir} — feed one to mail-tester.com or `spamassassin -t` for a score that includes your own authentication.");
        }

        $summary = "{$checked} renders checked, {$errors} ".str('error')->plural($errors)
            .", {$warnings} ".str('warning')->plural($warnings);

        if ($errors > 0 || ($this->option('strict') && $warnings > 0)) {
            $this->components->error($summary);

            return self::FAILURE;
        }

        $this->components->info($summary);
        $this->line('  <fg=gray>This covers the message only. SPF, DKIM, DMARC, domain reputation and list</>');
        $this->line('  <fg=gray>hygiene decide most of inbox placement — see docs/deliverability.md.</>');

        return self::SUCCESS;
    }

    private function weight(int $bytes): string
    {
        return round($bytes / 1024, 1).'KB';
    }
}
