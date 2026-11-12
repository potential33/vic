<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$pdo = db();

if (!db_column_exists('users', 'student_id')) {
    $pdo->exec('ALTER TABLE users ADD COLUMN student_id INT UNSIGNED NULL');
    db_create_index('users', 'idx_users_student', 'student_id');
    $pdo->exec(
        'ALTER TABLE users ADD CONSTRAINT fk_users_student
         FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL'
    );
    echo "Added users.student_id.\n";
} else {
    echo "users.student_id already exists.\n";
}

if (!db_column_exists('users', 'must_change_password')) {
    $pdo->exec(
        'ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0'
    );
    echo "Added users.must_change_password.\n";
} else {
    echo "users.must_change_password already exists.\n";
}

if (!db_column_exists('terms', 'next_resumption_date')) {
    $pdo->exec('ALTER TABLE terms ADD COLUMN next_resumption_date DATE NULL');
    echo "Added terms.next_resumption_date.\n";
} else {
    echo "terms.next_resumption_date already exists.\n";
}

$pdo->exec("
CREATE TABLE IF NOT EXISTS term_reports (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    class_teacher_remark VARCHAR(500) NULL,
    principal_remark VARCHAR(500) NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    UNIQUE KEY uq_term_reports_enrollment_term (enrollment_id, term_id),
    CONSTRAINT fk_term_reports_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    CONSTRAINT fk_term_reports_term FOREIGN KEY (term_id) REFERENCES terms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "term_reports ready.\n";
echo "Migration complete.\n";
