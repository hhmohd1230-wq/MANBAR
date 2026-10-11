<?php
function ensure_project_cover_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $cols = db_driver() === 'sqlite'
        ? array_column(qall('PRAGMA table_info(projects)'), 'name')
        : array_column(qall('SHOW COLUMNS FROM projects'), 'Field');
    if (!in_array('cover_image', $cols, true)) qexec('ALTER TABLE projects ADD COLUMN cover_image VARCHAR(255) NULL');
    if (!in_array('cover_theme', $cols, true)) qexec('ALTER TABLE projects ADD COLUMN cover_theme INT NULL DEFAULT NULL');
}

/** Open roles, a milestone roadmap and shared files for each project (created on first use). */
function ensure_project_extra_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    ensure_project_cover_schema();
    ensure_learning_schema();   // table_columns()
    $sqlite = db_driver() === 'sqlite';
    $pk = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $t = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    qexec("CREATE TABLE IF NOT EXISTS project_roles (id $pk, project_id INT NOT NULL, title VARCHAR(80) NOT NULL, skills VARCHAR(200) NULL, slots INT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS project_milestones (id $pk, project_id INT NOT NULL, title VARCHAR(160) NOT NULL, due_date DATE NULL, done INT NOT NULL DEFAULT 0, done_at DATETIME NULL, created_at DATETIME NOT NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS project_files (id $pk, project_id INT NOT NULL, user_id INT NOT NULL, kind VARCHAR(10) NOT NULL, title VARCHAR(200) NOT NULL, url VARCHAR(500) NULL,
        file_path VARCHAR(255) NULL, file_name VARCHAR(255) NULL, mime VARCHAR(120) NULL, size INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE)$t");
    if (!in_array('role_id', table_columns('project_applications'), true)) qexec('ALTER TABLE project_applications ADD COLUMN role_id INT NULL');
    if (!in_array('role_id', table_columns('project_members'), true)) qexec('ALTER TABLE project_members ADD COLUMN role_id INT NULL');
}
/** A user's skills (lower-case) for matching against what projects need. */
function user_skill_set(int $uid): array
{
    return array_map('mb_strtolower', array_column(qall("SELECT name FROM user_skills WHERE user_id = ? AND kind = 'skill'", [$uid]), 'name'));
}
function project_can_manage(array $p, array $u): bool { return (int) $p['owner_id'] === (int) $u['id']; }

function project_cover_theme(array $project): int
{
    if (isset($project['cover_theme'])) return max(0, min(count(PROJECT_COVER_THEMES) - 1, (int) $project['cover_theme']));
    $count = count(PROJECT_COVER_THEMES);
    return ($count - (abs((int) ($project['id'] ?? 0)) % $count)) % $count;
}

function project_cover_style(array $project): string
{
    if (empty($project['cover_image'])) return '';
    $src = url((string) $project['cover_image']);
    return '--project-cover-image:url("' . str_replace(['"', "'", ')'], '', $src) . '");';
}

function delete_project_cover_file(?string $path): void
{
    if (!$path || !preg_match('#^uploads/projects/[a-f0-9]{20,24}\.(?:jpg|png|webp|gif)$#i', $path)) return;
    $file = __DIR__ . '/../../public/' . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($file)) @unlink($file);
}

