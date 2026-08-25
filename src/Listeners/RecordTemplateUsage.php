<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Mailyte\EmailTemplates\Usage\UsageRecorder;

/**
 * Counts a template at the moment it is actually sent, then removes the marker
 * header so it never reaches the wire.
 *
 * Counting at render time would over-report: previews, tests and abandoned
 * queue jobs all render without ever sending. The header is how the slug
 * travels from render to send, and stripping it here matters -- left in place
 * it would tell every recipient, and every relay along the way, which template
 * you used, which is nobody's business but yours.
 */
class RecordTemplateUsage
{
    public const HEADER = 'X-Mailyte-Template';

    public const VERSION_HEADER = 'X-Mailyte-Template-Version';

    public function __construct(private readonly UsageRecorder $recorder) {}

    public function handle(MessageSending $event): void
    {
        $headers = $event->message->getHeaders();

        if (! $headers->has(self::HEADER)) {
            return;
        }

        $slug = trim((string) $headers->get(self::HEADER)?->getBodyAsString());
        $version = trim((string) $headers->get(self::VERSION_HEADER)?->getBodyAsString());

        $headers->remove(self::HEADER);
        $headers->remove(self::VERSION_HEADER);

        if ($slug === '') {
            return;
        }

        // Never let a counter break a send. A missing statistic is a nuisance;
        // an email that failed to go out because of one is a real problem.
        try {
            $this->recorder->record($slug, $version);
        } catch (\Throwable) {
            // Intentionally swallowed.
        }
    }
}
