<?php
function page_landing(): void
{
    if (current_user()) redirect('feed');
    $stats = [
        'students' => (int) qval("SELECT COUNT(*) FROM users"),
        'posts' => (int) qval("SELECT COUNT(*) FROM posts WHERE status = 'visible'"),
        'projects' => (int) qval("SELECT COUNT(*) FROM projects WHERE hidden = 0"),
        'courses' => (int) qval("SELECT COUNT(*) FROM courses WHERE status = 'published'"),
    ];
    render('landing', compact('stats'), 'layout_public');
}

function page_login(): void
{
    if (current_user()) redirect('feed');
    $unis = qall('SELECT domain, short_name FROM universities WHERE active = 1 ORDER BY id');
    // nonce for Chrome's "Sign in with Google" bubble (One Tap / FedCM); checked again in auth_google_finish()
    $onetap = '';
    if (cfg('google_client_id')) $_SESSION['onetap_nonce'] = $onetap = bin2hex(random_bytes(16));
    render('login', ['client_id' => cfg('google_client_id'), 'domains' => allowed_domains_text(), 'unis' => $unis, 'onetap_nonce' => $onetap, 'redirect_uri' => google_redirect_uri()], 'layout_public');
}

/**
 * Step 1 of the real sign-in / sign-up: send the person to Google's account chooser.
 * With a typed address we pass it as login_hint, so Google pre-selects that account and proves they own it.
 * Uses the OpenID Connect id_token flow (no client secret needed); the token comes back in the URL fragment.
 */
function auth_google_start(): void
{
    $cid = cfg('google_client_id');
    if (!$cid) { flash('Google sign-in is not switched on yet. Add your Google Client ID (see README).', 'error'); redirect('login'); }
    $typed = input('email');
    $hint = $typed !== '' ? normalize_university_email($typed, input('domain')) : null;
    if ($typed !== '' && !$hint) { flash('Use your university address, e.g. 202020280@aau.ac.ae or name.surname@aau.ac.ae (' . allowed_domains_text() . ').', 'error'); redirect('login'); }

    $state = bin2hex(random_bytes(16));
    $nonce = bin2hex(random_bytes(16));
    $_SESSION['oauth'] = ['state' => $state, 'nonce' => $nonce, 'hint' => $hint, 't' => time()];
    $domains = array_column(qall('SELECT domain FROM universities WHERE active = 1'), 'domain');
    $params = [
        'client_id' => $cid,
        'redirect_uri' => google_redirect_uri(),
        'response_type' => 'id_token',
        'response_mode' => 'fragment',
        'scope' => 'openid email profile',
        'state' => $state,
        'nonce' => $nonce,
        'prompt' => 'select_account',
    ];
    if ($hint) $params['login_hint'] = $hint;
    $hd = $hint ? substr(strrchr($hint, '@'), 1) : (count($domains) === 1 ? $domains[0] : '');
    if ($hd) $params['hd'] = $hd;   // Google shows only accounts from the university domain
    redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
}

/** Step 2: Google returns here with #id_token=…&state=… — a tiny page posts it back (same-origin, with CSRF). */
function page_google_callback(): void
{
    render('auth_callback', [], 'layout_public');
}

/** Step 3: verify the Google ID token (signature/audience/expiry via Google, our nonce + state), then sign in or create the account. */
function auth_google_finish(): void
{
    $mode = input('mode');
    // One Tap reloads the sign-in page on failure, so it also gets the message as a toast
    $fail = function (string $m) use ($mode): never { if ($mode === 'onetap') flash($m, 'error'); json_out(['ok' => false, 'error' => $m], 400); };
    $token = input('id_token');
    if ($token === '') $fail('Google did not return a sign-in token. Please try again.');
    $hint = null;
    if ($mode === 'onetap') {
        $nonce = $_SESSION['onetap_nonce'] ?? '';
        unset($_SESSION['onetap_nonce']);
    } else {
        $o = $_SESSION['oauth'] ?? null;
        unset($_SESSION['oauth']);
        if (!$o || time() - $o['t'] > 900) $fail('Your sign-in took too long. Please try again.');
        if (!hash_equals($o['state'], input('state'))) $fail('Sign-in could not be verified. Please try again.');
        $nonce = $o['nonce'];
        $hint = $o['hint'];
    }
    try {
        $p = google_verify_token($token);
        if (!$nonce || !hash_equals($nonce, (string) ($p['nonce'] ?? ''))) throw new RuntimeException('Sign-in could not be verified. Please try again.');
        $email = strtolower($p['email']);
        $u = sign_in_with_email($email, $p['name'] ?? '', $p['picture'] ?? null, $p['sub'] ?? null);
        if ($hint && $hint !== $email) flash("You typed {$hint}, so we signed you in with the account Google confirmed: {$email}.", 'info');
    } catch (Throwable $ex) {
        $fail($ex->getMessage());
    }
    $to = $_SESSION['after_login'] ?? '';
    unset($_SESSION['after_login']);
    $me = current_user();
    $dest = ($me && !$me['profile_complete']) ? url('onboarding') : ($to ?: url('feed'));
    json_out(['ok' => true, 'redirect' => $dest]);
}

