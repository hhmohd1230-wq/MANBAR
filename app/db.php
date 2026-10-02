<?php
/** Thin PDO layer. Works with MySQL/MariaDB (XAMPP) and SQLite. */

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = cfg('db');
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if ($c['driver'] === 'sqlite') {
        $pdo = new PDO('sqlite:' . $c['sqlite'], null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $opts);
        $pdo->exec("SET time_zone = '+04:00'");
    }
    return $pdo;
}

function db_driver(): string { return cfg('db')['driver']; }

function qall(string $sql, array $p = []): array
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

function qrow(string $sql, array $p = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($p);
    $r = $st->fetch();
    return $r === false ? null : $r;
}

function qval(string $sql, array $p = [])
{
    $st = db()->prepare($sql);
    $st->execute($p);
    $r = $st->fetchColumn();
    return $r === false ? null : $r;
}

function qexec(string $sql, array $p = []): int
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->rowCount();
}

/** Tables that have no created_at column (so we must not stamp them). */
const NO_STAMP_TABLES = ['settings', 'lessons', 'user_skills', 'badges', 'project_members'];

function insert(string $table, array $data): int
{
    if (!in_array($table, NO_STAMP_TABLES, true) && !array_key_exists('created_at', $data)) {
        $data['created_at'] = now();
    }
    $cols = array_keys($data);
    $sql = "INSERT INTO $table (" . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
    qexec($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function update(string $table, array $data, string $where, array $wp = []): int
{
    $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
    return qexec("UPDATE $table SET $set WHERE $where", [...array_values($data), ...$wp]);
}

function setting(string $k, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        try { $cache = array_column(qall('SELECT k, v FROM settings'), 'v', 'k'); } catch (Throwable) { $cache = []; }
    }
    return $cache[$k] ?? $default;
}

function set_setting(string $k, string $v): void
{
    if (qval('SELECT COUNT(*) FROM settings WHERE k = ?', [$k])) {
        qexec('UPDATE settings SET v = ? WHERE k = ?', [$v, $k]);
    } else {
        insert('settings', ['k' => $k, 'v' => $v]);
    }
}

function sql_rand(): string { return db_driver() === 'sqlite' ? 'RANDOM()' : 'RAND()'; }

/** Build "?, ?, ?" for IN() lists. */
function in_ph(array $a): string { return implode(',', array_fill(0, max(1, count($a)), '?')); }
