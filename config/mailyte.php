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
        'unsubscribe_url' => null,
        'preferences_url' => null,
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
    | `mailyte:lint` checks markup and content shape. It cannot verify SPF,
    | DKIM, DMARC, sender reputation or list hygiene -- those dominate inbox
    | placement. See docs/deliverability.md.
    |
    */

    'lint' => [
        'severity' => 'error',

        'clients' => [
            'gmail',
            'gmail-android',
            'gmail-ios',
            'outlook-windows',
            'outlook-com',
            'apple-mail',
            'ios-mail',
            'yahoo',
        ],

        'rules' => [
            'MT011' => ['max_image_ratio' => 0.6, 'min_words' => 40],
            'MT012' => ['error_bytes' => 102400, 'warn_bytes' => 81920],
            'MT017' => ['min_chars' => 10, 'max_chars' => 60],
            'MT033' => ['max_width' => 640],
            'MT035' => ['min_tap_target' => 44],
            'MT036' => ['min_font_size' => 14],
            'MT039' => ['min_contrast' => 4.5],
            'MT043' => ['max_links' => 25],
        ],

        // Rules to skip entirely, project-wide. Prefer a per-template
        // `lint.ignore` entry with a written reason over a blanket disable.
        'disabled' => [],
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
