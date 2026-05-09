<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts every PeerLink domain table from latin1_swedish_ci → utf8mb4_unicode_ci.
 *
 * Why? The original SQL dump created tables with latin1, so names/bios/messages
 * containing Filipino tildes, accented Spanish characters, or emoji are silently
 * truncated or mangled on insert. The Laravel `connection.charset` is utf8mb4,
 * but MySQL's per-table charset wins for storage purposes — so we have to
 * ALTER each table.
 *
 * `CONVERT TO CHARACTER SET utf8mb4` rewrites both the table default AND every
 * column's charset, so we only need one statement per table.
 *
 * Foreign keys are dropped/re-checked by MySQL automatically during the
 * conversion; we wrap in SET FOREIGN_KEY_CHECKS=0 anyway as belt-and-braces.
 *
 * Reversal isn't safe (any utf8mb4 character outside latin1's 256-glyph range
 * would be lost on the way back), so down() is a no-op with a clear note.
 */
return new class extends Migration {
    /** Tables that were created with `CHARSET=latin1`. Order is irrelevant
     *  because we toggle FOREIGN_KEY_CHECKS off during the migration. */
    private array $tables = [
        'Divisions',
        'Courses',
        'Course_Topics',
        'Tutor_Profiles',
        'Tutor_Expertise',
        'Rooms',
        'Requests',
        'Sessions',
        'Session_Reviews',
        'Session_Topics',
        'Session_Participants',
        'Tutee_Courses',
        'User_Photos',
        'Users',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            // sqlite has no table-level charset concept; nothing to do.
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Intentionally a no-op. Converting utf8mb4 → latin1 is lossy: any
        // character outside the 256-glyph latin1 range (every emoji, every
        // CJK character, the Philippine peso ₱) would be replaced with `?`.
        // If you genuinely need to roll this back, do it manually after
        // exporting the affected rows.
    }
};