function auth_google(): void
{
    // Google posts the credential cross-site; protect with the double-submit cookie it sets.
    $cookie = $_COOKIE['g_csrf_token'] ?? '';
    if (!$cookie || !hash_equals($cookie, (string) ($_POST['g_csrf_token'] ?? ''))) {
        flash('Sign-in could not be verified. Please try again.', 'error');
        redirect('login');
    }
    try {
        $p = google_verify_token((string) ($_POST['credential'] ?? ''));
        sign_in_with_email($p['email'], $p['name'] ?? '', $p['picture'] ?? null, $p['sub'] ?? null);
    } catch (Throwable $ex) {
        flash($ex->getMessage(), 'error');
        redirect('login');
    }
    $to = $_SESSION['after_login'] ?? 'feed';
    unset($_SESSION['after_login']);
    redirect($to ?: 'feed');
}

/* ---------- University email: code by email, optional password ---------- */

/** Absolute path to continue to after signing in: onboarding for new profiles, else where they were going, else the feed. */
function after_login_target(): string
{
    $to = $_SESSION['after_login'] ?? '';
    unset($_SESSION['after_login']);
    $me = current_user();
    if ($me && !$me['profile_complete']) return url('onboarding');
    return ($to && str_starts_with($to, base_path() . '/')) ? $to : url('feed');
}
function go(string $absPath): never { header('Location: ' . $absPath); exit; }

/** Step 1: typed address → password page (if the account has one) or an emailed code. */
function auth_email_start(): void
{
    // The admin console username (config 'admin_login') goes to its own password step.
    $adm = cfg('admin_login') ?? [];
    if (!empty($adm['username']) && !empty($adm['password_hash']) && hash_equals(strtolower($adm['username']), strtolower(input('email')))) {
        $_SESSION['admin_step'] = time();
        redirect('auth/admin');
    }
    $email = normalize_university_email(input('email'), input('domain'));
    if (!$email) { flash('Use your university address, e.g. 202020280@aau.ac.ae or name.surname@aau.ac.ae (' . allowed_domains_text() . ').', 'error'); redirect('login'); }
    ensure_auth_schema();
    $_SESSION['auth_email'] = $email;
    $u = qrow('SELECT password_hash FROM users WHERE email = ?', [$email]);
    if ($u && !empty($u['password_hash'])) redirect('auth/email/password');
    auth_send_code($email);
}

/** "Email me a code" / "Forgot password?" from the password page, and "Resend" on the code page. */
function auth_email_code(): void
{
    $email = $_SESSION['otp']['email'] ?? $_SESSION['auth_email'] ?? '';
    if (!$email) redirect('login');
    auth_send_code($email, input('reset') === '1' || !empty($_SESSION['otp']['reset']));
}

function auth_send_code(string $email, bool $reset = false): never
{
    try {
        $r = login_code_send($email);
    } catch (Throwable $ex) {
        flash($ex->getMessage(), 'error');
        redirect(match (true) {
            ($_SESSION['otp']['email'] ?? '') === $email => 'auth/email/verify',
            ($_SESSION['auth_email'] ?? '') === $email && !empty(qval('SELECT password_hash FROM users WHERE email = ?', [$email])) => 'auth/email/password',
            default => 'login',
        });
    }
    $_SESSION['otp'] = ['email' => $email, 'reset' => $reset, 'dev_code' => $r['dev_code'], 'sent_at' => time()];
    redirect('auth/email/verify');
}

