<?php
/** Authentication: Google Sign-In (university accounts only), roster lookup, roles. */

function current_user(bool $refresh = false): ?array
{
    static $u = false;
    if ($u !== false && !$refresh) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $u = qrow('SELECT u.*, un.short_name AS uni_short, un.name AS uni_name FROM users u JOIN universities un ON un.id = u.university_id WHERE u.id = ?', [$_SESSION['uid']]);
        if (!$u || $u['status'] !== 'active') { $u = null; unset($_SESSION['uid']); }
    }
    return $u;
}
function me(): array { return current_user() ?? abort(403, 'Please sign in.'); }
function uid(): int { return (int) (current_user()['id'] ?? 0); }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function is_teacher(): bool { return in_array(current_user()['role'] ?? '', ['teacher', 'admin'], true); }

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        if (wants_json()) json_out(['ok' => false, 'error' => 'Please sign in.'], 401);
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login');
    }
    if (!$u['profile_complete'] && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'onboarding') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'logout')) {
        redirect('onboarding');
    }
    return $u;
}
function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') abort(403, 'Administrators only.');
    return $u;
}
function require_teacher(): array
{
    $u = require_login();
    if (!in_array($u['role'], ['teacher', 'admin'], true)) abort(403, 'This area is for teachers and mentors.');
    return $u;
}

const PRODUCT_TOUR_RELEASE_AT = '2026-10-03 00:00:00';

/**
 * Add and normalize the product-tour flag on installations created before the
 * guided home experience shipped. Accounts created after the feature release
 * are treated as new users even if an older migration left their value NULL.
 */
function ensure_product_tour_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $cols = db_driver() === 'sqlite'
        ? array_column(qall('PRAGMA table_info(users)'), 'name')
        : array_column(qall('SHOW COLUMNS FROM users'), 'Field');
    if (!in_array('product_tour_completed', $cols, true)) {
        qexec('ALTER TABLE users ADD COLUMN product_tour_completed INT NULL DEFAULT NULL');
    }
    qexec('UPDATE users SET product_tour_completed = 1 WHERE product_tour_completed IS NULL AND (created_at < ? OR role = ?)', [PRODUCT_TOUR_RELEASE_AT, 'admin']);
    qexec('UPDATE users SET product_tour_completed = 0 WHERE product_tour_completed IS NULL AND created_at >= ? AND role <> ?', [PRODUCT_TOUR_RELEASE_AT, 'admin']);
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    update('users', ['last_login' => now()], 'id = ?', [$id]);
    current_user(true);   // later calls in this request see the signed-in user
}

/* ---------- Universities / domains ---------- */
function university_for_email(string $email): ?array
{
    $email = strtolower(trim($email));
    if (!preg_match('/^[^@\s]+@([^@\s]+)$/', $email, $m)) return null;
    $dom = $m[1];
    foreach (qall('SELECT * FROM universities WHERE active = 1') as $un) {
        if ($dom === $un['domain'] || str_ends_with($dom, '.' . $un['domain'])) return $un;
    }
    return null;
}
function allowed_domains_text(): string
{
    $d = array_column(qall('SELECT domain FROM universities WHERE active = 1'), 'domain');
    return implode(', ', array_map(fn($x) => '@' . $x, $d));
}
function email_student_id(string $email): ?string
{
    $local = strstr($email, '@', true);
    return ($local !== false && preg_match('/^\d{6,12}$/', $local)) ? $local : null;
}

/**
 * Turn what someone typed into a full university address, or null if it is not one.
 * "202020280" + "aau.ac.ae" → 202020280@aau.ac.ae · "saqib.iqbal@aau.ac.ae" stays as is.
 */
function normalize_university_email(string $typed, string $domain = ''): ?string
{
    $typed = strtolower(trim($typed));
    if ($typed === '') return null;
    if (!str_contains($typed, '@')) {
        $domains = array_column(qall('SELECT domain FROM universities WHERE active = 1 ORDER BY id'), 'domain');
        $domain = strtolower(trim($domain));
        if (!in_array($domain, $domains, true)) $domain = $domains[0] ?? '';
        $typed .= '@' . $domain;
    }
    if (!preg_match('/^[a-z0-9][a-z0-9._-]{1,63}@[a-z0-9.-]+$/', $typed)) return null;
    $local = (string) strstr($typed, '@', true);
    if (ctype_digit($local) ? !email_student_id($typed) : !ctype_alpha($local[0])) return null;   // 6–12 digit student number, or a name
    return university_for_email($typed) ? $typed : null;
}

/** Best-effort display name from a name-based address: saqib.iqbal@aau.ac.ae → "Saqib Iqbal". */
function name_from_email(string $email): string
{
    $local = (string) strstr($email, '@', true);
    return ucwords(trim(preg_replace('/[._-]+/', ' ', $local)));
}

