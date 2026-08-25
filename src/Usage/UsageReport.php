<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Usage;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;

/**
 * Builds the anonymous report shared with template authors.
 *
 * The premise: an author who contributes a template deserves to know whether
 * anyone is using it. The constraint: that curiosity does not entitle anyone to
 * your data.
 *
 * So the payload is assembled from a config allowlist rather than filtered
 * after the fact. A field that is not in `mailyte.usage.share.fields` cannot be
 * transmitted, even by mistake, and adding one is a visible change to a config
 * file the operator owns.
 *
 * There is deliberately no install identifier. A stable ID would make counting
 * tidier and would also make installations trackable over time, which is a
 * trade worth refusing for a statistic this soft.
 */
final class UsageReport
{
    public const PACKAGE_VERSION = '0.1.0';

    public function __construct(
        private readonly Config $config,
        private readonly Http $http,
    ) {}

    /**
     * @param  array<string, array{slug: string, version: string, count: int, last_used_at: string|null}>  $usage
     * @return array<string, mixed>
     */
    public function build(array $usage): array
    {
        $allowed = (array) $this->config->get('mailyte.usage.share.fields', []);

        $templates = [];

        foreach ($usage as $row) {
            $candidate = [
                'template_slug' => $row['slug'],
                'template_version' => $row['version'],
                'count' => $row['count'],
            ];

            $templates[] = array_intersect_key($candidate, array_flip($allowed));
        }

        $envelope = [
            'package_version' => self::PACKAGE_VERSION,
            'php_minor' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
            'laravel_minor' => $this->laravelMinor(),
            // Coarse on purpose. A precise timestamp is a fingerprint; the month
            // is all anyone needs to chart whether a template is catching on.
            'period' => date('Y-m'),
        ];

        return array_merge(
            array_intersect_key($envelope, array_flip($allowed)),
            ['templates' => $templates],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload): bool
    {
        if (! $this->config->get('mailyte.usage.share.enabled', false)) {
            return false;
        }

        $endpoint = (string) $this->config->get('mailyte.usage.share.endpoint');

        if ($endpoint === '') {
            return false;
        }

        try {
            // Short timeout, no retries, no queue. Sharing a statistic must
            // never become a reason an application hangs.
            return $this->http->timeout(5)->asJson()->post($endpoint, $payload)->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function laravelMinor(): string
    {
        $parts = explode('.', Application::VERSION);

        return $parts[0].'.'.($parts[1] ?? '0');
    }
}
