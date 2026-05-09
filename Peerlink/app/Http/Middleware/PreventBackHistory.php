<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents the browser from showing a cached copy of a page after the user
 * navigates away. Solves two specific UX/security bugs:
 *
 *   1. POST-LOGOUT BACK BUTTON. Without this, a user who logs out and
 *      presses Back is shown the dashboard rendered before logout — even
 *      though their session is gone. The next click that hits the server
 *      bounces them to /login, but the cached HTML had already leaked
 *      everything that was on the screen.
 *
 *   2. STALE BACK-FORWARD CACHE. A user who lingers on a page then opens
 *      it from history won't see fresh data — Chrome/Safari's BFCache
 *      hands them a snapshot that may pre-date a save.
 *
 * Apply via the 'no-cache' route alias (registered in bootstrap/app.php),
 * not globally — marketing pages and static assets benefit from caching
 * and shouldn't get these headers.
 *
 * Browsers each interpret these slightly differently, so we set all four
 * for belt-and-braces coverage:
 *   - Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private
 *       no-store        → disallow ALL caching (memory, disk, BFCache)
 *       no-cache        → must revalidate before reuse
 *       must-revalidate → once expired, MUST hit the server
 *       max-age=0       → already stale on arrival
 *       private         → never cache in shared/proxy caches
 *   - Pragma: no-cache  → HTTP/1.0 fallback (some old proxies)
 *   - Expires: 0        → force heuristic fresh-until calculations
 *                          into the past
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0, private'
        );
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
