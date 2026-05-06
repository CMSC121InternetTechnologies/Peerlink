<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if seed data already exists
        if (DB::table('Users')->where('user_id', 'like', 'aaaa%')->exists()) {
            $this->command->info('Seed data already exists — skipping.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $pw = Hash::make('password');

        // ── Users ─────────────────────────────────────────────────────────────
        // Only BSCS exists as a program_code in the Programs table
        DB::table('Users')->insert([
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000001', 'email' => 'alice@example.com',  'password_hash' => $pw, 'first_name' => 'Alice',  'middle_name' => null, 'last_name' => 'Reyes',  'contact_number' => '09171000001', 'current_year_level' => 3, 'program_code' => 'BSCS', 'created_at' => now()],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000002', 'email' => 'bob@example.com',    'password_hash' => $pw, 'first_name' => 'Bob',    'middle_name' => null, 'last_name' => 'Santos', 'contact_number' => '09171000002', 'current_year_level' => 2, 'program_code' => 'BSCS', 'created_at' => now()],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000003', 'email' => 'carol@example.com',  'password_hash' => $pw, 'first_name' => 'Carol',  'middle_name' => null, 'last_name' => 'Tan',    'contact_number' => '09171000003', 'current_year_level' => 4, 'program_code' => 'BSCS', 'created_at' => now()],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000004', 'email' => 'dan@example.com',    'password_hash' => $pw, 'first_name' => 'Dan',    'middle_name' => null, 'last_name' => 'Cruz',   'contact_number' => '09171000004', 'current_year_level' => 1, 'program_code' => 'BSCS', 'created_at' => now()],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000005', 'email' => 'eve@example.com',    'password_hash' => $pw, 'first_name' => 'Eve',    'middle_name' => null, 'last_name' => 'Lim',    'contact_number' => '09171000005', 'current_year_level' => 3, 'program_code' => 'BSCS', 'created_at' => now()],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000006', 'email' => 'frank@example.com',  'password_hash' => $pw, 'first_name' => 'Frank',  'middle_name' => null, 'last_name' => 'Uy',     'contact_number' => '09171000006', 'current_year_level' => 2, 'program_code' => 'BSCS', 'created_at' => now()],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000007', 'email' => 'grace@example.com',  'password_hash' => $pw, 'first_name' => 'Grace',  'middle_name' => null, 'last_name' => 'Go',     'contact_number' => '09171000007', 'current_year_level' => 4, 'program_code' => 'BSCS', 'created_at' => now()],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000008', 'email' => 'henry@example.com',  'password_hash' => $pw, 'first_name' => 'Henry',  'middle_name' => null, 'last_name' => 'Sy',     'contact_number' => '09171000008', 'current_year_level' => 3, 'program_code' => 'BSCS', 'created_at' => now()],
        ]);

        // ── Tutor Profiles ────────────────────────────────────────────────────
        // PK is user_id — no tutor_id column, no created_at column
        DB::table('Tutor_Profiles')->insert([
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000001', 'bio' => "Hi! I'm Alice, a 3rd-year CS student who loves algorithms and data structures.", 'rating_avg' => 4.50],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000003', 'bio' => "Carol here — 4th year CS, specializing in OS and networking.",                   'rating_avg' => 4.00],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000005', 'bio' => "Eve — I tutor web dev and databases. Let's code together!",                       'rating_avg' => 5.00],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000006', 'bio' => "Frank — 2nd year CS, good at web frameworks and server-side programming.",         'rating_avg' => 0.00],
        ]);

        // ── Tutor Expertise ───────────────────────────────────────────────────
        // FK: user_id → Tutor_Profiles(user_id), topic_id → course_topics(topic_id)
        // All existing course_topics (ids 1–9) belong to CMSC121 (Internet Technologies)
        DB::table('Tutor_Expertise')->insert([
            // Alice: Introduction to WWW, HTML Fundamentals
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000001', 'topic_id' => 1],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000001', 'topic_id' => 2],
            // Carol: CSS, JavaScript/DOM
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000003', 'topic_id' => 3],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000003', 'topic_id' => 4],
            // Eve: Client-side Web Apps, Server-side Programming
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000005', 'topic_id' => 5],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000005', 'topic_id' => 6],
            // Frank: SOA, Web Frameworks (Laravel)
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000006', 'topic_id' => 7],
            ['user_id' => 'aaaaaaaa-0000-0000-0000-000000000006', 'topic_id' => 8],
        ]);

        // ── Requests ─────────────────────────────────────────────────────────
        // course_id FK → Courses: CMSC121=6, CMSC122=7, CMSC123=8, CMSC124=9, CMSC125=10, CMSC128=11
        // status enum: 'Pending','Approved','Declined','Expired','CounterProposed'
        // counter_proposed_modality enum: 'In-Person','Online'
        // counter_proposed_room_id: int FK → Rooms (1=CS Lab 2, 2=CS Lab 1, 3=Google Meet, 4=Zoom)
        // NOTE: no preferred_time, modality, room_id, or meeting_link columns on Requests
        DB::table('Requests')->insert([
            // 1. Bob → Alice: Pending
            [
                'request_id'                => 'req00001-0000-0000-0000-000000000001',
                'student_id'                => 'aaaaaaaa-0000-0000-0000-000000000002',
                'tutor_id'                  => 'aaaaaaaa-0000-0000-0000-000000000001',
                'course_id'                 => 8, // CMSC123
                'message'                   => 'Hi Alice! Can you help me with CMSC123?',
                'status'                    => 'Pending',
                'counter_proposed_time'     => null,
                'counter_proposed_message'  => null,
                'counter_proposed_modality' => null,
                'counter_proposed_room_id'  => null,
                'created_at'                => now()->subHours(2),
            ],
            // 2. Dan → Alice: Approved (session created)
            [
                'request_id'                => 'req00001-0000-0000-0000-000000000002',
                'student_id'                => 'aaaaaaaa-0000-0000-0000-000000000004',
                'tutor_id'                  => 'aaaaaaaa-0000-0000-0000-000000000001',
                'course_id'                 => 6, // CMSC121
                'message'                   => 'Need help with CMSC121 web dev concepts.',
                'status'                    => 'Approved',
                'counter_proposed_time'     => null,
                'counter_proposed_message'  => null,
                'counter_proposed_modality' => null,
                'counter_proposed_room_id'  => null,
                'created_at'                => now()->subDays(1),
            ],
            // 3. Bob → Carol: Declined
            [
                'request_id'                => 'req00001-0000-0000-0000-000000000003',
                'student_id'                => 'aaaaaaaa-0000-0000-0000-000000000002',
                'tutor_id'                  => 'aaaaaaaa-0000-0000-0000-000000000003',
                'course_id'                 => 10, // CMSC125
                'message'                   => 'OS concepts help needed.',
                'status'                    => 'Declined',
                'counter_proposed_time'     => null,
                'counter_proposed_message'  => null,
                'counter_proposed_modality' => null,
                'counter_proposed_room_id'  => null,
                'created_at'                => now()->subDays(2),
            ],
            // 4. Henry → Alice: CounterProposed (tutor suggested new time)
            [
                'request_id'                => 'req00001-0000-0000-0000-000000000004',
                'student_id'                => 'aaaaaaaa-0000-0000-0000-000000000008',
                'tutor_id'                  => 'aaaaaaaa-0000-0000-0000-000000000001',
                'course_id'                 => 6, // CMSC121
                'message'                   => 'Please help with JavaScript DOM manipulation.',
                'status'                    => 'CounterProposed',
                'counter_proposed_time'     => now()->addDays(2)->setTime(10, 0),
                'counter_proposed_message'  => "I'm free at 10am instead — does that work?",
                'counter_proposed_modality' => 'In-Person',
                'counter_proposed_room_id'  => 1, // CS Lab 2
                'created_at'                => now()->subHours(5),
            ],
            // 5. Grace → Carol: Approved (student accepted counter-proposal, session created)
            [
                'request_id'                => 'req00001-0000-0000-0000-000000000005',
                'student_id'                => 'aaaaaaaa-0000-0000-0000-000000000007',
                'tutor_id'                  => 'aaaaaaaa-0000-0000-0000-000000000003',
                'course_id'                 => 11, // CMSC128
                'message'                   => 'Software engineering project help.',
                'status'                    => 'Approved',
                'counter_proposed_time'     => now()->addDays(4)->setTime(15, 0),
                'counter_proposed_message'  => 'Can we move to 3pm via Google Meet?',
                'counter_proposed_modality' => 'Online',
                'counter_proposed_room_id'  => 3, // Google Meet
                'created_at'                => now()->subDays(3),
            ],
            // 6. Dan → Eve: Expired (session already completed)
            [
                'request_id'                => 'req00001-0000-0000-0000-000000000006',
                'student_id'                => 'aaaaaaaa-0000-0000-0000-000000000004',
                'tutor_id'                  => 'aaaaaaaa-0000-0000-0000-000000000005',
                'course_id'                 => 6, // CMSC121
                'message'                   => 'Web dev session — CSS and client-side apps.',
                'status'                    => 'Expired',
                'counter_proposed_time'     => null,
                'counter_proposed_message'  => null,
                'counter_proposed_modality' => null,
                'counter_proposed_room_id'  => null,
                'created_at'                => now()->subDays(5),
            ],
            // 7. Bob → Frank: Pending
            [
                'request_id'                => 'req00001-0000-0000-0000-000000000007',
                'student_id'                => 'aaaaaaaa-0000-0000-0000-000000000002',
                'tutor_id'                  => 'aaaaaaaa-0000-0000-0000-000000000006',
                'course_id'                 => 6, // CMSC121
                'message'                   => 'Laravel framework help please!',
                'status'                    => 'Pending',
                'counter_proposed_time'     => null,
                'counter_proposed_message'  => null,
                'counter_proposed_modality' => null,
                'counter_proposed_room_id'  => null,
                'created_at'                => now()->subHours(1),
            ],
            // 8. Grace broadcasts: tutor_id NULL (any tutor can claim)
            [
                'request_id'                => 'req00001-0000-0000-0000-000000000008',
                'student_id'                => 'aaaaaaaa-0000-0000-0000-000000000007',
                'tutor_id'                  => null,
                'course_id'                 => 6, // CMSC121
                'message'                   => 'Anyone who can help with server-side programming?',
                'status'                    => 'Pending',
                'counter_proposed_time'     => null,
                'counter_proposed_message'  => null,
                'counter_proposed_modality' => null,
                'counter_proposed_room_id'  => null,
                'created_at'                => now()->subMinutes(30),
            ],
            // 9. Alice posts group session: student_id = tutor_id = Alice
            [
                'request_id'                => 'req00001-0000-0000-0000-000000000009',
                'student_id'                => 'aaaaaaaa-0000-0000-0000-000000000001',
                'tutor_id'                  => 'aaaaaaaa-0000-0000-0000-000000000001',
                'course_id'                 => 6, // CMSC121
                'message'                   => 'Group review session for CMSC121 exam prep!',
                'status'                    => 'Approved',
                'counter_proposed_time'     => null,
                'counter_proposed_message'  => null,
                'counter_proposed_modality' => null,
                'counter_proposed_room_id'  => null,
                'created_at'                => now()->subHours(3),
            ],
        ]);

        // ── Sessions ─────────────────────────────────────────────────────────
        // room_id is INT NOT NULL FK → Rooms (1=CS Lab 2, 2=CS Lab 1, 3=Google Meet, 4=Zoom)
        // modality enum: 'In-Person','Online'
        // status enum: 'Scheduled','Completed','Cancelled'
        // scheduled_time (not scheduled_at), no tutor_id column
        DB::table('Sessions')->insert([
            // From req2: Dan ↔ Alice, Online via Google Meet
            [
                'session_id'   => 'sess0001-0000-0000-0000-000000000001',
                'request_id'   => 'req00001-0000-0000-0000-000000000002',
                'modality'     => 'Online',
                'room_id'      => 3, // Google Meet
                'meeting_link' => 'https://meet.google.com/dan-alice',
                'scheduled_time' => now()->addDays(1)->setTime(14, 0),
                'status'       => 'Scheduled',
                'created_at'   => now()->subDays(1),
            ],
            // From req5: Grace ↔ Carol, Online via Zoom (counter-proposal accepted)
            [
                'session_id'   => 'sess0001-0000-0000-0000-000000000002',
                'request_id'   => 'req00001-0000-0000-0000-000000000005',
                'modality'     => 'Online',
                'room_id'      => 4, // Zoom
                'meeting_link' => 'https://zoom.us/j/grace-carol',
                'scheduled_time' => now()->addDays(4)->setTime(15, 0),
                'status'       => 'Scheduled',
                'created_at'   => now()->subDays(3),
            ],
            // From req6: Dan ↔ Eve, Online, already Completed
            [
                'session_id'   => 'sess0001-0000-0000-0000-000000000003',
                'request_id'   => 'req00001-0000-0000-0000-000000000006',
                'modality'     => 'Online',
                'room_id'      => 3, // Google Meet
                'meeting_link' => 'https://meet.google.com/dan-eve',
                'scheduled_time' => now()->subDays(2)->setTime(14, 0),
                'status'       => 'Completed',
                'created_at'   => now()->subDays(5),
            ],
            // From req9: Alice group session, In-Person CS Lab
            [
                'session_id'   => 'sess0001-0000-0000-0000-000000000004',
                'request_id'   => 'req00001-0000-0000-0000-000000000009',
                'modality'     => 'In-Person',
                'room_id'      => 1, // CS Lab 2
                'meeting_link' => null,
                'scheduled_time' => now()->addDays(6)->setTime(13, 0),
                'status'       => 'Scheduled',
                'created_at'   => now()->subHours(3),
            ],
        ]);

        // ── Session Participants ──────────────────────────────────────────────
        // participation_id: UUID, user_id (not student_id), role enum('Tutor','Tutee')
        DB::table('Session_Participants')->insert([
            // Session 1: Alice=Tutor, Dan=Tutee
            ['participation_id' => 'part0001-0000-0000-0000-000000000001', 'session_id' => 'sess0001-0000-0000-0000-000000000001', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000001', 'role' => 'Tutor',  'has_attended' => null, 'joined_at' => now()],
            ['participation_id' => 'part0001-0000-0000-0000-000000000002', 'session_id' => 'sess0001-0000-0000-0000-000000000001', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000004', 'role' => 'Tutee',  'has_attended' => null, 'joined_at' => now()],
            // Session 2: Carol=Tutor, Grace=Tutee
            ['participation_id' => 'part0001-0000-0000-0000-000000000003', 'session_id' => 'sess0001-0000-0000-0000-000000000002', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000003', 'role' => 'Tutor',  'has_attended' => null, 'joined_at' => now()],
            ['participation_id' => 'part0001-0000-0000-0000-000000000004', 'session_id' => 'sess0001-0000-0000-0000-000000000002', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000007', 'role' => 'Tutee',  'has_attended' => null, 'joined_at' => now()],
            // Session 3: Eve=Tutor, Dan=Tutee (completed)
            ['participation_id' => 'part0001-0000-0000-0000-000000000005', 'session_id' => 'sess0001-0000-0000-0000-000000000003', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000005', 'role' => 'Tutor',  'has_attended' => 1, 'joined_at' => now()->subDays(2)],
            ['participation_id' => 'part0001-0000-0000-0000-000000000006', 'session_id' => 'sess0001-0000-0000-0000-000000000003', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000004', 'role' => 'Tutee',  'has_attended' => 1, 'joined_at' => now()->subDays(2)],
            // Session 4 (group): Alice=Tutor, Bob/Dan/Henry=Tutee
            ['participation_id' => 'part0001-0000-0000-0000-000000000007', 'session_id' => 'sess0001-0000-0000-0000-000000000004', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000001', 'role' => 'Tutor',  'has_attended' => null, 'joined_at' => now()],
            ['participation_id' => 'part0001-0000-0000-0000-000000000008', 'session_id' => 'sess0001-0000-0000-0000-000000000004', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000002', 'role' => 'Tutee',  'has_attended' => null, 'joined_at' => now()],
            ['participation_id' => 'part0001-0000-0000-0000-000000000009', 'session_id' => 'sess0001-0000-0000-0000-000000000004', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000004', 'role' => 'Tutee',  'has_attended' => null, 'joined_at' => now()],
            ['participation_id' => 'part0001-0000-0000-0000-000000000010', 'session_id' => 'sess0001-0000-0000-0000-000000000004', 'user_id' => 'aaaaaaaa-0000-0000-0000-000000000008', 'role' => 'Tutee',  'has_attended' => null, 'joined_at' => now()],
        ]);

        // ── Session Topics ────────────────────────────────────────────────────
        // topic_id: int FK → course_topics (all CMSC121 topics, ids 1–9)
        DB::table('Session_Topics')->insert([
            ['session_id' => 'sess0001-0000-0000-0000-000000000001', 'topic_id' => 2], // HTML
            ['session_id' => 'sess0001-0000-0000-0000-000000000001', 'topic_id' => 4], // JS/DOM
            ['session_id' => 'sess0001-0000-0000-0000-000000000002', 'topic_id' => 3], // CSS
            ['session_id' => 'sess0001-0000-0000-0000-000000000002', 'topic_id' => 5], // Client-side apps
            ['session_id' => 'sess0001-0000-0000-0000-000000000003', 'topic_id' => 5], // Client-side apps
            ['session_id' => 'sess0001-0000-0000-0000-000000000003', 'topic_id' => 6], // Server-side
            ['session_id' => 'sess0001-0000-0000-0000-000000000004', 'topic_id' => 1], // Intro WWW
            ['session_id' => 'sess0001-0000-0000-0000-000000000004', 'topic_id' => 2], // HTML
        ]);

        // ── Reviews ───────────────────────────────────────────────────────────
        // feedback (not comment), reviewer_id, reviewee_id
        DB::table('Session_Reviews')->insert([
            [
                'review_id'   => 'revv0001-0000-0000-0000-000000000001',
                'session_id'  => 'sess0001-0000-0000-0000-000000000003',
                'reviewer_id' => 'aaaaaaaa-0000-0000-0000-000000000004', // Dan
                'reviewee_id' => 'aaaaaaaa-0000-0000-0000-000000000005', // Eve
                'rating'      => 5,
                'feedback'    => 'Eve explained everything really clearly. Super helpful!',
                'created_at'  => now()->subDays(1),
            ],
        ]);

        // ── Notifications ─────────────────────────────────────────────────────
        DB::table('Notifications')->insert([
            [
                'notification_id' => 'notf0001-0000-0000-0000-000000000001',
                'user_id'         => 'aaaaaaaa-0000-0000-0000-000000000001',
                'type'            => 'request_received',
                'message'         => 'Bob Santos sent you a tutoring request for CMSC123.',
                'request_id'      => 'req00001-0000-0000-0000-000000000001',
                'is_read'         => false,
                'created_at'      => now()->subHours(2),
            ],
            [
                'notification_id' => 'notf0001-0000-0000-0000-000000000002',
                'user_id'         => 'aaaaaaaa-0000-0000-0000-000000000004',
                'type'            => 'request_approved',
                'message'         => 'Alice Reyes approved your request for CMSC121. Session scheduled!',
                'request_id'      => 'req00001-0000-0000-0000-000000000002',
                'is_read'         => true,
                'created_at'      => now()->subDays(1),
            ],
            [
                'notification_id' => 'notf0001-0000-0000-0000-000000000003',
                'user_id'         => 'aaaaaaaa-0000-0000-0000-000000000002',
                'type'            => 'request_declined',
                'message'         => 'Carol Tan declined your request for CMSC125.',
                'request_id'      => 'req00001-0000-0000-0000-000000000003',
                'is_read'         => false,
                'created_at'      => now()->subDays(2),
            ],
            [
                'notification_id' => 'notf0001-0000-0000-0000-000000000004',
                'user_id'         => 'aaaaaaaa-0000-0000-0000-000000000008',
                'type'            => 'counter_proposed',
                'message'         => 'Alice Reyes sent a counter-proposal for your CMSC121 request.',
                'request_id'      => 'req00001-0000-0000-0000-000000000004',
                'is_read'         => false,
                'created_at'      => now()->subHours(5),
            ],
            [
                'notification_id' => 'notf0001-0000-0000-0000-000000000005',
                'user_id'         => 'aaaaaaaa-0000-0000-0000-000000000003',
                'type'            => 'request_approved',
                'message'         => 'Grace Go accepted your counter-proposal for CMSC128.',
                'request_id'      => 'req00001-0000-0000-0000-000000000005',
                'is_read'         => false,
                'created_at'      => now()->subDays(3),
            ],
            [
                'notification_id' => 'notf0001-0000-0000-0000-000000000006',
                'user_id'         => 'aaaaaaaa-0000-0000-0000-000000000004',
                'type'            => 'session_completed',
                'message'         => 'Your session with Eve Lim for CMSC121 is now completed.',
                'request_id'      => 'req00001-0000-0000-0000-000000000006',
                'is_read'         => true,
                'created_at'      => now()->subDays(2),
            ],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('PeerLink seed data inserted successfully.');
        $this->command->line('');
        $this->command->info('Test accounts (password: password)');
        $this->command->line('  alice@example.com  — tutor');
        $this->command->line('  bob@example.com    — student');
        $this->command->line('  carol@example.com  — tutor');
        $this->command->line('  dan@example.com    — student');
        $this->command->line('  eve@example.com    — tutor');
        $this->command->line('  frank@example.com  — tutor');
        $this->command->line('  grace@example.com  — student');
        $this->command->line('  henry@example.com  — student');
    }
}
