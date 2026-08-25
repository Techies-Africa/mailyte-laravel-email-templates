<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default theme
    |--------------------------------------------------------------------------
    |
    | The theme used when a send does not name one. Ships with "mailyte" (the
    | Mailyte brand palette) and "neutral" (brand-free). Publish your own with
    | `php artisan vendor:publish --tag=mailyte-mail-themes` and edit the JSON.
    |
    */

    'theme' => env('MAILYTE_THEME', 'neutral'),

    /*
    |--------------------------------------------------------------------------
    | Default layout preset
    |--------------------------------------------------------------------------
    |
    | Style is a render-time preset, not a separate copy of each template:
    |
    |   plain     near-plaintext HTML, no logo or hero. Highest deliverability.
    |   minimal   text-forward with a small wordmark header.
    |   branded   logo header, optional hero, full palette. The SaaS default.
    |   editorial multi-section with imagery and a social footer. Newsletters.
    |
    | A template declares which presets it supports in its manifest.
    |
    */

    'layout' => env('MAILYTE_LAYOUT', 'branded'),

    /*
    |--------------------------------------------------------------------------
    | Template sources
    |--------------------------------------------------------------------------
    |
    | Resolution order, first hit wins. Mirrors Laravel's view path precedence:
    | anything you publish into your app overrides what the package ships.
    |
    */

    'sources' => [
        'published' => resource_path('views/vendor/mailyte/templates'),
        'paths' => [
            // resource_path('emails'),
        ],
        'database' => [
            // Off by default. Enabling this means tenant-authored template
            // source is compiled to PHP on disk by Twig -- read the security
            // note in docs/security.md before turning it on.
            'enabled' => env('MAILYTE_DB_TEMPLATES', false),
            'connection' => env('MAILYTE_DB_CONNECTION'),
            'table' => 'mailyte_templates',
        ],
    ],

    /*
    | Include community-contributed templates in the catalog. Core templates
    | are reviewed by maintainers; community ones are reviewed by CI plus one
    | triage approval. Off by default so you opt in deliberately.
    */

    'include_community' => env('MAILYTE_INCLUDE_COMMUNITY', false),

    /*
    |--------------------------------------------------------------------------
    | Global template variables
    |--------------------------------------------------------------------------
    |
    | Merged into every render beneath the data you pass at send time. Every
    | visible string in a template is a variable with a default, so you can
    | rebrand and reword without touching any HTML.
    |
    */

    'globals' => [
        'product' => [
            'name' => env('APP_NAME', 'Laravel'),
            'url' => env('APP_URL', 'http://localhost'),
        ],
        'company' => [
            'name' => env('APP_NAME', 'Laravel'),
            'address' => env('MAILYTE_COMPANY_ADDRESS'),
        ],
        'support_url' => env('MAILYTE_SUPPORT_URL'),
        // Usually per-recipient and set at send time via ->with(), but a static
        // preferences page is common enough to be worth an env default.
        'unsubscribe_url' => env('MAILYTE_UNSUBSCRIBE_URL'),
        'preferences_url' => env('MAILYTE_PREFERENCES_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */

    'render' => [
        // Rewrites relative hrefs/srcs against this base. Mail clients have no
        // base URL, so a relative link is simply broken (lint rule MT014).
        'base_url' => env('APP_URL', 'http://localhost'),

        // Compiled-template cache, same model as Blade's.
        'cache' => [
            'enabled' => env('MAILYTE_CACHE', true),
            'path' => storage_path('framework/mailyte'),
        ],

        // Sandbox resource caps. The Twig sandbox blocks PHP execution but not
        // resource exhaustion, so these are the backstop against a hostile or
        // simply careless template.
        'limits' => [
            'loop_iterations' => 5000,
            'output_bytes' => 2 * 1024 * 1024,
            'nesting_depth' => 32,
        ],

        'text' => [
            'wrap_at' => 78,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Linting
    |--------------------------------------------------------------------------
    |
    | Two commands, two questions.
    |
    | `mailyte:lint` reads the bundle: does the manifest match the schema, does
    | the markup only use tokens it declares, does the content hold the line.
    | No rendering, so it is fast enough to run on every save.
    |
    | `mailyte:deliverability` renders each message and asks what a spam filter
    | would make of it: is it inside Gmail's clip threshold, is there enough
    | text to classify, do the links look like what they say.
    |
    | Neither can verify SPF, DKIM, DMARC, your sending domain's reputation or
    | your list hygiene -- and those dominate inbox placement. What each covers
    | and what it cannot is set out in docs/deliverability.md.
    |
    */

    'lint' => [
        // Every rule the two checkers apply, and the thresholds they use. A
        // template that genuinely does not need one waives it in its own
        // manifest under `lint.ignore`, with a written reason -- see
        // CONTRIBUTING.md. Prefer that over a blanket disable here.
        'rules' => [
            // mailyte:lint -- content, checked without rendering
            'MT006' => ['min_outer_padding' => 32],
            'MT011' => ['max_subject_chars' => 65],
            'MT015' => ['max_light_luminance' => 0.30],
            'MT016' => ['min_dark_luminance' => 0.35],

            // mailyte:deliverability -- checked against the rendered message
            'MT050' => ['error_bytes' => 102400, 'warn_bytes' => 81920],
            'MT051' => ['min_words' => 40],
            'MT053' => ['max_links' => 25],
            'MT058' => ['min_chars_per_image' => 90],
        ],

        // Rules to skip entirely, project-wide.
        'disabled' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel's own notification mail
    |--------------------------------------------------------------------------
    |
    | Laravel renders every mail notification -- including its own password
    | reset and email verification -- by building a MailMessage and pushing it
    | through the `notifications::email` markdown view. Switch this on and that
    | last step goes through Mailyte instead: every notification in the
    | application comes out designed, responsive and dark-mode aware, and not
    | one notification class changes.
    |
    | Off by default. Turning it on changes how every email the application
    | sends looks, which is not a decision a package makes for you.
    |
    | What is left alone: a MailMessage carrying an explicit `view`, or a
    | markdown view other than the framework default. Both mean somebody
    | already chose a template.
    |
    */

    'notifications' => [
        'enabled' => env('MAILYTE_ADOPT_NOTIFICATIONS', false),

        // The bundle that receives MailMessage content. The shipped shell takes
        // a greeting, lines, an action, a salutation and the URL fallback --
        // i.e. exactly what a MailMessage carries. Publish it with
        // `--tag=mailyte-notification-shell` to edit your own copy.
        'template' => 'laravel-notification',

        // Null follows the template's own preference and the global default.
        'layout' => env('MAILYTE_NOTIFICATIONS_LAYOUT'),
        'theme' => env('MAILYTE_NOTIFICATIONS_THEME'),

        // To render one specific notification as one of the catalog's designed
        // templates -- Laravel's password reset as `password-reset`, say --
        // use the framework's own seam, which lets you pass the data that
        // template actually needs. See docs/laravel-integration.md.
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | A gated UI for browsing the catalog, previewing a template across themes,
    | layouts, viewports and colour schemes, editing content, and sending test
    | messages.
    |
    | Access is decided by Mailyte\EmailTemplates\Dashboard::check(): a callback
    | registered via Dashboard::auth(), otherwise the `viewMailyte` gate,
    | otherwise local environments only. It renders real message content and can
    | send mail, so it stays shut in production until you say who may open it.
    |
    */

    'dashboard' => [
        'enabled' => env('MAILYTE_DASHBOARD', true),
        'path' => env('MAILYTE_DASHBOARD_PATH', 'mailyte'),
        'domain' => env('MAILYTE_DASHBOARD_DOMAIN'),
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    |
    | The things that are the same in every message you send: your mark, your
    | social accounts, your postal address, and which footer sections appear.
    |
    | These are facts about your application, not design decisions, so they live
    | here rather than in a theme. They are applied over whatever the theme and
    | the template's own design say, and anything you pass to ->theme() at send
    | time still wins over them -- which is how per-tenant branding works.
    |
    | Leave a value null to let the theme decide.
    |
    */

    'brand' => [

        // Must be absolute https URLs a mail client can reach. Publish the
        // bundled placeholder set with `vendor:publish --tag=mailyte-assets`.
        'logo' => [
            'url' => env('MAILYTE_LOGO_URL'),

            // Shown instead of the above in clients that honour dark mode. A
            // dark-ink mark on an inverted canvas is invisible, so ship both.
            'dark_url' => env('MAILYTE_LOGO_DARK_URL'),

            'alt' => env('MAILYTE_LOGO_ALT'),
            'width' => env('MAILYTE_LOGO_WIDTH'),

            // left, center or right.
            'align' => env('MAILYTE_LOGO_ALIGN'),
        ],

        // Accounts shown in the footer. `name` selects the bundled icon, so use
        // the platform's own name; `label` is what a screen reader announces.
        //
        //   ['name' => 'X', 'url' => 'https://x.com/acme'],
        //   ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/company/acme'],
        //
        // Supply `icon_url` on an entry to use your own artwork instead.
        'social' => [
            //
        ],

        'social_icons' => [
            // Where the published icon set lives. Relative paths are resolved
            // against `render.base_url`. Leave null and the footer falls back
            // to lettered cells, which need no hosting at all.
            'base_url' => env('MAILYTE_SOCIAL_ICON_BASE'),

            // round, pill or text.
            'style' => env('MAILYTE_SOCIAL_STYLE'),

            // dark or light. Left null it is derived from the surface the
            // footer sits on, which is right far more often than not.
            'ink' => env('MAILYTE_SOCIAL_ICON_INK'),

            'size' => env('MAILYTE_SOCIAL_ICON_SIZE'),
        ],

        'footer' => [
            // Required on marketing mail in most jurisdictions.
            'address' => env('MAILYTE_COMPANY_ADDRESS'),

            // Registration or tax line, where one is required.
            'legal' => env('MAILYTE_FOOTER_LEGAL'),

            // Why this person is receiving the message. Reduces spam
            // complaints more reliably than any subject-line trick.
            'reason' => env('MAILYTE_FOOTER_REASON'),

            // Defaults to "© <year> <company name>".
            'copyright' => env('MAILYTE_FOOTER_COPYRIGHT'),

            'unsubscribe_text' => env('MAILYTE_UNSUBSCRIBE_TEXT'),
            'preferences_text' => env('MAILYTE_PREFERENCES_TEXT'),

            // null lets the layout decide: `branded` and `editorial` carry the
            // full set, `minimal` drops the social row, `plain` drops brand
            // chrome entirely. true or false overrides that everywhere.
            'show_social' => null,
            'show_address' => null,
            'show_copyright' => null,
            'show_reason' => null,

            // null shows the unsubscribe row whenever a URL exists.
            'show_unsubscribe' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template usage
    |--------------------------------------------------------------------------
    |
    | Counts how many times each template was actually sent, so you can see
    | which ones your application really uses -- and so template authors can
    | learn whether their work is helping anyone.
    |
    | Local counting stores a slug, a version, a tally and a timestamp. Nothing
    | else: no recipients, no subjects, no message bodies. It never leaves this
    | machine.
    |
    | Sharing those counts publicly is a separate, strictly opt-in decision --
    | see the `share` block. It is off by default and will stay that way: a
    | package that phones home unasked has not earned the right to be installed.
    |
    */

    'usage' => [
        'enabled' => env('MAILYTE_USAGE', true),

        // 'cache' needs no migration but is only as durable as your cache
        // store. 'database' survives a cache flush; run the migration first.
        // 'null' turns counting off entirely.
        'driver' => env('MAILYTE_USAGE_DRIVER', 'cache'),

        'table' => 'mailyte_template_usage',

        'share' => [
            // Opt in deliberately. Nothing is transmitted while this is false,
            // and there is no background job waiting to change that.
            'enabled' => env('MAILYTE_USAGE_SHARE', false),

            'endpoint' => env('MAILYTE_USAGE_ENDPOINT', 'https://registry.mailyte.com/api/usage'),

            // Exactly what may be transmitted. The reporter builds its payload
            // from this allowlist, so anything absent here cannot be sent even
            // by accident. Run `mailyte:usage --share --dry-run` to print the
            // precise payload before enabling anything.
            'fields' => [
                'template_slug',
                'template_version',
                'count',
                'package_version',
                'php_minor',
                'laravel_minor',
                'period',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional render-testing services
    |--------------------------------------------------------------------------
    |
    | Opt-in only, never required and never part of CI. Without credentials
    | `mailyte:render-test` prints setup instructions and exits cleanly.
    |
    */

    'render_tests' => [
        'default' => env('MAILYTE_RENDER_TEST_SERVICE'),
        'services' => [
            'litmus' => [
                'key' => env('LITMUS_API_KEY'),
            ],
            'email_on_acid' => [
                'key' => env('EMAIL_ON_ACID_API_KEY'),
                'password' => env('EMAIL_ON_ACID_PASSWORD'),
            ],
            'testi' => [
                'key' => env('TESTI_API_KEY'),
            ],
        ],
    ],

];
