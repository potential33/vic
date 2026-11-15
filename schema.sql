-- School Manager schema for MySQL 8+.
-- Create the database first, then execute this file against it.

CREATE TABLE IF NOT EXISTS sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS terms (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    name VARCHAR(32) NOT NULL,
    next_resumption_date DATE NULL,
    UNIQUE KEY uq_terms_session_name (session_id, name),
    CONSTRAINT fk_terms_session FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) NOT NULL UNIQUE,
    section VARCHAR(16) NOT NULL,
    CONSTRAINT chk_classes_section CHECK (section IN ('junior', 'senior'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL UNIQUE,
    code VARCHAR(32) NULL UNIQUE,
    section VARCHAR(16) NOT NULL,
    CONSTRAINT chk_subjects_section CHECK (section IN ('junior', 'senior', 'both'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admission_no VARCHAR(64) NOT NULL UNIQUE,
    full_name VARCHAR(191) NOT NULL,
    gender CHAR(1) NULL,
    date_of_birth DATE NULL,
    CONSTRAINT chk_students_gender CHECK (gender IN ('M', 'F') OR gender IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teachers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(191) NOT NULL,
    email VARCHAR(254) NULL UNIQUE,
    user_id VARCHAR(100) NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS class_arms (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    arm VARCHAR(64) NOT NULL,
    UNIQUE KEY uq_class_arms_class_arm (class_id, arm),
    CONSTRAINT fk_class_arms_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subject_classes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    subject_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_subject_classes_subject_class (subject_id, class_id),
    CONSTRAINT fk_subject_classes_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_subject_classes_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrollments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    class_arm_id INT UNSIGNED NOT NULL,
    session_id INT UNSIGNED NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'active',
    status_changed_at DATETIME NULL,
    status_reason VARCHAR(500) NULL,
    UNIQUE KEY uq_enrollments_student_session (student_id, session_id),
    CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_class_arm FOREIGN KEY (class_arm_id) REFERENCES class_arms(id),
    CONSTRAINT fk_enrollments_session FOREIGN KEY (session_id) REFERENCES sessions(id),
    CONSTRAINT chk_enrollments_status CHECK (status IN ('active', 'inactive'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    class_arm_id INT UNSIGNED NOT NULL,
    session_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_teacher_subjects_assignment (teacher_id, subject_id, class_arm_id, session_id),
    CONSTRAINT fk_teacher_subjects_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_subjects_subject FOREIGN KEY (subject_id) REFERENCES subjects(id),
    CONSTRAINT fk_teacher_subjects_class_arm FOREIGN KEY (class_arm_id) REFERENCES class_arms(id),
    CONSTRAINT fk_teacher_subjects_session FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS class_teacher_assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    class_arm_id INT UNSIGNED NOT NULL,
    session_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_class_teacher_arm_session (class_arm_id, session_id),
    KEY idx_class_teacher_teacher_session (teacher_id, session_id),
    CONSTRAINT fk_class_teacher_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_teacher_class_arm FOREIGN KEY (class_arm_id) REFERENCES class_arms(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_teacher_session FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(16) NOT NULL,
    teacher_id INT UNSIGNED NULL,
    student_id INT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    KEY idx_users_teacher (teacher_id),
    KEY idx_users_student (student_id),
    CONSTRAINT fk_users_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    CONSTRAINT fk_users_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    CONSTRAINT chk_users_role CHECK (role IN ('admin', 'teacher', 'student'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS class_arm_subjects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    class_arm_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_class_arm_subjects_arm_subject (class_arm_id, subject_id),
    KEY idx_cas_arm (class_arm_id),
    CONSTRAINT fk_cas_class_arm FOREIGN KEY (class_arm_id) REFERENCES class_arms(id) ON DELETE CASCADE,
    CONSTRAINT fk_cas_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grading_components (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) NOT NULL UNIQUE,
    max_score DOUBLE NOT NULL,
    sort_order INT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_results_teacher FOREIGN KEY (entered_by) REFERENCES teachers(id),
    CONSTRAINT chk_results_status CHECK (status IN ('draft', 'submitted', 'approved'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_anomalies_subject FOREIGN KEY (subject_id) REFERENCES subjects(id),
    CONSTRAINT chk_anomalies_kind CHECK (kind IN ('all_empty', 'exam_missing_with_ca', 'over_max'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grading_bands (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) NOT NULL UNIQUE,
    min_score DOUBLE NOT NULL,
    label VARCHAR(100) NULL,
    sort_order INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(191) NOT NULL PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_term_reports_term FOREIGN KEY (term_id) REFERENCES terms(id),
    CONSTRAINT chk_term_reports_status CHECK (status IN ('draft', 'published'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER //
DROP TRIGGER IF EXISTS trg_teacher_subjects_check//
CREATE TRIGGER trg_teacher_subjects_check
BEFORE INSERT ON teacher_subjects
FOR EACH ROW
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM class_arms ca
        JOIN subject_classes sc ON sc.class_id = ca.class_id
        WHERE ca.id = NEW.class_arm_id
          AND sc.subject_id = NEW.subject_id
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Subject not offered in this class (see subject_classes).';
    END IF;
END//
DELIMITER ;
