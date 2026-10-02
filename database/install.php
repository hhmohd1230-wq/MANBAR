<?php
/**
 * Installer.   php database/install.php [--fresh] [--demo]
 *   --fresh  drop everything first      --demo  add demo students, posts, projects, courses
 * XAMPP (Windows):  C:\xampp\php\php.exe database\install.php --demo
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Run from the command line.'); }
require_once __DIR__ . '/../app/helpers.php';
require __DIR__ . '/lib.php';

$fresh = in_array('--fresh', $argv, true);
$demo  = in_array('--demo', $argv, true);
$c = cfg('db');
$driver = $c['driver'];

if ($driver === 'sqlite') {
    @mkdir(dirname($c['sqlite']), 0777, true);
    if ($fresh && is_file($c['sqlite'])) unlink($c['sqlite']);
    $pdo = new PDO('sqlite:' . $c['sqlite'], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('PRAGMA foreign_keys = ON');
} else {
    $root = new PDO("mysql:host={$c['host']};port={$c['port']};charset=utf8mb4", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ($fresh) $root->exec("DROP DATABASE IF EXISTS `{$c['name']}`");
    $root->exec("CREATE DATABASE IF NOT EXISTS `{$c['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

$exists = $driver === 'sqlite'
    ? (bool) $pdo->query("SELECT name FROM sqlite_master WHERE name = 'users'")->fetch()
    : (bool) $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
if ($exists) {
    echo "Tables already exist. Use --fresh to rebuild (this deletes all data).\n";
} else {
    foreach (schema_statements($driver) as $s) $pdo->exec($s);
    foreach (seed_statements() as $s) $pdo->exec($s);
    echo "✔ Database created and seeded ($driver).\n";
}

if ($demo) {
    require_once __DIR__ . '/../app/bootstrap.php';
    require __DIR__ . '/demo.php';
    seed_demo();
    echo "✔ Demo data added.\n";
}
echo "Done. Start the site:  php -S localhost:8000 -t public public/index.php\n";
