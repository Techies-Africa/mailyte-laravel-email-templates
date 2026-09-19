# Blocks

A block is a PHP class plus a Blade view that the package renders. A template
calls one by name and passes props; the template's own text never reaches the
Blade compiler. That boundary is what makes a contributed bundle safe to run.

```twig
{{ heading({ text: issue_title, level: '1', align: 'center' }) }}
```

There are 25 of them. `Mailyte::blocks()->names()` lists them.

## Asking a block what it accepts

Every packaged block declares its inputs:

```php
use Mailyte\EmailTemplates\Facades\Mailyte;

Mailyte::blocks()->get('heading')->schema();
```

```php
[
    'text'  => ['type' => 'text', 'label' => 'Heading', 'group' => 'content', 'required' => true],
    'level' => [
        'type' => 'enum',
        'label' => 'Level',
        'group' => 'style',
        'description' => 'How big it is set, and what it means in the document outline.',
        'default' => '1',
        'options' => ['1', '2', '3'],
    ],
    // align, color, space_above, space_below
]
```

`Mailyte::blocks()->schemas()` returns all 25 at once, keyed by block name. That
is the call an editor builds against: it answers "which blocks exist, and what
may each be given" in one go.

### What a prop says

| Key | |
| --- | --- |
| `type` | `text`, `richtext`, `url`, `image`, `color`, `enum`, `bool`, `number`, `length` or `array` |
| `label` | For a person, not a slug |
| `group` | `content`, `style` or `spacing` — see below |
| `description` | Present when there is something worth saying |
| `required` | Present and `true` only when the block cannot render without it |
| `default` | Present only when the block has one of its own. Most defaults come from the theme, and "defaults to whatever the theme says" is a different statement from a fixed value |
| `options` | `enum` only. Exactly the values `normalize()` will accept |
| `min`, `max` | `number` only |
| `fields` | `array` only. Describes one entry |

### The group is the useful part

Of the 204 props across the 25 blocks, **only 69 hold words**. Ninety-four are
appearance and 41 are spacing.

An editor that renders all of a block's inputs together hands somebody 17
controls for a split panel, most of them colours. Rendering the `content` props,
with `style` and `spacing` behind a disclosure, is a form a person can use. That
split is the reason the group exists; it is not decoration.

### What the schema is not

It is not `normalize()`'s return type. That array mixes accepted inputs with
values computed for the view — `button` takes `variant` and returns
`background`, `radius`, `shadow` and `bare`, none of which a template may pass,
while `border_color` is accepted and comes back under another name. The two sets
overlap and neither contains the other.

So the schema is written by hand, and `SchemaContractTest` keeps it honest. It
reads the props `normalize()` really reads and fails both ways: a prop read but
not declared, and a prop declared but no longer read — the second being the one
that produces a form full of controls that do nothing.

### Clamped, not validated

`columns.count` and `split.image_percent` are `number` props with a `min` and a
`max` rather than choices, because the blocks **clamp** them. A `columns` block
given a count of seven renders three columns; it does not refuse. Declaring
those as enums would promise a validation that does not happen.

### Slots

A block that wraps content — `card`, `section`, `text` — answers `hasSlot()`.
The slot is deliberately absent from the schema: saying it in two places is one
more thing to keep in step.

## Writing your own

Extend `Block`, implement `name()` and `normalize()`, and register it:

```php
Mailyte::blocks()->register(new CountdownBlock);
```

`schema()` is optional and defaults to empty, so an existing block keeps
working. A block without one still renders — it just cannot describe itself, so
an editor cannot offer it in a picker. Declaring it is a few lines with the
`Prop` factories:

```php
use Mailyte\EmailTemplates\Blocks\Prop;

public function schema(): array
{
    return [
        'ends_at' => Prop::text('Ends at', 'Written out — the block does no date formatting.', required: true),
        'label'   => Prop::text('Label'),
        'align'   => Prop::enum('Alignment', ['left', 'center'], 'center'),
        'space_below' => Prop::spacing('Space below'),
    ];
}
```

Blocks are overridable the way Laravel's mail components are: publish
`resources/views/vendor/mailyte/html/<name>.blade.php` and edit it.
