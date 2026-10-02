<?php
function admin_series(string $table, int $days = 14): array
{
    $out = [];
    $rows = array_column(qall("SELECT DATE(created_at) AS d, COUNT(*) AS n FROM $table WHERE created_at >= ? GROUP BY DATE(created_at)", [date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'))]), 'n', 'd');
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $out[] = ['d' => $d, 'n' => (int) ($rows[$d] ?? 0)];
    }
    return $out;
}

function page_admin(): void
{
    $u = require_admin();
    $c = fn(string $sql) => (int) qval($sql);
    $stats = [
        'users' => $c('SELECT COUNT(*) FROM users'),
        'students' => $c("SELECT COUNT(*) FROM users WHERE role = 'student'"),
        'teachers' => $c("SELECT COUNT(*) FROM users WHERE role = 'teacher'"),
        'posts' => $c('SELECT COUNT(*) FROM posts'),
        'comments' => $c('SELECT COUNT(*) FROM comments'),
        'reactions' => $c('SELECT COUNT(*) FROM reactions'),
        'projects' => $c('SELECT COUNT(*) FROM projects'),
        'services' => $c('SELECT COUNT(*) FROM services'),
        'courses' => $c('SELECT COUNT(*) FROM courses'),
        'enrollments' => $c('SELECT COUNT(*) FROM enrollments'),
        'mentorships' => $c("SELECT COUNT(*) FROM mentorship_requests WHERE status IN ('accepted','completed')"),
        'reports' => $c("SELECT COUNT(*) FROM reports WHERE status = 'open'"),
        'new_week' => $c("SELECT COUNT(*) FROM users WHERE created_at >= '" . date('Y-m-d', strtotime('-7 days')) . "'"),
        'suspended' => $c("SELECT COUNT(*) FROM users WHERE status = 'suspended'"),
    ];
    $series = ['users' => admin_series('users'), 'posts' => admin_series('posts'), 'comments' => admin_series('comments')];
    $byType = array_column(qall('SELECT type, COUNT(*) AS n FROM posts GROUP BY type ORDER BY n DESC'), 'n', 'type');
    $byFaculty = qall("SELECT COALESCE(NULLIF(faculty, ''), 'Not set') AS f, COUNT(*) AS n FROM users GROUP BY COALESCE(NULLIF(faculty, ''), 'Not set') ORDER BY n DESC LIMIT 6");
    $recentUsers = qall('SELECT id, full_name, email, role, verified, created_at, avatar_url FROM users ORDER BY id DESC LIMIT 6');
    $openReports = qall("SELECT r.*, u.full_name AS reporter FROM reports r JOIN users u ON u.id = r.reporter_id WHERE r.status = 'open' ORDER BY r.id DESC LIMIT 5");
    $top = qall("SELECT id, full_name, email, avatar_url, points FROM users WHERE status = 'active' ORDER BY points DESC LIMIT 5");
    render('admin/dashboard', compact('u', 'stats', 'series', 'byType', 'byFaculty', 'recentUsers', 'openReports', 'top'), 'admin/layout');
}

function page_admin_users(): void
{
    $u = require_admin();
    $q = input('q'); $role = input('role'); $st = input('status');
    $where = ['1=1']; $params = [];
    if ($q !== '') { $where[] = '(full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
    if (in_array($role, ['student', 'teacher', 'admin'], true)) { $where[] = 'role = ?'; $params[] = $role; }
    if (in_array($st, ['active', 'suspended'], true)) { $where[] = 'status = ?'; $params[] = $st; }
    if (input('unverified')) $where[] = 'verified = 0';
    $users = qall('SELECT u.*, (SELECT COUNT(*) FROM posts p WHERE p.user_id = u.id) AS posts FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.id DESC LIMIT 200', $params);
    if (input('export') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="manbar-users-' . date('Y-m-d') . '.csv"');
        $o = fopen('php://output', 'w');
        fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, ['id', 'student_id', 'full_name', 'email', 'role', 'status', 'verified', 'major', 'faculty', 'points', 'created_at']);
        foreach ($users as $r) fputcsv($o, [$r['id'], $r['student_id'], $r['full_name'], $r['email'], $r['role'], $r['status'], $r['verified'], $r['major'], $r['faculty'], $r['points'], $r['created_at']]);
        exit;
    }
    render('admin/users', compact('u', 'users', 'q', 'role', 'st'), 'admin/layout');
}

function admin_user_action(int $id): void
{
    $a = require_admin();
    $t = qrow('SELECT * FROM users WHERE id = ?', [$id]) ?? abort(404);
    $act = input('action');
    if ($id === (int) $a['id'] && in_array($act, ['suspend', 'delete', 'role'], true)) { flash('You cannot do that to your own account.', 'error'); redirect('admin/users'); }
    switch ($act) {
        case 'suspend': update('users', ['status' => 'suspended'], 'id = ?', [$id]); audit('suspend_user', $t['email']); flash('User suspended.'); break;
        case 'activate': update('users', ['status' => 'active'], 'id = ?', [$id]); audit('activate_user', $t['email']); flash('User re-activated.'); break;
        case 'verify': update('users', ['verified' => $t['verified'] ? 0 : 1], 'id = ?', [$id]); audit('toggle_verify', $t['email']); flash('Verification updated.'); break;
        case 'role':
            $r = input('role');
            if (!in_array($r, ['student', 'teacher', 'admin'], true)) abort(422);
            update('users', ['role' => $r], 'id = ?', [$id]);
            if ($r !== 'student' && !qval('SELECT COUNT(*) FROM mentor_profiles WHERE user_id = ?', [$id])) insert('mentor_profiles', ['user_id' => $id, 'expertise' => 'Add your expertise', 'active' => 0]);
            audit('set_role', $t['email'] . ' → ' . $r); flash('Role changed to ' . $r . '.'); break;
        case 'delete': qexec('DELETE FROM users WHERE id = ?', [$id]); audit('delete_user', $t['email']); flash('User deleted.'); break;
    }
    redirect('admin/users');
}

function page_admin_roster(): void
{
    $u = require_admin();
    $q = input('q');
    $where = '1=1'; $params = [];
    if ($q !== '') { $where = '(r.student_id LIKE ? OR r.full_name LIKE ?)'; $params = ["%$q%", "%$q%"]; }
    $rows = qall("SELECT r.*, un.short_name, (SELECT COUNT(*) FROM users u WHERE u.student_id = r.student_id AND u.university_id = r.university_id) AS registered FROM roster r JOIN universities un ON un.id = r.university_id WHERE $where ORDER BY r.id DESC LIMIT 200", $params);
    $total = (int) qval('SELECT COUNT(*) FROM roster');
    $unis = qall('SELECT * FROM universities WHERE active = 1');
    render('admin/roster', compact('u', 'rows', 'q', 'total', 'unis'), 'admin/layout');
}

function admin_roster_add(): void
{
    require_admin();
    $uni = input_int('university_id', 1);
    $sid = preg_replace('/\D/', '', input('student_id'));
    if (strlen($sid) < 6 || mb_strlen(input('full_name')) < 3) { flash('Student number (6+ digits) and full name are required.', 'error'); redirect('admin/roster'); }
    roster_upsert($uni, $sid, input('full_name'), input('major'), input('faculty'), input_int('year_level') ?: null);
    audit('roster_add', $sid);
    flash('Student added to the roster.');
    redirect('admin/roster');
}

function roster_upsert(int $uni, string $sid, string $name, ?string $major, ?string $faculty, ?int $year): void
{
    $d = ['full_name' => mb_substr($name, 0, 150), 'major' => $major ?: null, 'faculty' => $faculty ?: null, 'year_level' => $year];
    if (qval('SELECT COUNT(*) FROM roster WHERE university_id = ? AND student_id = ?', [$uni, $sid])) update('roster', $d, 'university_id = ? AND student_id = ?', [$uni, $sid]);
    else insert('roster', ['university_id' => $uni, 'student_id' => $sid] + $d);
}

function admin_roster_import(): void
{
    require_admin();
    $uni = input_int('university_id', 1);
    if (empty($_FILES['csv']['tmp_name'])) { flash('Choose a CSV file.', 'error'); redirect('admin/roster'); }
    $h = fopen($_FILES['csv']['tmp_name'], 'r');
    $n = 0; $bad = 0; $first = true;
    while (($row = fgetcsv($h)) !== false) {
        if ($first) { $first = false; $row[0] = ltrim($row[0], "\xEF\xBB\xBF"); if (!ctype_digit(trim($row[0]))) continue; }   // skip header
        $sid = preg_replace('/\D/', '', $row[0] ?? '');
        $name = trim($row[1] ?? '');
        if (strlen($sid) < 6 || $name === '') { $bad++; continue; }
        roster_upsert($uni, $sid, $name, trim($row[2] ?? ''), trim($row[3] ?? ''), (int) ($row[4] ?? 0) ?: null);
        $n++;
    }
    fclose($h);
    audit('roster_import', "$n rows");
    flash("Imported $n students" . ($bad ? " ($bad rows skipped)." : '.'));
    redirect('admin/roster');
}

function admin_roster_delete(int $id): void
{
    require_admin();
    qexec('DELETE FROM roster WHERE id = ?', [$id]);
    redirect('admin/roster');
}

function page_admin_moderation(): void
{
    $u = require_admin();
    $reports = qall("SELECT r.*, u.full_name AS reporter FROM reports r JOIN users u ON u.id = r.reporter_id ORDER BY (r.status = 'open') DESC, r.id DESC LIMIT 80");
    foreach ($reports as &$r) {
        $r['preview'] = null;
        if ($r['target_type'] === 'post') $r['preview'] = qrow('SELECT p.id, p.title AS text, p.status, u.full_name AS author FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?', [$r['target_id']]);
        if ($r['target_type'] === 'comment') $r['preview'] = qrow('SELECT c.id, c.body AS text, c.status, c.post_id, u.full_name AS author FROM comments c JOIN users u ON u.id = c.user_id WHERE c.id = ?', [$r['target_id']]);
        if ($r['target_type'] === 'service') $r['preview'] = qrow('SELECT s.id, s.title AS text, s.status, u.full_name AS author FROM services s JOIN users u ON u.id = s.user_id WHERE s.id = ?', [$r['target_id']]);
        if ($r['target_type'] === 'project') $r['preview'] = qrow("SELECT p.id, p.title AS text, CASE WHEN p.hidden = 1 THEN 'hidden' ELSE 'visible' END AS status, u.full_name AS author FROM projects p JOIN users u ON u.id = p.owner_id WHERE p.id = ?", [$r['target_id']]);
    }
    $recent = qall("SELECT p.id, p.title, p.type, p.status, p.pinned, p.created_at, u.full_name FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 15");
    render('admin/moderation', compact('u', 'reports', 'recent'), 'admin/layout');
}

function admin_moderate(): void
{
    require_admin();
    $type = input('type'); $id = input_int('id'); $act = input('action');
    $table = ['post' => 'posts', 'comment' => 'comments', 'service' => 'services', 'project' => 'projects'][$type] ?? abort(422);
    if ($act === 'hide') { $type === 'project' ? update($table, ['hidden' => 1], 'id = ?', [$id]) : update($table, ['status' => 'hidden'], 'id = ?', [$id]); audit('hide_' . $type, "#$id"); }
    elseif ($act === 'restore') { $type === 'project' ? update($table, ['hidden' => 0], 'id = ?', [$id]) : update($table, ['status' => $type === 'service' ? 'active' : 'visible'], 'id = ?', [$id]); audit('restore_' . $type, "#$id"); }
    elseif ($act === 'delete') { qexec("DELETE FROM $table WHERE id = ?", [$id]); audit('delete_' . $type, "#$id"); }
    if (in_array($act, ['hide', 'delete', 'dismiss', 'resolve'], true) && input_int('report_id')) update('reports', ['status' => $act === 'dismiss' ? 'dismissed' : 'resolved'], 'id = ?', [input_int('report_id')]);
    flash('Done.');
    redirect('admin/moderation');
}

function page_admin_content(): void
{
    $u = require_admin();
    $projects = qall('SELECT p.id, p.title, p.status, p.hidden, p.created_at, u.full_name FROM projects p JOIN users u ON u.id = p.owner_id ORDER BY p.id DESC LIMIT 50');
    $services = qall('SELECT s.id, s.title, s.status, s.price, s.created_at, u.full_name FROM services s JOIN users u ON u.id = s.user_id ORDER BY s.id DESC LIMIT 50');
    $courses = qall('SELECT c.id, c.title, c.status, c.created_at, u.full_name, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students FROM courses c JOIN users u ON u.id = c.author_id ORDER BY c.id DESC LIMIT 50');
    render('admin/content', compact('u', 'projects', 'services', 'courses'), 'admin/layout');
}

function admin_course_toggle(int $id): void
{
    require_admin();
    $c = qrow('SELECT status FROM courses WHERE id = ?', [$id]) ?? abort(404);
    update('courses', ['status' => $c['status'] === 'published' ? 'hidden' : 'published'], 'id = ?', [$id]);
    audit('toggle_course', "#$id");
    redirect('admin/content');
}

function page_admin_universities(): void
{
    $u = require_admin();
    $unis = qall('SELECT un.*, (SELECT COUNT(*) FROM users x WHERE x.university_id = un.id) AS users, (SELECT COUNT(*) FROM roster r WHERE r.university_id = un.id) AS roster FROM universities un ORDER BY un.id');
    render('admin/universities', compact('u', 'unis'), 'admin/layout');
}

function admin_university_save(): void
{
    require_admin();
    if (input('action') === 'add') {
        $dom = strtolower(input('domain'));
        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $dom) || mb_strlen(input('name')) < 3) { flash('Enter a valid name and domain (e.g. adu.ac.ae).', 'error'); redirect('admin/universities'); }
        if (qval('SELECT COUNT(*) FROM universities WHERE domain = ?', [$dom])) { flash('That domain already exists.', 'error'); redirect('admin/universities'); }
        insert('universities', ['name' => mb_substr(input('name'), 0, 150), 'short_name' => mb_substr(input('short_name') ?: strtoupper(strstr($dom, '.', true)), 0, 20), 'domain' => $dom, 'active' => 0]);
        audit('add_university', $dom);
        flash('University added (inactive). Switch it on when you are ready.');
    } else {
        $id = input_int('id');
        $un = qrow('SELECT * FROM universities WHERE id = ?', [$id]) ?? abort(404);
        update('universities', ['active' => $un['active'] ? 0 : 1], 'id = ?', [$id]);
        audit('toggle_university', $un['domain']);
        flash($un['short_name'] . ($un['active'] ? ' disabled.' : ' enabled — its students can now sign in.'));
    }
    redirect('admin/universities');
}

function page_admin_settings(): void
{
    $u = require_admin();
    $audit = qall('SELECT a.*, u.full_name FROM audit_log a LEFT JOIN users u ON u.id = a.admin_id ORDER BY a.id DESC LIMIT 40');
    render('admin/settings', compact('u', 'audit'), 'admin/layout');
}

function admin_settings_save(): void
{
    require_admin();
    set_setting('registration_open', input('registration_open') ? '1' : '0');
    set_setting('site_notice', mb_substr(input('site_notice'), 0, 300));
    audit('settings', 'updated');
    flash('Settings saved.');
    redirect('admin/settings');
}
