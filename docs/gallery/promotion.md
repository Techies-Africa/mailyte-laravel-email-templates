# Promotional offer — free Laravel email template

A discount or limited offer: the claim, the code, the deadline and the small print, in that order.

A free, responsive, dark-mode marketing email template for Laravel and PHP, MIT licensed and ready to send. Part of [Mailyte Email Templates](../../README.md), the largest open-source catalog of designed transactional email for Laravel -- part of Mailyte, a product of [Techies Africa](https://techies.africa).

**`promotion`** · **marketing** · **marketing** · **urgent**

**Subject** `{{ offer_headline }} — ends {{ expires_text }}`  
**Preheader** Use the code at checkout. It applies to everything in the sale.

```bash
php artisan mailyte:list promotion
```

```php
Mailyte::template('promotion')->with([...])->send($user);
```

## Every layout, light and dark

Each shot is the real render at 600px, captured from the preview gallery.

| Layout | Light | Dark |
|---|---|---|
| **minimal** | <a href="../previews/promotion/minimal-light.webp"><img src="../previews/promotion/minimal-light.webp" alt="promotion, minimal layout, light mode" width="330"></a> | <a href="../previews/promotion/minimal-dark.webp"><img src="../previews/promotion/minimal-dark.webp" alt="promotion, minimal layout, dark mode" width="330"></a> |
| **branded** | <a href="../previews/promotion/branded-light.webp"><img src="../previews/promotion/branded-light.webp" alt="promotion, branded layout, light mode" width="330"></a> | <a href="../previews/promotion/branded-dark.webp"><img src="../previews/promotion/branded-dark.webp" alt="promotion, branded layout, dark mode" width="330"></a> |
| **editorial** | <a href="../previews/promotion/editorial-light.webp"><img src="../previews/promotion/editorial-light.webp" alt="promotion, editorial layout, light mode" width="330"></a> | <a href="../previews/promotion/editorial-dark.webp"><img src="../previews/promotion/editorial-dark.webp" alt="promotion, editorial layout, dark mode" width="330"></a> |

## Data it expects

| Variable | Type | Required | What it is |
|---|---|---|---|
| `action_url` | url | yes | Where the offer is redeemed. |
| `expires_text` | string | yes | The deadline in words. An offer email without a visible deadline is the kind that gets reported. |
| `offer_headline` | string | yes | The claim, set at 38px in the opening split. Short enough to survive a preview pane. |
| `button_label` | string |  | Primary button label. |
| `footer_note` | text |  | Why they are receiving this, and how to stop. |
| `help_heading` | string |  | Heading for the closing dark band. |
| `help_text` | text |  | Closing support line. |
| `help_url` | url |  | Where the closing band's link goes. |
| `hero_image` | url |  | Product shot for the opening split. A photograph of the thing being discounted, not a mood board. |
| `offer_eyebrow` | string |  | Small label above the claim, naming the occasion. |
| `offer_text` | text |  | What the offer applies to, stated so nobody has to guess. |
| `products` | array |  | Two or three items to anchor the offer, each with `image`, `title`, `meta`, `price`, `was_price` and `url`. |
| `products_heading` | string |  | Heading above the product row. |
| `promo_code` | string |  | Code to enter at checkout. Leave empty for an automatic discount. |
| `terms` | text |  | The small print. Short, and actually accurate. |

## Credits

- Pair of brown leather boots by Haupes via Unsplash — [source](https://unsplash.com/photos/pair-of-brown-leather-boots-jIaJM8sTs04) (Unsplash License, sample data only)
- Flat lay photography of cosmetic kit by Tusik Only via Unsplash — [source](https://unsplash.com/photos/flat-lay-photography-of-cosmetic-kit-ayBCtRueEtI) (Unsplash License, sample data only)
- A table topped with black bottles and candles by Kat Sylvester via Unsplash — [source](https://unsplash.com/photos/a-table-topped-with-black-bottles-and-candles-vXOUWzw1v6o) (Unsplash License, sample data only)
- White and red labeled box by Mahbod Akhzami via Unsplash — [source](https://unsplash.com/photos/white-and-red-labeled-box-2h0DmbxUOPw) (Unsplash License, sample data only)

## More marketing email templates

[Product tips and updates](product-tips.md) · [What changed while you were away](re-engagement.md)

## Author

[Confidence Ugolo](https://www.linkedin.com/in/confidence-ugolo) · [Joel Omojefe](https://www.linkedin.com/in/joel-omojefe)

Part of Mailyte, a product of [Techies Africa](https://techies.africa). MIT licensed.

---

[← All 50 templates](../gallery.md) · [Package README](../../README.md) · [Sending](../sending.md) · [Theming](../theming.md) · [Deliverability](../deliverability.md)
