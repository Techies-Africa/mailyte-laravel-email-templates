<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Blocks\Block;
use Mailyte\EmailTemplates\Blocks\BlockRegistry;
use Mailyte\EmailTemplates\Blocks\Prop;
use Mailyte\EmailTemplates\Themes\Theme;

/**
 * `schema()` is hand-written, and a hand-written description of what a method
 * accepts is documentation -- documentation drifts, silently, and a block
 * picker built on a stale schema offers people fields that do nothing.
 *
 * So the schema is checked against the reads `normalize()` actually performs.
 * Those reads are found in the source: `$props` is type-hinted `array`, so a
 * recording ArrayAccess cannot be passed in its place without changing a public
 * signature every Block subclass outside this package implements.
 *
 * The idiom is ours and it is consistent -- `$this->string($props, 'key')` and
 * its siblings, plus the occasional direct `$props['key']`. A block that reads
 * props some other way will show up here as an undeclared read, which is the
 * failure we want rather than a false pass.
 */

/** @return array<int, string> */
function propsReadBy(Block $block): array
{
    $source = file_get_contents((new ReflectionClass($block))->getFileName());

    $keys = [];

    // $this->string($props, 'key'), ->enum, ->bool, ->url, ->list, ->slot
    preg_match_all(
        "/\\\$this->(?:string|enum|bool|url|list|slot)\(\s*\\\$props,\s*'([a-z_0-9]+)'/",
        $source,
        $helpers,
    );

    // A direct read: $props['key']
    preg_match_all("/\\\$props\[\s*'([a-z_0-9]+)'\s*\]/", $source, $direct);

    foreach ([$helpers[1], $direct[1]] as $found) {
        foreach ($found as $key) {
            $keys[$key] = true;
        }
    }

    // The slot is answered by hasSlot(), deliberately not by the schema.
    unset($keys['slot']);

    return array_keys($keys);
}

/** @return array<int, Block> */
function packagedBlocks(): array
{
    return array_values(app(BlockRegistry::class)->all());
}

it('declares a schema for every packaged block', function () {
    foreach (packagedBlocks() as $block) {
        expect($block->schema())
            ->not->toBeEmpty("{$block->name()} declares no schema");
    }
});

it('declares every prop its normalize() reads', function () {
    $missing = [];

    foreach (packagedBlocks() as $block) {
        $declared = array_keys($block->schema());

        foreach (propsReadBy($block) as $read) {
            if (! in_array($read, $declared, true)) {
                $missing[] = "{$block->name()}.{$read}";
            }
        }
    }

    expect($missing)->toBe([], 'props read by normalize() but absent from schema(): '.implode(', ', $missing));
});

/**
 * The opposite drift, and the one that produces a form full of inputs that do
 * nothing: a prop declared here that normalize() has stopped reading.
 */
it('declares nothing normalize() ignores', function () {
    $orphaned = [];

    foreach (packagedBlocks() as $block) {
        $read = propsReadBy($block);

        foreach (array_keys($block->schema()) as $declared) {
            if (! in_array($declared, $read, true)) {
                $orphaned[] = "{$block->name()}.{$declared}";
            }
        }
    }

    expect($orphaned)->toBe([], 'declared in schema() but never read: '.implode(', ', $orphaned));
});

it('gives every prop a type, a label and a group', function () {
    $groups = [Prop::CONTENT, Prop::STYLE, Prop::SPACING];
    $faults = [];

    foreach (packagedBlocks() as $block) {
        foreach ($block->schema() as $key => $prop) {
            $where = "{$block->name()}.{$key}";

            foreach (['type', 'label', 'group'] as $required) {
                if (! array_key_exists($required, $prop)) {
                    $faults[] = "{$where} has no {$required}";
                }
            }

            if (($prop['label'] ?? '') === '') {
                $faults[] = "{$where} has an empty label";
            }

            if (isset($prop['group']) && ! in_array($prop['group'], $groups, true)) {
                $faults[] = "{$where} is in unknown group '{$prop['group']}'";
            }
        }
    }

    expect($faults)->toBe([], implode('; ', $faults));
});

