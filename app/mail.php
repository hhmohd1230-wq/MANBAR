<?php
/**
 * Outgoing email over SMTP (Gmail, Outlook, Brevo, the university's server…) with no extra libraries.
 * Configure 'mail' in config/config.local.php. Without it (and with show_codes_on_screen on, for testing), emails are written to storage/mail.log.
 */

function mail_ready(): bool
{
    $m = cfg('mail') ?? [];
    return !empty($m['host']) && !empty($m['from']);
}

/** Send an email. Returns 'sent' or 'logged' (dev fallback). Throws on failure. */
function send_mail(string $to, string $subject, string $html, string $text): string
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Invalid email address.');
    if (!mail_ready()) {
        if (!cfg('show_codes_on_screen')) throw new RuntimeException('Email is not set up on this server yet. Please contact the MANBAR administrators.');
        $dir = __DIR__ . '/../storage';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        @file_put_contents($dir . '/mail.log', '[' . date('Y-m-d H:i:s') . "] To: $to\nSubject: $subject\n\n$text\n" . str_repeat('-', 60) . "\n", FILE_APPEND | LOCK_EX);
        return 'logged';
    }
    smtp_send(cfg('mail'), $to, $subject, $html, $text);
    return 'sent';
}

function smtp_send(array $c, string $to, string $subject, string $html, string $text): void
{
    $host = (string) $c['host'];
    $port = (int) ($c['port'] ?? 587);
    $secure = strtolower((string) ($c['secure'] ?? ($port === 465 ? 'ssl' : 'tls')));
    $from = (string) $c['from'];
    $fromName = (string) ($c['from_name'] ?? 'MANBAR');

    $ctx = stream_context_create(['ssl' => array_filter([
        'verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host,
        'cafile' => ini_get('openssl.cafile') ?: null,
    ], fn($v) => $v !== null)]);
    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) throw new RuntimeException("Could not reach the mail server ($errstr).");
    stream_set_timeout($fp, 20);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;   // last line of a (multi-line) reply
        }
        return $data;
    };
    $cmd = function (?string $line, array $ok) use ($fp, $read): string {
        if ($line !== null) fwrite($fp, $line . "\r\n");
        $r = $read();
        $code = (int) substr($r, 0, 3);
        if (!in_array($code, $ok, true)) {
            $safe = preg_replace('/\s+/', ' ', trim($r));
            if ($code === 535 || $code === 534) throw new RuntimeException('The mail server rejected the login. For Gmail, use a 16-letter App password (not your normal password) and check the address in "user". (' . mb_substr($safe, 0, 120) . ')');
            throw new RuntimeException('The mail server refused the message: ' . mb_substr($safe, 0, 160));
        }
        return $r;
    };

    try {
        $cmd(null, [220]);
        $ehlo = 'EHLO manbar.local';
        $cmd($ehlo, [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            $method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) $method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            if (!stream_socket_enable_crypto($fp, true, $method)) throw new RuntimeException('Could not start a secure connection with the mail server.');
            $cmd($ehlo, [250]);
        }
        if (!empty($c['user'])) {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode((string) $c['user']), [334]);
            $cmd(base64_encode((string) ($c['pass'] ?? '')), [235]);
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        $cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $cmd('DATA', [354]);

        $b = 'b' . bin2hex(random_bytes(12));
        $enc = fn(string $s) => '=?UTF-8?B?' . base64_encode($s) . '?=';
        $domain = substr(strrchr($from, '@'), 1) ?: 'manbar.local';
        $msg = implode("\r\n", [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $enc($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $enc($subject),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $b . '"',
            '',
            '--' . $b,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode($text), 76, "\r\n")),
            '--' . $b,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode($html), 76, "\r\n")),
            '--' . $b . '--',
        ]);
        $cmd($msg . "\r\n.", [250]);
        $cmd('QUIT', [221, 250]);
    } finally {
        fclose($fp);
    }
}

/** The sign-in code email (HTML with inline styles for email clients, plus plain text). */
function login_code_email(string $code, string $email, int $minutes): array
{
    $spaced = substr($code, 0, 3) . ' ' . substr($code, 3);
    $text = "Your MANBAR sign-in code is $spaced\n\nIt expires in $minutes minutes. Enter it on the sign-in page to continue as $email.\n\nIf you didn't ask for this code, you can ignore this email; nobody can sign in without it.\n\nMANBAR · منبر, the student platform of Al Ain University";
    $html = '<!doctype html><html><body style="margin:0;background:#f2f6ef;font-family:Segoe UI,Arial,sans-serif;color:#0c2117">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:16px;overflow:hidden">'
        . '<tr><td style="background:#050806;padding:22px 28px;font-size:20px;font-weight:800;letter-spacing:2px;color:#ffffff">MANBAR <span style="color:#c4f53a">·</span> <span style="font-weight:600;letter-spacing:0;color:#96a89c">منبر</span></td></tr>'
        . '<tr><td style="padding:30px 28px 8px;font-size:16px;line-height:1.5">Your sign-in code is</td></tr>'
        . '<tr><td style="padding:4px 28px 18px"><div style="display:inline-block;background:#0b3321;color:#c4f53a;font:700 34px/1 Consolas,Menlo,monospace;letter-spacing:6px;padding:16px 22px;border-radius:12px">' . htmlspecialchars($spaced) . '</div></td></tr>'
        . '<tr><td style="padding:0 28px 26px;font-size:14px;line-height:1.6;color:#4e6658">It expires in ' . $minutes . ' minutes. Enter it on the sign-in page to continue as <b style="color:#0c2117">' . htmlspecialchars($email) . '</b>.<br><br>If you didn’t ask for this code, you can ignore this email. Nobody can sign in without it.</td></tr>'
        . '<tr><td style="padding:16px 28px;background:#f2f6ef;font-size:12px;color:#4e6658">MANBAR, the student platform of Al Ain University</td></tr>'
        . '</table></td></tr></table></body></html>';
    return [$html, $text];
}
