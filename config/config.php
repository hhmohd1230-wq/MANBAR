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

    // Outgoing email (sign-in codes). Any SMTP server works, e.g. Gmail: host smtp.gmail.com, port 587, secure tls,
    // user = the Gmail address, pass = a Gmail "App password". Until 'host' and 'from' are set, codes go to storage/mail.log (dev only).
    'mail' => [
        'host'      => $env('MANBAR_MAIL_HOST', ''),
        'port'      => (int) $env('MANBAR_MAIL_PORT', 587),
        'secure'    => $env('MANBAR_MAIL_SECURE', 'tls'),   // tls (port 587) | ssl (port 465)
        'user'      => $env('MANBAR_MAIL_USER', ''),
        'pass'      => $env('MANBAR_MAIL_PASS', ''),
        'from'      => $env('MANBAR_MAIL_FROM', ''),
        'from_name' => 'MANBAR',
    ],

    // Testing only: while email is not set up, show sign-in codes on screen (and in storage/mail.log).
    // Anyone could then sign in as anyone, so keep this false on a real server.
    'show_codes_on_screen' => $env('MANBAR_SHOW_CODES', '0') === '1',

    // Admin console login: type this username on the sign-in page, then the password.
    // Store only a hash: C:\xampp\php\php.exe -r "echo password_hash('your password', PASSWORD_DEFAULT);"
    'admin_login' => ['username' => '', 'password_hash' => ''],

    // Old demo login (sign in as any account without checks). MUST stay false outside local testing.
    'dev_login' => $env('MANBAR_DEV_LOGIN', '0') === '1',

    // These emails become administrators on first login.
    'admin_emails' => ['202020280@aau.ac.ae'],

    // Optional: real language AI. OpenAI is tried first, then Claude, then the local rules.
    'openai_api_key'    => $env('OPENAI_API_KEY', ''),
    'openai_model'      => $env('OPENAI_MODEL', 'gpt-6.1-sol'),
    'anthropic_api_key' => $env('ANTHROPIC_API_KEY', ''),
    'anthropic_model'   => $env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),

    'upload_max_mb' => 4,
    'message_upload_max_mb' => 12,
    'page_size'     => 10,
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}
return $config;