/* ---------- Email codes, passwords, throttling ---------- */

const LOGIN_CODE_MINUTES = 10;

/** Creates the tables/columns email sign-in needs on databases installed before this feature (runs once per request). */
function ensure_auth_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $sqlite = db_driver() === 'sqlite';
    $pk = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $tail = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    qexec("CREATE TABLE IF NOT EXISTS login_codes (id $pk, email VARCHAR(190) NOT NULL, code_hash VARCHAR(255) NOT NULL, attempts INT NOT NULL DEFAULT 0, expires_at DATETIME NOT NULL, used_at DATETIME NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL)$tail");
    qexec("CREATE TABLE IF NOT EXISTS auth_events (id $pk, email VARCHAR(190) NOT NULL, kind VARCHAR(20) NOT NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL)$tail");
    $cols = $sqlite ? array_column(qall('PRAGMA table_info(users)'), 'name') : array_column(qall('SHOW COLUMNS FROM users'), 'Field');
    if (!in_array('password_hash', $cols, true)) qexec('ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL');
}

function client_ip(): string { return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45); }
function auth_event(string $email, string $kind): void { insert('auth_events', ['email' => $email, 'kind' => $kind, 'ip' => client_ip(), 'created_at' => now()]); }
function auth_events_since(string $kind, int $minutes, ?string $email = null, ?string $ip = null): int
{
    $sql = 'SELECT COUNT(*) FROM auth_events WHERE kind = ? AND created_at >= ?';
    $p = [$kind, date('Y-m-d H:i:s', time() - $minutes * 60)];
    if ($email !== null) { $sql .= ' AND email = ?'; $p[] = $email; }
    if ($ip !== null) { $sql .= ' AND ip = ?'; $p[] = $ip; }
    return (int) qval($sql, $p);
}

/**
 * Email a fresh 6-digit sign-in code. Limits: one per minute per address, 5 per hour per address, 20 per hour per IP.
 * Returns ['delivery' => 'sent'|'logged', 'dev_code' => code only when it was logged instead of sent].
 */
function login_code_send(string $email): array
{
    ensure_auth_schema();
    $last = qval('SELECT MAX(created_at) FROM login_codes WHERE email = ?', [$email]);
    if ($last && ($wait = 60 - (time() - strtotime((string) $last))) > 0) throw new RuntimeException("Please wait {$wait} seconds before asking for another code.");
    if (auth_events_since('code_sent', 60, $email) >= 5) throw new RuntimeException('Too many codes for this address. Please try again in an hour.');
    if (auth_events_since('code_sent', 60, null, client_ip()) >= 20) throw new RuntimeException('Too many sign-in attempts from this network. Please try again later.');

    $code = sprintf('%06d', random_int(0, 999999));
    qexec('UPDATE login_codes SET used_at = ? WHERE email = ? AND used_at IS NULL', [now(), $email]);   // only the newest code works
    [$html, $text] = login_code_email($code, $email, LOGIN_CODE_MINUTES);
    $delivery = send_mail($email, "Your MANBAR sign-in code: $code", $html, $text);
    insert('login_codes', ['email' => $email, 'code_hash' => password_hash($code, PASSWORD_DEFAULT), 'expires_at' => date('Y-m-d H:i:s', time() + LOGIN_CODE_MINUTES * 60), 'ip' => client_ip(), 'created_at' => now()]);
    auth_event($email, 'code_sent');
    return ['delivery' => $delivery, 'dev_code' => $delivery === 'logged' ? $code : null];
}

