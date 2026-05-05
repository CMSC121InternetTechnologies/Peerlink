<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Users')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            // Use latin1 to match the charset of other tables from the SQL dump
            // that have FK constraints referencing Users(user_id).
            DB::statement("
                CREATE TABLE `Users` (
                    `user_id`            char(36)     NOT NULL DEFAULT (uuid()),
                    `email`              varchar(255) NOT NULL,
                    `password_hash`      varchar(255) NOT NULL,
                    `first_name`         varchar(100) NOT NULL,
                    `middle_name`        varchar(100) DEFAULT NULL,
                    `last_name`          varchar(100) NOT NULL,
                    `contact_number`     varchar(15)  DEFAULT NULL,
                    `current_year_level` int(11)      NOT NULL,
                    `program_code`       varchar(15)  NOT NULL,
                    `created_at`         timestamp    NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`user_id`),
                    UNIQUE KEY `email` (`email`),
                    KEY `fk_users_program` (`program_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        } else {
            Schema::create('Users', function (Blueprint $table) {
                $table->uuid('user_id')->primary();
                $table->string('email', 255)->unique();
                $table->string('password_hash', 255);
                $table->string('first_name', 100);
                $table->string('middle_name', 100)->nullable();
                $table->string('last_name', 100);
                $table->string('contact_number', 15)->nullable();
                $table->integer('current_year_level');
                $table->string('program_code', 15);
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('Users');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
