<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Console;

use Illuminate\Console\Command;
use Mailyte\EmailTemplates\MailyteManager;

class PublishTemplateCommand extends Command
{
    protected $signature = 'mailyte:publish-template
        {slug : The template to copy into your application}
        {--as= : Publish under a different slug, leaving the original in place}
        {--force : Overwrite a copy you have already published}';

    protected $description = 'Copy one template into your application so you can edit it';

    /**
     * Publishing is per template on purpose.
     *
     * The framework publishes mail *components*, not your application's emails,
     * and the same reasoning holds here: copying the whole catalog would fork
     * fifty bundles you never intended to maintain, and every one of them would
     * stop receiving fixes. One slug at a time keeps the fork deliberate.
     */
    public function handle(MailyteManager $mailyte): int
    {
        $slug = (string) $this->argument('slug');

        if (! $mailyte->has($slug)) {
            $this->components->error("No template [{$slug}] in this catalog.");
            $this->line('  <fg=gray>Run</> php artisan mailyte:list <fg=gray>to see what is available.</>');

            return self::FAILURE;
        }

        $manifest = $mailyte->sources()->get($slug);
        $target = (string) ($this->option('as') ?: $slug);

        if (! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $target)) {
            $this->components->error("[{$target}] is not a valid slug. Use lower-case words separated by hyphens.");

            return self::FAILURE;
        }

        $root = (string) config(
            'mailyte.sources.published',
            resource_path('views/vendor/mailyte/templates'),
        );
        $destination = rtrim($root, '/').'/'.$target;

        if (is_dir($destination) && ! $this->option('force')) {
            $this->components->error("[{$destination}] already exists.");
            $this->line('  <fg=gray>Pass</> --force <fg=gray>to overwrite it, or</> --as=another-slug <fg=gray>to publish a second copy.</>');

            return self::FAILURE;
        }

        if (! is_dir($destination) && ! mkdir($destination, 0755, true) && ! is_dir($destination)) {
            $this->components->error("Could not create [{$destination}].");

            return self::FAILURE;
        }

        $copied = [];

        foreach (['template.json', 'design.json', 'email.html', 'email.txt', 'sample.json', 'styles.css'] as $file) {
            if (! $manifest->has($file)) {
                continue;
            }

            $contents = (string) $manifest->read($file);

            // A bundle's slug has to match its directory name, so a rename has
            // to reach the manifest too or the catalog will refuse to load it.
            if ($file === 'template.json' && $target !== $slug) {
                $contents = $this->reslug($contents, $slug, $target);
            }

            file_put_contents($destination.'/'.$file, $contents);
            $copied[] = $file;
        }

        $this->newLine();
        $this->components->info(sprintf('Published [%s] to %s', $slug, $destination));

        foreach ($copied as $file) {
            $this->components->twoColumnDetail("  {$file}", $this->purposeOf($file));
        }

        $this->newLine();

        if ($target === $slug) {
            $this->line('  <fg=gray>Your copy now takes precedence over the packaged one everywhere</>');
            $this->line('  <fg=gray>`'.$slug.'` is used, including in code you have already written.</>');
        } else {
            $this->line('  <fg=gray>The packaged</> '.$slug.' <fg=gray>is untouched. Use your copy with</>');
            $this->line('  Mailyte::template(\''.$target.'\')');
        }

        $this->newLine();
        $this->line('  <fg=gray>Preview it:</> php artisan mailyte:send-test '.$target.' --to=you@example.com');
        $this->newLine();

        return self::SUCCESS;
    }

    private function reslug(string $manifest, string $from, string $to): string
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);

        $data['slug'] = $to;

        // It is a fork of a catalog template, and saying so is what lets anyone
        // reading it later find what it diverged from.
        $data['tier'] = 'community';
        $data['origin'] = [
            'kind' => 'derived',
            'source' => 'https://mailyte.com/templates/'.$from,
            'note' => 'Published from the Mailyte catalog template ['.$from.'] and edited locally.',
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    }

    private function purposeOf(string $file): string
    {
        return match ($file) {
            'template.json' => 'what it is, and the data it takes',
            'design.json' => 'its palette, type scale and rhythm',
            'email.html' => 'the composition',
            'email.txt' => 'the plain-text alternative',
            'sample.json' => 'preview and test data',
            'styles.css' => 'extra CSS for this template',
            default => '',
        };
    }
}
