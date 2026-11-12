<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$pdo = db();

$pdo->exec("
CREATE TABLE IF NOT EXISTS grading_components (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) NOT NULL UNIQUE,
    max_score DOUBLE NOT NULL,
    sort_order INT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("
CREATE TABLE IF NOT EXISTS results (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    ca1 DOUBLE NULL,
    ca2 DOUBLE NULL,
    ca3 DOUBLE NULL,
    exam DOUBLE NULL,
    total DOUBLE NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'draft',
    entered_by INT UNSIGNED NULL,
    entered_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_results_enrollment_subject_term (enrollment_id, subject_id, term_id),
    KEY idx_results_enrollment_term (enrollment_id, term_id),
    KEY idx_results_subject_term (subject_id, term_id),
    KEY idx_results_status (status),
    CONSTRAINT fk_results_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    CONSTRAINT fk_results_subject FOREIGN KEY (subject_id) REFERENCES subjects(id),
    CONSTRAINT fk_results_term FOREIGN KEY (term_id) REFERENCES terms(id),
    CONSTRAINT fk_results_teacher FOREIGN KEY (entered_by) REFERENCES teachers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("
CREATE TABLE IF NOT EXISTS result_anomalies (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NULL,
    kind VARCHAR(32) NOT NULL,
    detail VARCHAR(500) NULL,
    flagged_at DATETIME NOT NULL,
    resolved TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_anomalies_enrollment_term_kind_subject (enrollment_id, term_id, kind, subject_id),
    KEY idx_anomalies_term (term_id, resolved),
    CONSTRAINT fk_anomalies_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    CONSTRAINT fk_anomalies_term FOREIGN KEY (term_id) REFERENCES terms(id),
    CONSTRAINT fk_anomalies_subject FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$n = (int) fetch_val("SELECT COUNT(*) FROM grading_components");
if ($n === 0) {
    foreach ([
        ['CA1', 10, 1],
        ['CA2', 10, 2],
        ['CA3', 10, 3],
        ['EXAM', 70, 4],
    ] as [$name, $max, $order]) {
        q("INSERT INTO grading_components (name, max_score, sort_order, active)
           VALUES (?, ?, ?, 1)", [$name, $max, $order]);
    }
    echo "Seeded grading_components.\n";
} else {
    echo "grading_components already has {$n} rows — skipped seed.\n";
}

echo "Migration complete.\n";
