<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Sources;

use Mailyte\EmailTemplates\Templates\TemplateManifest;

/**
 * A bundle that resolves by slug but never appears in the catalog.
 *
 * The notification shell is infrastructure, not one of the fifty designed
 * emails: it exists to receive whatever content a Laravel `MailMessage`
 * happens to carry, so it has no job of its own and nothing to preview. It
 * still has to be a real bundle -- theme, layout, blocks, dark mode, brand
 * config all come from the same pipeline -- so it lives in the source chain
 * with its listing suppressed rather than as a special case in the renderer.
 *
 * Publishing it moves the copy into the application's own published directory,
 * which is a listed source. At that point it does show up in the catalog, which
 * is right: it has become the application's template.
 */
class ShellSource extends DirectorySource
{
    public function find(string $slug): ?TemplateManifest
    {
        return $this->bundles()[$slug] ?? null;
    }

    /**
     * Deliberately empty: `all()` is what the catalog, the linter, the preview
     * gallery and the usage report enumerate, and a shell belongs in none of
     * them.
     *
     * @return array<string, TemplateManifest>
     */
    public function all(): array
    {
        return [];
    }

    /**
     * @return array<string, TemplateManifest>
     */
    private function bundles(): array
    {
        return parent::all();
    }
}
