<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Linting;

/**
 * One thing wrong with one template bundle.
 *
 * Every issue carries a stable `MT###` code so a bundle can waive a rule it
 * genuinely does not need -- with a written reason, recorded in the manifest
 * -- without turning the whole linter off.
 */
final class Issue
{
    public const ERROR = 'error';

    public const WARNING = 'warning';

    public function __construct(
        public readonly string $slug,
        public readonly string $rule,
        public readonly string $severity,
        public readonly string $message,
        public readonly ?string $waivedBecause = null,
    ) {}

    public static function error(string $slug, string $rule, string $message): self
    {
        return new self($slug, $rule, self::ERROR, $message);
    }

    public static function warning(string $slug, string $rule, string $message): self
    {
        return new self($slug, $rule, self::WARNING, $message);
    }

    public function waive(string $reason): self
    {
        return new self($this->slug, $this->rule, $this->severity, $this->message, $reason);
    }

    public function isWaived(): bool
    {
        return $this->waivedBecause !== null;
    }

    public function isError(): bool
    {
        return $this->severity === self::ERROR && ! $this->isWaived();
    }

    public function __toString(): string
    {
        return "{$this->slug} [{$this->rule}] {$this->message}";
    }
}
