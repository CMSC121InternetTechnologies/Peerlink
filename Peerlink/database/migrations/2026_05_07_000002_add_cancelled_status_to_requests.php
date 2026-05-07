<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite stores status as a plain string — no enum constraint to update.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `Requests` MODIFY COLUMN `status` enum('Pending','Approved','Declined','Expired','CounterProposed','Cancelled') DEFAULT 'Pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('Requests')->where('status', 'Cancelled')->update(['status' => 'Declined']);
        DB::statement("ALTER TABLE `Requests` MODIFY COLUMN `status` enum('Pending','Approved','Declined','Expired','CounterProposed') DEFAULT 'Pending'");
    }
};
