<?php

declare(strict_types=1);

/*
 * PeerLink uses session-based auth and the SPA is served from the same origin
 * as the API (both on http://127.0.0.1:8000). Cross-origin requests should
 * therefore NOT be needed at all in this app.
 *
 * We expose the bare minimum: only `/api/*` accepts CORS, and only from origins
 * we explicitly list in the FRONTEND_URL env var. Everything else gets the
 * browser's same-origin policy as the wall.
 *
 * Why bother if the SPA is same-origin? Because Laravel's HandleCors middleware
 * runs by default and, without a config file, falls back to permissive defaults.
 * Pinning the policy here closes that gap.
 */
return [

    // Only API routes need CORS — the dashboard, login, etc. all share the SPA's origin.
    'paths' => ['api/*'],

    // No specific HTTP methods restriction; let CORS speak for the actual route.
    'allowed_methods' => ['*'],

    // Whitelist of permitted browser origins. By default it's just the SPA itself
    // — set FRONTEND_URL in .env if you serve the SPA from a different host.
    'allowed_origins' => array_filter([
        env('APP_URL', 'http://127.0.0.1:8000'),
        env('FRONTEND_URL'),
    ]),

    // No regex patterns — exact matches only.
    'allowed_origins_patterns' => [],

    // Allow standard auth + JSON headers; the X-CSRF-TOKEN header is required
    // for our session-based PATCH/POST flows.
    'allowed_headers' => ['Content-Type', 'X-CSRF-TOKEN', 'X-XSRF-TOKEN', 'X-Requested-With', 'Accept', 'Origin', 'Authorization'],

    // No headers exposed to JS by default.
    'exposed_headers' => [],

    // No credentials caching — re-authenticate per request via session cookie.
    'max_age' => 0,

    // CRITICAL: required for our session-based auth so the browser includes the
    // session cookie on cross-origin requests. Pairs with allowed_origins above.
    'supports_credentials' => true,
];