/** Check a typed code; 5 tries per code. Throws with a friendly message when it doesn't match. */
function login_code_verify(string $email, string $code): void
{
    ensure_auth_schema();
    $row = qrow('SELECT * FROM login_codes WHERE email = ? AND used_at IS NULL AND expires_at >= ? ORDER BY id DESC LIMIT 1', [$email, now()]);
    if (!$row) throw new RuntimeException('This code has expired. Ask for a new one.');
    if ((int) $row['attempts'] >= 5) throw new RuntimeException('Too many wrong tries. Ask for a new code.');
    qexec('UPDATE login_codes SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
    if (!preg_match('/^\d{6}$/', $code) || !password_verify($code, $row['code_hash'])) {
        $left = 4 - (int) $row['attempts'];
        throw new RuntimeException($left > 0 ? "That code isn’t right. $left " . ($left === 1 ? 'try' : 'tries') . ' left.' : 'Too many wrong tries. Ask for a new code.');
    }
    qexec('UPDATE login_codes SET used_at = ? WHERE id = ?', [now(), $row['id']]);
}

/** Password sign-in for accounts that set one. 8 wrong passwords in 15 minutes locks the password route (codes still work). */
function password_sign_in(string $email, string $password): array
{
    ensure_auth_schema();
    if (auth_events_since('pw_fail', 15, $email) >= 8) throw new RuntimeException('Too many wrong passwords. Use “Email me a code” or try again in 15 minutes.');
    $u = qrow('SELECT * FROM users WHERE email = ?', [$email]);
    if (!$u || empty($u['password_hash']) || !password_verify($password, $u['password_hash'])) {
        auth_event($email, 'pw_fail');
        throw new RuntimeException('That password isn’t right.');
    }
    if ($u['status'] !== 'active') throw new RuntimeException('This account is suspended. Contact the MANBAR administrators.');
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
    login_user((int) $u['id']);
    return $u;
}

/** Where Google sends people back after they confirm their account (must be registered in Google Cloud). */
function google_redirect_uri(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return rtrim(cfg('base_url') ?: $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/') . url('auth/google/callback');
}

/* ---------- Google ID token verification ---------- */
function google_verify_token(string $idToken): array
{
    $cid = cfg('google_client_id');
    if (!$cid) throw new RuntimeException('Google Sign-In is not configured.');
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
    $body = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $ca = getenv('SSL_CERT_FILE') ?: getenv('CURL_CA_BUNDLE');
        if ($ca && is_file($ca)) curl_setopt($ch, CURLOPT_CAINFO, $ca);
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
        $body = @file_get_contents($url);
    }
    $p = $body ? json_decode($body, true) : null;
    if (!is_array($p) || empty($p['email'])) throw new RuntimeException('Google could not verify this sign-in.');
    if (($p['aud'] ?? '') !== $cid) throw new RuntimeException('Sign-in token was issued for a different app.');
    if (!in_array($p['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) throw new RuntimeException('Invalid token issuer.');
    if ((int) ($p['exp'] ?? 0) < time()) throw new RuntimeException('Sign-in token expired.');
    if (($p['email_verified'] ?? 'false') !== 'true' && ($p['email_verified'] ?? false) !== true) throw new RuntimeException('Your Google email is not verified.');
    return $p;
}

/**
 * Create or update the user for a verified university email.
 * The student number in the email (202020280@aau.ac.ae) is matched against the roster to pre-fill the profile.
 */
function sign_in_with_email(string $email, string $name, ?string $picture = null, ?string $sub = null): array
{
    ensure_product_tour_schema();
    $email = strtolower(trim($email));
    $uni = university_for_email($email);
    if (!$uni) throw new RuntimeException('Only university emails are allowed (' . allowed_domains_text() . ').');

    $user = qrow('SELECT * FROM users WHERE email = ?', [$email]);
    if ($user) {
        if ($user['status'] !== 'active') throw new RuntimeException('This account is suspended. Contact the MANBAR administrators.');
        $upd = [];
        if ($sub && !$user['google_sub']) $upd['google_sub'] = $sub;
        if ($picture && !$user['avatar_url']) $upd['avatar_url'] = $picture;
        if ($upd) update('users', $upd, 'id = ?', [$user['id']]);
        login_user((int) $user['id']);
        return $user;
    }

    if (setting('registration_open', '1') !== '1') throw new RuntimeException('Registration is temporarily closed. Please try again later.');
    $sid = email_student_id($email);
    $ros = $sid ? qrow('SELECT * FROM roster WHERE university_id = ? AND student_id = ?', [$uni['id'], $sid]) : null;
    $role = $sid ? 'student' : 'teacher';   // numeric ID => student, name-based address => staff/teacher
    if (in_array($email, array_map('strtolower', cfg('admin_emails')), true)) $role = 'admin';

    $id = insert('users', [
        'university_id' => $uni['id'],
        'email' => $email,
        'google_sub' => $sub,
        'student_id' => $sid,
        'full_name' => $ros['full_name'] ?? ($name ?: ($sid ?? name_from_email($email))),
        'role' => $role,
        'verified' => ($ros || $role !== 'student') ? 1 : 0,
        'avatar_url' => $picture,
        'major' => $ros['major'] ?? null,
        'faculty' => $ros['faculty'] ?? null,
        'year_level' => $ros['year_level'] ?? null,
        'theme' => random_int(0, 5),
        'product_tour_completed' => 0,
    ]);
    award_points($id, 5, 'Welcome to MANBAR');
    notify($id, 'welcome', 'Welcome to MANBAR! Complete your profile to earn your first badge.', 'profile/edit');
    if ($role === 'teacher') {
        insert('mentor_profiles', ['user_id' => $id, 'expertise' => 'Add your expertise', 'about' => '', 'availability' => '', 'active' => 0]);
    }
    login_user($id);
    return qrow('SELECT * FROM users WHERE id = ?', [$id]);
}
