<?php
function profile_data(int $id): ?array
{
    $p = qrow('SELECT u.*, un.short_name AS uni_short, un.name AS uni_name FROM users u JOIN universities un ON un.id = u.university_id WHERE u.id = ?', [$id]);
    if (!$p) return null;
    $p['skills'] = array_column(qall("SELECT name FROM user_skills WHERE user_id = ? AND kind = 'skill' ORDER BY name", [$id]), 'name');
    $p['interests'] = array_column(qall("SELECT name FROM user_skills WHERE user_id = ? AND kind = 'interest' ORDER BY name", [$id]), 'name');
    $p['followers'] = (int) qval('SELECT COUNT(*) FROM follows WHERE followed_id = ?', [$id]);
    $p['following'] = (int) qval('SELECT COUNT(*) FROM follows WHERE follower_id = ?', [$id]);
    $p['is_following'] = (bool) qval('SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = ?', [uid(), $id]);
    return $p;
}

function page_profile(int $id): void
{
    $u = require_login();
    $p = profile_data($id) ?? abort(404, 'This profile does not exist.');
    $tab = input('tab', 'posts');
    $posts = fetch_posts('p.user_id = ?', [$id], 20);
    $projects = qall("SELECT pr.*, (SELECT COUNT(*) FROM project_members m WHERE m.project_id = pr.id) AS members FROM projects pr
        WHERE pr.hidden = 0 AND (pr.owner_id = ? OR pr.id IN (SELECT project_id FROM project_members WHERE user_id = ?)) ORDER BY pr.id DESC", [$id, $id]);
    $services = qall("SELECT * FROM services WHERE user_id = ? AND status = 'active' ORDER BY id DESC", [$id]);
    $badges = qall('SELECT b.*, ub.created_at AS earned FROM user_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.user_id = ? ORDER BY ub.created_at DESC', [$id]);
    $courses = qall("SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students FROM courses c WHERE c.author_id = ? AND c.status = 'published'", [$id]);
    $mentor = qrow('SELECT * FROM mentor_profiles WHERE user_id = ? AND active = 1', [$id]);
    $lvl = level_for((int) $p['points']);
    render('profile', compact('u', 'p', 'tab', 'posts', 'projects', 'services', 'badges', 'courses', 'mentor', 'lvl'));
}

function page_me(): void { redirect('profile/' . require_login()['id']); }

function page_profile_edit(): void
{
    $u = require_login();
    $p = profile_data((int) $u['id']);
    $roster = $u['student_id'] ? qrow('SELECT * FROM roster WHERE university_id = ? AND student_id = ?', [$u['university_id'], $u['student_id']]) : null;
    $mentor = qrow('SELECT * FROM mentor_profiles WHERE user_id = ?', [$u['id']]);
    render('profile_edit', compact('u', 'p', 'roster', 'mentor'));
}

function profile_save(): void
{
    $u = require_login();
    $roster = $u['student_id'] ? qrow('SELECT * FROM roster WHERE university_id = ? AND student_id = ?', [$u['university_id'], $u['student_id']]) : null;
    $name = $roster ? $roster['full_name'] : input('full_name');
    if (mb_strlen($name) < 3) { flash('Please enter your full name.', 'error'); redirect('profile/edit'); }
    $data = [
        'full_name' => mb_substr($name, 0, 150),
        'headline' => mb_substr(input('headline'), 0, 160) ?: null,
        'bio' => mb_substr(input('bio'), 0, 1000) ?: null,
        'faculty' => mb_substr(input('faculty'), 0, 120) ?: null,
        'major' => mb_substr(input('major'), 0, 120) ?: null,
        'department' => mb_substr(input('department'), 0, 120) ?: null,
        'year_level' => input_int('year_level') ?: null,
        'theme' => max(0, min(5, input_int('theme'))),
        'website' => safe_url(input('website')),
        'linkedin' => safe_url(input('linkedin')),
        'github' => safe_url(input('github')),
    ];
    try { if ($img = save_upload('avatar', 'avatars')) $data['avatar_url'] = $img; } catch (RuntimeException $ex) { flash($ex->getMessage(), 'error'); redirect('profile/edit'); }
    update('users', $data, 'id = ?', [$u['id']]);
    save_skill_list((int) $u['id'], 'skill', input('skills'));
    save_skill_list((int) $u['id'], 'interest', input('interests'));

    if (in_array($u['role'], ['teacher', 'admin'], true)) {
        $ex = qval('SELECT COUNT(*) FROM mentor_profiles WHERE user_id = ?', [$u['id']]);
        $m = ['expertise' => mb_substr(input('m_expertise'), 0, 250) ?: 'General guidance', 'about' => mb_substr(input('m_about'), 0, 1000), 'availability' => mb_substr(input('m_availability'), 0, 250), 'active' => input('m_active') ? 1 : 0];
        if ($ex) update('mentor_profiles', $m, 'user_id = ?', [$u['id']]); else insert('mentor_profiles', ['user_id' => $u['id']] + $m);
    }
    check_badges((int) $u['id']);
    flash('Profile saved.');
    redirect('profile/' . $u['id']);
}

function safe_url(string $s): ?string
{
    $s = trim($s);
    if ($s === '') return null;
    if (!preg_match('#^https?://#i', $s)) $s = 'https://' . $s;
    return filter_var($s, FILTER_VALIDATE_URL) ? mb_substr($s, 0, 250) : null;
}

/* ---------- people directory ---------- */
function page_people(): void
{
    $u = require_login();
    $q = input('q');
    $role = input('role');
    $major = input('major');
    $skill = input('skill');
    $where = ["u.status = 'active'", 'u.profile_complete = 1'];
    $params = [];
    if ($q !== '') { $where[] = '(u.full_name LIKE ? OR u.headline LIKE ? OR u.student_id LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
    if (in_array($role, ['student', 'teacher'], true)) { $where[] = 'u.role = ?'; $params[] = $role; }
    if ($major !== '') { $where[] = 'u.major LIKE ?'; $params[] = "%$major%"; }
    if ($skill !== '') { $where[] = 'u.id IN (SELECT user_id FROM user_skills WHERE name LIKE ?)'; $params[] = "%$skill%"; }
    $people = qall('SELECT u.id, u.full_name, u.avatar_url, u.email, u.headline, u.major, u.role, u.verified, u.points, u.theme FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.points DESC, u.id LIMIT 60', $params);
    foreach ($people as &$p) $p['skills'] = array_column(qall("SELECT name FROM user_skills WHERE user_id = ? AND kind = 'skill' LIMIT 4", [$p['id']]), 'name');
    $topSkills = array_column(qall('SELECT name, COUNT(*) AS n FROM user_skills GROUP BY name ORDER BY n DESC, name LIMIT 14'), 'name');
    $majors = array_column(qall("SELECT DISTINCT major FROM users WHERE major IS NOT NULL AND major <> '' ORDER BY major"), 'major');
    render('people', compact('u', 'people', 'q', 'role', 'major', 'skill', 'topSkills', 'majors'));
}

function api_follow(): void
{
    $u = require_login();
    $tid = input_int('id');
    if ($tid === (int) $u['id'] || !qval('SELECT COUNT(*) FROM users WHERE id = ?', [$tid])) json_out(['ok' => false, 'error' => 'Invalid'], 422);
    if (qval('SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = ?', [$u['id'], $tid])) {
        qexec('DELETE FROM follows WHERE follower_id = ? AND followed_id = ?', [$u['id'], $tid]);
        $f = false;
    } else {
        insert('follows', ['follower_id' => $u['id'], 'followed_id' => $tid]);
        notify($tid, 'follow', $u['full_name'] . ' started following you', 'profile/' . $u['id']);
        check_badges($tid);
        $f = true;
    }
    json_out(['ok' => true, 'following' => $f, 'followers' => (int) qval('SELECT COUNT(*) FROM follows WHERE followed_id = ?', [$tid])]);
}
