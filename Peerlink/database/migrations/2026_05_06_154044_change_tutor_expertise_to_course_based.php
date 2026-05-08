<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (tests) starts with course_id already set in 000005 — nothing to do.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // MySQL: if the column is already course_id (e.g. DB bootstrapped from
        // database.sql which has course_id from the start), skip the ALTER.
        if (Schema::hasColumn('Tutor_Expertise', 'course_id')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('Tutor_Expertise')->truncate();

        DB::statement('ALTER TABLE Tutor_Expertise DROP FOREIGN KEY Tutor_Expertise_ibfk_2');
        DB::statement('ALTER TABLE Tutor_Expertise DROP INDEX topic_id');
        DB::statement('ALTER TABLE Tutor_Expertise CHANGE COLUMN topic_id course_id INT NOT NULL');
        DB::statement('ALTER TABLE Tutor_Expertise ADD CONSTRAINT Tutor_Expertise_course_fk FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // This block was written as a data-fix for upgrading an existing database.
        // On a fresh migrate:fresh the Tutor_Profiles table is empty at this point
        // because the seeder has not run yet, so the FK constraint fails.
        // Guard: only re-seed if the seed users already exist in Tutor_Profiles
        // (i.e. the DB was bootstrapped from database.sql, not from a fresh migration).
        // The DatabaseSeeder inserts the same rows for a clean install.
        $seedUserExists = DB::table('Tutor_Profiles')
            ->where('user_id', 'aaaa0001-0001-0001-0001-000000000001')
            ->exists();

        if ($seedUserExists) {
            DB::table('Tutor_Expertise')->insert([
                ['user_id' => 'aaaa0001-0001-0001-0001-000000000001', 'course_id' => 6],
                ['user_id' => 'aaaa0001-0001-0001-0001-000000000001', 'course_id' => 7],
                ['user_id' => 'aaaa0002-0002-0002-0002-000000000002', 'course_id' => 22],
                ['user_id' => 'aaaa0002-0002-0002-0002-000000000002', 'course_id' => 33],
                ['user_id' => 'aaaa0003-0003-0003-0003-000000000003', 'course_id' => 4],
                ['user_id' => 'aaaa0003-0003-0003-0003-000000000003', 'course_id' => 5],
                ['user_id' => 'aaaa0007-0007-0007-0007-000000000007', 'course_id' => 12],
                ['user_id' => 'aaaa0008-0008-0008-0008-000000000008', 'course_id' => 3],
            ]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('Tutor_Expertise')->truncate();
        DB::statement('ALTER TABLE Tutor_Expertise DROP FOREIGN KEY Tutor_Expertise_course_fk');
        DB::statement('ALTER TABLE Tutor_Expertise CHANGE COLUMN course_id topic_id INT NOT NULL');
        DB::statement('ALTER TABLE Tutor_Expertise ADD CONSTRAINT Tutor_Expertise_ibfk_2 FOREIGN KEY (topic_id) REFERENCES Course_Topics(topic_id) ON DELETE CASCADE');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
