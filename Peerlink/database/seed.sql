-- =============================================================
-- PeerLink Sample Data Seed
-- Run AFTER database.sql to populate test data.
--
-- All accounts use password:  password
-- Bcrypt hash ($2y$12$):  $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
--
-- Users created:
--   Tutors        : Alex Santos, Maria Cruz, Ramon dela Pena
--   Students only : Bianca Reyes, Carlo Manalo, Diana Lim
--   Mixed roles   : Elena Navarro (tutor+student), Francis Tan (tutor+student)
--
-- Request cases covered:
--   R1  Direct   | Bianca → Alex   | CMSC121 | Pending         (tutor has not responded)
--   R2  Direct   | Carlo  → Alex   | CMSC122 | Approved        (In-Person session, upcoming, no review)
--   R3  Direct   | Diana  → Maria  | MATH18  | Approved        (Online session, Completed, has review)
--   R4  Direct   | Carlo  → Maria  | STAT105 | Declined        (tutor declined)
--   R5  Direct   | Bianca → Ramon  | CMSC11  | CounterProposed (tutor proposed new time; student pending response)
--   R6  Direct   | Elena  → Ramon  | CMSC12  | Expired         (nobody acted; request expired)
--   R7  Broadcast| Diana           | MATH18  | Pending         (no tutor yet; claimable)
--   R8  Broadcast| Francis → Alex  | CMSC121 | Approved        (originally broadcast, Alex claimed it; upcoming)
--   R9  Group    | Alex   (self)   | CMSC121 | Approved        (tutor posted open group session)
-- =============================================================

USE `PeerLink`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------
-- SCHEMA PATCHES
-- Each ALTER TABLE runs independently so that if a column already
-- exists the duplicate-column error is shown but execution continues.
-- ---------------------------------------------------------------

-- Add counter-proposal columns one at a time (errors are harmless if they already exist)
ALTER TABLE `Requests` ADD COLUMN `counter_proposed_time`     datetime                   DEFAULT NULL;
ALTER TABLE `Requests` ADD COLUMN `counter_proposed_message`  text                       DEFAULT NULL;
ALTER TABLE `Requests` ADD COLUMN `counter_proposed_modality` enum('In-Person','Online') DEFAULT NULL;
ALTER TABLE `Requests` ADD COLUMN `counter_proposed_room_id`  int(11)                    DEFAULT NULL;

-- Extend status enum to include CounterProposed
ALTER TABLE `Requests`
  MODIFY COLUMN `status` enum('Pending','Approved','Declined','Expired','CounterProposed') DEFAULT 'Pending';

-- Add FK for counter_proposed_room_id (error is harmless if it already exists)
ALTER TABLE `Requests`
  ADD CONSTRAINT `Requests_ibfk_4`
  FOREIGN KEY (`counter_proposed_room_id`) REFERENCES `Rooms` (`room_id`);

