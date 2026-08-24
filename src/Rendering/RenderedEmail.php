<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Rendering;

/**
 * The finished message: HTML, plain-text alternative, and the headers worth
 * setting alongside them.
 */
final class RenderedEmail
{
    /**
     * @param  array<string, string>  $suggestedHeaders
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly string $html,
        public readonly string $text,
        public readonly string $subject,
        public readonly string $preheader,
        public readonly array $suggestedHeaders = [],
        public readonly array $warnings = [],
    ) {}

    /**
     * Whether the rendered body carries no real content.
     *
     * Worth checking before a digest goes out: an empty digest is worse than no
     * digest, and "know when not to send" is a genuine deliverability practice
     * rather than a nicety -- pointless sends train recipients to ignore you.
     */
    public function isEmpty(int $minimumWords = 3): bool
    {
        $words = preg_split('/\s+/', trim(strip_tags($this->text))) ?: [];

        return count(array_filter($words)) < $minimumWords;
    }

    public function bytes(): int
    {
        return strlen($this->html);
    }

    /**
     * Gmail clips messages over roughly 102KB, hiding whatever is past the cut
     * -- typically the footer, and with it the unsubscribe link.
     */
    public function willBeClippedByGmail(): bool
    {
        return $this->bytes() > 102400;
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
