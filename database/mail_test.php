<?php
/**
 * Check the email settings in config/config.local.php by sending one test message.
 *   C:\xampp\php\php.exe database\mail_test.php you@example.com
 */
if (PHP_SAPI !== 'cli') exit("Run this from the command line.\n");
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/manbar/public/index.php';
require __DIR__ . '/../app/bootstrap.php';

$to = $argv[1] ?? '';
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) exit("Usage: php database/mail_test.php you@example.com\n");
$m = cfg('mail') ?? [];
if (!mail_ready()) exit("Email is not set up yet: fill 'host' and 'from' in the 'mail' block of config/config.local.php.\n");
echo "Server : {$m['host']}:{$m['port']} ({$m['secure']})\nLogin  : " . ($m['user'] ?: '(none)') . "\nFrom   : {$m['from']}\nTo     : $to\n\nSending…\n";
try {
    [$html, $text] = login_code_email('123456', $to, LOGIN_CODE_MINUTES);
    smtp_send($m, $to, 'MANBAR test email: your settings work', $html, $text);
    echo "SENT. Check the inbox of $to (and the spam folder). Sign-in codes will now be emailed for real.\n";
} catch (Throwable $ex) {
    echo 'FAILED: ' . $ex->getMessage() . "\n";
    exit(1);
}
