<?php
require __DIR__ . '/../app/bootstrap.php';

start_session();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// Built-in server: let it serve real static files itself.
if (PHP_SAPI === 'cli-server') {
    $f = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($f) && !str_ends_with($f, '.php')) return false;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$base = base_path();
if ($base !== '' && str_starts_with($path, $base)) $path = substr($path, strlen($base));
$path = '/' . trim(rawurldecode($path), '/');
$method = $_SERVER['REQUEST_METHOD'];

try {
    if (!file_exists(cfg('db')['driver'] === 'sqlite' ? cfg('db')['sqlite'] : __FILE__)) {
        throw new RuntimeException('Database not installed yet. Run: php database/install.php');
    }
    if ($method === 'POST' && $path !== '/auth/google') verify_csrf();

    foreach (require __DIR__ . '/../app/routes.php' as [$m, $pattern, $handler]) {
        if ($m !== $method) continue;
        $re = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (preg_match($re, $path, $mm)) {
            $args = array_filter($mm, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler(...array_map(fn($v) => ctype_digit($v) ? (int) $v : $v, $args));
            exit;
        }
    }
    abort(404, 'We could not find that page.');
} catch (PDOException $ex) {
    http_response_code(500);
    $msg = cfg('env') === 'production' ? 'A database error occurred.' : $ex->getMessage();
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:system-ui;max-width:640px;margin:10vh auto;padding:0 20px"><h1>Database problem</h1><p>' . e($msg) . '</p><p>Is MySQL running in XAMPP and have you imported <code>database/manbar.sql</code> (or run <code>php database/install.php</code>)?</p>';
} catch (Throwable $ex) {
    http_response_code(500);
    if (wants_json()) json_out(['ok' => false, 'error' => cfg('env') === 'production' ? 'Server error' : $ex->getMessage()], 500);
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:system-ui;max-width:640px;margin:10vh auto;padding:0 20px"><h1>Something went wrong</h1><p>' . e(cfg('env') === 'production' ? 'Please try again.' : $ex->getMessage() . ' in ' . basename($ex->getFile()) . ':' . $ex->getLine()) . '</p>';
}
