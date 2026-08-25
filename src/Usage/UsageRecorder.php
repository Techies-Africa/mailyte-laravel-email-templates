<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Usage;

/**
 * Counts how often each template is actually sent.
 *
 * Deliberately narrow: a slug, a version, and a tally. No recipients, no
 * subjects, no message bodies. The question this answers is "is this template
 * pulling its weight", which needs a number and nothing else.
 */
interface UsageRecorder
{
    public function record(string $slug, string $version): void;

    /**
     * @return array<string, array{slug: string, version: string, count: int, last_used_at: string|null}>
     */
    public function all(): array;

    public function countFor(string $slug): int;

    public function flush(): void;
}
