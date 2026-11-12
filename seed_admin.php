<?php
// /app/seed_admin.php
//
// Creates the predictable admin / admin123 account.
// Intended for development or first-time setup; change the password
// immediately after first login.
declare(strict_types=1);
require __DIR__ . '/db.php';

if (!fetch_val("SHOW TABLES LIKE 'users'")) {
    fwrite(STDERR, "ERROR: 'users' table not found. Import schema.sql first.\n");
    exit(1);
}

$existing = fetch_val("SELECT id FROM users WHERE username = 'admin'");
if ($existing) {
    echo "admin already exists (id={$existing}). Skipping.\n";
    exit(0);
}

$hash = password_hash('admin123', PASSWORD_DEFAULT);
q("INSERT INTO users (username, password_hash, role, teacher_id, active, created_at)
   VALUES (?, ?, 'admin', NULL, 1, ?)",
  ['admin', $hash, date('Y-m-d H:i:s')]);

echo "Created admin / admin123. Change it after first login.\n";