<?php
const PROFILE_NAME_STYLES = ['classic', 'rounded', 'wide', 'code'];
const PROFILE_EFFECTS = ['none', 'aura', 'orbit', 'spark'];

function ensure_profile_customization_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $cols = db_driver() === 'sqlite'
        ? array_column(qall('PRAGMA table_info(users)'), 'name')
        : array_column(qall('SHOW COLUMNS FROM users'), 'Field');
    $definitions = [
        'cover_image' => 'VARCHAR(255) NULL',
        'discord' => 'VARCHAR(255) NULL',
        'whatsapp' => 'VARCHAR(32) NULL',
        'phone' => 'VARCHAR(32) NULL',
        'name_style' => "VARCHAR(20) NOT NULL DEFAULT 'classic'",
        'profile_effect' => "VARCHAR(20) NOT NULL DEFAULT 'none'",
    ];
    foreach ($definitions as $column => $definition) {
        if (!in_array($column, $cols, true)) qexec("ALTER TABLE users ADD COLUMN $column $definition");
    }
}

function profile_cover_style(array $profile): string
{
    if (empty($profile['cover_image'])) return '';
    $src = url((string) $profile['cover_image']);
    return '--profile-cover-image:url("' . str_replace(['"', "'", ')'], '', $src) . '");';
}

function delete_profile_cover_file(?string $path): void
{
    if (!$path || !preg_match('#^uploads/profile-covers/[a-f0-9]{20}\.(?:jpg|png|webp|gif)$#i', $path)) return;
    $file = __DIR__ . '/../../public/' . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($file)) @unlink($file);
}

function safe_phone(string $value): ?string
{
    $value = trim($value);
    if ($value === '') return null;
    $clean = preg_replace('/[^0-9+()\- .]/', '', $value);
    $digits = preg_replace('/\D/', '', $clean);
    return strlen($digits) >= 7 ? mb_substr($clean, 0, 32) : null;
}

