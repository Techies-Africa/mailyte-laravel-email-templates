# Changing how your email looks

There are four places colour can come from, and they resolve in this order — each one overriding the one above it:

```
1. the theme            the house style for your whole application
2. the template's own   design.json inside the bundle, so a receipt and a
   design               newsletter do not have to look the same
3. your brand overrides ->theme([...]) at send time, per tenant if you like
4. the recipient's      dark mode, which every layer above has to survive
   mail client
```

## The quickest change: one colour, at send time

```php
Mailyte::template('welcome')
    ->with(['action_url' => $url])
    ->theme(['color.primary' => '#0F766E'])
    ->send($user);
```

Any token can be overridden this way. This is also how you do per-tenant branding — pass the organisation's colour and logo on each send:

```php
->theme([
    'color.primary' => $org->brand_colour,
    'logo.url'      => $org->logo_url,      // must be https and absolute
])
```

Overrides are sanitised: colours must be valid CSS colours, and asset URLs must be absolute `https`. A relative logo path is refused rather than silently shipped, because a mail client has no base URL to resolve it against.

## The things that are the same in every message

Your mark, your social accounts, your postal address and which footer sections
appear are facts about your application, not design decisions. They live in one
place — `config/mailyte.php` under `brand` — and are applied over the theme and
over each template's own design:

```php
'brand' => [
    'logo' => [
        'url'      => env('MAILYTE_LOGO_URL'),
        'dark_url' => env('MAILYTE_LOGO_DARK_URL'),   // shown in dark mode
        'alt'      => env('MAILYTE_LOGO_ALT'),
        'width'    => env('MAILYTE_LOGO_WIDTH'),
        'align'    => env('MAILYTE_LOGO_ALIGN'),      // left | center | right
    ],

    'social' => [
        ['name' => 'X', 'url' => 'https://x.com/acme'],
        ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/company/acme'],
    ],

    'social_icons' => [
        'base_url' => env('MAILYTE_SOCIAL_ICON_BASE'),  // where the icon set is published
        'style'    => env('MAILYTE_SOCIAL_STYLE'),      // round | pill | text
        'ink'      => env('MAILYTE_SOCIAL_ICON_INK'),   // null derives it from the surface
        'size'     => env('MAILYTE_SOCIAL_ICON_SIZE'),
    ],

    'footer' => [
        'address'   => env('MAILYTE_COMPANY_ADDRESS'),
        'legal'     => env('MAILYTE_FOOTER_LEGAL'),
        'reason'    => env('MAILYTE_FOOTER_REASON'),
        'copyright' => env('MAILYTE_FOOTER_COPYRIGHT'),   // defaults to "© <year> <company>"

        'show_social'      => null,   // null lets the layout decide
        'show_address'     => null,
        'show_copyright'   => null,
        'show_reason'      => null,
        'show_unsubscribe' => null,   // null shows it whenever a URL exists
    ],
],
```

The unsubscribe and preferences URLs sit in `globals` rather than here, because
they are usually per-recipient — pass them at send time, or set
`MAILYTE_UNSUBSCRIBE_URL` / `MAILYTE_PREFERENCES_URL` for a static page.

A `null` means "leave it to the theme", which is different from `false`. And a
per-send `->theme([...])` still beats everything here, which is what makes
per-tenant branding work:

```php
Mailyte::template('welcome')
    ->theme(['logo.url' => $org->logo_url])   // beats the brand config
    ->send($user);
```

## Application-wide: publish a theme

```bash
php artisan vendor:publish --tag=mailyte-mail-themes
```

That writes the theme JSON into `resources/mailyte/themes/`, where you can edit it. Point the package at yours:

```dotenv
MAILYTE_THEME=acme
```

A theme is a token tree. The ones you will actually reach for:

| Token | What it does |
|---|---|
| `color.primary` / `color.primary_text` | Buttons, links and accents, and the text that sits on them |
| `color.bg` / `color.surface` / `color.surface_alt` | The page behind the email, the card, and tinted bands |
| `color.text` / `color.text_muted` | Body copy and secondary copy |
| `color.border` | Every rule and divider |
| `font.heading` / `font.body` / `font.mono` | Type. Stick to stacks that exist on the recipient's machine |
| `type.h1.size` … `type.footer.size` | The type scale, with an optional `mobile_size` per step |
| `radius.sm` / `md` / `lg` / `pill` | Corner rounding, `0` for a squared-off design |
| `spacing.xs` … `xl` | Vertical rhythm |
| `layout.width` / `layout.gutter` | Canvas width and the measure inside it |
| `logo.url` / `logo.dark_url` / `logo.align` | The mark in the header, and whether it sits left, centre or right |
| `social` | The accounts in the footer, as `{name, url}` |
| `footer.show_social` / `show_address` / `show_copyright` | Switch footer sections off |

Every colour token accepts either a single value or a light/dark pair:

```json
"color": {
    "primary": { "light": "#0F766E", "dark": "#5EEAD4" }
}
```

Give a single string and the dark variant is kept rather than dropped, so setting a brand colour never costs you dark-mode handling by accident.

## Per template: `design.json`

A template bundle can carry its own design, which is why the catalog does not look like a hundred copies of one email:

```
resources/templates/core/invoice/
├── template.json    what it is and what data it takes
├── design.json      what it looks like  <-- here
├── email.html       the composition
└── sample.json      preview fixtures
```

`design.json` uses the same token shape as a theme. It sits *under* your brand overrides, so a template's design is a starting point rather than a hijacking of your colours.

## Social icons

The bundled icon set has to be served from a URL a mail client can reach:

```bash
php artisan vendor:publish --tag=mailyte-assets
```

Then point the footer at wherever they landed:

```json
"footer": {
    "social_icon_base": "https://yourapp.com/vendor/mailyte/social",
    "social_icon_ink": "dark",
    "social_style": "round"
}
```

Leave `social_icon_base` unset and the footer falls back to lettered cells, which need no hosting at all. Set `icon_url` on an individual social entry to use your own artwork instead.

## Seeing it before you send it

The dashboard renders any template across every theme, layout, width and colour scheme:

```
/mailyte
```

Switch **Theme** to compare your palette against the catalog's, and **Scheme** to check dark mode. The width buttons (320 / 375 / 600 / 1024) are the ones worth spending time in — 320px is where a design either holds together or does not.
