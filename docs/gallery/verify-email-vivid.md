# Verify email address — Vivid — free Laravel email template

Sent immediately after sign-up to confirm the address is real and reachable. Bold banded treatment for consumer products.

A free, responsive, dark-mode account email template for Laravel and PHP, MIT licensed and ready to send. Part of [Mailyte Email Templates](../../README.md), the largest open-source catalog of designed transactional email for Laravel -- part of Mailyte, a product of [Techies Africa](https://techies.africa).

**`verify-email-vivid`** · **account** · **transactional** · **friendly**

**Subject** `{{ verification_code }} is your {{ product.name }} code`  
**Preheader** Good for {{ expires_in }}, and it only works once.

Same job, different design: [verify-email](verify-email.md) · [verify-email-code](verify-email-code.md) (code only) · [verify-email-link](verify-email-link.md) (link only) · [verify-email-typeset](verify-email-typeset.md) (typeset) · **verify-email-vivid**

```bash
php artisan mailyte:list verify-email-vivid
```

```php
Mailyte::template('verify-email-vivid')->with([...])->send($user);
```

## Every layout, light and dark

Each shot is the real render at 600px, captured from the preview gallery.

| Layout | Light | Dark |
|---|---|---|
| **minimal** | <a href="../previews/verify-email-vivid/minimal-light.webp"><img src="../previews/verify-email-vivid/minimal-light.webp" alt="verify-email-vivid, minimal layout, light mode" width="330"></a> | <a href="../previews/verify-email-vivid/minimal-dark.webp"><img src="../previews/verify-email-vivid/minimal-dark.webp" alt="verify-email-vivid, minimal layout, dark mode" width="330"></a> |
| **branded** | <a href="../previews/verify-email-vivid/branded-light.webp"><img src="../previews/verify-email-vivid/branded-light.webp" alt="verify-email-vivid, branded layout, light mode" width="330"></a> | <a href="../previews/verify-email-vivid/branded-dark.webp"><img src="../previews/verify-email-vivid/branded-dark.webp" alt="verify-email-vivid, branded layout, dark mode" width="330"></a> |

## Data it expects

| Variable | Type | Required | What it is |
|---|---|---|---|
| `verification_code` | string | yes | The code shown in the panel and in the subject line. |
| `action_url` | url |  | One-click verification link. Omit it to send a code-only email. |
| `body` | text |  | Opening paragraph. |
| `button_label` | string |  | Call to action, used only when action_url is set. |
| `expires_in` | string |  | How long the code stays valid. Stated explicitly because senders genuinely differ here, from five minutes to 24 hours. |
| `heading_text` | string |  | Main headline. Every visible string is a variable, so this is editable without touching markup. |
| `security_note` | text |  | Closing reassurance. Near-universal convention in shipped verification email. |
| `user.first_name` | string |  | Recipient's first name. Falls back to a generic greeting when absent. |

## More account email templates

[Account scheduled for deletion](account-deleted.md) · [Email address changed](email-changed.md) · [Password changed](password-changed.md) · [Reset your password](password-reset.md)

## Author

[Confidence Ugolo](https://www.linkedin.com/in/confidence-ugolo) · [Joel Omojefe](https://www.linkedin.com/in/joel-omojefe)

Part of Mailyte, a product of [Techies Africa](https://techies.africa). MIT licensed.

---

[← All 50 templates](../gallery.md) · [Package README](../../README.md) · [Sending](../sending.md) · [Theming](../theming.md) · [Deliverability](../deliverability.md)
