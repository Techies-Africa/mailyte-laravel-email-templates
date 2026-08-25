<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Usage;

use Illuminate\Database\ConnectionInterface;

/**
 * Durable recorder. Requires the mailyte_template_usage migration.
 *
 * Still just slug, version, count, timestamp -- moving the tally into a table
 * is not an invitation to start storing message contents alongside it.
 */
class DatabaseUsageRecorder implements UsageRecorder
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly string $table = 'mailyte_template_usage',
    ) {}

    public function record(string $slug, string $version): void
    {
        $existing = $this->connection->table($this->table)->where('slug', $slug)->first();

        if ($existing === null) {
            $this->connection->table($this->table)->insert([
                'slug' => $slug,
                'version' => $version,
                'count' => 1,
                'last_used_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $this->connection->table($this->table)->where('slug', $slug)->update([
            'version' => $version,
            'count' => $this->connection->raw('count + 1'),
            'last_used_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function all(): array
    {
        $rows = [];

        foreach ($this->connection->table($this->table)->orderByDesc('count')->get() as $row) {
            $rows[$row->slug] = [
                'slug' => (string) $row->slug,
                'version' => (string) $row->version,
                'count' => (int) $row->count,
                'last_used_at' => $row->last_used_at !== null ? (string) $row->last_used_at : null,
            ];
        }

        return $rows;
    }

    public function countFor(string $slug): int
    {
        return (int) ($this->connection->table($this->table)->where('slug', $slug)->value('count') ?? 0);
    }

    public function flush(): void
    {
        $this->connection->table($this->table)->truncate();
    }
}
