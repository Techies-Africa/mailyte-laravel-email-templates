<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Usage;

use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Default recorder: counts in the cache, so it needs no migration.
 *
 * Good enough to answer "which templates does this app actually send", but it
 * is only as durable as the cache store -- a flush loses the tally. Use the
 * database recorder where the numbers need to survive.
 */
class CacheUsageRecorder implements UsageRecorder
{
    private const KEY = 'mailyte:usage';

    public function __construct(private readonly Cache $cache) {}

    public function record(string $slug, string $version): void
    {
        $all = $this->all();

        $all[$slug] = [
            'slug' => $slug,
            'version' => $version,
            'count' => ($all[$slug]['count'] ?? 0) + 1,
            'last_used_at' => now()->toIso8601String(),
        ];

        $this->cache->forever(self::KEY, $all);
    }

    public function all(): array
    {
        /** @var array<string, array{slug: string, version: string, count: int, last_used_at: string|null}> $all */
        $all = $this->cache->get(self::KEY, []);

        return $all;
    }

    public function countFor(string $slug): int
    {
        return (int) ($this->all()[$slug]['count'] ?? 0);
    }

    public function flush(): void
    {
        $this->cache->forget(self::KEY);
    }
}