function profile_data(int $id): ?array
{
    ensure_profile_customization_schema();
    ensure_reputation_schema();
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
    $rankScore = refresh_reputation_score($id);
    $p = profile_data($id) ?? abort(404, 'This profile does not exist.');
    $tab = input('tab', 'posts');
    $posts = fetch_posts('p.user_id = ?', [$id], 20);
    $projects = qall("SELECT pr.*, (SELECT COUNT(*) FROM project_members m WHERE m.project_id = pr.id) AS members FROM projects pr
        WHERE pr.hidden = 0 AND (pr.owner_id = ? OR pr.id IN (SELECT project_id FROM project_members WHERE user_id = ?)) ORDER BY pr.id DESC", [$id, $id]);
    $services = qall("SELECT * FROM services WHERE user_id = ? AND status = 'active' ORDER BY id DESC", [$id]);
    $badges = qall('SELECT b.*, ub.created_at AS earned FROM user_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.user_id = ? ORDER BY ub.created_at DESC', [$id]);
    foreach ($badges as &$badge) $badge['tier'] = badge_tier((string) $badge['code']);
    unset($badge);
    $courses = qall("SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students FROM courses c WHERE c.author_id = ? AND c.status = 'published'", [$id]);
    $mentor = qrow('SELECT * FROM mentor_profiles WHERE user_id = ? AND active = 1', [$id]);
    $followers = qall("SELECT u.id, u.full_name, u.email, u.avatar_url, u.headline, u.major, u.role, u.verified, u.reputation_score
        FROM follows f JOIN users u ON u.id = f.follower_id
        WHERE f.followed_id = ? AND u.status = 'active' ORDER BY f.created_at DESC LIMIT 100", [$id]);
    $following = qall("SELECT u.id, u.full_name, u.email, u.avatar_url, u.headline, u.major, u.role, u.verified, u.reputation_score
        FROM follows f JOIN users u ON u.id = f.followed_id
        WHERE f.follower_id = ? AND u.status = 'active' ORDER BY f.created_at DESC LIMIT 100", [$id]);
    $recommendations = qall('SELECT r.*, u.full_name, u.avatar_url, u.email, u.headline, u.major, u.role, u.verified, u.reputation_score FROM recommendations r JOIN users u ON u.id = r.author_id WHERE r.subject_id = ? ORDER BY r.id DESC', [$id]);
    $vouches = qall('SELECT v.*, u.full_name, u.avatar_url, u.email, u.headline, u.major, u.role, u.verified, u.reputation_score, p.title AS project_title FROM project_vouches v JOIN users u ON u.id = v.author_id JOIN projects p ON p.id = v.project_id WHERE v.subject_id = ? ORDER BY v.id DESC', [$id]);
    $reputation = reputation_breakdown($id);
    $canRecommend = (int) $u['id'] !== $id ? recommendation_eligibility((int) $u['id'], $id) : null;
    $myRecommendation = (int) $u['id'] !== $id ? qrow('SELECT * FROM recommendations WHERE author_id = ? AND subject_id = ?', [$u['id'], $id]) : null;
    $lvl = level_for($rankScore);
    render('profile', compact('u', 'p', 'tab', 'posts', 'projects', 'services', 'badges', 'courses', 'mentor', 'followers', 'following', 'recommendations', 'vouches', 'reputation', 'canRecommend', 'myRecommendation', 'rankScore', 'lvl'));
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
    ensure_profile_customization_schema();
    $current = profile_data((int) $u['id']);
    $roster = $u['student_id'] ? qrow('SELECT * FROM roster WHERE university_id = ? AND student_id = ?', [$u['university_id'], $u['student_id']]) : null;
    $name = $roster ? $roster['full_name'] : input('full_name');
    if (mb_strlen($name) < 3) { flash('Please enter your full name.', 'error'); redirect('profile/edit'); }
    $nameStyle = input('name_style', 'classic');
    $profileEffect = input('profile_effect', 'none');
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
        'discord' => safe_url(input('discord')),
        'whatsapp' => safe_phone(input('whatsapp')),
        'phone' => safe_phone(input('phone')),
        'name_style' => in_array($nameStyle, PROFILE_NAME_STYLES, true) ? $nameStyle : 'classic',
        'profile_effect' => in_array($profileEffect, PROFILE_EFFECTS, true) ? $profileEffect : 'none',
    ];
    try { if ($img = save_upload('avatar', 'avatars')) $data['avatar_url'] = $img; } catch (RuntimeException $ex) { flash($ex->getMessage(), 'error'); redirect('profile/edit'); }
    $oldCover = $current['cover_image'] ?? null;
    if (input('remove_cover_image')) $data['cover_image'] = null;
    try {
        if ($cover = save_upload('cover_image', 'profile-covers')) $data['cover_image'] = $cover;
    } catch (RuntimeException $ex) { flash($ex->getMessage(), 'error'); redirect('profile/edit'); }
    update('users', $data, 'id = ?', [$u['id']]);
    if (array_key_exists('cover_image', $data) && $data['cover_image'] !== $oldCover) delete_profile_cover_file($oldCover);
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

function profile_recommend(int $id): void
{
    $u = require_login();
    ensure_reputation_schema();
    $subject = qrow("SELECT id, full_name FROM users WHERE id = ? AND status = 'active'", [$id]) ?? abort(404);
    $eligibility = recommendation_eligibility((int) $u['id'], $id);
    if (!$eligibility) { flash('Recommendations unlock after completed marketplace work, a completed project, or a mentoring session.', 'error'); redirect("profile/$id?tab=recommendations"); }
    $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 800);
    $rating = max(1, min(5, input_int('rating', 5)));
    if (mb_strlen($body) < 20) { flash('Write at least 20 characters about your experience.', 'error'); redirect("profile/$id?tab=recommendations"); }
    $existing = qrow('SELECT id FROM recommendations WHERE author_id = ? AND subject_id = ?', [$u['id'], $id]);
    $data = ['context_type' => $eligibility['type'], 'context_id' => $eligibility['id'], 'rating' => $rating, 'body' => $body, 'updated_at' => now()];
    if ($existing) update('recommendations', $data, 'id = ?', [$existing['id']]);
    else insert('recommendations', ['author_id' => $u['id'], 'subject_id' => $id] + $data);
    refresh_reputation_score($id);
    notify($id, 'review', $u['full_name'] . ' recommended you after ' . strtolower($eligibility['label']) . '.', "profile/$id?tab=recommendations");
    flash('Your verified recommendation is now part of ' . $subject['full_name'] . '’s profile.');
    redirect("profile/$id?tab=recommendations");
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
    ensure_reputation_schema();
    $people = qall('SELECT u.id, u.full_name, u.avatar_url, u.email, u.headline, u.major, u.role, u.verified, u.points, u.reputation_score, u.theme FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.reputation_score DESC, u.points DESC, u.id LIMIT 60', $params);
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
