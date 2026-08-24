<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates;

use Illuminate\Support\Facades\Gate;

/**
 * Authorization for the dashboard, following Horizon's pattern.
 *
 * The dashboard exposes real message content and can send test mail, so it is
 * gated rather than merely hidden. Outside `local` it is closed unless the
 * application says otherwise -- either through the `viewMailyte` gate or a
 * callback registered here.
 */
final class Dashboard
{
    /** @var (callable(mixed):bool)|null */
    public static $authUsing = null;

    /**
     * Register the callback that decides who may open the dashboard.
     *
     * Typically called from a published MailyteServiceProvider:
     *
     *     Dashboard::auth(fn ($request) => $request->user()?->isAdmin() === true);
     *
     * @param  callable(mixed):bool  $callback
     */
    public static function auth(callable $callback): void
    {
        self::$authUsing = $callback;
    }

    public static function check(mixed $request): bool
    {
        if (self::$authUsing !== null) {
            return (bool) call_user_func(self::$authUsing, $request);
        }

        if (Gate::has('viewMailyte')) {
            return Gate::check('viewMailyte', [$request?->user()]);
        }

        // No gate and no callback: allowed only where a developer is clearly
        // working locally. Anything else stays shut, because failing open on a
        // dashboard that can send mail is not a reasonable default.
        return app()->environment('local');
    }
}
