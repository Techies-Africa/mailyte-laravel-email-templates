# Changelog

All notable changes to `mailyte/laravel-email-templates` are documented here.
Mailyte is a product of [Techies Africa](https://techies.africa).

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Template bundles carry their own `version` in `template.json`, independent of
the package version. A redesign of one template is a minor package release; a
change to the block API that existing bundles were written against is a major
one, which is what the `engine` constraint in every manifest is there to catch.

## [Unreleased]

### Added

- Laravel 13 support.

## [1.0.0] - 2026-08-25

First public release. Created by [Confidence Ugolo](https://www.linkedin.com/in/confidence-ugolo),
founder of Mailyte, with [Joel Omojefe](https://www.linkedin.com/in/joel-omojefe).

### Added

- 50 hand-built template bundles across 10 categories, each with its own design
  tokens rather than one house style wearing different colours.
- 25 blocks — hero, split, line items, product grid, stat row, offer, quote,
  status banner, key/value and the rest — composed through a sandboxed Twig
  environment with no filesystem access, no `include`/`extends`, and autoescape
  that cannot be turned off.
- Four layouts (`plain`, `minimal`, `branded`, `editorial`) and a token
  precedence chain: theme → template design → application brand config →
  per-send overrides.
- Dark mode across the whole catalog, including the Outlook.com attribute
  hooks, with light/dark token pairs and social icons that invert with the
  scheme.
- `Mailyte::template('slug')` as a drop-in alongside `view:` and `markdown:`,
  plus a `MailyteMailable` base class and a `UsesMailyteTemplate` trait for
  existing mailables.
- `config('mailyte.brand')` for the values that are the same in every message:
  light and dark logo, social links, footer address, legal line, unsubscribe
  and preferences URLs, and which footer sections appear at all.
- A notification channel that renders every Laravel `MailMessage` through
  Mailyte — the framework's own password reset and email verification included —
  behind a single config flag, with no notification class changes.
- `mailyte:adopt <slug>`, which takes one template's design and applies it to
  every email Laravel already sends -- notifications and markdown mailables --
  in a single command.
- Commands: `mailyte:list`, `mailyte:adopt`, `mailyte:lint`, `mailyte:publish-template`,
  `mailyte:send-test`, `mailyte:usage`.
- A preview gallery at `/mailyte`, with live thumbnails, layout, theme, scheme
  and width switching, and the plain-text part beside the HTML.
- `mailyte:publish-template` copies a bundle into the application so it can be
  edited and kept, resolved ahead of the packaged catalog the same way
  published views take precedence over package views.
- Local usage counting, off-by-default sharing, and a `--dry-run` that prints
  the exact payload before anything leaves the machine.

[Unreleased]: https://github.com/Techies-Africa/mailyte-laravel-email-templates/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Techies-Africa/mailyte-laravel-email-templates/releases/tag/v1.0.0
