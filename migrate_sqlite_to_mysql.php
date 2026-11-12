<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$sourcePath = getenv('SQLITE_SOURCE') ?: __DIR__ . '/school.sqlite';
if (!is_file($sourcePath)) {
    throw new RuntimeException("SQLite source database not found: {$sourcePath}");
}

$source = new PDO('sqlite:' . $sourcePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$target = db();

$tables = [
    'sessions',
    'classes',
    'subjects',
    'students',
    'teachers',
    'terms',
    'class_arms',
    'subject_classes',
    'enrollments',
    'teacher_subjects',
    'users',
    'class_arm_subjects',
    'subject_exemptions',
    'grading_components',
    'results',
    'result_anomalies',
    'grading_bands',
    'settings',
    'term_reports',
];

foreach ($tables as $table) {
    try {
        $count = (int) $target->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    } catch (PDOException $e) {
        throw new RuntimeException(
            "MySQL table `$table` is unavailable. Apply schema.sql before importing.",
            0,
            $e
        );
    }
    if ($count !== 0) {
        throw new RuntimeException(
            "MySQL table `$table` is not empty. Import only into an empty MySQL database."
        );
    }
}

$target->beginTransaction();
$imported = [];
try {
    foreach ($tables as $table) {
        $exists = $source->prepare(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?"
        );
        $exists->execute([$table]);
        if ($exists->fetchColumn() === false) {
            continue;
        }

        $columns = array_column(
            $source->query("PRAGMA table_info(`$table`)")->fetchAll(),
            'name'
        );
        if (!$columns) {
            continue;
        }

        $quotedColumns = implode(', ', array_map(
            static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`',
            $columns
        ));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $insert = $target->prepare(
            "INSERT INTO `$table` ($quotedColumns) VALUES ($placeholders)"
        );

        $rows = $source->query("SELECT * FROM `$table`");
        $count = 0;
        while (($row = $rows->fetch()) !== false) {
            $insert->execute(array_values($row));
            $count++;
        }
        $imported[$table] = $count;
    }
    $target->commit();
} catch (Throwable $e) {
    if ($target->inTransaction()) {
        $target->rollBack();
    }
    throw new RuntimeException('SQLite-to-MySQL import failed; no rows were committed.', 0, $e);
}

echo "Import complete from {$sourcePath}:\n";
foreach ($imported as $table => $count) {
    echo "  {$table}: {$count} row(s)\n";
}
