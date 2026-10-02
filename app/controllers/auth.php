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
    $demo = cfg('dev_login') ? qall("SELECT full_name, email, role FROM users WHERE email LIKE '%@aau.ac.ae' ORDER BY (role = 'admin') DESC, role, id LIMIT 8") : [];
    render('login', ['client_id' => cfg('google_client_id'), 'demo' => $demo, 'domains' => allowed_domains_text()], 'layout_public');
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
