<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Brings the Programs table in line with the canonical 12-program list
 * across 4 divisions. Idempotent — uses upsert so re-running this is safe.
 *
 * The list (per the school directory):
 *   DNSM (Natural Sciences and Mathematics):
 *     BSCS, BSBio, BSAMath, MSEnvSci
 *   DM   (Management):
 *     BSAcc, BSMgt, MM
 *   DSS  (Social Sciences):
 *     BAPolSci, BAPsych, BSEcon
 *   DH   (Humanities):
 *     BALit, BAMedia
 *
 * Two programs were missing from the live DB (BSBio, BAPolSci); the rest
 * already exist but we upsert so program_name corrections also land.
 */
return new class extends Migration {
    public function up(): void
    {
        // Make sure all four divisions exist before we hang programs off them.
        DB::table('Divisions')->upsert([
            ['division_id' => 'DNSM', 'division_name' => 'Division of Natural Sciences and Mathematics'],
            ['division_id' => 'DM',   'division_name' => 'Division of Management'],
            ['division_id' => 'DSS',  'division_name' => 'Division of Social Sciences'],
            ['division_id' => 'DH',   'division_name' => 'Division of Humanities'],
        ], ['division_id'], ['division_name']);

        DB::table('Programs')->upsert([
            // DNSM
            ['program_code' => 'BSCS',     'division_id' => 'DNSM', 'program_name' => 'BS Computer Science'],
            ['program_code' => 'BSBio',    'division_id' => 'DNSM', 'program_name' => 'BS Biology'],
            ['program_code' => 'BSAMath',  'division_id' => 'DNSM', 'program_name' => 'BS Applied Mathematics'],
            ['program_code' => 'MSEnvSci', 'division_id' => 'DNSM', 'program_name' => 'MS Environmental Science'],
            // DM
            ['program_code' => 'BSAcc',    'division_id' => 'DM',   'program_name' => 'BS Accountancy'],
            ['program_code' => 'BSMgt',    'division_id' => 'DM',   'program_name' => 'BS Management'],
            ['program_code' => 'MM',       'division_id' => 'DM',   'program_name' => 'Master of Management'],
            // DSS
            ['program_code' => 'BAPolSci', 'division_id' => 'DSS',  'program_name' => 'BA Political Science'],
            ['program_code' => 'BAPsych',  'division_id' => 'DSS',  'program_name' => 'BA Psychology'],
            ['program_code' => 'BSEcon',   'division_id' => 'DSS',  'program_name' => 'BS Economics'],
            // DH
            ['program_code' => 'BALit',    'division_id' => 'DH',   'program_name' => 'BA Literature'],
            ['program_code' => 'BAMedia',  'division_id' => 'DH',   'program_name' => 'BA Media Arts'],
        ], ['program_code'], ['division_id', 'program_name']);
    }

    public function down(): void
    {
        // Don't auto-delete programs in down() — users could be referencing
        // them via Users.program_code FK. If you really need to roll back,
        // do it via a manual script after fixing the dependent rows.
    }
};
