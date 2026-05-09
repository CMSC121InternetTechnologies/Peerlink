<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('Users')->where('user_id', 'like', 'aaaa%')->exists()) {
            $this->command->info('Seed data already exists — skipping.');
            return;
        }

        $isMySQL = DB::getDriverName() === 'mysql';
        if ($isMySQL) DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ── Base lookup tables (Divisions, Programs, Courses, Rooms) ──────────
        // These were previously loaded from a separate database.sql file that is
        // not version-controlled. They are now seeded here so that migrate:fresh
        // --seed produces a complete, self-contained database from scratch.
        DB::table('Divisions')->insertOrIgnore([
            ['division_id' => 'DH',   'division_name' => 'Division of Humanities'],
            ['division_id' => 'DM',   'division_name' => 'Division of Management'],
            ['division_id' => 'DNSM', 'division_name' => 'Division of Natural Sciences and Mathematics'],
            ['division_id' => 'DSS',  'division_name' => 'Division of Social Sciences'],
        ]);

        DB::table('Programs')->insertOrIgnore([
            // Division of Humanities (DH)
            ['program_code' => 'BALit',    'division_id' => 'DH',   'program_name' => 'BA Literature'],
            ['program_code' => 'BAMedia',  'division_id' => 'DH',   'program_name' => 'BA Media Arts'],
            // Division of Management (DM)
            ['program_code' => 'BSAcc',    'division_id' => 'DM',   'program_name' => 'BS Accountancy'],
            ['program_code' => 'BSMgt',    'division_id' => 'DM',   'program_name' => 'BS Management'],
            ['program_code' => 'MM',       'division_id' => 'DM',   'program_name' => 'Master of Management'],
            // Division of Natural Sciences and Mathematics (DNSM)
            ['program_code' => 'BSAMath',  'division_id' => 'DNSM', 'program_name' => 'BS Applied Mathematics'],
            ['program_code' => 'BSBio',    'division_id' => 'DNSM', 'program_name' => 'BS Biology'],
            ['program_code' => 'BSCS',     'division_id' => 'DNSM', 'program_name' => 'BS Computer Science'],
            ['program_code' => 'MSEnvSci', 'division_id' => 'DNSM', 'program_name' => 'MS Environmental Science'],
            // Division of Social Sciences (DSS)
            ['program_code' => 'BAPolSci', 'division_id' => 'DSS',  'program_name' => 'BA Political Science'],
            ['program_code' => 'BAPsych',  'division_id' => 'DSS',  'program_name' => 'BA Psychology'],
            ['program_code' => 'BSEcon',   'division_id' => 'DSS',  'program_name' => 'BS Economics'],
        ]);

        // Courses: specific course_ids are hard-coded in Tutor_Expertise, Requests,
        // and Sessions seed rows, so each ID must match exactly.
        DB::table('Courses')->insertOrIgnore([
            ['course_id' => 1,  'division_id' => 'DNSM', 'course_code' => 'CMSC1',   'course_name' => 'Introduction to Computing Systems'],
            ['course_id' => 2,  'division_id' => 'DNSM', 'course_code' => 'CMSC2',   'course_name' => 'Digital Circuits and Systems'],
            ['course_id' => 3,  'division_id' => 'DNSM', 'course_code' => 'CMSC10',  'course_name' => 'Introduction to Computing'],
            ['course_id' => 4,  'division_id' => 'DNSM', 'course_code' => 'CMSC11',  'course_name' => 'Introduction to Computer Science I'],
            ['course_id' => 5,  'division_id' => 'DNSM', 'course_code' => 'CMSC12',  'course_name' => 'Introduction to Computer Science II'],
            ['course_id' => 6,  'division_id' => 'DNSM', 'course_code' => 'CMSC121', 'course_name' => 'Fundamentals of Web Development'],
            ['course_id' => 7,  'division_id' => 'DNSM', 'course_code' => 'CMSC122', 'course_name' => 'Data Structures and Algorithms'],
            ['course_id' => 8,  'division_id' => 'DNSM', 'course_code' => 'CMSC130', 'course_name' => 'Automata Theory and Formal Languages'],
            ['course_id' => 9,  'division_id' => 'DNSM', 'course_code' => 'CMSC131', 'course_name' => 'Computer Organization and Assembly Language'],
            ['course_id' => 10, 'division_id' => 'DNSM', 'course_code' => 'CMSC132', 'course_name' => 'Operating Systems'],
            ['course_id' => 11, 'division_id' => 'DNSM', 'course_code' => 'CMSC135', 'course_name' => 'Analysis of Algorithms'],
            ['course_id' => 12, 'division_id' => 'DNSM', 'course_code' => 'CMSC13',  'course_name' => 'Discrete Mathematical Structures I'],
            ['course_id' => 13, 'division_id' => 'DNSM', 'course_code' => 'CMSC140', 'course_name' => 'Programming Languages'],
            ['course_id' => 14, 'division_id' => 'DNSM', 'course_code' => 'CMSC150', 'course_name' => 'Numerical and Symbolic Computation'],
            ['course_id' => 15, 'division_id' => 'DNSM', 'course_code' => 'CMSC197', 'course_name' => 'Machine Learning'],
            ['course_id' => 16, 'division_id' => 'DNSM', 'course_code' => 'CMSC198', 'course_name' => 'Special Topics in Computer Science'],
            ['course_id' => 17, 'division_id' => 'DNSM', 'course_code' => 'MATH11',  'course_name' => 'College Algebra'],
            ['course_id' => 18, 'division_id' => 'DNSM', 'course_code' => 'MATH14',  'course_name' => 'Plane and Spherical Trigonometry'],
            ['course_id' => 19, 'division_id' => 'DNSM', 'course_code' => 'MATH17',  'course_name' => 'Algebra and Trigonometry'],
            ['course_id' => 20, 'division_id' => 'DNSM', 'course_code' => 'MATH55',  'course_name' => 'Elementary Analysis I'],
            ['course_id' => 21, 'division_id' => 'DNSM', 'course_code' => 'MATH56',  'course_name' => 'Elementary Analysis II'],
            ['course_id' => 22, 'division_id' => 'DNSM', 'course_code' => 'MATH18',  'course_name' => 'Analytic Geometry and Calculus I'],
            ['course_id' => 23, 'division_id' => 'DNSM', 'course_code' => 'MATH60',  'course_name' => 'Linear Algebra I'],
            ['course_id' => 24, 'division_id' => 'DNSM', 'course_code' => 'MATH61',  'course_name' => 'Linear Algebra II'],
            ['course_id' => 25, 'division_id' => 'DNSM', 'course_code' => 'MATH100', 'course_name' => 'Mathematics for Science Students'],
            ['course_id' => 26, 'division_id' => 'DNSM', 'course_code' => 'MATH114', 'course_name' => 'Combinatorics'],
            ['course_id' => 27, 'division_id' => 'DNSM', 'course_code' => 'MATH115', 'course_name' => 'Graph Theory'],
            ['course_id' => 28, 'division_id' => 'DNSM', 'course_code' => 'MATH121', 'course_name' => 'Abstract Algebra I'],
            ['course_id' => 29, 'division_id' => 'DNSM', 'course_code' => 'MATH131', 'course_name' => 'Differential Equations'],
            ['course_id' => 30, 'division_id' => 'DNSM', 'course_code' => 'MATH133', 'course_name' => 'Mathematical Analysis I'],
            ['course_id' => 31, 'division_id' => 'DNSM', 'course_code' => 'MATH150', 'course_name' => 'Numerical Analysis I'],
            ['course_id' => 32, 'division_id' => 'DNSM', 'course_code' => 'MATH151', 'course_name' => 'Numerical Analysis II'],
            ['course_id' => 33, 'division_id' => 'DNSM', 'course_code' => 'STAT105', 'course_name' => 'Statistical Methods I'],
        ]);

        // Course Topics 1–9 are for CMSC121 (course_id 6). Topics 10–21 are inserted
        // later in this seeder alongside the other sample content.
        DB::table('Course_Topics')->insertOrIgnore([
            ['topic_id' => 1, 'course_id' => 6, 'topic_name' => 'Introduction to HTML and CSS'],
            ['topic_id' => 2, 'course_id' => 6, 'topic_name' => 'CSS Layouts and Responsive Design'],
            ['topic_id' => 3, 'course_id' => 6, 'topic_name' => 'PHP Basics and Server-Side Scripting'],
            ['topic_id' => 4, 'course_id' => 6, 'topic_name' => 'JavaScript and DOM Manipulation'],
            ['topic_id' => 5, 'course_id' => 6, 'topic_name' => 'Building Client-Side Web Applications'],
            ['topic_id' => 6, 'course_id' => 6, 'topic_name' => 'Database Design and SQL'],
            ['topic_id' => 7, 'course_id' => 6, 'topic_name' => 'MVC Architecture and Routing'],
            ['topic_id' => 8, 'course_id' => 6, 'topic_name' => 'Web Frameworks (Laravel)'],
            ['topic_id' => 9, 'course_id' => 6, 'topic_name' => 'Web Security and Authentication'],
        ]);

        // Rooms: room_ids 1–3 are hard-coded in Sessions and counter-proposal seed rows.
        DB::table('Rooms')->insertOrIgnore([
            ['room_id' => 1, 'room_code' => 'CSLAB2', 'room_name' => 'CS Lab 2',     'room_type' => 'Physical', 'capacity' => 30],
            ['room_id' => 2, 'room_code' => 'CSLAB1', 'room_name' => 'CS Lab 1',     'room_type' => 'Physical', 'capacity' => 25],
            ['room_id' => 3, 'room_code' => 'GMEET',  'room_name' => 'Google Meet',  'room_type' => 'Virtual',  'capacity' => 50],
            ['room_id' => 4, 'room_code' => 'ZOOM',   'room_name' => 'Zoom Meeting', 'room_type' => 'Virtual',  'capacity' => 50],
        ]);

        $pw = Hash::make('password');

        // ── Users ─────────────────────────────────────────────────────────────
        // All emails must end in @up.edu.ph (matches the registration rule).
        DB::table('Users')->insert([
            ['user_id' => 'aaaa0001-0001-0001-0001-000000000001', 'email' => 'alex.santos@up.edu.ph',    'password_hash' => $pw, 'first_name' => 'Alex',    'middle_name' => 'M.',  'last_name' => 'Santos',    'contact_number' => '09171234001', 'current_year_level' => 3, 'program_code' => 'BSCS',  'created_at' => '2026-01-10 08:00:00'],
            ['user_id' => 'aaaa0002-0002-0002-0002-000000000002', 'email' => 'maria.cruz@up.edu.ph',     'password_hash' => $pw, 'first_name' => 'Maria',   'middle_name' => 'B.',  'last_name' => 'Cruz',      'contact_number' => '09171234002', 'current_year_level' => 4, 'program_code' => 'BSCS',  'created_at' => '2026-01-11 08:00:00'],
            ['user_id' => 'aaaa0003-0003-0003-0003-000000000003', 'email' => 'ramon.delapena@up.edu.ph', 'password_hash' => $pw, 'first_name' => 'Ramon',   'middle_name' => null,  'last_name' => 'dela Pena', 'contact_number' => '09171234003', 'current_year_level' => 4, 'program_code' => 'BSCS',  'created_at' => '2026-01-12 08:00:00'],
            ['user_id' => 'aaaa0004-0004-0004-0004-000000000004', 'email' => 'bianca.reyes@up.edu.ph',   'password_hash' => $pw, 'first_name' => 'Bianca',  'middle_name' => 'C.',  'last_name' => 'Reyes',     'contact_number' => '09171234004', 'current_year_level' => 2, 'program_code' => 'BSCS',  'created_at' => '2026-01-15 08:00:00'],
            ['user_id' => 'aaaa0005-0005-0005-0005-000000000005', 'email' => 'carlo.manalo@up.edu.ph',   'password_hash' => $pw, 'first_name' => 'Carlo',   'middle_name' => 'D.',  'last_name' => 'Manalo',    'contact_number' => '09171234005', 'current_year_level' => 1, 'program_code' => 'BSCS',  'created_at' => '2026-01-16 08:00:00'],
            ['user_id' => 'aaaa0006-0006-0006-0006-000000000006', 'email' => 'diana.lim@up.edu.ph',      'password_hash' => $pw, 'first_name' => 'Diana',   'middle_name' => null,  'last_name' => 'Lim',       'contact_number' => '09171234006', 'current_year_level' => 2, 'program_code' => 'BAPsych', 'created_at' => '2026-01-17 08:00:00'],
            ['user_id' => 'aaaa0007-0007-0007-0007-000000000007', 'email' => 'elena.navarro@up.edu.ph',  'password_hash' => $pw, 'first_name' => 'Elena',   'middle_name' => 'F.',  'last_name' => 'Navarro',   'contact_number' => '09171234007', 'current_year_level' => 3, 'program_code' => 'BSCS',  'created_at' => '2026-01-18 08:00:00'],
            ['user_id' => 'aaaa0008-0008-0008-0008-000000000008', 'email' => 'francis.tan@up.edu.ph',    'password_hash' => $pw, 'first_name' => 'Francis', 'middle_name' => null,  'last_name' => 'Tan',       'contact_number' => '09171234008', 'current_year_level' => 2, 'program_code' => 'BSCS',  'created_at' => '2026-01-19 08:00:00'],
        ]);

        // ── Tutor Profiles ────────────────────────────────────────────────────
        // Alex, Maria, Ramon = dedicated tutors. Elena, Francis = mixed (also students).
        // rating_avg: Maria=5.00 (has review), Alex=4.80 (prior sessions), Ramon=4.50.
        DB::table('Tutor_Profiles')->insert([
            ['user_id' => 'aaaa0001-0001-0001-0001-000000000001', 'bio' => 'Hi! I am a 3rd-year BSCS student passionate about web development and data structures. I have been tutoring CMSC121 and CMSC122 for two semesters. Happy to help at any skill level!', 'rating_avg' => 4.80],
            ['user_id' => 'aaaa0002-0002-0002-0002-000000000002', 'bio' => 'Senior BSCS student with a strong background in mathematics and statistics. I enjoy breaking down complex concepts into clear, step-by-step explanations. Available weekday afternoons.', 'rating_avg' => 5.00],
            ['user_id' => 'aaaa0003-0003-0003-0003-000000000003', 'bio' => 'Fourth-year CS student. I remember how challenging CMSC11 and CMSC12 were as a freshman, so I make it a point to be patient and thorough with beginners.', 'rating_avg' => 4.50],
            ['user_id' => 'aaaa0007-0007-0007-0007-000000000007', 'bio' => 'Third-year CS student who loves programming languages and paradigms. Currently offering tutoring for CMSC13 while still expanding my own knowledge.', 'rating_avg' => 0.00],
            ['user_id' => 'aaaa0008-0008-0008-0008-000000000008', 'bio' => 'Sophomore CS student comfortable with introductory CS concepts. Great at explaining CMSC10 to absolute beginners.', 'rating_avg' => 0.00],
        ]);

        // ── Course Topics (10–21) ─────────────────────────────────────────────
        // Topics 1–9 already exist in database.sql (CMSC121). Add topics for the
        // other courses used in requests and sessions.
        DB::table('Course_Topics')->insert([
            ['topic_id' => 10, 'course_id' => 4,  'topic_name' => 'Variables, Data Types and Control Structures'],
            ['topic_id' => 11, 'course_id' => 4,  'topic_name' => 'Functions and Recursion in C'],
            ['topic_id' => 12, 'course_id' => 5,  'topic_name' => 'Object-Oriented Programming Concepts'],
            ['topic_id' => 13, 'course_id' => 5,  'topic_name' => 'Pointers and Dynamic Memory Management'],
            ['topic_id' => 14, 'course_id' => 7,  'topic_name' => 'Arrays, Linked Lists and Iterators'],
            ['topic_id' => 15, 'course_id' => 7,  'topic_name' => 'Stacks, Queues, Trees and Graphs'],
            ['topic_id' => 16, 'course_id' => 22, 'topic_name' => 'Trigonometric Functions and Identities'],
            ['topic_id' => 17, 'course_id' => 22, 'topic_name' => 'Polynomials, Rational Expressions and Equations'],
            ['topic_id' => 18, 'course_id' => 33, 'topic_name' => 'Descriptive Statistics and Data Visualization'],
            ['topic_id' => 19, 'course_id' => 33, 'topic_name' => 'Probability, Distributions and Hypothesis Testing'],
            ['topic_id' => 20, 'course_id' => 12, 'topic_name' => 'Functional and Logic Programming Paradigms'],
            ['topic_id' => 21, 'course_id' => 3,  'topic_name' => 'Introduction to Algorithms and Computational Thinking'],
        ]);

        // ── Tutor Expertise (stored by course_id, not topic_id) ───────────────
        DB::table('Tutor_Expertise')->insert([
            // Alex: CMSC121 (6), CMSC122 (7)
            ['user_id' => 'aaaa0001-0001-0001-0001-000000000001', 'course_id' => 6],
            ['user_id' => 'aaaa0001-0001-0001-0001-000000000001', 'course_id' => 7],
            // Maria: MATH18 (22), STAT105 (33)
            ['user_id' => 'aaaa0002-0002-0002-0002-000000000002', 'course_id' => 22],
            ['user_id' => 'aaaa0002-0002-0002-0002-000000000002', 'course_id' => 33],
            // Ramon: CMSC11 (4), CMSC12 (5)
            ['user_id' => 'aaaa0003-0003-0003-0003-000000000003', 'course_id' => 4],
            ['user_id' => 'aaaa0003-0003-0003-0003-000000000003', 'course_id' => 5],
            // Elena: CMSC13 (12)
            ['user_id' => 'aaaa0007-0007-0007-0007-000000000007', 'course_id' => 12],
            // Francis: CMSC10 (3)
            ['user_id' => 'aaaa0008-0008-0008-0008-000000000008', 'course_id' => 3],
        ]);

        // ── Requests ─────────────────────────────────────────────────────────
        // R1  Direct   | Bianca → Alex   | CMSC121  | Pending
        // R2  Direct   | Carlo  → Alex   | CMSC122  | Approved  (upcoming session)
        // R3  Direct   | Diana  → Maria  | MATH18   | Approved  (completed session + review)
        // R4  Direct   | Carlo  → Maria  | STAT105  | Declined
        // R5  Direct   | Bianca → Ramon  | CMSC11   | CounterProposed (student has not responded)
        // R6  Direct   | Elena  → Ramon  | CMSC12   | Expired
        // R7  Broadcast| Diana           | MATH18   | Pending   (no tutor; claimable)
        // R8  Broadcast| Francis → Alex  | CMSC121  | Approved  (broadcast claimed; upcoming)
        // R9  Group    | Alex   (self)   | CMSC121  | Approved  (open group session)
        DB::table('Requests')->insert([
            [
                'request_id'                => 'rrrr0001-0001-0001-0001-000000000001',
                'student_id'                => 'aaaa0004-0004-0004-0004-000000000004',
                'tutor_id'                  => 'aaaa0001-0001-0001-0001-000000000001',
                'course_id'                 => 6,
                'message'                   => '[Preferred: 2026-05-15T10:00] Hi Alex! I am struggling with Laravel routing and middleware. Can we meet?',
                'status'                    => 'Pending',
                'counter_proposed_time'     => null, 'counter_proposed_message' => null,
                'counter_proposed_modality' => null, 'counter_proposed_room_id' => null,
                'created_at'                => '2026-05-05 09:00:00',
            ],
            [
                'request_id'                => 'rrrr0002-0002-0002-0002-000000000002',
                'student_id'                => 'aaaa0005-0005-0005-0005-000000000005',
                'tutor_id'                  => 'aaaa0001-0001-0001-0001-000000000001',
                'course_id'                 => 7,
                'message'                   => '[Preferred: 2026-05-12T14:00] Hi! I need help understanding linked lists and binary trees for my long exam.',
                'status'                    => 'Approved',
                'counter_proposed_time'     => null, 'counter_proposed_message' => null,
                'counter_proposed_modality' => null, 'counter_proposed_room_id' => null,
                'created_at'                => '2026-05-04 10:00:00',
            ],
            [
                'request_id'                => 'rrrr0003-0003-0003-0003-000000000003',
                'student_id'                => 'aaaa0006-0006-0006-0006-000000000006',
                'tutor_id'                  => 'aaaa0002-0002-0002-0002-000000000002',
                'course_id'                 => 22,
                'message'                   => '[Preferred: 2026-04-28T15:00] I have a midterm exam on trigonometry next week and I am completely lost. Please help!',
                'status'                    => 'Approved',
                'counter_proposed_time'     => null, 'counter_proposed_message' => null,
                'counter_proposed_modality' => null, 'counter_proposed_room_id' => null,
                'created_at'                => '2026-04-26 11:00:00',
            ],
            [
                'request_id'                => 'rrrr0004-0004-0004-0004-000000000004',
                'student_id'                => 'aaaa0005-0005-0005-0005-000000000005',
                'tutor_id'                  => 'aaaa0002-0002-0002-0002-000000000002',
                'course_id'                 => 33,
                'message'                   => '[Preferred: 2026-05-02T09:00] Can we cover hypothesis testing and p-values?',
                'status'                    => 'Declined',
                'counter_proposed_time'     => null, 'counter_proposed_message' => null,
                'counter_proposed_modality' => null, 'counter_proposed_room_id' => null,
                'created_at'                => '2026-04-30 12:00:00',
            ],
            [
                'request_id'                => 'rrrr0005-0005-0005-0005-000000000005',
                'student_id'                => 'aaaa0004-0004-0004-0004-000000000004',
                'tutor_id'                  => 'aaaa0003-0003-0003-0003-000000000003',
                'course_id'                 => 4,
                'message'                   => '[Preferred: 2026-05-10T10:00] I am confused about pointers and recursion. Can you help me?',
                'status'                    => 'CounterProposed',
                'counter_proposed_time'     => '2026-05-12 14:00:00',
                'counter_proposed_message'  => 'Sorry, I am occupied on May 10. How about May 12 at 2 PM in CSLAB2? Works better for me.',
                'counter_proposed_modality' => 'In-Person',
                'counter_proposed_room_id'  => 1,
                'created_at'                => '2026-05-03 13:00:00',
            ],
            [
                'request_id'                => 'rrrr0006-0006-0006-0006-000000000006',
                'student_id'                => 'aaaa0007-0007-0007-0007-000000000007',
                'tutor_id'                  => 'aaaa0003-0003-0003-0003-000000000003',
                'course_id'                 => 5,
                'message'                   => '[Preferred: 2026-04-20T11:00] Can we review OOP concepts and inheritance for my finals?',
                'status'                    => 'Expired',
                'counter_proposed_time'     => null, 'counter_proposed_message' => null,
                'counter_proposed_modality' => null, 'counter_proposed_room_id' => null,
                'created_at'                => '2026-04-18 14:00:00',
            ],
            [
                'request_id'                => 'rrrr0007-0007-0007-0007-000000000007',
                'student_id'                => 'aaaa0006-0006-0006-0006-000000000006',
                'tutor_id'                  => null,
                'course_id'                 => 22,
                'message'                   => 'Looking for a tutor who can help me review polynomial functions before my midterms. Flexible on schedule!',
                'status'                    => 'Pending',
                'counter_proposed_time'     => null, 'counter_proposed_message' => null,
                'counter_proposed_modality' => null, 'counter_proposed_room_id' => null,
                'created_at'                => '2026-05-05 10:00:00',
            ],
            [
                'request_id'                => 'rrrr0008-0008-0008-0008-000000000008',
                'student_id'                => 'aaaa0008-0008-0008-0008-000000000008',
                'tutor_id'                  => 'aaaa0001-0001-0001-0001-000000000001',
                'course_id'                 => 6,
                'message'                   => 'Need help with JavaScript DOM manipulation. No specific tutor in mind — anyone available?',
                'status'                    => 'Approved',
                'counter_proposed_time'     => null, 'counter_proposed_message' => null,
                'counter_proposed_modality' => null, 'counter_proposed_room_id' => null,
                'created_at'                => '2026-05-01 09:00:00',
            ],
            [
                'request_id'                => 'rrrr0009-0009-0009-0009-000000000009',
                'student_id'                => 'aaaa0001-0001-0001-0001-000000000001',
                'tutor_id'                  => 'aaaa0001-0001-0001-0001-000000000001',
                'course_id'                 => 6,
                'message'                   => '[GROUP] Open review session for CMSC121 midterms. Will cover Laravel routing and web security. All are welcome!',
                'status'                    => 'Approved',
                'counter_proposed_time'     => null, 'counter_proposed_message' => null,
                'counter_proposed_modality' => null, 'counter_proposed_room_id' => null,
                'created_at'                => '2026-05-04 08:00:00',
            ],
        ]);

        // ── Sessions ─────────────────────────────────────────────────────────
        // Only Approved requests get sessions. No session for R1 (Pending), R4 (Declined),
        // R5 (CounterProposed), R6 (Expired), R7 (Pending broadcast).
        DB::table('Sessions')->insert([
            // S2: R2 — Carlo+Alex, In-Person CSLAB2, upcoming
            [
                'session_id'     => 'ssss0002-0002-0002-0002-000000000002',
                'request_id'     => 'rrrr0002-0002-0002-0002-000000000002',
                'modality'       => 'In-Person',
                'room_id'        => 1, // CSLAB2
                'meeting_link'   => null,
                'scheduled_time' => '2026-05-12 14:00:00',
                'status'         => 'Scheduled',
                'created_at'     => '2026-05-04 10:05:00',
            ],
            // S3: R3 — Diana+Maria, Online Google Meet, completed (review exists)
            [
                'session_id'     => 'ssss0003-0003-0003-0003-000000000003',
                'request_id'     => 'rrrr0003-0003-0003-0003-000000000003',
                'modality'       => 'Online',
                'room_id'        => 3, // Google Meet
                'meeting_link'   => 'https://meet.google.com/peerlink-seed-003',
                'scheduled_time' => '2026-04-28 15:00:00',
                'status'         => 'Completed',
                'created_at'     => '2026-04-26 11:05:00',
            ],
            // S8: R8 — Francis+Alex, In-Person CSLAB1, upcoming (claimed broadcast)
            [
                'session_id'     => 'ssss0008-0008-0008-0008-000000000008',
                'request_id'     => 'rrrr0008-0008-0008-0008-000000000008',
                'modality'       => 'In-Person',
                'room_id'        => 2, // CSLAB1
                'meeting_link'   => null,
                'scheduled_time' => '2026-05-08 10:00:00',
                'status'         => 'Scheduled',
                'created_at'     => '2026-05-01 09:05:00',
            ],
            // S9: R9 — Alex group session, In-Person CSLAB2, upcoming
            [
                'session_id'     => 'ssss0009-0009-0009-0009-000000000009',
                'request_id'     => 'rrrr0009-0009-0009-0009-000000000009',
                'modality'       => 'In-Person',
                'room_id'        => 1, // CSLAB2
                'meeting_link'   => null,
                'scheduled_time' => '2026-05-09 13:00:00',
                'status'         => 'Scheduled',
                'created_at'     => '2026-05-04 08:05:00',
            ],
        ]);

        // ── Session Participants ──────────────────────────────────────────────
        DB::table('Session_Participants')->insert([
            // S2: Alex (Tutor) + Carlo (Tutee) — upcoming
            ['participation_id' => 'pppp0001-0001-0001-0001-000000000001', 'session_id' => 'ssss0002-0002-0002-0002-000000000002', 'user_id' => 'aaaa0001-0001-0001-0001-000000000001', 'role' => 'Tutor', 'has_attended' => null, 'joined_at' => '2026-05-04 10:05:00'],
            ['participation_id' => 'pppp0002-0002-0002-0002-000000000002', 'session_id' => 'ssss0002-0002-0002-0002-000000000002', 'user_id' => 'aaaa0005-0005-0005-0005-000000000005', 'role' => 'Tutee', 'has_attended' => null, 'joined_at' => '2026-05-04 10:05:00'],
            // S3: Maria (Tutor) + Diana (Tutee) — completed, both attended
            ['participation_id' => 'pppp0003-0003-0003-0003-000000000003', 'session_id' => 'ssss0003-0003-0003-0003-000000000003', 'user_id' => 'aaaa0002-0002-0002-0002-000000000002', 'role' => 'Tutor', 'has_attended' => 1,    'joined_at' => '2026-04-26 11:05:00'],
            ['participation_id' => 'pppp0004-0004-0004-0004-000000000004', 'session_id' => 'ssss0003-0003-0003-0003-000000000003', 'user_id' => 'aaaa0006-0006-0006-0006-000000000006', 'role' => 'Tutee', 'has_attended' => 1,    'joined_at' => '2026-04-26 11:05:00'],
            // S8: Alex (Tutor) + Francis (Tutee) — upcoming
            ['participation_id' => 'pppp0005-0005-0005-0005-000000000005', 'session_id' => 'ssss0008-0008-0008-0008-000000000008', 'user_id' => 'aaaa0001-0001-0001-0001-000000000001', 'role' => 'Tutor', 'has_attended' => null, 'joined_at' => '2026-05-01 09:05:00'],
            ['participation_id' => 'pppp0006-0006-0006-0006-000000000006', 'session_id' => 'ssss0008-0008-0008-0008-000000000008', 'user_id' => 'aaaa0008-0008-0008-0008-000000000008', 'role' => 'Tutee', 'has_attended' => null, 'joined_at' => '2026-05-01 09:05:00'],
            // S9: Alex (Tutor) — open group session, no student participants yet
            ['participation_id' => 'pppp0007-0007-0007-0007-000000000007', 'session_id' => 'ssss0009-0009-0009-0009-000000000009', 'user_id' => 'aaaa0001-0001-0001-0001-000000000001', 'role' => 'Tutor', 'has_attended' => null, 'joined_at' => '2026-05-04 08:05:00'],
        ]);

        // ── Session Topics ────────────────────────────────────────────────────
        DB::table('Session_Topics')->insert([
            ['session_id' => 'ssss0002-0002-0002-0002-000000000002', 'topic_id' => 14], // Arrays & Linked Lists
            ['session_id' => 'ssss0002-0002-0002-0002-000000000002', 'topic_id' => 15], // Stacks, Queues, Trees
            ['session_id' => 'ssss0003-0003-0003-0003-000000000003', 'topic_id' => 16], // Trigonometry
            ['session_id' => 'ssss0008-0008-0008-0008-000000000008', 'topic_id' => 4],  // JavaScript & DOM
            ['session_id' => 'ssss0008-0008-0008-0008-000000000008', 'topic_id' => 5],  // Client-side Web Apps
            ['session_id' => 'ssss0009-0009-0009-0009-000000000009', 'topic_id' => 8],  // Web Frameworks (Laravel)
            ['session_id' => 'ssss0009-0009-0009-0009-000000000009', 'topic_id' => 9],  // Web Security
        ]);

        // ── Reviews ───────────────────────────────────────────────────────────
        // Diana reviewed Maria (5★) for the completed MATH18 session.
        DB::table('Session_Reviews')->insert([
            [
                'review_id'   => 'revv0001-0001-0001-0001-000000000001',
                'session_id'  => 'ssss0003-0003-0003-0003-000000000003',
                'reviewer_id' => 'aaaa0006-0006-0006-0006-000000000006',
                'reviewee_id' => 'aaaa0002-0002-0002-0002-000000000002',
                'rating'      => 5,
                'feedback'    => 'Maria was incredibly patient and explained every concept step by step. I passed my midterm because of her session! Highly recommend.',
                'created_at'  => '2026-04-29 09:00:00',
            ],
        ]);

        // Notifications are generated naturally by the application when users interact
        // (send requests, accept, decline, counter-propose, etc.) — not seeded here.

        if ($isMySQL) DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('PeerLink seed data inserted successfully.');
        $this->command->line('');
        $this->command->info('Test accounts (password: password)');
        $this->command->table(
            ['Email', 'Name', 'Role'],
            [
                ['alex.santos@up.edu.ph',    'Alex Santos',    'Tutor'],
                ['maria.cruz@up.edu.ph',     'Maria Cruz',     'Tutor'],
                ['ramon.delapena@up.edu.ph', 'Ramon dela Pena','Tutor'],
                ['bianca.reyes@up.edu.ph',   'Bianca Reyes',   'Student'],
                ['carlo.manalo@up.edu.ph',   'Carlo Manalo',   'Student'],
                ['diana.lim@up.edu.ph',      'Diana Lim',      'Student'],
                ['elena.navarro@up.edu.ph',  'Elena Navarro',  'Mixed (Tutor + Student)'],
                ['francis.tan@up.edu.ph',    'Francis Tan',    'Mixed (Tutor + Student)'],
            ]
        );
    }
}
