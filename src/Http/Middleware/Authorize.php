<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mailyte\EmailTemplates\Dashboard;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Dashboard::check($request), 403, 'Not authorized to view the Mailyte dashboard.');

        return $next($request);
    }
}
