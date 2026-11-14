<?php
// /app/seed_reference.php
//
// Inserts reference data the schema does not provide:
//   sessions, terms, classes, class_arms, subjects,
//   subject_classes, grading_components, grading_bands.
//
// Idempotent: existing rows are skipped, so it is safe to re-run.
// Does NOT create users, students, teachers, enrollments or results.
//
// Run AFTER importing schema.sql and setting DB_* env vars.
// Edit the arrays below to match your school before running in production.
declare(strict_types=1);
require __DIR__ . '/db.php';

// ---------------------------------------------------------------------------
// 0. Sanity check: schema must already be imported.
// ---------------------------------------------------------------------------
if (!fetch_val("SHOW TABLES LIKE 'sessions'")) {
    fwrite(STDERR, "ERROR: 'sessions' table not found. Import schema.sql first.\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// 1. Edit these arrays to match your school.
// ---------------------------------------------------------------------------

// Sessions.
$sessions = ['2025/2026', '2026/2027'];

// Terms per session: [term_name, next_resumption_date|null].
$termsBySession = [
    '2025/2026' => [
        ['First Term',  '2025-09-15'],
        ['Second Term', '2026-01-12'],
        ['Third Term',  '2026-04-27'],
    ],
    '2026/2027' => [
        ['First Term',  '2026-09-14'],
        ['Second Term', '2027-01-11'],
        ['Third Term',  '2027-04-26'],
    ],
];

// Classes: [name, section]. section = 'junior' | 'senior'.
$classes = [
    ['JSS1', 'junior'],
    ['JSS2', 'junior'],
    ['JSS3', 'junior'],
    ['SS1',  'senior'],
    ['SS2',  'senior'],
    ['SS3',  'senior'],
];

// Arms applied to every class above.
$armsPerClass = ['A', 'B'];

// Subjects: [name, code, section]. section = 'junior' | 'senior' | 'both'.
$subjects = [
    ['English Language',    'ENG', 'both'],
    ['Mathematics',         'MTH', 'both'],
    ['Basic Science',       'BSC', 'junior'],
    ['Social Studies',      'SOS', 'junior'],
    ['Civic Education',     'CIV', 'both'],
    ['Computer Studies',    'CMP', 'both'],
    ['Biology',             'BIO', 'senior'],
    ['Chemistry',           'CHM', 'senior'],
    ['Physics',             'PHY', 'senior'],
    ['Further Mathematics', 'FMT', 'senior'],
    ['Commerce',            'COM', 'senior'],
    ['Economics',           'ECO', 'senior'],
    ['Government',          'GOV', 'senior'],
    ['Literature in English','LIT','senior'],
    ['Digital Technology',    'DGT', 'both'],
    ['Christian Religious Studies', 'CRS', 'both'],
    ['Agricultural Science', 'AGR', 'both'],
    ['Home Economics',       'HME', 'both'],
];

// Grading components: [name, max_score, sort_order].
$gradingComponents = [
    ['CA1',  10, 1],
    ['CA2',  20, 2],
    ['EXAM', 70, 3],
];

// Grading bands: [name, min_score, label, sort_order].
$gradingBands = [
    ['A', 70, 'Excellent', 1],
    ['B', 60, 'Very Good', 2],
    ['C', 50, 'Good',      3],
    ['D', 45, 'Pass',      4],
    ['E', 40, 'Weak Pass', 5],
    ['F',  0, 'Fail',      6],
];

// ---------------------------------------------------------------------------
// 2. Insert (idempotent).
// ---------------------------------------------------------------------------
$counts = [
    'sessions'           => 0,
    'terms'              => 0,
    'classes'            => 0,
    'class_arms'         => 0,
    'subjects'           => 0,
    'subject_classes'    => 0,
    'grading_components' => 0,
    'grading_bands'      => 0,
];

// --- sessions ---
foreach ($sessions as $name) {
    if (!fetch_val("SELECT id FROM sessions WHERE name = ?", [$name])) {
        q("INSERT INTO sessions (name) VALUES (?)", [$name]);
        $counts['sessions']++;
    }
}

// --- terms ---
foreach ($termsBySession as $sessionName => $rows) {
    $sessionId = fetch_val("SELECT id FROM sessions WHERE name = ?", [$sessionName]);
    if (!$sessionId) {
        fwrite(STDERR, "WARN: session '{$sessionName}' missing, skipping its terms.\n");
        continue;
    }
    foreach ($rows as [$termName, $nextDate]) {
        if (!fetch_val(
            "SELECT id FROM terms WHERE session_id = ? AND name = ?",
            [$sessionId, $termName]
        )) {
            q(
                "INSERT INTO terms (session_id, name, next_resumption_date) VALUES (?, ?, ?)",
                [$sessionId, $termName, $nextDate]
            );
            $counts['terms']++;
        }
    }
}

// --- classes ---
foreach ($classes as [$name, $section]) {
    if (!fetch_val("SELECT id FROM classes WHERE name = ?", [$name])) {
        q("INSERT INTO classes (name, section) VALUES (?, ?)", [$name, $section]);
        $counts['classes']++;
    }
}

// --- class_arms ---
foreach ($classes as [$className, $_]) {
    $classId = fetch_val("SELECT id FROM classes WHERE name = ?", [$className]);
    foreach ($armsPerClass as $arm) {
        if (!fetch_val(
            "SELECT id FROM class_arms WHERE class_id = ? AND arm = ?",
            [$classId, $arm]
        )) {
            q("INSERT INTO class_arms (class_id, arm) VALUES (?, ?)", [$classId, $arm]);
            $counts['class_arms']++;
        }
    }
}

// --- subjects ---
foreach ($subjects as [$name, $code, $section]) {
    if (!fetch_val("SELECT id FROM subjects WHERE name = ?", [$name])) {
        q(
            "INSERT INTO subjects (name, code, section) VALUES (?, ?, ?)",
            [$name, $code, $section]
        );
        $counts['subjects']++;
    }
}

// --- subject_classes (which subjects are offered in which classes) ---
foreach ($classes as [$className, $classSection]) {
    $classId = fetch_val("SELECT id FROM classes WHERE name = ?", [$className]);
    foreach ($subjects as [$subjectName, , $subjectSection]) {
        if ($subjectSection !== 'both' && $subjectSection !== $classSection) {
            continue;
        }
        $subjectId = fetch_val("SELECT id FROM subjects WHERE name = ?", [$subjectName]);
        if (!fetch_val(
            "SELECT id FROM subject_classes WHERE subject_id = ? AND class_id = ?",
            [$subjectId, $classId]
        )) {
            q(
                "INSERT INTO subject_classes (subject_id, class_id) VALUES (?, ?)",
                [$subjectId, $classId]
            );
            $counts['subject_classes']++;
        }
    }
}

// --- grading_components ---
foreach ($gradingComponents as [$name, $maxScore, $sortOrder]) {
    $componentId = fetch_val(
        "SELECT id FROM grading_components WHERE UPPER(name) = ? ORDER BY id LIMIT 1",
        [$name]
    );
    if ($componentId === null) {
        q(
            "INSERT INTO grading_components (name, max_score, sort_order, active)
             VALUES (?, ?, ?, 1)",
            [$name, $maxScore, $sortOrder]
        );
        $counts['grading_components']++;
    } else {
        q(
            "UPDATE grading_components
             SET name = ?, max_score = ?, sort_order = ?, active = 1
             WHERE id = ?",
            [$name, $maxScore, $sortOrder, $componentId]
        );
    }
}
q("UPDATE grading_components
   SET active = 0
   WHERE UPPER(name) NOT IN ('CA1', 'CA2', 'EXAM')");

// --- grading_bands ---
foreach ($gradingBands as [$name, $minScore, $label, $sortOrder]) {
    if (!fetch_val("SELECT id FROM grading_bands WHERE name = ?", [$name])) {
        q(
            "INSERT INTO grading_bands (name, min_score, label, sort_order)
             VALUES (?, ?, ?, ?)",
            [$name, $minScore, $label, $sortOrder]
        );
        $counts['grading_bands']++;
    }
}

// ---------------------------------------------------------------------------
// 3. Report.
// ---------------------------------------------------------------------------
echo "Reference data inserted (new rows only):\n";
foreach ($counts as $table => $n) {
    printf("  %-20s %d\n", $table, $n);
}