function page_projects(): void
{
    $u = require_login();
    ensure_project_extra_schema();
    ensure_reputation_schema();
    $status = input('status');
    $skill = input('skill');
    $q = input('q');
    $mine = input('mine');
    $where = ['p.hidden = 0'];
    $params = [];
    if (in_array($status, ['open', 'in_progress', 'completed'], true)) { $where[] = 'p.status = ?'; $params[] = $status; }
    if ($skill !== '') { $where[] = 'p.needed_skills LIKE ?'; $params[] = "%$skill%"; }
    if ($q !== '') { $where[] = '(p.title LIKE ? OR p.description LIKE ?)'; array_push($params, "%$q%", "%$q%"); }
    if ($mine) { $where[] = '(p.owner_id = ? OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?))'; array_push($params, $u['id'], $u['id']); }
    $projects = qall('SELECT p.*, u.full_name, u.avatar_url, u.email, u.verified, u.reputation_score,
        (SELECT COUNT(*) FROM project_members m WHERE m.project_id = p.id) AS members,
        (SELECT COUNT(*) FROM project_tasks t WHERE t.project_id = p.id) AS tasks,
        (SELECT COUNT(*) FROM project_tasks t WHERE t.project_id = p.id AND t.status = \'done\') AS tasks_done,
        (SELECT COUNT(*) FROM project_milestones ms WHERE ms.project_id = p.id) AS milestones,
        (SELECT COUNT(*) FROM project_milestones ms WHERE ms.project_id = p.id AND ms.done = 1) AS milestones_done,
        (SELECT COALESCE(SUM(r.slots), 0) FROM project_roles r WHERE r.project_id = p.id) AS role_slots,
        (SELECT COUNT(*) FROM project_files f WHERE f.project_id = p.id) AS files,
        GREATEST(p.created_at, COALESCE((SELECT MAX(m.created_at) FROM project_messages m WHERE m.project_id = p.id), p.created_at), COALESCE((SELECT MAX(t.created_at) FROM project_tasks t WHERE t.project_id = p.id), p.created_at)) AS active_at
        FROM projects p JOIN users u ON u.id = p.owner_id WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . (input('sort') === 'active' ? 'active_at DESC' : 'p.id DESC') . ' LIMIT 60', $params);
    // faces of the team on each card
    $faces = [];
    if ($projects) foreach (qall('SELECT m.project_id, u.id, u.full_name, u.avatar_url, u.email FROM project_members m JOIN users u ON u.id = m.user_id WHERE m.project_id IN (' . implode(',', array_map('intval', array_column($projects, 'id'))) . ') ORDER BY m.joined_at') as $f) $faces[(int) $f['project_id']][] = $f;
    // projects that need what I can do
    $mySkills = user_skill_set((int) $u['id']);
    $matches = [];
    if ($mySkills) foreach (qall("SELECT p.*, u.full_name, u.avatar_url, u.email, (SELECT COUNT(*) FROM project_members m WHERE m.project_id = p.id) AS members FROM projects p JOIN users u ON u.id = p.owner_id
        WHERE p.hidden = 0 AND p.status = 'open' AND p.owner_id <> ? AND NOT EXISTS (SELECT 1 FROM project_members m WHERE m.project_id = p.id AND m.user_id = ?)", [$u['id'], $u['id']]) as $mp) {
        $need = array_merge(csv_list($mp['needed_skills']), ...array_map(fn($r) => csv_list($r['skills']), qall('SELECT skills FROM project_roles WHERE project_id = ?', [$mp['id']])));
        $hit = array_values(array_unique(array_filter($need, fn($n) => in_array(mb_strtolower(trim($n)), $mySkills, true))));
        if ($hit && (int) $mp['members'] < (int) $mp['max_members']) $matches[] = $mp + ['hit' => $hit];
    }
    usort($matches, fn($a, $b) => count($b['hit']) <=> count($a['hit']));
    $matches = array_slice($matches, 0, 3);
    $stats = qrow("SELECT (SELECT COUNT(*) FROM projects WHERE hidden = 0 AND status = 'open') AS open,
        (SELECT COUNT(DISTINCT m.user_id) FROM project_members m JOIN projects p ON p.id = m.project_id WHERE p.hidden = 0) AS builders,
        (SELECT COUNT(*) FROM project_tasks t JOIN projects p ON p.id = t.project_id WHERE p.hidden = 0 AND t.status = 'done') AS tasks_done,
        (SELECT COUNT(*) FROM projects WHERE hidden = 0 AND status = 'completed') AS completed");
    $counts = array_column(qall('SELECT status, COUNT(*) AS n FROM projects WHERE hidden = 0 GROUP BY status'), 'n', 'status');
    $skills = [];
    foreach (qall("SELECT needed_skills FROM projects WHERE hidden = 0 AND needed_skills <> ''") as $r) foreach (csv_list($r['needed_skills']) as $s) $skills[$s] = ($skills[$s] ?? 0) + 1;
    arsort($skills);
    render('projects', ['u' => $u, 'projects' => $projects, 'status' => $status, 'skill' => $skill, 'q' => $q, 'mine' => $mine, 'skills' => array_slice(array_keys($skills), 0, 10),
        'faces' => $faces, 'matches' => $matches, 'mySkills' => $mySkills, 'stats' => $stats, 'counts' => $counts, 'sort' => input('sort')]);
}

function page_project_new(): void
{
    $u = require_login();
    ensure_project_cover_schema();
    $from = input_int('from_post') ? qrow('SELECT id, title, body, tags FROM posts WHERE id = ? AND user_id = ?', [input_int('from_post'), $u['id']]) : null;
    render('project_new', compact('u', 'from'));
}

function project_create(): void
{
    $u = require_login();
    ensure_project_cover_schema();
    $title = mb_substr(input('title'), 0, 200);
    $desc = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 5000);
    if (mb_strlen($title) < 3 || mb_strlen($desc) < 15) { flash('Add a title and a description (15+ characters).', 'error'); redirect('projects/new'); }
    try {
        $coverImage = save_upload('cover_image', 'projects');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
        redirect('projects/new');
    }
    $id = insert('projects', [
        'owner_id' => $u['id'], 'title' => $title, 'description' => $desc,
        'needed_skills' => implode(',', array_slice(parse_skill_input(input('needed_skills')), 0, 10)) ?: null,
        'max_members' => max(2, min(30, input_int('max_members', 5))),
        'cover_image' => $coverImage,
        'cover_theme' => max(0, min(count(PROJECT_COVER_THEMES) - 1, input_int('cover_theme'))),
    ]);
    insert('project_members', ['project_id' => $id, 'user_id' => $u['id'], 'role' => 'Project owner', 'joined_at' => now()]);
    if (input_int('from_post')) update('posts', ['project_id' => $id], 'id = ? AND user_id = ?', [input_int('from_post'), $u['id']]);
    award_points((int) $u['id'], 15, 'Started a project');
    flash('Project created! Add tasks and wait for applications. +15 points');
    redirect("projects/$id");
}

function load_project(int $id): array
{
    ensure_project_extra_schema();
    ensure_reputation_schema();
    return qrow('SELECT p.*, u.full_name AS owner_name, u.avatar_url AS owner_avatar, u.email AS owner_email, u.verified AS owner_verified, u.reputation_score AS owner_reputation_score FROM projects p JOIN users u ON u.id = p.owner_id WHERE p.id = ?', [$id]) ?? abort(404, 'Project not found.');
}
function project_member(int $pid, int $uid): bool { return (bool) qval('SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ?', [$pid, $uid]); }

function page_project(int $id): void
{
    $u = require_login();
    $p = load_project($id);
    if ($p['hidden'] && !is_admin() && (int) $p['owner_id'] !== (int) $u['id']) abort(404);
    $isOwner = (int) $p['owner_id'] === (int) $u['id'];
    $isMember = project_member($id, (int) $u['id']);
    $members = qall('SELECT m.*, u.full_name, u.avatar_url, u.email, u.verified, u.reputation_score, u.headline FROM project_members m JOIN users u ON u.id = m.user_id WHERE m.project_id = ? ORDER BY m.joined_at', [$id]);
    $apps = $isOwner ? qall("SELECT a.*, u.full_name, u.avatar_url, u.email, u.major, u.verified, u.reputation_score, r.title AS role_title FROM project_applications a JOIN users u ON u.id = a.user_id LEFT JOIN project_roles r ON r.id = a.role_id WHERE a.project_id = ? AND a.status = 'pending' ORDER BY a.id", [$id]) : [];
    $myApp = qrow('SELECT * FROM project_applications WHERE project_id = ? AND user_id = ?', [$id, $u['id']]);
    $tasks = ($isMember || is_admin()) ? qall('SELECT t.*, u.full_name AS assignee FROM project_tasks t LEFT JOIN users u ON u.id = t.assignee_id WHERE t.project_id = ? ORDER BY t.id', [$id]) : [];
    $msgs = ($isMember || is_admin()) ? qall('SELECT m.*, u.full_name, u.avatar_url, u.email FROM project_messages m JOIN users u ON u.id = m.user_id WHERE m.project_id = ? ORDER BY m.id DESC LIMIT 40', [$id]) : [];
    $updates = qall('SELECT m.*, u.full_name, u.avatar_url, u.email FROM project_messages m JOIN users u ON u.id = m.user_id WHERE m.project_id = ? AND m.is_update = 1 ORDER BY m.id DESC LIMIT 10', [$id]);
    $myVouches = [];
    if ($isMember && $p['status'] === 'completed') foreach (qall('SELECT * FROM project_vouches WHERE project_id = ? AND author_id = ?', [$id, $u['id']]) as $vouch) $myVouches[(int) $vouch['subject_id']] = $vouch;
    $roles = qall('SELECT r.*, (SELECT COUNT(*) FROM project_members m WHERE m.project_id = r.project_id AND m.role_id = r.id) AS filled FROM project_roles r WHERE r.project_id = ? ORDER BY r.id', [$id]);
    $milestones = qall('SELECT * FROM project_milestones WHERE project_id = ? ORDER BY due_date IS NULL, due_date, id', [$id]);
    $canSeeFiles = $isMember || is_admin() || $p['status'] === 'completed';   // the team's workspace; public proof of work once completed
    $files = $canSeeFiles ? qall('SELECT f.*, u.full_name FROM project_files f JOIN users u ON u.id = f.user_id WHERE f.project_id = ? ORDER BY f.id DESC', [$id]) : [];
    $fileCount = (int) qval('SELECT COUNT(*) FROM project_files WHERE project_id = ?', [$id]);
    $mySkills = user_skill_set((int) $u['id']);
    $doneTasks = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));
    $taskTotal = $tasks ? count($tasks) : (int) qval('SELECT COUNT(*) FROM project_tasks WHERE project_id = ?', [$id]);
    if (!$tasks) $doneTasks = (int) qval("SELECT COUNT(*) FROM project_tasks WHERE project_id = ? AND status = 'done'", [$id]);
    $lastActive = qval('SELECT MAX(created_at) FROM project_messages WHERE project_id = ?', [$id]) ?: $p['created_at'];
    render('project', compact('u', 'p', 'isOwner', 'isMember', 'members', 'apps', 'myApp', 'tasks', 'msgs', 'updates', 'myVouches', 'roles', 'milestones', 'files', 'fileCount', 'canSeeFiles', 'mySkills', 'doneTasks', 'taskTotal', 'lastActive'));
}

