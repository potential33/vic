<?php
// /app/db.php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
    $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

    if ($name === false || $name === '' || $user === false || $user === '') {
        throw new RuntimeException('Set DB_NAME and DB_USER in the environment before connecting.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $host,
        $port,
        $name,
        $charset
    );
    $pdo = new PDO($dsn, $user, $password === false ? '' : $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

/** Run a query and return the statement */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** Fetch all rows */
function fetch_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** Fetch a single row or null */
function fetch_one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** Fetch a single scalar value */
function fetch_val(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** Insert helper: returns last insert id */
function insert(string $sql, array $params = []): int
{
    q($sql, $params);
    return (int) db()->lastInsertId();
}

function db_is_duplicate_key(PDOException $exception): bool
{
    return isset($exception->errorInfo[1])
        && (int) $exception->errorInfo[1] === 1062;
}

function db_column_exists(string $table, string $column): bool
{
    return (bool) fetch_val(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column]
    );
}

function db_index_exists(string $table, string $index): bool
{
    return (bool) fetch_val(
        'SELECT 1 FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
        [$table, $index]
    );
}

function db_create_index(string $table, string $index, string $columns): void
{
    foreach ([$table, $index] as $identifier) {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new InvalidArgumentException('Invalid SQL identifier.');
        }
    }
    if (!preg_match('/^[A-Za-z0-9_`, ]+$/', $columns)) {
        throw new InvalidArgumentException('Invalid index column list.');
    }
    if (!db_index_exists($table, $index)) {
        db()->exec("CREATE INDEX `$index` ON `$table` ($columns)");
    }
}