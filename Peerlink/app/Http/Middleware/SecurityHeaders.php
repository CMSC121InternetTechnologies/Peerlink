<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds defense-in-depth security headers to every response.
 *
 * What each one does:
 *   - Content-Security-Policy: stops most XSS by whitelisting where scripts/
 *     styles/images can come from. Inline scripts are allowed because the
 *     dashboard.blade.php intentionally embeds window.__authUser etc. as
 *     inline JSON; inline event handlers (onclick=…) are still permitted
 *     for the same reason. Tightening these requires moving to nonces.
 *   - X-Content-Type-Options: prevents browsers from MIME-sniffing a
 *     response away from its Content-Type — blocks "magic" interpretation
 *     of an image as a script, etc.
 *   - X-Frame-Options: blocks the page from being framed → clickjacking
 *     defence.
 *   - Referrer-Policy: don't leak full URLs to third parties.
 *   - Permissions-Policy: deny browser features we don't use (camera,
 *     microphone, geolocation, …) so a future XSS can't enable them.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ──────────────────────────────────────────────────────────────────
        // CSP — different policy in local dev vs production. In local,
        // Vite's dev server serves CSS/JS from http://localhost:5173 and
        // opens a websocket for HMR; the strict prod policy blocks both.
        // We don't set 'unsafe-inline' for scripts in production because
        // window.__authUser etc. are written inline in dashboard.blade.php
        // and most onclick handlers are inline — a future refactor can
        // replace those with nonced scripts.
        // ──────────────────────────────────────────────────────────────────
        $isLocal = app()->environment('local');

        if ($isLocal) {
            // Permissive policy: allow same-origin + the Vite dev server.
            // We pin Vite to 127.0.0.1 in vite.config.js (default would be
            // `[::1]`, which Chrome rejects as an invalid CSP source when
            // paired with a port wildcard). So only IPv4 + hostname here.
            $devOrigins = 'http://localhost:* http://127.0.0.1:* '
                        . 'ws://localhost:* ws://127.0.0.1:*';
            $csp = [
                "default-src 'self' {$devOrigins}",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$devOrigins}",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com {$devOrigins}",
                "font-src 'self' https://fonts.gstatic.com data: {$devOrigins}",
                "img-src 'self' data: blob: {$devOrigins}",
                "connect-src 'self' {$devOrigins}",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "object-src 'none'",
            ];
        } else {
            $csp = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com data:",
                "img-src 'self' data: blob:",
                "connect-src 'self'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "object-src 'none'",
            ];
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        return $response;
    }
}
