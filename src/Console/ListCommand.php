<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Console;

use Illuminate\Console\Command;
use Mailyte\EmailTemplates\MailyteManager;
use Mailyte\EmailTemplates\Templates\TemplateManifest;

class ListCommand extends Command
{
    protected $signature = 'mailyte:list
        {slug? : Show one template in detail, including the data it expects}
        {--category= : Filter by category}
        {--type= : Filter by transactional|notification|marketing}
        {--tier= : Filter by core|community}';

    protected $description = 'List the templates available to this application';

    public function handle(MailyteManager $mailyte): int
    {
        if (is_string($slug = $this->argument('slug'))) {
            return $this->describe($mailyte, $slug);
        }

        $templates = $mailyte->catalog();

        foreach (['category', 'type', 'tier'] as $facet) {
            if ($value = $this->option($facet)) {
                $templates = array_filter(
                    $templates,
                    static fn (TemplateManifest $m): bool => $value === $m->{$facet}()
                );
            }
        }

        if ($templates === []) {
            $this->components->warn('No templates matched.');

            return self::SUCCESS;
        }

        $this->table(
            ['Slug', 'Category', 'Type', 'Layouts', 'Source'],
            array_map(static fn (TemplateManifest $m): array => [
                $m->slug,
                $m->category(),
                $m->type(),
                implode(', ', $m->supportedLayouts()),
                $m->source,
            ], array_values($templates)),
        );

        return self::SUCCESS;
    }

    /**
     * One template in detail: what it is, and what data it expects.
     *
     * Written for the moment someone is about to call it from application code
     * and needs to know which fields are required, so required ones are listed
     * first and defaults are shown rather than described.
     */
    private function describe(MailyteManager $mailyte, string $slug): int
    {
        if (! $mailyte->has($slug)) {
            $this->components->error("No template [{$slug}] in this catalog.");

            return self::FAILURE;
        }

        $manifest = $mailyte->sources()->get($slug);

        $this->newLine();
        $this->line("  <fg=white;options=bold>{$manifest->name()}</> <fg=gray>{$manifest->slug}</>");
        $this->line("  <fg=gray>{$manifest->description()}</>");
        $this->newLine();

        $this->components->twoColumnDetail('<fg=gray>Category</>', $manifest->category());
        $this->components->twoColumnDetail('<fg=gray>Type</>', $manifest->type());
        $this->components->twoColumnDetail('<fg=gray>Layouts</>', implode(', ', $manifest->supportedLayouts()));
        $this->components->twoColumnDetail('<fg=gray>Subject</>', $manifest->subject());
        $this->components->twoColumnDetail('<fg=gray>Samples</>', implode(', ', array_keys($manifest->samples())));

        $required = [];
        $optional = [];

        foreach ($manifest->variables() as $name => $spec) {
            $row = [
                $name,
                (string) ($spec['type'] ?? 'string'),
                ($spec['required'] ?? false) ? 'required' : $this->shorten($spec['default'] ?? null),
                (string) ($spec['description'] ?? ''),
            ];

            ($spec['required'] ?? false) ? $required[] = $row : $optional[] = $row;
        }

        $this->newLine();
        $this->table(['Variable', 'Type', 'Default', 'What it is for'], [...$required, ...$optional]);

        $this->line('  <fg=gray>Send it:</> php artisan mailyte:send-test '.$manifest->slug.' --to=you@example.com');
        $this->newLine();

        return self::SUCCESS;
    }

    private function shorten(mixed $default): string
    {
        if ($default === null || $default === '' || $default === []) {
            return '<fg=gray>—</>';
        }

        $text = is_scalar($default) ? (string) $default : json_encode($default);

        return mb_strlen((string) $text) > 32 ? mb_substr((string) $text, 0, 31).'…' : (string) $text;
    }
}
