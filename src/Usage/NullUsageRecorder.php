<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Usage;

class NullUsageRecorder implements UsageRecorder
{
    public function record(string $slug, string $version): void {}

    public function all(): array
    {
        return [];
    }

    public function countFor(string $slug): int
    {
        return 0;
    }

    public function flush(): void {}
}
