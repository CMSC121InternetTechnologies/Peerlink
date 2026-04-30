<?php
/**
 * GET /api/get_requests.php
 * Returns pending session requests for the logged-in tutor.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// MOCK DATA — Replace with actual DB query in production
$pending_requests = [
  [
    'id'        => 101,
    'tuteeName' => 'Juan Dela Cruz',
    'topic'     => 'Pointers and Memory Allocation in C',
    'date'      => '2026-05-05T14:00',
    'message'   => 'Hi! I am struggling with our CMSC 21 lab about pointers. Can you help me debug my code?'
  ],
  [
    'id'        => 102,
    'tuteeName' => 'Bea Gomez',
    'topic'     => 'Derivatives and Chain Rule',
    'date'      => '2026-05-06T10:00',
    'message'   => 'Need a quick review for our MATH 17 long exam next week. Thank you!'
  ]
];

echo json_encode(['requests' => $pending_requests]);