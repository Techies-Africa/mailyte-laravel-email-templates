# Verify email address

Sent immediately after sign-up. Verification emails see the highest open rates
of anything you will ever send, so this one is deliberately plain: one code, one
optional link, no marketing.

## Two shapes, on purpose

Shipped verification emails split into two camps and this bundle supports both:

- **Code-first** (AWS, Slack, Plaid): a code in a card, no button at all. Leave
  `action_url` empty and the CTA disappears.
- **Link-first with a code fallback** (Linear): both, with the raw URL repeated
  under the button for clients that mangle it.

## Notes

- The code renders as live text, never an image — screen readers cannot read an
  image, and several clients block images by default.
- `expires_in` is a variable rather than a hardcoded 24 hours because real
  senders range from 5 minutes to a day.
- Supports the `plain` layout, which is worth using here: security-critical mail
  benefits more from deliverability than from presentation.
