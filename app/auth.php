<?php
/** Authentication: Google Sign-In (university accounts only), roster lookup, roles. */

function current_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
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

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    update('users', ['last_login' => now()], 'id = ?', [$id]);
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
        'full_name' => $ros['full_name'] ?? ($name ?: ($sid ?? strstr($email, '@', true))),
        'role' => $role,
        'verified' => ($ros || $role !== 'student') ? 1 : 0,
        'avatar_url' => $picture,
        'major' => $ros['major'] ?? null,
        'faculty' => $ros['faculty'] ?? null,
        'year_level' => $ros['year_level'] ?? null,
        'theme' => random_int(0, 5),
    ]);
    award_points($id, 5, 'Welcome to MANBAR');
    notify($id, 'welcome', 'Welcome to MANBAR! Complete your profile to earn your first badge.', 'profile/edit');
    if ($role === 'teacher') {
        insert('mentor_profiles', ['user_id' => $id, 'expertise' => 'Add your expertise', 'about' => '', 'availability' => '', 'active' => 0]);
    }
    login_user($id);
    return qrow('SELECT * FROM users WHERE id = ?', [$id]);
}