-- Create Notifications without FOREIGN KEY constraints to avoid charset/collation
-- incompatibilities between MySQL and MariaDB versions.
-- Referential integrity is enforced at the application layer by Eloquent.
CREATE TABLE IF NOT EXISTS `Notifications` (
  `notification_id` char(36)    NOT NULL,
  `user_id`         char(36)    NOT NULL,
  `type`            varchar(50) NOT NULL,
  `message`         text        NOT NULL,
  `request_id`      char(36)    DEFAULT NULL,
  `is_read`         tinyint(1)  NOT NULL DEFAULT 0,
  `created_at`      timestamp   NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_notif_user`    (`user_id`),
  KEY `idx_notif_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- ADDITIONAL COURSE TOPICS
-- (Existing topic_id 1-9 are for CMSC121; we add topics for the
-- other courses tutors will offer.)
-- ---------------------------------------------------------------

LOCK TABLES `Course_Topics` WRITE;
INSERT INTO `Course_Topics` (`topic_id`, `course_id`, `topic_name`) VALUES
  (10, 4,  'Variables, Data Types and Control Structures'),
  (11, 4,  'Functions and Recursion in C'),
  (12, 5,  'Object-Oriented Programming Concepts'),
  (13, 5,  'Pointers and Dynamic Memory Management'),
  (14, 7,  'Arrays, Linked Lists and Iterators'),
  (15, 7,  'Stacks, Queues, Trees and Graphs'),
  (16, 22, 'Trigonometric Functions and Identities'),
  (17, 22, 'Polynomials, Rational Expressions and Equations'),
  (18, 33, 'Descriptive Statistics and Data Visualization'),
  (19, 33, 'Probability, Distributions and Hypothesis Testing'),
  (20, 12, 'Functional and Logic Programming Paradigms'),
  (21, 3,  'Introduction to Algorithms and Computational Thinking');
UNLOCK TABLES;

-- ---------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------

LOCK TABLES `Users` WRITE;
INSERT INTO `Users`
  (`user_id`, `email`, `password_hash`,
   `first_name`, `middle_name`, `last_name`,
   `contact_number`, `current_year_level`, `program_code`, `created_at`)
VALUES
  -- ── Tutors ──────────────────────────────────────────────────
  ('aaaa0001-0001-0001-0001-000000000001',
   'alex.santos@up.edu.ph',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Alex', 'M.', 'Santos',
   '09171234001', 3, 'BSCS', '2026-01-10 08:00:00'),

  ('aaaa0002-0002-0002-0002-000000000002',
   'maria.cruz@up.edu.ph',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Maria', 'B.', 'Cruz',
   '09171234002', 4, 'BSCS', '2026-01-11 08:00:00'),

  ('aaaa0003-0003-0003-0003-000000000003',
   'ramon.delapena@up.edu.ph',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Ramon', NULL, 'dela Pena',
   '09171234003', 4, 'BSCS', '2026-01-12 08:00:00'),

  -- ── Students only ───────────────────────────────────────────
  ('aaaa0004-0004-0004-0004-000000000004',
   'bianca.reyes@up.edu.ph',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Bianca', 'C.', 'Reyes',
   '09171234004', 2, 'BSCS', '2026-01-15 08:00:00'),

  ('aaaa0005-0005-0005-0005-000000000005',
   'carlo.manalo@up.edu.ph',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Carlo', 'D.', 'Manalo',
   '09171234005', 1, 'BSCS', '2026-01-16 08:00:00'),

  ('aaaa0006-0006-0006-0006-000000000006',
   'diana.lim@up.edu.ph',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Diana', NULL, 'Lim',
   '09171234006', 2, 'BSBio', '2026-01-17 08:00:00'),

  -- ── Mixed roles (also have tutor profiles) ──────────────────
  ('aaaa0007-0007-0007-0007-000000000007',
   'elena.navarro@up.edu.ph',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Elena', 'F.', 'Navarro',
   '09171234007', 3, 'BSCS', '2026-01-18 08:00:00'),

  ('aaaa0008-0008-0008-0008-000000000008',
   'francis.tan@up.edu.ph',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Francis', NULL, 'Tan',
   '09171234008', 2, 'BSCS', '2026-01-19 08:00:00');
UNLOCK TABLES;

-- ---------------------------------------------------------------
-- TUTOR PROFILES
-- rating_avg for Maria is 5.00 (she has one 5-star review in the seed).
-- Alex has 4.80 (reflects prior sessions outside this seed).
-- ---------------------------------------------------------------

LOCK TABLES `Tutor_Profiles` WRITE;
INSERT INTO `Tutor_Profiles` (`user_id`, `bio`, `rating_avg`) VALUES
  ('aaaa0001-0001-0001-0001-000000000001',
   'Hi! I am a 3rd-year BSCS student passionate about web development and data structures. I have been tutoring CMSC121 and CMSC122 for two semesters. Happy to help at any skill level!',
   4.80),

  ('aaaa0002-0002-0002-0002-000000000002',
   'Senior BSCS student with a strong background in mathematics and statistics. I enjoy breaking down complex concepts into clear, step-by-step explanations. Available weekday afternoons.',
   5.00),

  ('aaaa0003-0003-0003-0003-000000000003',
   'Fourth-year CS student. I remember how challenging CMSC11 and CMSC12 were as a freshman, so I make it a point to be patient and thorough with beginners.',
   4.50),

  ('aaaa0007-0007-0007-0007-000000000007',
   'Third-year CS student who loves programming languages and paradigms. Currently offering tutoring for CMSC13 while still expanding my own knowledge.',
   0.00),

  ('aaaa0008-0008-0008-0008-000000000008',
   'Sophomore CS student comfortable with introductory CS concepts. Great at explaining CMSC10 to absolute beginners.',
   0.00);
UNLOCK TABLES;

-- ---------------------------------------------------------------
-- TUTOR EXPERTISE
-- ---------------------------------------------------------------

LOCK TABLES `Tutor_Expertise` WRITE;
INSERT INTO `Tutor_Expertise` (`user_id`, `topic_id`) VALUES
  -- Alex: all CMSC121 topics (1-9) + CMSC122 (14,15)
  ('aaaa0001-0001-0001-0001-000000000001',  1),
  ('aaaa0001-0001-0001-0001-000000000001',  2),
  ('aaaa0001-0001-0001-0001-000000000001',  3),
  ('aaaa0001-0001-0001-0001-000000000001',  4),
  ('aaaa0001-0001-0001-0001-000000000001',  5),
  ('aaaa0001-0001-0001-0001-000000000001',  6),
  ('aaaa0001-0001-0001-0001-000000000001',  7),
  ('aaaa0001-0001-0001-0001-000000000001',  8),
  ('aaaa0001-0001-0001-0001-000000000001',  9),
  ('aaaa0001-0001-0001-0001-000000000001', 14),
  ('aaaa0001-0001-0001-0001-000000000001', 15),
  -- Maria: MATH18 (16,17) + STAT105 (18,19)
  ('aaaa0002-0002-0002-0002-000000000002', 16),
  ('aaaa0002-0002-0002-0002-000000000002', 17),
  ('aaaa0002-0002-0002-0002-000000000002', 18),
  ('aaaa0002-0002-0002-0002-000000000002', 19),
  -- Ramon: CMSC11 (10,11) + CMSC12 (12,13)
  ('aaaa0003-0003-0003-0003-000000000003', 10),
  ('aaaa0003-0003-0003-0003-000000000003', 11),
  ('aaaa0003-0003-0003-0003-000000000003', 12),
  ('aaaa0003-0003-0003-0003-000000000003', 13),
  -- Elena: CMSC13 (20)
  ('aaaa0007-0007-0007-0007-000000000007', 20),
  -- Francis: CMSC10 (21)
  ('aaaa0008-0008-0008-0008-000000000008', 21);
UNLOCK TABLES;

-- ---------------------------------------------------------------
-- REQUESTS  (9 rows, one per case)
-- ---------------------------------------------------------------

LOCK TABLES `Requests` WRITE;
INSERT INTO `Requests`
  (`request_id`, `student_id`, `tutor_id`, `course_id`, `message`, `status`,
   `counter_proposed_time`, `counter_proposed_message`,
   `counter_proposed_modality`, `counter_proposed_room_id`, `created_at`)
VALUES

  -- R1 ── Direct | Pending ──────────────────────────────────────
  ('rrrr0001-0001-0001-0001-000000000001',
   'aaaa0004-0004-0004-0004-000000000004',   -- Bianca (student)
   'aaaa0001-0001-0001-0001-000000000001',   -- Alex (tutor)
   6,   -- CMSC121
   '[Preferred: 2026-05-15T10:00] Hi Alex! I am struggling with Laravel routing and middleware. Can we meet?',
   'Pending',
   NULL, NULL, NULL, NULL,
   '2026-05-05 09:00:00'),

  -- R2 ── Direct | Approved | In-Person session (upcoming, no review) ──
  ('rrrr0002-0002-0002-0002-000000000002',
   'aaaa0005-0005-0005-0005-000000000005',   -- Carlo
   'aaaa0001-0001-0001-0001-000000000001',   -- Alex
   7,   -- CMSC122
   '[Preferred: 2026-05-12T14:00] Hi! I need help understanding linked lists and binary trees for my long exam.',
   'Approved',
   NULL, NULL, NULL, NULL,
   '2026-05-04 10:00:00'),

  -- R3 ── Direct | Approved | Online session (completed + reviewed) ──
  ('rrrr0003-0003-0003-0003-000000000003',
   'aaaa0006-0006-0006-0006-000000000006',   -- Diana
   'aaaa0002-0002-0002-0002-000000000002',   -- Maria
   22,  -- MATH18
   '[Preferred: 2026-04-28T15:00] I have a midterm exam on trigonometry next week and I am completely lost. Please help!',
   'Approved',
   NULL, NULL, NULL, NULL,
   '2026-04-26 11:00:00'),

  -- R4 ── Direct | Declined ─────────────────────────────────────
  ('rrrr0004-0004-0004-0004-000000000004',
   'aaaa0005-0005-0005-0005-000000000005',   -- Carlo
   'aaaa0002-0002-0002-0002-000000000002',   -- Maria
   33,  -- STAT105
   '[Preferred: 2026-05-02T09:00] Can we cover hypothesis testing and p-values?',
   'Declined',
   NULL, NULL, NULL, NULL,
   '2026-04-30 12:00:00'),

  -- R5 ── Direct | CounterProposed (tutor proposed a new time; student has not responded yet) ──
  ('rrrr0005-0005-0005-0005-000000000005',
   'aaaa0004-0004-0004-0004-000000000004',   -- Bianca
   'aaaa0003-0003-0003-0003-000000000003',   -- Ramon
   4,   -- CMSC11
   '[Preferred: 2026-05-10T10:00] I am confused about pointers and recursion. Can you help me?',
   'CounterProposed',
   '2026-05-12 14:00:00',
   'Sorry, I am occupied on May 10. How about May 12 at 2 PM in CSLAB2? Works better for me.',
   'In-Person',
   1,   -- CSLAB2 (room_id=1)
   '2026-05-03 13:00:00'),

  -- R6 ── Direct | Expired ──────────────────────────────────────
  ('rrrr0006-0006-0006-0006-000000000006',
   'aaaa0007-0007-0007-0007-000000000007',   -- Elena (acting as student)
   'aaaa0003-0003-0003-0003-000000000003',   -- Ramon
   5,   -- CMSC12
   '[Preferred: 2026-04-20T11:00] Can we review OOP concepts and inheritance for my finals?',
   'Expired',
   NULL, NULL, NULL, NULL,
   '2026-04-18 14:00:00'),

  -- R7 ── Broadcast | Pending (no tutor_id; any tutor can claim) ──
  ('rrrr0007-0007-0007-0007-000000000007',
   'aaaa0006-0006-0006-0006-000000000006',   -- Diana
   NULL,                                      -- broadcast: no tutor assigned
   22,  -- MATH18
   'Looking for a tutor who can help me review polynomial functions before my midterms. Flexible on schedule!',
   'Pending',
   NULL, NULL, NULL, NULL,
   '2026-05-05 10:00:00'),

  -- R8 ── Broadcast claimed | Approved | In-Person session (upcoming) ──
  -- Originally broadcast; Alex claimed it.
  ('rrrr0008-0008-0008-0008-000000000008',
   'aaaa0008-0008-0008-0008-000000000008',   -- Francis (student)
   'aaaa0001-0001-0001-0001-000000000001',   -- Alex (claimed it)
   6,   -- CMSC121
   'Need help with JavaScript DOM manipulation. No specific tutor in mind — anyone available?',
   'Approved',
   NULL, NULL, NULL, NULL,
   '2026-05-01 09:00:00'),

  -- R9 ── Group session posted by Alex (student_id = tutor_id) ──
  ('rrrr0009-0009-0009-0009-000000000009',
   'aaaa0001-0001-0001-0001-000000000001',   -- Alex (self-post as group host)
   'aaaa0001-0001-0001-0001-000000000001',   -- Alex
   6,   -- CMSC121
   '[GROUP] Open review session for CMSC121 midterms. Will cover Laravel routing and web security. All are welcome!',
   'Approved',
   NULL, NULL, NULL, NULL,
   '2026-05-04 08:00:00');

UNLOCK TABLES;

-- ---------------------------------------------------------------
-- SESSIONS  (one per Approved request)
-- ---------------------------------------------------------------

LOCK TABLES `Sessions` WRITE;
INSERT INTO `Sessions`
  (`session_id`, `request_id`, `modality`, `room_id`, `meeting_link`,
   `scheduled_time`, `status`, `created_at`)
VALUES

  -- S2: R2 — In-Person, CSLAB2, upcoming
  ('ssss0002-0002-0002-0002-000000000002',
   'rrrr0002-0002-0002-0002-000000000002',
   'In-Person', 1, NULL,
   '2026-05-12 14:00:00', 'Scheduled',
   '2026-05-04 10:05:00'),

  -- S3: R3 — Online, Google Meet, completed (past date → can be reviewed)
  ('ssss0003-0003-0003-0003-000000000003',
   'rrrr0003-0003-0003-0003-000000000003',
   'Online', 3, 'https://meet.google.com/peerlink-seed-003',
   '2026-04-28 15:00:00', 'Completed',
   '2026-04-26 11:05:00'),

  -- S8: R8 — In-Person, CSLAB1, upcoming (claimed broadcast)
  ('ssss0008-0008-0008-0008-000000000008',
   'rrrr0008-0008-0008-0008-000000000008',
   'In-Person', 2, NULL,
   '2026-05-08 10:00:00', 'Scheduled',
   '2026-05-01 09:05:00'),

  -- S9: R9 — In-Person, CSLAB2, upcoming (group session)
  ('ssss0009-0009-0009-0009-000000000009',
   'rrrr0009-0009-0009-0009-000000000009',
   'In-Person', 1, NULL,
   '2026-05-09 13:00:00', 'Scheduled',
   '2026-05-04 08:05:00');

UNLOCK TABLES;

-- ---------------------------------------------------------------
-- SESSION PARTICIPANTS
-- ---------------------------------------------------------------

LOCK TABLES `Session_Participants` WRITE;
INSERT INTO `Session_Participants`
  (`participation_id`, `session_id`, `user_id`, `role`, `has_attended`, `joined_at`)
VALUES
  -- S2: Alex (Tutor) + Carlo (Tutee) — upcoming, attendance unknown
  ('pppp0001-0001-0001-0001-000000000001',
   'ssss0002-0002-0002-0002-000000000002',
   'aaaa0001-0001-0001-0001-000000000001', 'Tutor', NULL,
   '2026-05-04 10:05:00'),
  ('pppp0002-0002-0002-0002-000000000002',
   'ssss0002-0002-0002-0002-000000000002',
   'aaaa0005-0005-0005-0005-000000000005', 'Tutee', NULL,
   '2026-05-04 10:05:00'),

  -- S3: Maria (Tutor) + Diana (Tutee) — completed, both attended
  ('pppp0003-0003-0003-0003-000000000003',
   'ssss0003-0003-0003-0003-000000000003',
   'aaaa0002-0002-0002-0002-000000000002', 'Tutor', 1,
   '2026-04-26 11:05:00'),
  ('pppp0004-0004-0004-0004-000000000004',
   'ssss0003-0003-0003-0003-000000000003',
   'aaaa0006-0006-0006-0006-000000000006', 'Tutee', 1,
   '2026-04-26 11:05:00'),

  -- S8: Alex (Tutor) + Francis (Tutee) — upcoming
  ('pppp0005-0005-0005-0005-000000000005',
   'ssss0008-0008-0008-0008-000000000008',
   'aaaa0001-0001-0001-0001-000000000001', 'Tutor', NULL,
   '2026-05-01 09:05:00'),
  ('pppp0006-0006-0006-0006-000000000006',
   'ssss0008-0008-0008-0008-000000000008',
   'aaaa0008-0008-0008-0008-000000000008', 'Tutee', NULL,
   '2026-05-01 09:05:00'),

  -- S9: Alex as group host (Tutor) — open group session
  ('pppp0007-0007-0007-0007-000000000007',
   'ssss0009-0009-0009-0009-000000000009',
   'aaaa0001-0001-0001-0001-000000000001', 'Tutor', NULL,
   '2026-05-04 08:05:00');

UNLOCK TABLES;

-- ---------------------------------------------------------------
-- SESSION TOPICS
-- ---------------------------------------------------------------

LOCK TABLES `Session_Topics` WRITE;
INSERT INTO `Session_Topics` (`session_id`, `topic_id`) VALUES
  -- S2: CMSC122 — linked lists, trees
  ('ssss0002-0002-0002-0002-000000000002', 14),
  ('ssss0002-0002-0002-0002-000000000002', 15),
  -- S3: MATH18 — trigonometry
  ('ssss0003-0003-0003-0003-000000000003', 16),
  -- S8: CMSC121 — JavaScript & DOM
  ('ssss0008-0008-0008-0008-000000000008',  4),
  ('ssss0008-0008-0008-0008-000000000008',  5),
  -- S9: CMSC121 — Laravel & web security (group review)
  ('ssss0009-0009-0009-0009-000000000009',  8),
  ('ssss0009-0009-0009-0009-000000000009',  9);
UNLOCK TABLES;

-- ---------------------------------------------------------------
-- SESSION REVIEWS
-- Diana reviewed Maria (5★) for the completed MATH18 session (S3).
-- Maria's rating_avg is already set to 5.00 in Tutor_Profiles above.
-- ---------------------------------------------------------------

LOCK TABLES `Session_Reviews` WRITE;
INSERT INTO `Session_Reviews`
  (`review_id`, `session_id`, `reviewer_id`, `reviewee_id`, `rating`, `feedback`, `created_at`)
VALUES
  ('revv0001-0001-0001-0001-000000000001',
   'ssss0003-0003-0003-0003-000000000003',
   'aaaa0006-0006-0006-0006-000000000006',   -- Diana (reviewer)
   'aaaa0002-0002-0002-0002-000000000002',   -- Maria (reviewee)
   5,
   'Maria was incredibly patient and explained every concept step by step. I passed my midterm because of her session! Highly recommend.',
   '2026-04-29 09:00:00');
UNLOCK TABLES;

-- ---------------------------------------------------------------
-- NOTIFICATIONS
-- One notification per type so the bell and dropdown can be tested.
-- ---------------------------------------------------------------

LOCK TABLES `Notifications` WRITE;
INSERT INTO `Notifications`
  (`notification_id`, `user_id`, `type`, `message`, `request_id`, `is_read`, `created_at`)
VALUES
  -- new_request: Alex received Bianca's direct request (R1) — UNREAD
  ('noti0001-0001-0001-0001-000000000001',
   'aaaa0001-0001-0001-0001-000000000001',
   'new_request',
   'Bianca Reyes sent you a tutoring request for CMSC121.',
   'rrrr0001-0001-0001-0001-000000000001',
   0, '2026-05-05 09:00:05'),

  -- request_accepted: Carlo notified that Alex accepted (R2) — READ
  ('noti0002-0002-0002-0002-000000000002',
   'aaaa0005-0005-0005-0005-000000000005',
   'request_accepted',
   'Alex Santos accepted your tutoring request for CMSC122.',
   'rrrr0002-0002-0002-0002-000000000002',
   1, '2026-05-04 10:05:05'),

  -- request_accepted: Diana notified that Maria accepted (R3) — READ
  ('noti0003-0003-0003-0003-000000000003',
   'aaaa0006-0006-0006-0006-000000000006',
   'request_accepted',
   'Maria Cruz accepted your tutoring request for MATH18.',
   'rrrr0003-0003-0003-0003-000000000003',
   1, '2026-04-26 11:05:05'),

  -- request_declined: Carlo notified that Maria declined (R4) — READ
  ('noti0004-0004-0004-0004-000000000004',
   'aaaa0005-0005-0005-0005-000000000005',
   'request_declined',
   'Maria Cruz declined your tutoring request for STAT105.',
   'rrrr0004-0004-0004-0004-000000000004',
   1, '2026-04-30 14:00:05'),

  -- counter_proposed: Bianca notified of Ramon's counter-proposal (R5) — UNREAD
  ('noti0005-0005-0005-0005-000000000005',
   'aaaa0004-0004-0004-0004-000000000004',
   'counter_proposed',
   'Ramon dela Pena proposed a new schedule for your CMSC11 request.',
   'rrrr0005-0005-0005-0005-000000000005',
   0, '2026-05-03 14:30:00'),

  -- request_accepted: Francis notified that Alex accepted the broadcast (R8) — UNREAD
  ('noti0006-0006-0006-0006-000000000006',
   'aaaa0008-0008-0008-0008-000000000008',
   'request_accepted',
   'Alex Santos accepted your tutoring request for CMSC121.',
   'rrrr0008-0008-0008-0008-000000000008',
   0, '2026-05-01 09:05:05'),

  -- new_request (broadcast claim): Alex notified that Francis originally broadcast
  -- (simulates the notification Alex would see from claiming the broadcast)
  ('noti0007-0007-0007-0007-000000000007',
   'aaaa0001-0001-0001-0001-000000000001',
   'new_request',
   'A broadcast request for CMSC121 from Francis Tan is available to claim.',
   'rrrr0008-0008-0008-0008-000000000008',
   1, '2026-05-01 09:00:05');

UNLOCK TABLES;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- Seed complete.
-- Log in with any of the accounts above using password: password
-- =============================================================
