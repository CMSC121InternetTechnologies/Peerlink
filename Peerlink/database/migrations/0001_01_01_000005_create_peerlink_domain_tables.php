<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    private function upMySQL(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if (!Schema::hasTable('Divisions')) {
            DB::statement("
                CREATE TABLE `Divisions` (
                    `division_id`   varchar(10) NOT NULL,
                    `division_name` text        NOT NULL,
                    PRIMARY KEY (`division_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }

        if (!Schema::hasTable('Courses')) {
            DB::statement("
                CREATE TABLE `Courses` (
                    `course_id`   int(11)      NOT NULL AUTO_INCREMENT,
                    `division_id` varchar(10)  DEFAULT NULL,
                    `course_code` varchar(20)  NOT NULL,
                    `course_name` text         NOT NULL,
                    PRIMARY KEY (`course_id`),
                    KEY `division_id` (`division_id`),
                    CONSTRAINT `Courses_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `Divisions` (`division_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }

        if (!Schema::hasTable('Course_Topics')) {
            DB::statement("
                CREATE TABLE `Course_Topics` (
                    `topic_id`   int(11) NOT NULL AUTO_INCREMENT,
                    `course_id`  int(11) NOT NULL,
                    `topic_name` text    NOT NULL,
                    PRIMARY KEY (`topic_id`),
                    KEY `course_id` (`course_id`),
                    CONSTRAINT `Course_Topics_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `Courses` (`course_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }

        if (!Schema::hasTable('Tutor_Profiles')) {
            DB::statement("
                CREATE TABLE `Tutor_Profiles` (
                    `user_id`    char(36)       NOT NULL,
                    `bio`        text           DEFAULT NULL,
                    `rating_avg` decimal(3,2)   DEFAULT 0.00,
                    PRIMARY KEY (`user_id`),
                    CONSTRAINT `Tutor_Profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }

        if (!Schema::hasTable('Tutor_Expertise')) {
            DB::statement("
                CREATE TABLE `Tutor_Expertise` (
                    `user_id`  char(36) NOT NULL,
                    `topic_id` int(11)  NOT NULL,
                    PRIMARY KEY (`user_id`, `topic_id`),
                    KEY `topic_id` (`topic_id`),
                    CONSTRAINT `Tutor_Expertise_ibfk_1` FOREIGN KEY (`user_id`)  REFERENCES `Tutor_Profiles` (`user_id`) ON DELETE CASCADE,
                    CONSTRAINT `Tutor_Expertise_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `Course_Topics` (`topic_id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }

        if (!Schema::hasTable('Rooms')) {
            DB::statement("
                CREATE TABLE `Rooms` (
                    `room_id`   int(11)                   NOT NULL AUTO_INCREMENT,
                    `room_code` varchar(20)               NOT NULL,
                    `room_name` text                      NOT NULL,
                    `room_type` enum('Physical','Virtual') NOT NULL,
                    `capacity`  int(11)                   NOT NULL,
                    PRIMARY KEY (`room_id`),
                    UNIQUE KEY `room_code` (`room_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }

        if (!Schema::hasTable('Requests')) {
            DB::statement("
                CREATE TABLE `Requests` (
                    `request_id` char(36)                                          NOT NULL DEFAULT (uuid()),
                    `student_id` char(36)                                          NOT NULL,
                    `tutor_id`   char(36)                                          DEFAULT NULL,
                    `course_id`  int(11)                                           NOT NULL,
                    `message`    text                                               DEFAULT NULL,
                    `status`     enum('Pending','Approved','Declined','Expired')   DEFAULT 'Pending',
                    `created_at` timestamp                                          NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`request_id`),
                    KEY `student_id` (`student_id`),
                    KEY `tutor_id`   (`tutor_id`),
                    KEY `course_id`  (`course_id`),
                    CONSTRAINT `Requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `Users`   (`user_id`),
                    CONSTRAINT `Requests_ibfk_2` FOREIGN KEY (`tutor_id`)   REFERENCES `Users`   (`user_id`),
                    CONSTRAINT `Requests_ibfk_3` FOREIGN KEY (`course_id`)  REFERENCES `Courses` (`course_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }

        if (!Schema::hasTable('Sessions')) {
            DB::statement("
                CREATE TABLE `Sessions` (
                    `session_id`     char(36)                                      NOT NULL DEFAULT (uuid()),
                    `request_id`     char(36)                                      NOT NULL,
                    `modality`       enum('In-Person','Online')                    NOT NULL,
                    `room_id`        int(11)                                       NOT NULL,
                    `meeting_link`   text                                           DEFAULT NULL,
                    `scheduled_time` datetime                                       NOT NULL,
                    `status`         enum('Scheduled','Completed','Cancelled')     DEFAULT 'Scheduled',
                    `created_at`     timestamp                                      NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`session_id`),
                    UNIQUE KEY `request_id` (`request_id`),
                    KEY `room_id` (`room_id`),
                    CONSTRAINT `Sessions_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `Requests` (`request_id`) ON DELETE CASCADE,
                    CONSTRAINT `Sessions_ibfk_2` FOREIGN KEY (`room_id`)    REFERENCES `Rooms`    (`room_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }

        if (!Schema::hasTable('Session_Reviews')) {
            DB::statement("
                CREATE TABLE `Session_Reviews` (
                    `review_id`   char(36) NOT NULL DEFAULT (uuid()),
                    `session_id`  char(36) NOT NULL,
                    `reviewer_id` char(36) NOT NULL,
                    `reviewee_id` char(36) NOT NULL,
                    `rating`      int(11)  DEFAULT NULL,
                    `feedback`    text     DEFAULT NULL,
                    `created_at`  timestamp NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`review_id`),
                    KEY `session_id`  (`session_id`),
                    KEY `reviewer_id` (`reviewer_id`),
                    KEY `reviewee_id` (`reviewee_id`),
                    CONSTRAINT `Session_Reviews_ibfk_1` FOREIGN KEY (`session_id`)  REFERENCES `Sessions` (`session_id`),
                    CONSTRAINT `Session_Reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `Users`    (`user_id`),
                    CONSTRAINT `Session_Reviews_ibfk_3` FOREIGN KEY (`reviewee_id`) REFERENCES `Users`    (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function upSQLite(): void
    {
        // SQLite branch for PHPUnit tests (no FK charset concerns)
        Schema::create('Divisions', function (Blueprint $table) {
            $table->string('division_id', 10)->primary();
            $table->text('division_name');
        });

        Schema::create('Courses', function (Blueprint $table) {
            $table->integerIncrements('course_id');
            $table->string('division_id', 10)->nullable();
            $table->string('course_code', 20);
            $table->text('course_name');
        });

        Schema::create('Course_Topics', function (Blueprint $table) {
            $table->integerIncrements('topic_id');
            $table->unsignedInteger('course_id');
            $table->text('topic_name');
        });

        Schema::create('Tutor_Profiles', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->text('bio')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
        });

        Schema::create('Tutor_Expertise', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->unsignedInteger('topic_id');
            $table->primary(['user_id', 'topic_id']);
        });

        Schema::create('Rooms', function (Blueprint $table) {
            $table->integerIncrements('room_id');
            $table->string('room_code', 20)->unique();
            $table->text('room_name');
            $table->string('room_type', 20);
            $table->integer('capacity');
        });

        Schema::create('Requests', function (Blueprint $table) {
            $table->uuid('request_id')->primary();
            $table->uuid('student_id');
            $table->uuid('tutor_id')->nullable();
            $table->unsignedInteger('course_id');
            $table->text('message')->nullable();
            $table->string('status', 20)->default('Pending');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('Sessions', function (Blueprint $table) {
            $table->uuid('session_id')->primary();
            $table->uuid('request_id')->unique();
            $table->string('modality', 20);
            $table->unsignedInteger('room_id');
            $table->text('meeting_link')->nullable();
            $table->dateTime('scheduled_time');
            $table->string('status', 20)->default('Scheduled');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('Session_Reviews', function (Blueprint $table) {
            $table->uuid('review_id')->primary();
            $table->uuid('session_id');
            $table->uuid('reviewer_id');
            $table->uuid('reviewee_id');
            $table->integer('rating')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['Session_Reviews', 'Sessions', 'Requests', 'Rooms',
                  'Tutor_Expertise', 'Tutor_Profiles', 'Course_Topics', 'Courses', 'Divisions'] as $table) {
            Schema::dropIfExists($table);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