function project_apply(int $id): void
{
    $u = require_login();
    $p = load_project($id);
    if ($p['status'] !== 'open') { flash('This project is not accepting applications.', 'error'); redirect("projects/$id"); }
    if (project_member($id, (int) $u['id']) || qval('SELECT COUNT(*) FROM project_applications WHERE project_id = ? AND user_id = ?', [$id, $u['id']])) { flash('You already applied or you are in the team.', 'error'); redirect("projects/$id"); }
    if ((int) qval('SELECT COUNT(*) FROM project_members WHERE project_id = ?', [$id]) >= (int) $p['max_members']) { flash('The team is full.', 'error'); redirect("projects/$id"); }
    $role = input_int('role_id') ? qrow('SELECT * FROM project_roles WHERE id = ? AND project_id = ?', [input_int('role_id'), $id]) : null;
    if ($role && (int) qval('SELECT COUNT(*) FROM project_members WHERE project_id = ? AND role_id = ?', [$id, $role['id']]) >= (int) $role['slots']) { flash('That role is already filled. Pick another one.', 'error'); redirect("projects/$id#roles"); }
    insert('project_applications', ['project_id' => $id, 'user_id' => $u['id'], 'message' => mb_substr(input('message'), 0, 1000), 'role_id' => $role['id'] ?? null]);
    notify((int) $p['owner_id'], 'application', $u['full_name'] . ' applied to “' . excerpt($p['title'], 50) . '”', "projects/$id");
    flash('Application sent! The project owner will review it.');
    redirect("projects/$id");
}

