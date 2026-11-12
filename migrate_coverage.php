<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$pdo = db();

$pdo->exec("
CREATE TABLE IF NOT EXISTS class_arm_subjects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    class_arm_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_class_arm_subjects_arm_subject (class_arm_id, subject_id),
    KEY idx_cas_arm (class_arm_id),
    CONSTRAINT fk_cas_class_arm FOREIGN KEY (class_arm_id) REFERENCES class_arms(id) ON DELETE CASCADE,
    CONSTRAINT fk_cas_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "class_arm_subjects ready.\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS subject_exemptions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    reason VARCHAR(500) NULL,
    exempted_by INT UNSIGNED NULL,
    exempted_at DATETIME NOT NULL,
    UNIQUE KEY uq_subject_exemptions_enrollment_subject (enrollment_id, subject_id),
    KEY idx_exemptions_enr (enrollment_id),
    CONSTRAINT fk_exemptions_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    CONSTRAINT fk_exemptions_subject FOREIGN KEY (subject_id) REFERENCES subjects(id),
    CONSTRAINT fk_exemptions_user FOREIGN KEY (exempted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "subject_exemptions ready.\n";

$columns = [
    'status' => "VARCHAR(16) NOT NULL DEFAULT 'active'",
    'status_changed_at' => 'DATETIME NULL',
    'status_reason' => 'VARCHAR(500) NULL',
];
$addedStatus = false;
foreach ($columns as $column => $definition) {
    if (!db_column_exists('enrollments', $column)) {
        $pdo->exec("ALTER TABLE enrollments ADD COLUMN `$column` $definition");
        if ($column === 'status') $addedStatus = true;
    }
}
echo $addedStatus
    ? "enrollments.status added.\n"
    : "enrollments.status already exists.\n";
echo "Migration complete.\n";