function page_email_verify(): void
{
    $otp = $_SESSION['otp'] ?? null;
    if (!$otp) redirect('login');
    render('auth_code', ['otp' => $otp, 'wait' => max(0, 60 - (time() - (int) $otp['sent_at']))], 'layout_public');
}

/** Step 2: the 6-digit code signs in (and creates the account the first time), then offers a password. */
function auth_email_verify(): void
{
    $otp = $_SESSION['otp'] ?? null;
    if (!$otp) redirect('login');
    try {
        login_code_verify($otp['email'], preg_replace('/\D+/', '', input('code')));
        sign_in_with_email($otp['email'], '');
    } catch (Throwable $ex) {
        flash($ex->getMessage(), 'error');
        redirect('auth/email/verify');
    }
    unset($_SESSION['otp'], $_SESSION['auth_email']);
    $next = after_login_target();
    $me = current_user();
    if ($otp['reset'] || empty($me['password_hash'])) {
        $_SESSION['after_pw'] = $next;
        $_SESSION['pw_reset'] = (bool) $otp['reset'];
        redirect('auth/password');
    }
    go($next);
}

function page_email_password(): void
{
    $email = $_SESSION['auth_email'] ?? '';
    if (!$email) redirect('login');
    render('auth_password', ['email' => $email], 'layout_public');
}

function auth_email_password(): void
{
    $email = $_SESSION['auth_email'] ?? '';
    if (!$email) redirect('login');
    try {
        password_sign_in($email, (string) ($_POST['password'] ?? ''));
    } catch (Throwable $ex) {
        flash($ex->getMessage(), 'error');
        redirect('auth/email/password');
    }
    unset($_SESSION['auth_email']);
    go(after_login_target());
}

/* ---------- Admin console login (username + password from config, hash only) ---------- */

function page_admin_login(): void
{
    if (empty($_SESSION['admin_step']) || time() - $_SESSION['admin_step'] > 900) redirect('login');
    render('auth_password', ['email' => null, 'admin' => true], 'layout_public');
}

function auth_admin_login(): void
{
    $adm = cfg('admin_login') ?? [];
    if (empty($_SESSION['admin_step']) || empty($adm['password_hash'])) redirect('login');
    ensure_auth_schema();
    if (auth_events_since('admin_fail', 15, 'admin-console') >= 5 || auth_events_since('admin_fail', 15, null, client_ip()) >= 10) {
        flash('Too many wrong passwords. Try again in 15 minutes.', 'error');
        redirect('auth/admin');
    }
    if (!password_verify((string) ($_POST['password'] ?? ''), $adm['password_hash'])) {
        auth_event('admin-console', 'admin_fail');
        flash('That password isn’t right.', 'error');
        redirect('auth/admin');
    }
    // Internal address (not a university domain), so this account can never be reached with an email code.
    $email = strtolower($adm['username']) . '@admin.manbar';
    $u = qrow('SELECT * FROM users WHERE email = ?', [$email]);
    if ($u) {
        $id = (int) $u['id'];
        if ($u['role'] !== 'admin' || $u['status'] !== 'active') update('users', ['role' => 'admin', 'status' => 'active'], 'id = ?', [$id]);
    } else {
        $id = insert('users', [
            'university_id' => (int) qval('SELECT id FROM universities ORDER BY id LIMIT 1'),
            'email' => $email, 'full_name' => 'MANBAR Admin', 'headline' => 'Platform administrator',
            'role' => 'admin', 'verified' => 1, 'profile_complete' => 1,
        ]);
    }
    unset($_SESSION['admin_step'], $_SESSION['auth_email']);
    login_user($id);
    redirect('admin');
}

/** Set or change the account password (offered right after a code sign-in; also reachable any time while signed in). */
function page_password_set(): void
{
    $u = current_user() ?? redirect('login');
    ensure_auth_schema();
    render('auth_password_set', ['u' => $u, 'has' => !empty($u['password_hash']), 'reset' => !empty($_SESSION['pw_reset']), 'next' => $_SESSION['after_pw'] ?? url('feed')], 'layout_public');
}

