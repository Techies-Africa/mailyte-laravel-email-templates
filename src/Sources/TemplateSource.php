<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Sources;

use Mailyte\EmailTemplates\Templates\TemplateManifest;

interface TemplateSource
{
    public function find(string $slug): ?TemplateManifest;

    /**
     * @return array<string, TemplateManifest>
     */
    public function all(): array;

    public function name(): string;
}