function project_decide(int $id, int $appId): void
{
    $u = require_login();
    $p = load_project($id);
    if ((int) $p['owner_id'] !== (int) $u['id']) abort(403);
    $a = qrow("SELECT * FROM project_applications WHERE id = ? AND project_id = ? AND status = 'pending'", [$appId, $id]) ?? abort(404);
    if (input('decision') === 'accept') {
        if ((int) qval('SELECT COUNT(*) FROM project_members WHERE project_id = ?', [$id]) >= (int) $p['max_members']) { flash('Team is full — raise the team size first.', 'error'); redirect("projects/$id"); }
        update('project_applications', ['status' => 'accepted'], 'id = ?', [$appId]);
        $role = $a['role_id'] ? qrow('SELECT * FROM project_roles WHERE id = ?', [$a['role_id']]) : null;
        insert('project_members', ['project_id' => $id, 'user_id' => $a['user_id'], 'role' => $role['title'] ?? 'Member', 'role_id' => $role['id'] ?? null, 'joined_at' => now()]);
        notify((int) $a['user_id'], 'accepted', 'You were accepted into “' . excerpt($p['title'], 50) . '” 🎉', "projects/$id");
        award_points((int) $a['user_id'], 10, 'Joined a project team');
        flash('Applicant added to the team.');
    } else {
        update('project_applications', ['status' => 'rejected'], 'id = ?', [$appId]);
        notify((int) $a['user_id'], 'rejected', 'Your application to “' . excerpt($p['title'], 50) . '” was not accepted this time.', "projects/$id");
        flash('Applicant declined.');
    }
    redirect("projects/$id");
}

