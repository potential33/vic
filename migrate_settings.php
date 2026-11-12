<?php
// /app/migrate_settings.php
declare(strict_types=1);
require __DIR__ . '/db.php';

$pdo = db();
$pdo->exec("
CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(191) NOT NULL PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$defaults = [
    'school_name'   => 'Your School Name',
    'school_motto'  => '',
    'school_address'=> '',
];
foreach ($defaults as $k => $v) {
    if (!fetch_val("SELECT 1 FROM settings WHERE `key` = ?", [$k])) {
        q("INSERT INTO settings (`key`, `value`) VALUES (?, ?)", [$k, $v]);
        echo "Seeded {$k}.\n";
    }
}
echo "settings ready.\n";