function auth_password_set(): void
{
    $u = current_user() ?? redirect('login');
    ensure_auth_schema();
    $pw = (string) ($_POST['password'] ?? '');
    $local = strtolower((string) strstr($u['email'], '@', true));
    $err = match (true) {
        mb_strlen($pw) < 8 => 'Use at least 8 characters.',
        mb_strlen($pw) > 128 => 'Use at most 128 characters.',
        $pw !== (string) ($_POST['password2'] ?? '') => 'The two passwords don’t match.',
        strtolower($pw) === $local || strtolower($pw) === strtolower($u['email']) => 'Don’t use your email or student number as your password.',
        default => null,
    };
    if ($err) { flash($err, 'error'); redirect('auth/password'); }
    update('users', ['password_hash' => password_hash($pw, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
    $next = $_SESSION['after_pw'] ?? url('feed');
    unset($_SESSION['after_pw'], $_SESSION['pw_reset']);
    flash('Password saved. Next time, type your email and sign in with it.');
    go($next);
}

function auth_password_skip(): void
{
    $next = $_SESSION['after_pw'] ?? url('feed');
    unset($_SESSION['after_pw'], $_SESSION['pw_reset']);
    go($next);
}

function auth_dev(): void
{
    if (!cfg('dev_login')) abort(404);
    $id = strtolower(input('identifier'));
    if ($id === '') { flash('Enter a student number or university email.', 'error'); redirect('login'); }
    if (preg_match('/^\d{6,12}$/', $id)) $id .= '@aau.ac.ae';
    try {
        sign_in_with_email($id, input('name'));
    } catch (Throwable $ex) {
        flash($ex->getMessage(), 'error');
        redirect('login');
    }
    $to = $_SESSION['after_login'] ?? 'feed';
    unset($_SESSION['after_login']);
    redirect($to ?: 'feed');
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    flash('You have been signed out.');
    redirect('login');
}

function page_onboarding(): void
{
    $u = current_user() ?? redirect('login');
    $skills = array_column(qall("SELECT name FROM user_skills WHERE user_id = ? AND kind = 'skill'", [$u['id']]), 'name');
    $interests = array_column(qall("SELECT name FROM user_skills WHERE user_id = ? AND kind = 'interest'", [$u['id']]), 'name');
    $roster = $u['student_id'] ? qrow('SELECT * FROM roster WHERE university_id = ? AND student_id = ?', [$u['university_id'], $u['student_id']]) : null;
    render('onboarding', compact('u', 'skills', 'interests', 'roster'), 'layout_public');
}

function onboarding_save(): void
{
    $u = current_user() ?? redirect('login');
    $roster = $u['student_id'] ? qrow('SELECT * FROM roster WHERE university_id = ? AND student_id = ?', [$u['university_id'], $u['student_id']]) : null;
    $name = $roster ? $roster['full_name'] : input('full_name');
    if (mb_strlen($name) < 3) { flash('Please enter your full name.', 'error'); redirect('onboarding'); }
    $first = !$u['profile_complete'];
    update('users', [
        'full_name' => mb_substr($name, 0, 150),
        'faculty' => mb_substr(input('faculty'), 0, 120) ?: null,
        'major' => mb_substr(input('major'), 0, 120) ?: null,
        'year_level' => input_int('year_level') ?: null,
        'department' => mb_substr(input('department'), 0, 120) ?: null,
        'headline' => mb_substr(input('headline'), 0, 160) ?: null,
        'bio' => mb_substr(input('bio'), 0, 1000) ?: null,
        'theme' => max(0, min(5, input_int('theme'))),
        'profile_complete' => 1,
    ], 'id = ?', [$u['id']]);
    save_skill_list((int) $u['id'], 'skill', input('skills'));
    save_skill_list((int) $u['id'], 'interest', input('interests'));
    if ($first) { award_points((int) $u['id'], 20, 'Completed your profile'); flash('Welcome to MANBAR! +20 points for completing your profile 🎉'); }
    redirect('feed');
}

function save_skill_list(int $uid, string $kind, string $csv): void
{
    qexec('DELETE FROM user_skills WHERE user_id = ? AND kind = ?', [$uid, $kind]);
    $seen = [];
    foreach (array_slice(parse_skill_input($csv), 0, 15) as $s) {
        if (isset($seen[mb_strtolower($s)])) continue;
        $seen[mb_strtolower($s)] = 1;
        insert('user_skills', ['user_id' => $uid, 'name' => $s, 'kind' => $kind]);
    }
}
function parse_skill_input(string $csv): array
{
    return array_values(array_filter(array_map(fn($s) => mb_substr(trim($s), 0, 40), preg_split('/[,\n]+/u', $csv))));
}
