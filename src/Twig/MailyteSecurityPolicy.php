<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Twig;

use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * The allowlist that makes untrusted template files safe to render.
 *
 * Template bundles are contributed by strangers and may be installed from a
 * public catalog, so the threat model is "this file is hostile". The policy is
 * therefore a strict allowlist rather than a denylist:
 *
 *  - No object methods or properties at all. Render data is deep-converted to
 *    arrays before it reaches Twig, so there is nothing to traverse even if a
 *    template tries.
 *  - No `include`, `extends`, `embed`, `import`, `use` or `source` -- those tags
 *    are never registered on the environment, so a template has no reach into
 *    the filesystem.
 *  - No `raw` filter. Autoescaping is always on and cannot be opted out of;
 *    HTML that must pass through unescaped comes from our own block classes,
 *    which mark their output safe at the extension level.
 *
 * What this does NOT protect against is resource exhaustion -- see
 * SandboxFactory for the caps that cover that.
 */
final class MailyteSecurityPolicy implements SecurityPolicyInterface
{
    /** @var array<int, string> */
    public const ALLOWED_TAGS = ['if', 'else', 'elseif', 'for', 'set', 'verbatim', 'apply'];

    /** @var array<int, string> */
    public const ALLOWED_FILTERS = [
        'abs', 'capitalize', 'date', 'default', 'escape', 'e', 'first', 'format',
        'join', 'json_encode', 'keys', 'last', 'length', 'lower', 'merge', 'nl2br',
        'number_format', 'replace', 'reverse', 'round', 'slice', 'sort', 'split',
        'striptags', 'title', 'trim', 'upper', 'url_encode',
    ];

    /** @var array<int, string> */
    public const ALLOWED_FUNCTIONS = ['max', 'min', 'theme'];

    /** @var array<int, string> */
    public const ALLOWED_TESTS = [
        'defined', 'divisible by', 'empty', 'even', 'iterable', 'null', 'odd', 'same as',
    ];

    /**
     * @param  array<int, string>  $blockFunctions  block names registered by BlockExtension
     */
    public function __construct(private array $blockFunctions = []) {}

    /**
     * @param  array<int, string>  $blockFunctions
     */
    public function withBlockFunctions(array $blockFunctions): self
    {
        return new self($blockFunctions);
    }

    /**
     * @param  string[]  $tags
     * @param  string[]  $filters
     * @param  string[]  $functions
     * @param  string[]  $tests
     */
    public function checkSecurity($tags, $filters, $functions, array $tests = []): void
    {
        foreach ($tags as $tag) {
            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                throw new SecurityNotAllowedTagError($this->explainTag($tag), $tag);
            }
        }

        foreach ($filters as $filter) {
            if (! in_array($filter, self::ALLOWED_FILTERS, true)) {
                throw new SecurityNotAllowedFilterError($this->explainFilter($filter), $filter);
            }
        }

        $allowedFunctions = [...self::ALLOWED_FUNCTIONS, ...$this->blockFunctions];

        foreach ($functions as $function) {
            if (! in_array($function, $allowedFunctions, true)) {
                throw new SecurityNotAllowedFunctionError($this->explainFunction($function), $function);
            }
        }

        foreach ($tests as $test) {
            if (! in_array($test, self::ALLOWED_TESTS, true)) {
                throw new SecurityError(sprintf('Test "%s" is not allowed in email templates.', $test));
            }
        }
    }

    /**
     * @param  object  $obj
     * @param  string  $method
     */
    public function checkMethodAllowed($obj, $method): void
    {
        // Block output is Twig\Markup, which this package created and already
        // escaped. Twig stringifies it on the way out, so that one call has to
        // be permitted or no block could render at all. Everything else -- any
        // method on any caller-supplied object -- still fails closed.
        if ($obj instanceof Markup && strtolower($method) === '__tostring') {
            return;
        }

        throw new SecurityNotAllowedMethodError(
            sprintf(
                'Calling method "%s" on %s is not allowed. Template data is converted to plain arrays '
                .'before rendering, so use array access ({{ user.name }}) instead of method calls.',
                $method,
                get_debug_type($obj),
            ),
            get_class($obj),
            $method,
        );
    }

    /**
     * @param  object  $obj
     * @param  string  $property
     */
    public function checkPropertyAllowed($obj, $property): void
    {
        throw new SecurityNotAllowedPropertyError(
            sprintf(
                'Reading property "%s" on %s is not allowed. Template data is converted to plain arrays '
                .'before rendering, so use array access ({{ user.name }}) instead.',
                $property,
                get_debug_type($obj),
            ),
            get_class($obj),
            $property,
        );
    }

    private function explainTag(string $tag): string
    {
        $hint = match ($tag) {
            'include', 'extends', 'embed', 'import', 'use', 'from' => 'Templates cannot pull in other files. '
                .'Compose with blocks instead, and use a layout preset for shared chrome.',
            'macro' => 'Macros are not available. If you are repeating markup, that is usually a sign it '
                .'should become a block -- open an issue and we will look at adding one.',
            'apply' => '',
            default => 'Allowed tags are: '.implode(', ', self::ALLOWED_TAGS).'.',
        };

        return trim(sprintf('The "%s" tag is not allowed in email templates. %s', $tag, $hint));
    }

    private function explainFilter(string $filter): string
    {
        $hint = match ($filter) {
            'raw' => 'Unescaped output is never permitted -- it is the main way a template could inject '
                .'markup into someone else\'s email. Blocks emit trusted HTML for you.',
            'map', 'filter', 'reduce' => 'Arrow-function filters can be used to build very large strings, '
                .'so they are disabled. Shape the data in your application instead.',
            default => 'Allowed filters are: '.implode(', ', self::ALLOWED_FILTERS).'.',
        };

        return sprintf('The "%s" filter is not allowed in email templates. %s', $filter, $hint);
    }

    private function explainFunction(string $function): string
    {
        $hint = match ($function) {
            'range' => 'Generating sequences would let a template build unbounded output.',
            'include', 'source' => 'Templates have no filesystem access.',
            'dump' => 'Debug output has no place in an email.',
            'attribute' => 'Dynamic attribute access is a sandbox-escape primitive.',
            default => 'Available functions are the block helpers plus: '.implode(', ', self::ALLOWED_FUNCTIONS).'.',
        };

        return sprintf('The "%s" function is not allowed in email templates. %s', $function, $hint);
    }
}
