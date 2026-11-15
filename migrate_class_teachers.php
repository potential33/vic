<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$exists = fetch_val(
    "SELECT 1 FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'class_teacher_assignments'"
);
if ($exists) {
    echo "class_teacher_assignments already exists.\n";
    exit(0);
}

db()->exec("
CREATE TABLE class_teacher_assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    class_arm_id INT UNSIGNED NOT NULL,
    session_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_class_teacher_arm_session (class_arm_id, session_id),
    KEY idx_class_teacher_teacher_session (teacher_id, session_id),
    CONSTRAINT fk_class_teacher_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_teacher_class_arm FOREIGN KEY (class_arm_id) REFERENCES class_arms(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_teacher_session FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "class_teacher_assignments created.\n";
