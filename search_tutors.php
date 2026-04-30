<?php
/**
 * GET /api/search_tutors.php?subject=CMSC+11&subject=MATH+17
 *
 * Returns tutors matching ANY of the provided subject codes.
 * In production, replace mock data with real DB queries.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ---------------------------------------------------------------------------
// MOCK DATA — replace with DB query (PDO recommended)
// ---------------------------------------------------------------------------
$all_tutors = [
  [
    'id'       => 1,
    'name'     => 'Alex Reyes',
    'degree'   => 'Computer Science Junior',
    'courses'  => ['CMSC 11', 'CMSC 12', 'CMSC 21'],
    'rating'   => 4.9,
    'reviews'  => 34,
    'initials' => 'AR',
    'color'    => 'linear-gradient(135deg, #4ab3c3, #7c6fc4)',
    'avatar'   => null,
  ],
  [
    'id'       => 2,
    'name'     => 'Mia Santos',
    'degree'   => 'Mathematics Sophomore',
    'courses'  => ['MATH 17', 'MATH 55', 'PHYS 71'],
    'rating'   => 4.8,
    'reviews'  => 21,
    'initials' => 'MS',
    'color'    => 'linear-gradient(135deg, #e07060, #f5a623)',
    'avatar'   => null,
  ],
  [
    'id'       => 3,
    'name'     => 'Luis Tan',
    'degree'   => 'Computer Science Senior',
    'courses'  => ['CMSC 22', 'CMSC 21', 'CMSC 12'],
    'rating'   => 5.0,
    'reviews'  => 58,
    'initials' => 'LT',
    'color'    => 'linear-gradient(135deg, #7c6fc4, #4ab3c3)',
    'avatar'   => null,
  ],
  [
    'id'       => 4,
    'name'     => 'Cara Lim',
    'degree'   => 'Physics Junior',
    'courses'  => ['PHYS 71', 'MATH 17'],
    'rating'   => 4.7,
    'reviews'  => 15,
    'initials' => 'CL',
    'color'    => 'linear-gradient(135deg, #3aa580, #4ab3c3)',
    'avatar'   => null,
  ],
  [
    'id'       => 5,
    'name'     => 'Diego Cruz',
    'degree'   => 'Computer Science Sophomore',
    'courses'  => ['CMSC 11', 'MATH 17'],
    'rating'   => 4.6,
    'reviews'  => 9,
    'initials' => 'DC',
    'color'    => 'linear-gradient(135deg, #e07060, #7c6fc4)',
    'avatar'   => null,
  ],
  [
    'id'       => 6,
    'name'     => 'Nina Flores',
    'degree'   => 'Applied Math Senior',
    'courses'  => ['MATH 55', 'MATH 17', 'CMSC 21'],
    'rating'   => 4.9,
    'reviews'  => 42,
    'initials' => 'NF',
    'color'    => 'linear-gradient(135deg, #f5a623, #e07060)',
    'avatar'   => null,
  ],
];

// ---------------------------------------------------------------------------
// Read ?subject= params (can be repeated)
// ---------------------------------------------------------------------------
$requested = [];

if (isset($_GET['subject'])) {
  $raw = $_GET['subject'];
  // PHP collapses duplicate keys; use query string directly for multiple values
  $requested = array_map(
    fn($s) => strtoupper(trim($s)),
    is_array($raw) ? $raw : [$raw]
  );
}

// Re-parse query string to support ?subject=X&subject=Y
parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
if (isset($qs['subject'])) {
  $requested = array_map(
    fn($s) => strtoupper(trim($s)),
    is_array($qs['subject']) ? $qs['subject'] : [$qs['subject']]
  );
}

// ---------------------------------------------------------------------------
// Filter
// ---------------------------------------------------------------------------
if (empty($requested)) {
  // No filter — return all
  echo json_encode(['tutors' => $all_tutors]);
  exit;
}

$matched = array_values(array_filter($all_tutors, function ($tutor) use ($requested) {
  // Tutor must teach at least one of the requested subjects
  foreach ($requested as $subj) {
    if (in_array($subj, $tutor['courses'], true)) {
      return true;
    }
  }
  return false;
}));

echo json_encode([
  'tutors'   => $matched,
  'filters'  => $requested,
  'total'    => count($matched),
]);
