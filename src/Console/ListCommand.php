<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Console;

use Illuminate\Console\Command;
use Mailyte\EmailTemplates\MailyteManager;
use Mailyte\EmailTemplates\Templates\TemplateManifest;

class ListCommand extends Command
{
    protected $signature = 'mailyte:list
        {--category= : Filter by category}
        {--type= : Filter by transactional|notification|marketing}
        {--tier= : Filter by core|community}';

    protected $description = 'List the templates available to this application';

    public function handle(MailyteManager $mailyte): int
    {
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
}