it('gives every choice prop its choices', function () {
    $faults = [];

    foreach (packagedBlocks() as $block) {
        foreach ($block->schema() as $key => $prop) {
            if ($prop['type'] !== 'enum') {
                continue;
            }

            $where = "{$block->name()}.{$key}";
            $options = $prop['options'] ?? [];

            if ($options === []) {
                $faults[] = "{$where} is a choice with no choices";

                continue;
            }

            // A default outside its own option list would be a form that opens
            // on a value the field cannot hold.
            if (! in_array($prop['default'] ?? null, $options, true)) {
                $faults[] = "{$where} defaults to something it does not offer";
            }
        }
    }

    expect($faults)->toBe([], implode('; ', $faults));
});

/**
 * An enum's options have to be the ones normalize() will actually accept --
 * `Block::enum()` silently falls back to its default for anything else, so a
 * schema offering a choice the block rejects produces a control that appears to
 * work and changes nothing.
 */
it('offers only choices normalize() accepts', function () {
    $wrong = [];

    foreach (packagedBlocks() as $block) {
        $reflection = new ReflectionClass($block);
        $source = file_get_contents($reflection->getFileName());

        foreach ($block->schema() as $key => $prop) {
            if ($prop['type'] !== 'enum') {
                continue;
            }

            // $this->enum($props, 'key', <options>, 'default') -- where the
            // options are an inline array in every block but status_banner,
            // which names a constant.
            $found = preg_match(
                "/\\\$this->enum\(\s*\\\$props,\s*'".preg_quote($key, '/')."',\s*(\[.*?\]|self::[A-Z_]+)/s",
                $source,
                $match,
            );

            if (! $found) {
                // Never a silent skip: an enum this test could not locate is a
                // block that reads props some way the guard does not know
                // about, which is the drift it exists to catch.
                $wrong[] = "{$block->name()}.{$key} declares choices this test could not find in normalize()";

                continue;
            }

            if (str_starts_with($match[1], 'self::')) {
                $constant = $reflection->getConstant(substr($match[1], 6));
                $allowed = [1 => is_array($constant) ? array_values($constant) : []];
            } else {
                preg_match_all("/'([^']*)'/", $match[1], $allowed);
            }

            $extra = array_diff($prop['options'], $allowed[1]);
            $absent = array_diff($allowed[1], $prop['options']);

            foreach ($extra as $option) {
                $wrong[] = "{$block->name()}.{$key} offers '{$option}', which normalize() rejects";
            }

            foreach ($absent as $option) {
                $wrong[] = "{$block->name()}.{$key} hides '{$option}', which normalize() accepts";
            }
        }
    }

    expect($wrong)->toBe([], implode('; ', $wrong));
});

/**
 * Every block has to hold words, or the picker it exists for would offer people
 * blocks with nothing to say. Spacers and dividers are the honest exceptions.
 */
it('gives most blocks something to write in', function () {
    $wordless = [];

    foreach (packagedBlocks() as $block) {
        $content = array_filter(
            $block->schema(),
            static fn (array $prop): bool => $prop['group'] === Prop::CONTENT,
        );

        if ($content === [] && ! $block->hasSlot()) {
            $wordless[] = $block->name();
        }
    }

    expect($wordless)->toBe(['divider', 'spacer']);
});

/**
 * The surface an editor builds against, rather than 25 separate lookups.
 */
it('hands over every block schema in one call', function () {
    $schemas = app(BlockRegistry::class)->schemas();

    expect($schemas)->toHaveCount(25);
    expect(array_keys($schemas))->toContain('heading', 'button', 'line_items');
    expect($schemas['heading']['text']['label'])->toBe('Heading');

    // A block registered from outside the package has no schema to give, and
    // must still appear: it exists and it renders.
    app(BlockRegistry::class)->register(new class extends Block
    {
        public function name(): string
        {
            return 'contributed';
        }

        public function normalize(array $props, Theme $theme): array
        {
            return [];
        }
    });

    expect(app(BlockRegistry::class)->schemas())->toHaveKey('contributed');
    expect(app(BlockRegistry::class)->schemas()['contributed'])->toBe([]);
});