function project_leave(int $id): void
{
    $u = require_login();
    $p = load_project($id);
    if ((int) $p['owner_id'] === (int) $u['id']) { flash('Owners cannot leave their own project — close it instead.', 'error'); redirect("projects/$id"); }
    qexec('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$id, $u['id']]);
    qexec('UPDATE project_tasks SET assignee_id = NULL WHERE project_id = ? AND assignee_id = ?', [$id, $u['id']]);
    notify((int) $p['owner_id'], 'left', $u['full_name'] . ' left “' . excerpt($p['title'], 50) . '”', "projects/$id");
    flash('You left the project.');
    redirect('projects');
}

function project_task_add(int $id): void
{
    $u = require_login();
    if (!project_member($id, (int) $u['id'])) abort(403);
    $title = mb_substr(input('title'), 0, 200);
    if ($title !== '') insert('project_tasks', ['project_id' => $id, 'title' => $title, 'assignee_id' => input_int('assignee_id') ?: null, 'due_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', input('due_date')) ? input('due_date') : null]);
    redirect("projects/$id#tasks");
}
function project_task_move(int $id, int $tid): void
{
    $u = require_login();
    if (!project_member($id, (int) $u['id'])) abort(403);
    $st = input('status');
    if (in_array($st, ['todo', 'doing', 'done'], true)) update('project_tasks', ['status' => $st], 'id = ? AND project_id = ?', [$tid, $id]);
    if (input('delete')) qexec('DELETE FROM project_tasks WHERE id = ? AND project_id = ?', [$tid, $id]);
    redirect("projects/$id#tasks");
}
function project_message(int $id): void
{
    $u = require_login();
    if (!project_member($id, (int) $u['id'])) abort(403);
    $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 2000);
    if ($body !== '') {
        $isUpdate = input('is_update') ? 1 : 0;
        insert('project_messages', ['project_id' => $id, 'user_id' => $u['id'], 'body' => $body, 'is_update' => $isUpdate]);
        $p = load_project($id);
        foreach (qall('SELECT user_id FROM project_members WHERE project_id = ?', [$id]) as $m) notify((int) $m['user_id'], 'project_msg', $u['full_name'] . ($isUpdate ? ' posted an update in ' : ' wrote in ') . '“' . excerpt($p['title'], 40) . '”', "projects/$id#chat");
    }
    redirect("projects/$id#chat");
}

function project_vouch(int $id, int $memberId): void
{
    $u = require_login();
    $p = load_project($id);
    $authorId = (int) $u['id'];
    if ($p['status'] !== 'completed') { flash('Team vouches unlock after the project is completed.', 'error'); redirect("projects/$id"); }
    if ($authorId === $memberId || !project_member($id, $authorId) || !project_member($id, $memberId)) abort(403);
    $subject = qrow('SELECT id, full_name FROM users WHERE id = ? AND status = ?', [$memberId, 'active']) ?? abort(404);
    $allowedTraits = ['Collaborative', 'Reliable', 'Strong communicator', 'High-quality work', 'Problem solver', 'Supportive leader'];
    $postedTraits = is_array($_POST['traits'] ?? null) ? $_POST['traits'] : [];
    $traits = array_values(array_slice(array_intersect($allowedTraits, array_map('strval', $postedTraits)), 0, 3));
    $rating = max(1, min(5, input_int('rating', 5)));
    $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 800);
    if (mb_strlen($body) < 30) { flash('Write at least 30 characters about how this teammate contributed.', 'error'); redirect("projects/$id#team-vouches"); }
    $xp = project_vouch_xp($rating, count($traits));
    $existing = qrow('SELECT id FROM project_vouches WHERE project_id = ? AND author_id = ? AND subject_id = ?', [$id, $authorId, $memberId]);
    $data = ['rating' => $rating, 'traits' => $traits ? implode(',', $traits) : null, 'body' => $body, 'xp_value' => $xp, 'updated_at' => now()];
    if ($existing) update('project_vouches', $data, 'id = ?', [$existing['id']]);
    else insert('project_vouches', ['project_id' => $id, 'author_id' => $authorId, 'subject_id' => $memberId] + $data);
    refresh_reputation_score($memberId);
    check_badges($memberId);
    notify($memberId, 'review', $u['full_name'] . ' vouched for your work on “' . excerpt($p['title'], 48) . '” — +' . $xp . ' reputation XP.', "profile/$memberId?tab=recommendations");
    flash(($existing ? 'Vouch updated. ' : 'Vouch published. ') . $subject['full_name'] . ' receives +' . $xp . ' reputation XP.');
    redirect("projects/$id#team-vouches");
}

