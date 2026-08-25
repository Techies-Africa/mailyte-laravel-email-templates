# Security policy

Mailyte is a product of [Techies Africa](https://techies.africa).

## Supported versions

| Version | Supported |
| ------- | --------- |
| 1.x     | Yes       |

## Reporting a vulnerability

Please do not open a public issue. Report it privately through
[GitHub's private vulnerability reporting](https://github.com/Techies-Africa/mailyte-laravel-email-templates/security/advisories/new),
or by email to **security@mailyte.com**.

Include the version, a description of the issue, and the smallest template or
snippet that reproduces it. You should get an acknowledgement within three
working days and an assessment within a week.

## What this package treats as a vulnerability

Templates are data, and this package renders them. That makes the sandbox the
security boundary, so anything that crosses it is in scope:

- Escaping a template out of the Twig sandbox — reaching the filesystem, the
  container, arbitrary PHP callables, `include`, `extends`, or any function
  outside the allowlist in `MailyteSecurityPolicy`.
- Rendering unescaped output from template data. Autoescape is always on and
  the `raw` filter is not available to templates; a way around either is a
  vulnerability.
- HTML injection through a token that reaches the rendered message without
  passing the sanitizer, including through design tokens and brand config.
- Header injection through a subject, preheader, or suggested header.
- Anything that makes a published or community bundle able to read or affect
  the application beyond the data it was given.

## What is not a vulnerability

- Markup that renders badly in a particular mail client. That is a bug — open
  an issue.
- A template rendering data the application passed it. Whatever you put in
  `->with()` is trusted input by definition; sanitize it before it gets here.
- The preview gallery being reachable in an environment where it was left
  enabled. It is gated by the `Dashboard::auth` callback and restricted to local
  environments by default; deploying it open is a configuration decision.
