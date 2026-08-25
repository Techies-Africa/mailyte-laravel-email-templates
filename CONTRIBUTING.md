# Contributing

Thanks for considering a contribution. The catalog only stays useful if new
templates hold the same line as the existing ones, so most of this document is
about what "done" means for a template.

## Getting set up

```bash
git clone https://github.com/Techies-Africa/mailyte-laravel-email-templates
cd laravel-email-templates
composer install
composer preview      # the gallery, at http://127.0.0.1:8000/mailyte
```

Before opening a pull request:

```bash
composer check        # style, static analysis, catalog lint, deliverability, tests
```

## Adding a template

A bundle is a directory under `resources/templates/core/<slug>/`:

| File            | Required | What it holds                                        |
| --------------- | -------- | ---------------------------------------------------- |
| `template.json` | yes      | The manifest, validated against the JSON schema      |
| `email.html`    | yes      | The markup, written in blocks                        |
| `design.json`   | yes      | This template's own design tokens                    |
| `sample.json`   | yes      | At least two samples: a default and an edge case     |
| `email.txt`     | no       | Overrides the text part derived from the HTML        |
| `styles.css`    | no       | Extra CSS, inlined at render time                    |

Run `php artisan mailyte:lint <slug> --strict` as you work. It checks the
manifest against `resources/schema/template.schema.json`, cross-checks the
declared variables against the ones the markup actually reads in both
directions, and applies the catalog's content rules. Every rule has an `MT###`
code, and a bundle that genuinely does not need one can waive it in the
manifest — with a written reason, which is the point:

```json
"lint": {
  "ignore": [
    { "rule": "MT011", "reason": "Legal wording; the subject is prescribed by the regulator." }
  ]
}
```

### What a template has to clear

- **Its own design.** The catalog's value is 50 different designs, not one
  design in 50 colourways. If yours could be mistaken for an existing bundle
  with the tokens swapped, it is not ready.
- **Responsive down to 320px.** No horizontal scroll, nothing clipped, no
  fixed width that survives a narrow viewport. Test at 320, 375, 480 and 600.
- **Light and dark.** Both schemes, and every element in both — including
  buttons that are outlined rather than filled, and logos that need a light
  and a dark file.
- **AA contrast** in both schemes, for body text and buttons alike.
- **A text part that reads.** It is derived from the HTML unless you ship an
  `email.txt`; check what it produces before assuming it is fine.
- **No emoji, and no images without alt text.**

### Previews

The gallery under [docs/gallery.md](docs/gallery.md) is generated, not
hand-written, and CI fails a pull request whose template has no previews. After
adding or redesigning a template:

```bash
php vendor/bin/testbench serve --port=8321 &
python3 .github/scripts/gallery.py <slug>      # renders every layout, light and dark
python3 .github/scripts/gallery-pages.py       # rewrites the index and the pages
python3 .github/scripts/check-gallery.py       # what CI runs
```

Needs Chrome on `PATH` as `google-chrome`, and Pillow. Omit the slug to
re-render all 50 -- about five minutes.

### Images

Bundles never hotlink third-party media in shipped defaults; sample data may,
and when it does the manifest must credit the asset in `credits` with the
author, the source, the licence and where it appears. See `CREDITS.md`.

## Contributing code

- The style is enforced by Pint (`composer format`) and analysis by PHPStan at
  level 6 (`composer analyse`). Both run in CI.
- Tests are Pest. New behaviour needs a test; a bug fix needs the test that
  would have caught it.
- Don't assert on generated CSS text. It is re-serialised during inlining, so
  spacing and declaration order are not stable — assert on the element and the
  declaration instead.

## Licence

Contributions are accepted under the MIT licence, and template bundles must be
MIT-licensed or derived from an MIT-compatible work, recorded in `origin`.
