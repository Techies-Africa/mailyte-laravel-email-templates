<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks;

/**
 * One declared input on a block, as `Block::schema()` returns it.
 *
 * A plain array rather than an object, to stay in step with `normalize()`,
 * which returns plain arrays too. These factories exist because there are 204
 * declared props across the 25 blocks and `space_below` alone appears in 23 of
 * them -- written out by hand, the schemas would disagree with each other
 * within a week.
 *
 * ## The group, and why it is not cosmetic
 *
 * Every prop is `content`, `style` or `spacing`, and the split is the whole
 * point of declaring any of this. A block picker offering somebody 17 inputs
 * for a split panel is the flat-form problem again one level down; offering the
 * seven that hold words, with the other ten behind "appearance", is a form a
 * person can use. `spacing` is separate from `style` because it is the group
 * an editor is most likely to hide outright.
 */
final class Prop
{
    public const CONTENT = 'content';

    public const STYLE = 'style';

    public const SPACING = 'spacing';

    /**
     * Words. A single line -- a heading, a label, a name.
     *
     * @return array<string, mixed>
     */
    public static function text(string $label, ?string $description = null, bool $required = false, ?string $default = null): array
    {
        return self::make('text', $label, $description, self::CONTENT, $required, $default);
    }

    /**
     * Words that run to a paragraph or more.
     *
     * Distinct from `text` so an editor can give it a textarea rather than an
     * input -- the same distinction the manifest variables draw between
     * `string` and `text`.
     *
     * @return array<string, mixed>
     */
    public static function richtext(string $label, ?string $description = null, bool $required = false, ?string $default = null): array
    {
        return self::make('richtext', $label, $description, self::CONTENT, $required, $default);
    }

    /**
     * A link.
     *
     * Always absolute in the end: a mail client has no base URL to resolve a
     * relative one against, so a relative href is simply dead. `Block::url()`
     * and the MT014 lint rule both say so; this is where an editor learns it
     * early enough to warn.
     *
     * @return array<string, mixed>
     */
    public static function url(string $label, ?string $description = null, bool $required = false): array
    {
        return self::make('url', $label, $description, self::CONTENT, $required);
    }

    /**
     * An image source. A URL, but one an editor should offer a picker for.
     *
     * @return array<string, mixed>
     */
    public static function image(string $label, ?string $description = null, bool $required = false): array
    {
        return self::make('image', $label, $description, self::CONTENT, $required);
    }

    /**
     * A colour, always overriding a theme token that already has an answer.
     *
     * `style`, never `content`: every one of these is optional by construction,
     * because the theme supplies the value when the prop is absent. An editor
     * that surfaced them beside the words would be handing people a way to
     * break their own palette.
     *
     * @return array<string, mixed>
     */
    public static function color(string $label, ?string $description = null): array
    {
        return self::make('color', $label, $description, self::STYLE);
    }

    /**
     * A fixed set of choices.
     *
     * @param  array<int, string>  $options
     * @return array<string, mixed>
     */
    public static function enum(string $label, array $options, string $default, ?string $description = null, string $group = self::STYLE): array
    {
        return self::make('enum', $label, $description, $group, false, $default) + ['options' => array_values($options)];
    }

    /**
     * On or off.
     *
     * @return array<string, mixed>
     */
    public static function bool(string $label, bool $default = false, ?string $description = null, string $group = self::STYLE): array
    {
        return self::make('bool', $label, $description, $group, false, $default);
    }

    /**
     * A bare number, with the range the block will hold it to.
     *
     * Deliberately not an enum even where the range is two or three values:
     * these props are CLAMPED rather than rejected. `columns` given a count of
     * seven renders three columns; it does not refuse. An enum would promise a
     * validation the block does not perform.
     *
     * @return array<string, mixed>
     */
    public static function number(string $label, int $min, int $max, ?string $description = null, string $group = self::STYLE): array
    {
        return self::make('number', $label, $description, $group) + ['min' => $min, 'max' => $max];
    }

    /**
     * A measurement the block writes straight into a style attribute, so it
     * carries its own CSS unit ("24px", "0").
     *
     * @return array<string, mixed>
     */
    public static function length(string $label, ?string $description = null, string $group = self::STYLE): array
    {
        return self::make('length', $label, $description, $group);
    }

    /**
     * Space above or below the block. Its own factory because 23 of the 25
     * blocks take one and they should not drift apart.
     *
     * @return array<string, mixed>
     */
    public static function spacing(string $label): array
    {
        return self::make('length', $label, 'A CSS length such as "24px". Defaults to the theme\'s own rhythm.', self::SPACING);
    }

    /**
     * A repeatable: line items, stat figures, list entries.
     *
     * `fields` describes ONE entry, which is what an editor renders per row.
     *
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public static function items(string $label, array $fields, ?string $description = null, bool $required = false): array
    {
        return self::make('array', $label, $description, self::CONTENT, $required) + ['fields' => $fields];
    }

    /**
     * @return array<string, mixed>
     */
    private static function make(
        string $type,
        string $label,
        ?string $description,
        string $group,
        bool $required = false,
        string|bool|null $default = null,
    ): array {
        $prop = [
            'type' => $type,
            'label' => $label,
            'group' => $group,
        ];

        if ($description !== null) {
            $prop['description'] = $description;
        }

        if ($required) {
            $prop['required'] = true;
        }

        // Written only when there is one. A null default and "defaults to
        // whatever the theme says" are different statements, and the second is
        // the true one for most of these.
        if ($default !== null) {
            $prop['default'] = $default;
        }

        return $prop;
    }
}
