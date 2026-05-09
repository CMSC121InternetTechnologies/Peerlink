<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an `image_path` column so we can store profile photos on disk
 * (storage/app/public/photos/{user_id}.{ext}) instead of base64 BLOBs.
 *
 * The legacy `image_data` column stays — old rows still render — but new
 * uploads will populate `image_path` only and image_data will be nulled.
 *
 * We use raw SQL for the longblob change because Laravel's Blueprint doesn't
 * expose a `longBlob()` helper, and `binary()->change()` would downgrade the
 * column type from longblob to blob.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('User_Photos', function (Blueprint $table): void {
            $table->string('image_path', 255)->nullable()->after('image_data');
        });
        DB::statement('ALTER TABLE `User_Photos` MODIFY `image_data` longblob NULL');
    }

    public function down(): void
    {
        Schema::table('User_Photos', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
        // Leaving image_data nullable on rollback — making it required again
        // would fail if any path-only rows exist.
    }
};
