<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the personal_access_tokens table.
 *
 * Why: PeerLink uses session-based authentication exclusively. The Sanctum
 * token table was created by the package's default migration but it's
 * unused — the comment in routes/api.php documents that token auth via
 * Sanctum is incompatible with the UUID user_id column (tokenable_id is a
 * bigint by default).
 *
 * Leaving an unused, unmaintained tokens table around is a footgun: a
 * future code change that accidentally enables Sanctum middleware on a
 * route would silently authenticate against an empty table, and any
 * brute-forced or replayed token would have full access. Removing the
 * table closes that gap.
 *
 * The original create migration (2026_05_03_113306) stays so existing
 * environments that ran it can still roll forward; this migration just
 * drops the resulting table on top.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }

    public function down(): void
    {
        // Recreating it would put us back in the vulnerable state described
        // above, so down() is intentionally a no-op. Re-running the original
        // create migration would still work if you really need this table.
    }
};
