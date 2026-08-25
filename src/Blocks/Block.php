<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks;

use Mailyte\EmailTemplates\Themes\Theme;
use Twig\Markup;

/**
 * A block is a PHP class plus a Blade view that *we* render.
 *
 * This is the boundary that makes contributed templates safe: a template calls
 * a block by name and passes props, but its own text never reaches the Blade
 * compiler. Every string a contributor supplies arrives here as a typed,
 * escaped prop.
 *
 * Blocks are overridable exactly like Laravel's mail components -- publish
 * resources/views/vendor/mailyte/html/<name>.blade.php and edit it.
 */
abstract class Block
{
    /** The name templates call it by, and the Blade view basename. */
    abstract public function name(): string;

    /**
     * Validate, default and coerce the props a template passed.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    abstract public function normalize(array $props, Theme $theme): array;

    /** Whether the block wraps content, i.e. `{% card %}...{% endcard %}`. */
    public function hasSlot(): bool
    {
        return false;
    }

    /**
     * Whether this block spans the full canvas rather than sitting inside the
     * layout gutter.
     *
     * The content column has no horizontal padding, so the gutter is applied
     * per block. A block that returns true here gets none, which is how a
     * coloured band or a bled photograph reaches the edges of the message the
     * way it does in every email worth copying.
     *
     * @param  array<string, mixed>  $props
     */
    public function fullBleed(array $props = []): bool
    {
        return false;
    }

    public function view(): string
    {
        return 'mailyte::html.'.$this->name();
    }

    public function textView(): string
    {
        return 'mailyte::text.'.$this->name();
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function string(array $props, string $key, string $default = ''): string
    {
        $value = $props[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Read a slot: the content a wrapping block was given.
     *
     * Output of another block arrives as Twig\Markup and is already trusted --
     * we generated it. Anything else is a plain string a template author
     * interpolated, which may carry template data, so it is escaped here. Without
     * this split, `{{ card({slot: user.bio}) }}` would inject unescaped markup
     * into the message.
     *
     * @param  array<string, mixed>  $props
     */
    protected function slot(array $props, string $key = 'slot'): string
    {
        $value = $props[$key] ?? '';

        if ($value instanceof Markup) {
            return (string) $value;
        }

        if (! is_scalar($value)) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function bool(array $props, string $key, bool $default = false): bool
    {
        return isset($props[$key]) ? (bool) $props[$key] : $default;
    }

    /**
     * @param  array<string, mixed>  $props
     * @param  array<int, string>  $allowed
     */
    protected function enum(array $props, string $key, array $allowed, string $default): string
    {
        $value = $this->string($props, $key, $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<int, mixed>
     */
    protected function list(array $props, string $key): array
    {
        $value = $props[$key] ?? [];

        return is_array($value) ? array_values($value) : [];
    }

    /**
     * Absolute-URL guard.
     *
     * Mail clients have no base URL, so a relative href is simply dead. The
     * linter flags this too (MT014), but blocks refuse it at render time so a
     * broken link can never reach an inbox.
     *
     * @param  array<string, mixed>  $props
     */
    protected function url(array $props, string $key, ?string $default = null): ?string
    {
        $value = $this->string($props, $key, $default ?? '');

        if ($value === '') {
            return null;
        }

        // Left as-is when it is already absolute or an allowed scheme; the
        // pipeline's PostProcess stage resolves anything relative against the
        // configured base URL before the document is finished.
        return $value;
    }
}
