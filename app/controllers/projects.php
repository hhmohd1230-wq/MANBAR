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
    if (!$path || !preg_match('#^uploads/projects/[a-f0-9]{24}\.(?:jpg|png|webp|gif)$#i', $path)) return;
    $file = __DIR__ . '/../../public/' . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($file)) @unlink($file);
}

function page_projects(): void
{
    $u = require_login();
    ensure_project_cover_schema();
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
    $projects = qall('SELECT p.*, u.full_name, u.avatar_url, u.email, u.verified,
        (SELECT COUNT(*) FROM project_members m WHERE m.project_id = p.id) AS members,
        (SELECT COUNT(*) FROM project_tasks t WHERE t.project_id = p.id) AS tasks,
        (SELECT COUNT(*) FROM project_tasks t WHERE t.project_id = p.id AND t.status = \'done\') AS tasks_done
        FROM projects p JOIN users u ON u.id = p.owner_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id DESC LIMIT 60', $params);
    $skills = [];
    foreach (qall("SELECT needed_skills FROM projects WHERE hidden = 0 AND needed_skills <> ''") as $r) foreach (csv_list($r['needed_skills']) as $s) $skills[$s] = ($skills[$s] ?? 0) + 1;
    arsort($skills);
    render('projects', ['u' => $u, 'projects' => $projects, 'status' => $status, 'skill' => $skill, 'q' => $q, 'mine' => $mine, 'skills' => array_slice(array_keys($skills), 0, 10)]);
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
    ensure_project_cover_schema();
    return qrow('SELECT p.*, u.full_name AS owner_name, u.avatar_url AS owner_avatar, u.email AS owner_email, u.verified AS owner_verified FROM projects p JOIN users u ON u.id = p.owner_id WHERE p.id = ?', [$id]) ?? abort(404, 'Project not found.');
}
function project_member(int $pid, int $uid): bool { return (bool) qval('SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ?', [$pid, $uid]); }

function page_project(int $id): void
{
    $u = require_login();
    $p = load_project($id);
    if ($p['hidden'] && !is_admin() && (int) $p['owner_id'] !== (int) $u['id']) abort(404);
    $isOwner = (int) $p['owner_id'] === (int) $u['id'];
    $isMember = project_member($id, (int) $u['id']);
    $members = qall('SELECT m.*, u.full_name, u.avatar_url, u.email, u.verified, u.headline FROM project_members m JOIN users u ON u.id = m.user_id WHERE m.project_id = ? ORDER BY m.joined_at', [$id]);
    $apps = $isOwner ? qall("SELECT a.*, u.full_name, u.avatar_url, u.email, u.major, u.verified FROM project_applications a JOIN users u ON u.id = a.user_id WHERE a.project_id = ? AND a.status = 'pending' ORDER BY a.id", [$id]) : [];
    $myApp = qrow('SELECT * FROM project_applications WHERE project_id = ? AND user_id = ?', [$id, $u['id']]);
    $tasks = ($isMember || is_admin()) ? qall('SELECT t.*, u.full_name AS assignee FROM project_tasks t LEFT JOIN users u ON u.id = t.assignee_id WHERE t.project_id = ? ORDER BY t.id', [$id]) : [];
    $msgs = ($isMember || is_admin()) ? qall('SELECT m.*, u.full_name, u.avatar_url, u.email FROM project_messages m JOIN users u ON u.id = m.user_id WHERE m.project_id = ? ORDER BY m.id DESC LIMIT 40', [$id]) : [];
    $updates = qall('SELECT m.*, u.full_name, u.avatar_url, u.email FROM project_messages m JOIN users u ON u.id = m.user_id WHERE m.project_id = ? AND m.is_update = 1 ORDER BY m.id DESC LIMIT 10', [$id]);
    render('project', compact('u', 'p', 'isOwner', 'isMember', 'members', 'apps', 'myApp', 'tasks', 'msgs', 'updates'));
}

function project_apply(int $id): void
{
    $u = require_login();
    $p = load_project($id);
    if ($p['status'] !== 'open') { flash('This project is not accepting applications.', 'error'); redirect("projects/$id"); }
    if (project_member($id, (int) $u['id']) || qval('SELECT COUNT(*) FROM project_applications WHERE project_id = ? AND user_id = ?', [$id, $u['id']])) { flash('You already applied or you are in the team.', 'error'); redirect("projects/$id"); }
    if ((int) qval('SELECT COUNT(*) FROM project_members WHERE project_id = ?', [$id]) >= (int) $p['max_members']) { flash('The team is full.', 'error'); redirect("projects/$id"); }
    insert('project_applications', ['project_id' => $id, 'user_id' => $u['id'], 'message' => mb_substr(input('message'), 0, 1000)]);
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
        insert('project_members', ['project_id' => $id, 'user_id' => $a['user_id'], 'role' => 'Member', 'joined_at' => now()]);
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
            notify((int) $m['user_id'], 'completed', '“' . excerpt($p['title'], 50) . '” was marked completed — +30 points!', "projects/$id");
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
    qexec('DELETE FROM projects WHERE id = ?', [$id]);
    delete_project_cover_file($p['cover_image'] ?? null);
    flash('Project deleted.');
    redirect('projects');
}