function project_status(int $id): void
{
    $u = require_login();
    $p = load_project($id);
    if ((int) $p['owner_id'] !== (int) $u['id']) abort(403);
    $st = input('status');
    if (!in_array($st, ['open', 'in_progress', 'completed', 'closed'], true)) abort(422);
    $data = ['status' => $st];
    $data['cover_theme'] = max(0, min(count(PROJECT_COVER_THEMES) - 1, input_int('cover_theme', project_cover_theme($p))));
    if (input('remove_cover_image')) $data['cover_image'] = null;
    try {
        if ($coverImage = save_upload('cover_image', 'projects')) $data['cover_image'] = $coverImage;
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
        redirect("projects/$id");
    }
    if ($st === 'completed') {
        $data['outcome'] = mb_substr(trim((string) ($_POST['outcome'] ?? '')), 0, 3000) ?: null;
        if ($p['status'] !== 'completed') foreach (qall('SELECT user_id FROM project_members WHERE project_id = ?', [$id]) as $m) {
            award_points((int) $m['user_id'], 30, 'Project completed');
            notify((int) $m['user_id'], 'completed', '“' . excerpt($p['title'], 50) . '” was completed — +30 activity XP. You can now vouch for your teammates.', "projects/$id#team-vouches");
        }
    }
    if ($p['max_members'] != input_int('max_members', (int) $p['max_members']) && input_int('max_members')) $data['max_members'] = max(2, min(30, input_int('max_members')));
    update('projects', $data, 'id = ?', [$id]);
    if (array_key_exists('cover_image', $data) && $data['cover_image'] !== ($p['cover_image'] ?? null)) delete_project_cover_file($p['cover_image'] ?? null);
    flash('Project updated.');
    redirect("projects/$id");
}
function project_delete(int $id): void
{
    $u = require_login();
    $p = load_project($id);
    if ((int) $p['owner_id'] !== (int) $u['id'] && !is_admin()) abort(403);
    foreach (qall("SELECT file_path FROM project_files WHERE project_id = ? AND kind = 'file'", [$id]) as $f) @unlink(dirname(__DIR__, 2) . '/storage/project_files/' . $f['file_path']);
    @rmdir(dirname(__DIR__, 2) . "/storage/project_files/$id");
    qexec('DELETE FROM projects WHERE id = ?', [$id]);
    delete_project_cover_file($p['cover_image'] ?? null);
    flash('Project deleted.');
    redirect('projects');
}

/* ---------- open roles, milestones, files ---------- */

function project_role_add(int $id): void
{
    $u = require_login();
    $p = load_project($id);
    if (!project_can_manage($p, $u)) abort(403);
    if (input('delete')) { qexec('DELETE FROM project_roles WHERE id = ? AND project_id = ?', [input_int('delete'), $id]); qexec('UPDATE project_members SET role_id = NULL WHERE project_id = ? AND role_id = ?', [$id, input_int('delete')]); redirect("projects/$id#roles"); }
    $title = mb_substr(input('title'), 0, 80);
    if ($title === '') { flash('Give the role a name, e.g. UI designer.', 'error'); redirect("projects/$id#roles"); }
    insert('project_roles', ['project_id' => $id, 'title' => $title, 'skills' => implode(',', array_slice(parse_skill_input(input('skills')), 0, 6)) ?: null, 'slots' => max(1, min(10, input_int('slots', 1)))]);
    flash('Role added. People can now apply for it.');
    redirect("projects/$id#roles");
}

