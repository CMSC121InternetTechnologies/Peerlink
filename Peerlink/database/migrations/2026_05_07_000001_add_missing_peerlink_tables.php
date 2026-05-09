<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds every table that the application references but that was absent from
 * the original migration set, plus patches the Requests table with the
 * counter-proposal columns that were added later via seed.sql.
 *
 * All MySQL blocks are guarded with hasTable() / hasColumn() so this
 * migration is safe to run against a database that was bootstrapped from
 * database.sql (which already has some of these tables/columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->upMySQL();
        } else {
            $this->upSQLite();
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MySQL
    // ──────────────────────────────────────────────────────────────────────────
    private function upMySQL(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ── 1. Requests: counter-proposal columns + CounterProposed status ───
        if (!Schema::hasColumn('Requests', 'counter_proposed_time')) {
            DB::statement("ALTER TABLE `Requests` ADD COLUMN `counter_proposed_time` datetime DEFAULT NULL");
        }
        if (!Schema::hasColumn('Requests', 'counter_proposed_message')) {
            DB::statement("ALTER TABLE `Requests` ADD COLUMN `counter_proposed_message` text DEFAULT NULL");
        }
        if (!Schema::hasColumn('Requests', 'counter_proposed_modality')) {
            DB::statement("ALTER TABLE `Requests` ADD COLUMN `counter_proposed_modality` enum('In-Person','Online') DEFAULT NULL");
        }
        if (!Schema::hasColumn('Requests', 'counter_proposed_room_id')) {
            DB::statement("ALTER TABLE `Requests` ADD COLUMN `counter_proposed_room_id` int(11) DEFAULT NULL");
            DB::statement("ALTER TABLE `Requests` ADD KEY `counter_proposed_room_id` (`counter_proposed_room_id`)");
        }
        // Extend enum to include CounterProposed (safe to run even if already present)
        DB::statement("ALTER TABLE `Requests` MODIFY COLUMN `status` enum('Pending','Approved','Declined','Expired','CounterProposed') DEFAULT 'Pending'");

        // ── 2. Tutee_Courses ──────────────────────────────────────────────────
        if (!Schema::hasTable('Tutee_Courses')) {
            DB::statement("
                CREATE TABLE `Tutee_Courses` (
                    `user_id`   char(36) NOT NULL,
                    `course_id` int(11)  NOT NULL,
                    PRIMARY KEY (`user_id`, `course_id`),
                    KEY `Tutee_Courses_ibfk_2` (`course_id`),
                    CONSTRAINT `Tutee_Courses_ibfk_1` FOREIGN KEY (`user_id`)   REFERENCES `Users`   (`user_id`) ON DELETE CASCADE,
                    CONSTRAINT `Tutee_Courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `Courses` (`course_id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ── 3. Session_Participants ───────────────────────────────────────────
        if (!Schema::hasTable('Session_Participants')) {
            DB::statement("
                CREATE TABLE `Session_Participants` (
                    `participation_id` char(36)                  NOT NULL DEFAULT (uuid()),
                    `session_id`       char(36)                  NOT NULL,
                    `user_id`          char(36)                  NOT NULL,
                    `role`             enum('Tutor','Tutee')     NOT NULL,
                    `has_attended`     tinyint(1)                DEFAULT NULL,
                    `joined_at`        timestamp                 NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`participation_id`),
                    KEY `session_id` (`session_id`),
                    KEY `user_id`    (`user_id`),
                    CONSTRAINT `SP_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `Sessions` (`session_id`) ON DELETE CASCADE,
                    CONSTRAINT `SP_ibfk_2` FOREIGN KEY (`user_id`)    REFERENCES `Users`    (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ── 4. Session_Topics ─────────────────────────────────────────────────
        if (!Schema::hasTable('Session_Topics')) {
            DB::statement("
                CREATE TABLE `Session_Topics` (
                    `session_id` char(36) NOT NULL,
                    `topic_id`   int(11)  NOT NULL,
                    PRIMARY KEY (`session_id`, `topic_id`),
                    KEY `topic_id` (`topic_id`),
                    CONSTRAINT `ST_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `Sessions`      (`session_id`) ON DELETE CASCADE,
                    CONSTRAINT `ST_ibfk_2` FOREIGN KEY (`topic_id`)   REFERENCES `Course_Topics` (`topic_id`)   ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ── 5. User_Photos ────────────────────────────────────────────────────
        if (!Schema::hasTable('User_Photos')) {
            DB::statement("
                CREATE TABLE `User_Photos` (
                    `user_id`     char(36)      NOT NULL,
                    `image_data`  longblob      NOT NULL,
                    `mime_type`   varchar(50)   NOT NULL DEFAULT 'image/jpeg',
                    `uploaded_at` timestamp     NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`user_id`),
                    CONSTRAINT `UP_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } elseif (!Schema::hasColumn('User_Photos', 'mime_type')) {
            // Bug: mime_type column was absent; MIME was hardcoded as image/jpeg on
            // read-back. Add the column for existing tables so real MIME can be stored.
            DB::statement("ALTER TABLE `User_Photos` ADD COLUMN `mime_type` varchar(50) NOT NULL DEFAULT 'image/jpeg' AFTER `image_data`");
        }

        // ── 6. Notifications ──────────────────────────────────────────────────
        if (!Schema::hasTable('Notifications')) {
            DB::statement("
                CREATE TABLE `Notifications` (
                    `notification_id` char(36)    NOT NULL DEFAULT (uuid()),
                    `user_id`         char(36)    NOT NULL,
                    `type`            varchar(50) NOT NULL,
                    `message`         text        NOT NULL,
                    `request_id`      char(36)    DEFAULT NULL,
                    `is_read`         tinyint(1)  NOT NULL DEFAULT 0,
                    `created_at`      timestamp   NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`notification_id`),
                    KEY `idx_notif_user`    (`user_id`),
                    KEY `idx_notif_request` (`request_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SQLite  (PHPUnit / feature tests — always starts with a fresh database)
    // ──────────────────────────────────────────────────────────────────────────
    private function upSQLite(): void
    {
        // Requests: add counter-proposal columns
        Schema::table('Requests', function (Blueprint $table) {
            $table->dateTime('counter_proposed_time')->nullable()->after('status');
            $table->text('counter_proposed_message')->nullable()->after('counter_proposed_time');
            $table->string('counter_proposed_modality', 20)->nullable()->after('counter_proposed_message');
            $table->unsignedInteger('counter_proposed_room_id')->nullable()->after('counter_proposed_modality');
        });

        // Tutee_Courses
        Schema::create('Tutee_Courses', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->unsignedInteger('course_id');
            $table->primary(['user_id', 'course_id']);
        });

        // Session_Participants
        Schema::create('Session_Participants', function (Blueprint $table) {
            $table->uuid('participation_id')->primary();
            $table->uuid('session_id');
            $table->uuid('user_id');
            $table->string('role', 10);
            $table->boolean('has_attended')->nullable();
            $table->timestamp('joined_at')->nullable()->useCurrent();
        });

        // Session_Topics
        Schema::create('Session_Topics', function (Blueprint $table) {
            $table->uuid('session_id');
            $table->unsignedInteger('topic_id');
            $table->primary(['session_id', 'topic_id']);
        });

        // User_Photos
        Schema::create('User_Photos', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->binary('image_data');
            // Added mime_type so the correct MIME is served instead of hardcoded image/jpeg.
            $table->string('mime_type', 50)->default('image/jpeg');
            $table->timestamp('uploaded_at')->nullable()->useCurrent();
        });

        // Notifications
        Schema::create('Notifications', function (Blueprint $table) {
            $table->uuid('notification_id')->primary();
            $table->uuid('user_id');
            $table->string('type', 50);
            $table->text('message');
            $table->uuid('request_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Rollback
    // ──────────────────────────────────────────────────────────────────────────
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['Notifications', 'User_Photos', 'Session_Topics', 'Session_Participants', 'Tutee_Courses'] as $table) {
            Schema::dropIfExists($table);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Remove counter-proposal columns from Requests (MySQL only; SQLite doesn't support DROP COLUMN reliably)
        if (DB::getDriverName() === 'mysql') {
            foreach (['counter_proposed_time', 'counter_proposed_message', 'counter_proposed_modality', 'counter_proposed_room_id'] as $col) {
                if (Schema::hasColumn('Requests', $col)) {
                    DB::statement("ALTER TABLE `Requests` DROP COLUMN `{$col}`");
                }
            }
            // Bug: shrinking the enum without first clearing CounterProposed rows causes
            // MySQL to silently convert them to an empty string (data corruption).
            // Fix: demote CounterProposed rows to Pending before removing the value,
            // mirroring the pattern used in migration 000002 for the Cancelled status.
            DB::table('Requests')->where('status', 'CounterProposed')->update(['status' => 'Pending']);
            DB::statement("ALTER TABLE `Requests` MODIFY COLUMN `status` enum('Pending','Approved','Declined','Expired') DEFAULT 'Pending'");
        }
    }
};
