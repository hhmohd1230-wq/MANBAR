<?php
/**
 * MANBAR configuration.
 * Do NOT put secrets here. Copy config/config.local.sample.php to config/config.local.php
 * (git-ignored) and override what you need, or use environment variables.
 */
$env = static fn(string $k, $d = null) => getenv($k) !== false ? getenv($k) : $d;

$config = [
    'app_name'  => 'MANBAR',
    'env'       => $env('MANBAR_ENV', 'local'),          // local | production
    'timezone'  => 'Asia/Dubai',
    'base_url'  => $env('MANBAR_BASE_URL', ''),          // optional absolute URL, e.g. https://manbar.example.com

    // Database: XAMPP defaults (MySQL/MariaDB). 'sqlite' is only for quick local tests.
    'db' => [
        'driver'   => $env('MANBAR_DB_DRIVER', 'mysql'),
        'host'     => $env('MANBAR_DB_HOST', '127.0.0.1'),
        'port'     => $env('MANBAR_DB_PORT', '3306'),
        'name'     => $env('MANBAR_DB_NAME', 'manbar'),
        'user'     => $env('MANBAR_DB_USER', 'root'),
        'pass'     => $env('MANBAR_DB_PASS', ''),
        'sqlite'   => __DIR__ . '/../storage/manbar.sqlite',
    ],

    // Google Sign-In (https://console.cloud.google.com -> APIs & Services -> Credentials -> OAuth client ID, "Web application")
    'google_client_id' => $env('GOOGLE_CLIENT_ID', ''),

    // Demo login (no Google needed). MUST be false in production.
    'dev_login' => $env('MANBAR_DEV_LOGIN', '1') === '1',

    // These emails become administrators on first login.
    'admin_emails' => ['202020280@aau.ac.ae'],

    // Optional: real AI (Claude API). Leave empty to use the built-in rule-based assistant.
    'anthropic_api_key' => $env('ANTHROPIC_API_KEY', ''),
    'anthropic_model'   => $env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),

    'upload_max_mb' => 4,
    'page_size'     => 10,
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}
return $config;