function project_milestone(int $id): void
{
    $u = require_login();
    $p = load_project($id);
    $member = project_member($id, (int) $u['id']);
    if (!$member) abort(403);
    if ($mid = input_int('toggle')) {   // any team member can tick a milestone
        $m = qrow('SELECT * FROM project_milestones WHERE id = ? AND project_id = ?', [$mid, $id]) ?? abort(404);
        update('project_milestones', ['done' => $m['done'] ? 0 : 1, 'done_at' => $m['done'] ? null : now()], 'id = ?', [$mid]);
        if (!$m['done']) insert('project_messages', ['project_id' => $id, 'user_id' => $u['id'], 'body' => '🏁 Milestone reached: ' . $m['title'], 'is_update' => 1]);
        redirect("projects/$id#roadmap");
    }
    if (!project_can_manage($p, $u)) abort(403);
    if ($mid = input_int('delete')) { qexec('DELETE FROM project_milestones WHERE id = ? AND project_id = ?', [$mid, $id]); redirect("projects/$id#roadmap"); }
    $title = mb_substr(input('title'), 0, 160);
    if ($title !== '') insert('project_milestones', ['project_id' => $id, 'title' => $title, 'due_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', input('due_date')) ? input('due_date') : null]);
    redirect("projects/$id#roadmap");
}

function project_files_add(int $id): void
{
    $u = require_login();
    load_project($id);
    if (!project_member($id, (int) $u['id'])) abort(403);
    $saved = 0; $skipped = [];
    $F = $_FILES['files'] ?? null;
    if ($F && is_array($F['name'])) foreach ($F['name'] as $i => $name) {
        if ($name === '' || ($F['error'][$i] ?? 0) === UPLOAD_ERR_NO_FILE) continue;
        try {
            $f = store_course_file(['name' => $name, 'type' => $F['type'][$i], 'tmp_name' => $F['tmp_name'][$i], 'error' => $F['error'][$i], 'size' => $F['size'][$i]], $id, 'project_files');
            insert('project_files', ['project_id' => $id, 'user_id' => $u['id'], 'kind' => 'file', 'title' => mb_substr(pathinfo($f['file_name'], PATHINFO_FILENAME), 0, 200)] + $f);
            $saved++;
        } catch (Throwable $ex) { $skipped[] = basename((string) $name); }
    }
    foreach (preg_split('/\R/', (string) ($_POST['links'] ?? '')) as $line) {
        $line = trim($line);
        [$title, $link] = str_contains($line, '|') ? array_map('trim', explode('|', $line, 2)) : ['', $line];
        if (!preg_match('#^https?://[^\s]+$#i', $link)) continue;
        insert('project_files', ['project_id' => $id, 'user_id' => $u['id'], 'kind' => 'link', 'title' => mb_substr($title ?: link_host($link), 0, 200), 'url' => mb_substr($link, 0, 500)]);
        $saved++;
    }
    flash($skipped ? $saved . ' added. Skipped (type or size not allowed): ' . implode(', ', array_slice($skipped, 0, 5)) : ($saved ? 'Shared with your team.' : 'Choose files or paste links.'), $skipped || !$saved ? 'error' : 'success');
    redirect("projects/$id#files");
}

function project_file_delete(int $id, int $fid): void
{
    $u = require_login();
    $p = load_project($id);
    $f = qrow('SELECT * FROM project_files WHERE id = ? AND project_id = ?', [$fid, $id]) ?? abort(404);
    if ((int) $f['user_id'] !== (int) $u['id'] && !project_can_manage($p, $u)) abort(403);
    if ($f['file_path']) @unlink(dirname(__DIR__, 2) . '/storage/project_files/' . $f['file_path']);
    qexec('DELETE FROM project_files WHERE id = ?', [$fid]);
    redirect("projects/$id#files");
}

/** Team files: members only while the project runs; every signed-in member once it's completed (proof of work). */
function project_file(int $id, int $fid): void
{
    $u = require_login();
    $p = load_project($id);
    if (!project_member($id, (int) $u['id']) && !is_admin() && $p['status'] !== 'completed') abort(403, 'Only the team can open project files.');
    $f = qrow("SELECT * FROM project_files WHERE id = ? AND project_id = ? AND kind = 'file'", [$fid, $id]) ?? abort(404);
    $path = dirname(__DIR__, 2) . '/storage/project_files/' . $f['file_path'];
    if (!is_file($path)) abort(404, 'This file is missing.');
    $inline = in_array($f['mime'], ['application/pdf', 'image/png', 'image/jpeg', 'image/webp', 'image/gif', 'text/plain'], true) && input('download') !== '1';
    header('Content-Type: ' . $f['mime']);
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=3600');
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($f['file_name']));
    readfile($path);
    exit;
}
