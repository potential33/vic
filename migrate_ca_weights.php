<?php
// /app/migrate_ca_weights.php
declare(strict_types=1);
require __DIR__ . '/db.php';
$host = getenv('DB_HOST')     ?: 'localhost';
$name = getenv('DB_NAME')     ?: 'school_db';
$user = getenv('DB_USER')     ?: 'school_app';
$pass = getenv('DB_PASSWORD') ?: '';          // note: DB_PASSWORD, not DB_PASS

$pdo = new PDO(
    "mysql:host=$host;dbname=$name;charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$pdo->beginTransaction();

try {
    // Keep only CA1=10, CA2=20, and EXAM=70 active.
    q("UPDATE grading_components SET active = 0");

    foreach ([
        'CA1' => [10, 1],
        'CA2' => [20, 2],
        'EXAM' => [70, 3],
    ] as $name => [$maxScore, $sortOrder]) {
        $ids = array_column(
            fetch_all(
                "SELECT id FROM grading_components WHERE UPPER(name) = ? ORDER BY id",
                [$name]
            ),
            'id'
        );

        if (!$ids) {
            q(
                "INSERT INTO grading_components (name, max_score, sort_order, active)
                 VALUES (?, ?, ?, 1)",
                [$name, $maxScore, $sortOrder]
            );
            continue;
        }

        $id = (int) array_shift($ids);
        foreach ($ids as $duplicateId) {
            q("DELETE FROM grading_components WHERE id = ?", [$duplicateId]);
        }
        q(
            "UPDATE grading_components
             SET name = ?, max_score = ?, sort_order = ?, active = 1
             WHERE id = ?",
            [$name, $maxScore, $sortOrder, $id]
        );
    }

    // Recompute totals and clear legacy CA3 values for every result.
    $results = fetch_all("SELECT id, ca1, ca2, exam FROM results");
    $updated = 0;
    foreach ($results as $r) {
        $values = [$r['ca1'], $r['ca2'], $r['exam']];
        $total = 0.0;
        $any = false;
        foreach ($values as $value) {
            if ($value !== null) {
                $total += (float) $value;
                $any = true;
            }
        }
        q(
            "UPDATE results SET total = ?, ca3 = NULL WHERE id = ?",
            [$any ? $total : null, $r['id']]
        );
        $updated++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo "Active components:\n  CA1 (max 10)\n  CA2 (max 20)\n  EXAM (max 70)\n";
echo "Recomputed totals for {$updated} result(s).\n";
echo "Done.\n";