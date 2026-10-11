<?php
function page_learn(): void
{
    $u = require_login();
    ensure_learning_schema();
    $cat = input('cat');
    $q = input('q');
    $where = ["c.status = 'published'"];
    $params = [];
    if (isset(COURSE_CATEGORIES[$cat])) { $where[] = 'c.category = ?'; $params[] = $cat; }
    if ($q !== '') { $where[] = '(c.title LIKE ? OR c.description LIKE ?)'; array_push($params, "%$q%", "%$q%"); }
    ensure_reputation_schema();
    $courses = qall('SELECT c.*, u.full_name, u.avatar_url, u.email, u.verified, u.reputation_score,
        (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id AND l.kind = \'lesson\') AS lessons,
        (SELECT COUNT(*) FROM quizzes z WHERE z.course_id = c.id AND z.status = \'published\') AS quizzes,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students
        FROM courses c JOIN users u ON u.id = c.author_id WHERE ' . implode(' AND ', $where) . ' ORDER BY c.id DESC LIMIT 60', $params);
    $mine = qall('SELECT c.id, c.title, c.theme, c.category, c.cover_image FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE e.user_id = ? ORDER BY e.created_at DESC', [$u['id']]);
    foreach ($mine as &$m) {   // progress along the course path (lessons, summaries and quizzes)
        $p = course_path((int) $m['id'], (int) $u['id']);
        $m['total'] = count($p['steps']);
        $m['done'] = $p['finished'] ? $m['total'] : (int) $p['cur'];
        $m['finished'] = $p['finished'];
    }
    unset($m);
    $hosting = qall('SELECT c.id, c.title, c.theme, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students,
        (SELECT COUNT(*) FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id WHERE z.course_id = c.id AND a.status = \'submitted\' AND a.is_preview = 0) AS to_mark
        FROM courses c WHERE c.author_id = ? ORDER BY c.id DESC', [$u['id']]);
    $canCreate = can_create_courses($u);
    // the board at the top: live numbers and the most popular courses
    $stats = qrow("SELECT (SELECT COUNT(*) FROM courses WHERE status = 'published') AS courses,
        (SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE c.status = 'published') AS learners,
        (SELECT COUNT(*) FROM lessons l JOIN courses c ON c.id = l.course_id WHERE c.status = 'published') AS steps,
        (SELECT COUNT(*) FROM enrollments WHERE completed_at IS NOT NULL) AS finished");
    $popular = qall("SELECT c.id, c.title, c.theme, c.cover_image, c.category, c.price_aed, u.full_name, u.avatar_url, u.email,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.completed_at IS NOT NULL) AS finished
        FROM courses c JOIN users u ON u.id = c.author_id WHERE c.status = 'published' ORDER BY students DESC, c.id DESC LIMIT 4");
    render('learn', compact('u', 'courses', 'mine', 'hosting', 'cat', 'q', 'canCreate', 'stats', 'popular'));
}

function page_course(int $id, ?int $lessonId = null): void
{
    $u = require_login();
    $c = course_load($id);
    $canManage = course_can_manage($c, $u);
    $studentView = course_student_view($c, $u);   // host previewing the course as a student
    $manage = $canManage && !$studentView;
    if ($c['status'] !== 'published' && !$canManage) abort(404);
    $lessons = qall('SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order, position, id', [$id]);
    $enrolled = qrow('SELECT * FROM enrollments WHERE course_id = ? AND user_id = ?', [$id, $u['id']]);
    $asVisitor = course_preview_visitor($c, $u);   // previewing as someone who hasn't joined or paid
    if ($asVisitor) $enrolled = null;
    elseif ($studentView && !$enrolled) $enrolled = ['completed_at' => null, 'preview' => true];   // show it as a joined student sees it
    $access = $manage || $enrolled;
    $done = array_map('intval', array_column(qall('SELECT lesson_id FROM lesson_progress WHERE user_id = ?', [$u['id']]), 'lesson_id'));
    $lesson = null;
    if ($lessonId) {
        foreach ($lessons as $l) if ((int) $l['id'] === $lessonId) $lesson = $l;
        if (!$lesson) abort(404);
        if (!$access) { flash(course_is_paid($c) ? 'Buy the course to open its lessons.' : 'Enroll to open the lessons.', 'error'); redirect("learn/$id"); }
        course_step_gate($c, $u, "l$lessonId");
    }
    if ($access && !$canManage) track_course_visit($id, (int) $u['id']);
    $materials = qall('SELECT * FROM course_materials WHERE course_id = ? ORDER BY lesson_id IS NOT NULL, id', [$id]);
    $quizzes = qall('SELECT z.*, (SELECT COUNT(*) FROM quiz_questions q WHERE q.quiz_id = z.id) AS questions,
        (SELECT MAX(CASE WHEN a.max_score > 0 THEN ROUND((a.auto_score + COALESCE(a.essay_score, 0)) * 100 / a.max_score) END) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.user_id = ? AND a.status <> \'open\' AND a.is_preview = 0) AS best
        FROM quizzes z WHERE z.course_id = ? ' . ($manage ? '' : "AND z.status = 'published' ") . 'ORDER BY z.id', [$u['id'], $id]);
    $students = (int) qval('SELECT COUNT(*) FROM enrollments WHERE course_id = ?', [$id]);
    $live = qrow("SELECT s.code, z.title FROM live_sessions s JOIN quizzes z ON z.id = s.quiz_id WHERE z.course_id = ? AND s.state <> 'ended' AND s.created_at >= ? ORDER BY s.id DESC LIMIT 1", [$id, date('Y-m-d H:i:s', time() - 3 * 3600)]);
    $isAuthor = (int) $c['author_id'] === (int) $u['id'];
    [$classmates, $classmateCount] = course_classmates($id, 8);
    $path = $access && !$manage ? ($studentView ? course_path_preview($id) : course_path($id, (int) $u['id'])) : null;   // the student's place on the course path
    render('course', compact('u', 'c', 'lessons', 'enrolled', 'access', 'done', 'isAuthor', 'manage', 'canManage', 'studentView', 'lesson', 'students', 'materials', 'quizzes', 'live', 'path', 'classmates', 'classmateCount', 'asVisitor'));
}

/** Host drags lessons and quizzes into a new order. order = "l12,q5,l13,..." and must list every lesson and quiz of the course. */
function course_order_save(int $id): void
{
    course_manage_or_403($id);
    $kinds = array_column(qall('SELECT id, kind FROM lessons WHERE course_id = ?', [$id]), 'kind', 'id');
    $want = ['l' => array_map('intval', array_keys($kinds)), 'q' => array_map('intval', array_column(qall('SELECT id FROM quizzes WHERE course_id = ?', [$id]), 'id'))];
    $got = ['l' => [], 'q' => []];
    $keys = array_values(array_filter(explode(',', input('order'))));
    foreach ($keys as $k) { if (!preg_match('/^([lq])(\d+)$/', $k, $m)) json_out(['ok' => false, 'error' => 'Bad order.'], 422); $got[$m[1]][] = (int) $m[2]; }
    foreach (['l', 'q'] as $t) { $a = $got[$t]; $b = $want[$t]; sort($a); sort($b); if ($a !== $b) json_out(['ok' => false, 'error' => 'The course changed. Refresh the page and try again.'], 409); }
    $n = 0;
    foreach ($keys as $i => $k) {
        $kid = (int) substr($k, 1);
        if ($k[0] === 'l') qexec('UPDATE lessons SET sort_order = ?, position = ? WHERE id = ? AND course_id = ?', [$i + 1, $kinds[$kid] === 'summary' ? 0 : ++$n, $kid, $id]);
        else qexec('UPDATE quizzes SET sort_order = ? WHERE id = ? AND course_id = ?', [$i + 1, $kid, $id]);
    }
    json_out(['ok' => true]);
}

/** Host switches between editing (host view) and seeing the course exactly as students do (student view). */
function course_view_mode(int $id): void
{
    [$c] = course_manage_or_403($id);
    $_SESSION['student_view'][$id] = input('mode') === 'student';
    if (in_array(input('as'), ['joined', 'visitor'], true)) $_SESSION['student_view_as'][$id] = input('as');   // false = host tools (admins on other people's courses need it set explicitly)
    $back = input('back');
    redirect(preg_match('#^learn/' . $id . '(?:/(?:lesson|quiz)/\d+)?$#', $back) ? $back : "learn/$id");
}

function lesson_edit(int $id, int $lid): void
{
    course_manage_or_403($id);
    $l = qrow('SELECT * FROM lessons WHERE id = ? AND course_id = ?', [$lid, $id]) ?? abort(404);
    $title = mb_substr(input('title'), 0, 200);
    $content = mb_substr(trim((string) ($_POST['content'] ?? '')), 0, 20000);
    if ($title === '' || $content === '') { flash('Lesson title and content are required.', 'error'); redirect("learn/$id/lesson/$lid"); }
    $video = input('video_url');
    update('lessons', ['title' => $title, 'content' => $content, 'video_url' => preg_match('#^https?://#i', $video) ? mb_substr($video, 0, 250) : null], 'id = ?', [$lid]);
    flash('Lesson updated.');
    redirect("learn/$id/lesson/$lid");
}

function course_enroll(int $id): void
{
    $u = require_login();
    $c = course_load($id);
    if ($c['status'] !== 'published') abort(404);
    if (course_is_paid($c) && !course_can_manage($c, $u)) redirect("learn/$id");   // paid courses go through checkout
    if (!course_is_enrolled($id, (int) $u['id'])) { enroll_user($id, (int) $u['id']); flash('You are enrolled! Start with the first lesson.'); }
    redirect("learn/$id");
}

function lesson_complete(int $id, int $lid): void { course_step_done($id, "l$lid"); }

/** Student presses "Start course" or "Start again": go to step 1 (the host's Student view just walks through, nothing saved). */
function course_start(int $id): void
{
    $u = require_login();
    $c = course_load($id);
    $steps = course_steps($id);
    if (!$steps) { flash('This course has no steps yet.', 'error'); redirect("learn/$id"); }
    if (!course_student_view($c, $u)) {
        if (!course_is_enrolled($id, (int) $u['id'])) { flash(course_is_paid($c) ? 'Buy the course to start it.' : 'Enroll to start the course.', 'error'); redirect("learn/$id"); }
        update('enrollments', ['step_key' => $steps[0]['key']], 'course_id = ? AND user_id = ?', [$id, $u['id']]);
    }
    redirect($steps[0]['url']);
}

/** "Continue" on a step (lesson, summary or quiz). */
function course_next(int $id): void { course_step_done($id, (string) input('from')); }

function course_step_done(int $id, string $key): void
{
    $u = require_login();
    $c = course_load($id);
    if (!preg_match('/^([lq])(\d+)$/', $key, $m)) redirect("learn/$id");
    $kid = (int) $m[2];
    if (course_student_view($c, $u)) {   // host preview: walk the path without saving anything
        $steps = course_steps($id);
        $i = course_step_index($steps, $key);
        redirect($i !== null && isset($steps[$i + 1]) ? $steps[$i + 1]['url'] : "learn/$id");
    }
    if (!course_is_enrolled($id, (int) $u['id'])) abort(403);
    course_step_gate($c, $u, $key);   // can't finish a step that isn't open yet
    if ($m[1] === 'l') {
        qrow('SELECT id FROM lessons WHERE id = ? AND course_id = ?', [$kid, $id]) ?? abort(404);
        if (!qval('SELECT COUNT(*) FROM lesson_progress WHERE lesson_id = ? AND user_id = ?', [$kid, $u['id']])) {
            insert('lesson_progress', ['lesson_id' => $kid, 'user_id' => $u['id']]);
            award_points((int) $u['id'], 3, 'Completed a lesson');
        }
    } else {
        qrow('SELECT id FROM quizzes WHERE id = ? AND course_id = ?', [$kid, $id]) ?? abort(404);
        if (!qval("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? AND status <> 'open' AND is_preview = 0", [$kid, $u['id']])) {
            flash('Finish the quiz first, then continue.', 'error');
            redirect("learn/$id/quiz/$kid");
        }
    }
    redirect(course_advance($id, (int) $u['id'], $key));
}

/** My courses: the ones I joined (with my place on the path) and the ones I host (with students, marking and income). */
function page_my_courses(): void
{
    $u = require_login();
    ensure_learning_schema();
    $me = (int) $u['id'];
    $joined = qall('SELECT c.*, u.full_name, u.avatar_url, u.email, e.created_at AS joined_at, e.completed_at, e.runs FROM enrollments e JOIN courses c ON c.id = e.course_id JOIN users u ON u.id = c.author_id WHERE e.user_id = ? ORDER BY e.created_at DESC', [$me]);
    foreach ($joined as &$c) {
        $p = course_path((int) $c['id'], $me);
        $c['steps'] = count($p['steps']);
        $c['at'] = $p['finished'] ? $c['steps'] : (int) $p['cur'];
        $c['finished'] = $p['finished'];
        $c['started'] = $p['started'];
        $c['next'] = !$p['finished'] && $p['cur'] !== null ? $p['steps'][$p['cur']] : null;
        $c['last'] = qval('SELECT MAX(day) FROM course_visits WHERE course_id = ? AND user_id = ?', [$c['id'], $me]);
    }
    unset($c);
    $hosting = qall('SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.completed_at IS NOT NULL) AS finished,
        (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS steps,
        (SELECT COUNT(*) FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id WHERE z.course_id = c.id AND a.status = \'submitted\' AND a.is_preview = 0) AS to_mark,
        (SELECT COALESCE(SUM(p.amount), 0) FROM course_payments p WHERE p.course_id = c.id AND p.status = \'paid\') AS income
        FROM courses c WHERE c.author_id = ? ORDER BY c.id DESC', [$me]);
    $tab = input('tab') === 'hosting' || (!$joined && $hosting) ? 'hosting' : 'joined';
    $canCreate = can_create_courses($u);
    render('my_courses', compact('u', 'joined', 'hosting', 'tab', 'canCreate'));
}

function page_course_new(): void
{
    $u = require_login();
    if (!can_create_courses($u)) abort(403, 'Courses can be created by teachers and by members who reached the Gold rank.');
    render('course_new', ['u' => $u]);
}

function course_price_input(): float
{
    $p = round((float) str_replace(',', '.', input('price_aed', '0')), 2);
    if ($p > 0 && $p < 2) $p = 2;   // card payments need at least AED 2
    return max(0, min(9999, $p));
}

function course_create(): void
{
    $u = require_login();
    if (!can_create_courses($u)) abort(403, 'Courses can be created by teachers and by members who reached the Gold rank.');
    ensure_learning_schema();
    $title = mb_substr(input('title'), 0, 200);
    $desc = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 4000);
    if (mb_strlen($title) < 5 || mb_strlen($desc) < 20) { flash('Add a title and a description (20+ characters).', 'error'); redirect('learn/new'); }
    $id = insert('courses', [
        'author_id' => $u['id'], 'title' => $title, 'description' => $desc,
        'category' => isset(COURSE_CATEGORIES[input('category')]) ? input('category') : 'general',
        'level' => in_array(input('level'), ['beginner', 'intermediate', 'advanced'], true) ? input('level') : 'beginner',
        'theme' => random_int(0, 5), 'price_aed' => course_price_input(),
    ]);
    award_points((int) $u['id'], 15, 'Published a course');
    flash('Course created. Add lessons, files and quizzes from the host tools.');
    redirect("learn/$id");
}

function course_settings_save(int $id): void
{
    [$c] = course_manage_or_403($id);
    $title = mb_substr(input('title'), 0, 200);
    $desc = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 4000);
    if (mb_strlen($title) < 5 || mb_strlen($desc) < 20) { flash('Add a title and a description (20+ characters).', 'error'); redirect("learn/$id/dashboard#settings"); }
    update('courses', [
        'title' => $title, 'description' => $desc, 'price_aed' => course_price_input(),
        'level' => in_array(input('level'), ['beginner', 'intermediate', 'advanced'], true) ? input('level') : $c['level'],
        'category' => isset(COURSE_CATEGORIES[input('category')]) ? input('category') : $c['category'],
        'status' => input('status') === 'hidden' ? 'hidden' : 'published',
    ], 'id = ?', [$id]);
    flash('Course settings saved.');
    redirect("learn/$id/dashboard#settings");
}

/** Host sets or removes the course picture (shown on the catalog card and at the top of the course). */
function course_cover(int $id): void
{
    [$c] = course_manage_or_403($id);
    $old = (string) ($c['cover_image'] ?? '');
    try {
        $new = input('remove') ? null : (save_upload('cover', 'course-covers') ?? throw new RuntimeException('Choose an image for the cover.'));
    } catch (Throwable $ex) { flash($ex->getMessage(), 'error'); redirect("learn/$id"); }
    update('courses', ['cover_image' => $new], 'id = ?', [$id]);
    if ($old && preg_match('#^uploads/course-covers/[a-f0-9]{20}\.(?:jpg|png|webp|gif)$#', $old)) @unlink(dirname(__DIR__, 2) . '/public/' . $old);
    flash($new ? 'Cover updated. It shows on the course card and at the top of the course.' : 'Cover removed.');
    redirect("learn/$id");
}

function lesson_add(int $id): void
{
    [$c] = course_manage_or_403($id);
    $title = mb_substr(input('title'), 0, 200);
    $content = mb_substr(trim((string) ($_POST['content'] ?? '')), 0, 20000);
    if ($title === '' || $content === '') { flash('Lesson title and content are required.', 'error'); redirect("learn/$id"); }
    $kind = input('kind') === 'summary' ? 'summary' : 'lesson';   // summaries aren't numbered as lessons
    $pos = $kind === 'summary' ? 0 : 1 + (int) qval("SELECT COALESCE(MAX(position), 0) FROM lessons WHERE course_id = ? AND kind = 'lesson'", [$id]);
    $video = input('video_url');
    $lid = insert('lessons', ['course_id' => $id, 'position' => $pos, 'kind' => $kind, 'sort_order' => course_next_sort($id), 'title' => $title, 'content' => $content, 'video_url' => preg_match('#^https?://#i', $video) ? mb_substr($video, 0, 250) : null]);
    if (!empty($_FILES['file']['name'])) {
        try {
            $f = store_course_file($_FILES['file'], $id);
            insert('course_materials', ['course_id' => $id, 'lesson_id' => $lid, 'kind' => 'file', 'title' => mb_substr(input('file_title') ?: pathinfo($f['file_name'], PATHINFO_FILENAME), 0, 200)] + $f);
        } catch (Throwable $ex) { flash('Lesson added, but the file was not: ' . $ex->getMessage(), 'error'); redirect("learn/$id/lesson/$lid"); }
    }
    $errors = course_store_uploads($id, $lid);
    course_store_links($id, $lid, (string) ($_POST['links'] ?? ''));
    if ($errors) { flash('Added, but some files were not: ' . implode(' · ', $errors), 'error'); redirect("learn/$id/lesson/$lid"); }
    flash($kind === 'summary' ? 'Summary added. Drag it into place in Course content.' : 'Lesson added.');
    redirect("learn/$id/lesson/$lid");
}

/** Add a guide file (PDF/Office/…) or an online link (YouTube, Drive, any website) to the course or one lesson. */
function material_add(int $id): void
{
    course_manage_or_403($id);
    $lesson = input_int('lesson_id') ?: null;
    if ($lesson && !qval('SELECT COUNT(*) FROM lessons WHERE id = ? AND course_id = ?', [$lesson, $id])) $lesson = null;
    $title = mb_substr(input('title'), 0, 200);
    $back = $lesson ? "learn/$id/lesson/$lesson" : "learn/$id#materials";
    try {
        if (!empty($_FILES['files']['name'][0]) || trim((string) ($_POST['links'] ?? '')) !== '') {   // several files and links at once
            $errors = course_store_uploads($id, $lesson);
            $links = course_store_links($id, $lesson, (string) ($_POST['links'] ?? ''));
            if ($errors) { flash(implode(' · ', $errors), 'error'); redirect($back); }
            flash($links || !empty($_FILES['files']['name'][0]) ? 'Added to the course.' : 'Nothing to add: choose files or paste links.', $links || !empty($_FILES['files']['name'][0]) ? 'success' : 'error');
            redirect($back);
        }
        if (!empty($_FILES['file']['name'])) {
            $f = store_course_file($_FILES['file'], $id);
            insert('course_materials', ['course_id' => $id, 'lesson_id' => $lesson, 'kind' => 'file', 'title' => $title ?: mb_substr(pathinfo($f['file_name'], PATHINFO_FILENAME), 0, 200)] + $f);
        } else {
            $url = input('url');
            if (!preg_match('#^https?://[^\s]+$#i', $url)) throw new RuntimeException('Add a file or a link that starts with https://');
            insert('course_materials', ['course_id' => $id, 'lesson_id' => $lesson, 'kind' => 'link', 'title' => $title ?: link_host($url), 'url' => mb_substr($url, 0, 500)]);
        }
    } catch (Throwable $ex) { flash($ex->getMessage(), 'error'); redirect($back); }
    flash('Added to the course.');
    redirect($back);
}

function material_delete(int $id, int $mid): void
{
    course_manage_or_403($id);
    $m = qrow('SELECT * FROM course_materials WHERE id = ? AND course_id = ?', [$mid, $id]) ?? abort(404);
    if ($m['file_path']) @unlink(dirname(__DIR__, 2) . '/storage/course_files/' . $m['file_path']);
    qexec('DELETE FROM course_materials WHERE id = ?', [$mid]);
    flash('Removed.');
    redirect(input('back') === 'lesson' && $m['lesson_id'] ? "learn/$id/lesson/{$m['lesson_id']}" : "learn/$id#materials");
}

/** Stream a course file to people with access (files live outside the web root). */
function material_file(int $id, int $mid): void
{
    $u = require_login();
    $c = course_load($id);
    if (!course_has_access($c, $u)) abort(403, 'Enroll in the course to open its files.');
    $m = qrow("SELECT * FROM course_materials WHERE id = ? AND course_id = ? AND kind = 'file'", [$mid, $id]) ?? abort(404);
    $path = dirname(__DIR__, 2) . '/storage/course_files/' . $m['file_path'];
    if (!is_file($path)) abort(404, 'This file is missing.');
    $inline = in_array($m['mime'], ['application/pdf', 'image/png', 'image/jpeg', 'image/webp', 'image/gif', 'text/plain'], true) && input('download') !== '1';
    header('Content-Type: ' . $m['mime']);
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=3600');
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($m['file_name']));
    readfile($path);
    exit;
}

function course_delete(int $id): void
{
    course_manage_or_403($id);
    if (input('lesson')) { qexec('DELETE FROM lessons WHERE id = ? AND course_id = ?', [input_int('lesson'), $id]); redirect("learn/$id"); }
    foreach (qall("SELECT file_path FROM course_materials WHERE course_id = ? AND kind = 'file'", [$id]) as $m) @unlink(dirname(__DIR__, 2) . '/storage/course_files/' . $m['file_path']);
    @rmdir(dirname(__DIR__, 2) . "/storage/course_files/$id");
    $cover = (string) qval('SELECT cover_image FROM courses WHERE id = ?', [$id]);
    if (preg_match('#^uploads/course-covers/[a-f0-9]{20}\.(?:jpg|png|webp|gif)$#', $cover)) @unlink(dirname(__DIR__, 2) . '/public/' . $cover);
    qexec('DELETE FROM courses WHERE id = ?', [$id]);
    flash('Course deleted.');
    redirect('learn');
}

/* ---------- Host dashboard ---------- */

function course_dashboard_data(int $id): array
{
    $quizIds = array_map('intval', array_column(qall('SELECT id FROM quizzes WHERE course_id = ?', [$id]), 'id'));
    $lessons = (int) qval('SELECT COUNT(*) FROM lessons WHERE course_id = ?', [$id]);
    $students = qall('SELECT e.user_id, e.created_at AS joined, e.completed_at, u.full_name, u.email, u.avatar_url, u.student_id,
        (SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON l.id = lp.lesson_id WHERE l.course_id = e.course_id AND lp.user_id = e.user_id) AS lessons_done,
        (SELECT MAX(v.day) FROM course_visits v WHERE v.course_id = e.course_id AND v.user_id = e.user_id) AS last_seen,
        (SELECT COALESCE(SUM(v.hits), 0) FROM course_visits v WHERE v.course_id = e.course_id AND v.user_id = e.user_id) AS visits,
        (SELECT ROUND(AVG(CASE WHEN a.max_score > 0 THEN (a.auto_score + COALESCE(a.essay_score, 0)) * 100 / a.max_score END)) FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id WHERE z.course_id = e.course_id AND a.user_id = e.user_id AND a.status <> \'open\' AND a.is_preview = 0) AS quiz_avg,
        (SELECT COUNT(*) FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id WHERE z.course_id = e.course_id AND a.user_id = e.user_id AND a.status <> \'open\' AND a.is_preview = 0) AS attempts,
        (SELECT p.provider FROM course_payments p WHERE p.course_id = e.course_id AND p.user_id = e.user_id AND p.status = \'paid\' ORDER BY p.id DESC LIMIT 1) AS payment
        FROM enrollments e JOIN users u ON u.id = e.user_id WHERE e.course_id = ? ORDER BY e.created_at DESC', [$id]);
    foreach ($students as &$s) $s['progress'] = $lessons ? (int) round($s['lessons_done'] / $lessons * 100) : 0;
    unset($s);
    $quizzes = qall("SELECT z.*, (SELECT COUNT(*) FROM quiz_questions q WHERE q.quiz_id = z.id) AS questions,
        (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.status <> 'open' AND a.is_preview = 0) AS attempts,
        (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.status = 'submitted' AND a.is_preview = 0) AS to_mark,
        (SELECT ROUND(AVG(CASE WHEN a.max_score > 0 THEN (a.auto_score + COALESCE(a.essay_score, 0)) * 100 / a.max_score END)) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.status = 'graded' AND a.is_preview = 0) AS avg_pct,
        (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.status = 'graded' AND a.is_preview = 0 AND a.max_score > 0 AND (a.auto_score + COALESCE(a.essay_score, 0)) * 100 >= z.pass_percent * a.max_score) AS passed,
        (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.status = 'graded' AND a.is_preview = 0) AS graded
        FROM quizzes z WHERE z.course_id = ? ORDER BY z.id", [$id]);
    $toMark = $quizIds ? qall('SELECT a.id, a.submitted_at, u.full_name, z.title FROM quiz_attempts a JOIN users u ON u.id = a.user_id JOIN quizzes z ON z.id = a.quiz_id WHERE a.quiz_id IN (' . in_ph($quizIds) . ") AND a.status = 'submitted' AND a.is_preview = 0 ORDER BY a.submitted_at", $quizIds) : [];
    $hardest = $quizIds ? qall('SELECT q.id, q.prompt, z.title, COUNT(*) AS answered, SUM(qa.is_correct) AS correct FROM quiz_answers qa JOIN quiz_attempts at ON at.id = qa.attempt_id AND at.is_preview = 0 AND at.status <> \'open\' JOIN quiz_questions q ON q.id = qa.question_id JOIN quizzes z ON z.id = q.quiz_id
        WHERE q.quiz_id IN (' . in_ph($quizIds) . ") AND q.type = 'mcq' AND qa.is_correct IS NOT NULL GROUP BY q.id, q.prompt, z.title HAVING COUNT(*) >= 1 ORDER BY SUM(qa.is_correct) / COUNT(*) ASC LIMIT 5", $quizIds) : [];
    $liveRuns = $quizIds ? qall('SELECT s.*, z.title, (SELECT COUNT(*) FROM live_players p WHERE p.session_id = s.id) AS players,
        (SELECT u.full_name FROM live_players p JOIN users u ON u.id = p.user_id WHERE p.session_id = s.id ORDER BY p.score DESC LIMIT 1) AS winner
        FROM live_sessions s JOIN quizzes z ON z.id = s.quiz_id WHERE s.quiz_id IN (' . in_ph($quizIds) . ') ORDER BY s.id DESC LIMIT 10', $quizIds) : [];
    $days = [];
    for ($i = 13; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i days"))] = 0;
    foreach (qall('SELECT day, COUNT(DISTINCT user_id) AS n FROM course_visits WHERE course_id = ? AND day >= ? GROUP BY day', [$id, array_key_first($days)]) as $r) $days[$r['day']] = (int) $r['n'];
    $payments = qall("SELECT p.*, u.full_name FROM course_payments p JOIN users u ON u.id = p.user_id WHERE p.course_id = ? AND p.status = 'paid' ORDER BY p.paid_at DESC LIMIT 20", [$id]);
    $revenue = (float) qval("SELECT COALESCE(SUM(amount), 0) FROM course_payments WHERE course_id = ? AND status = 'paid'", [$id]);
    $active7 = (int) qval('SELECT COUNT(DISTINCT user_id) FROM course_visits WHERE course_id = ? AND day >= ?', [$id, date('Y-m-d', strtotime('-6 days'))]);
    return compact('students', 'quizzes', 'toMark', 'hardest', 'liveRuns', 'days', 'payments', 'revenue', 'active7', 'lessons');
}

function page_course_dashboard(int $id): void
{
    [$c, $u] = course_manage_or_403($id);
    render('course_dashboard', ['c' => $c, 'u' => $u] + course_dashboard_data($id));
}

function course_students_csv(int $id): void
{
    [$c] = course_manage_or_403($id);
    $d = course_dashboard_data($id);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="manbar-course-' . $id . '-students.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // Excel opens UTF-8 correctly
    fputcsv($out, ['Name', 'Email', 'Student ID', 'Joined', 'Last seen', 'Visits', 'Lessons done', 'Progress %', 'Quiz attempts', 'Quiz average %', 'Completed', 'Payment']);
    foreach ($d['students'] as $s) fputcsv($out, [$s['full_name'], $s['email'], $s['student_id'], substr($s['joined'], 0, 10), $s['last_seen'], $s['visits'], $s['lessons_done'], $s['progress'], $s['attempts'], $s['quiz_avg'], $s['completed_at'] ? substr($s['completed_at'], 0, 10) : '', ['stripe' => 'card', 'manual' => 'access given'][$s['payment']] ?? 'joined free']);
    exit;
}

/** Give someone access by email/student number (free invite, or a paid course settled outside the site). */
function course_student_add(int $id): void
{
    [$c, $u] = course_manage_or_403($id);
    $email = normalize_university_email(input('email'), input('domain')) ?? strtolower(trim(input('email')));
    $sid = (int) qval('SELECT id FROM users WHERE email = ?', [$email]);
    if (!$sid) { flash("No MANBAR account uses $email yet. Ask them to sign in once first.", 'error'); redirect("learn/$id/dashboard#students"); }
    if (course_is_enrolled($id, $sid)) { flash('They already have access.'); redirect("learn/$id/dashboard#students"); }
    enroll_user($id, $sid, false);
    if (course_is_paid($c)) insert('course_payments', ['course_id' => $id, 'user_id' => $sid, 'provider' => 'manual', 'amount' => 0, 'currency' => 'aed', 'status' => 'paid', 'note' => 'Access granted by host', 'paid_at' => now()]);
    notify($sid, 'enroll', $u['full_name'] . ' gave you access to “' . excerpt($c['title'], 50) . '”', "learn/$id");
    flash('Access granted.');
    redirect("learn/$id/dashboard#students");
}

function course_student_remove(int $id, int $sid): void
{
    course_manage_or_403($id);
    qexec('DELETE FROM enrollments WHERE course_id = ? AND user_id = ?', [$id, $sid]);
    flash('Removed from the course.');
    redirect("learn/$id/dashboard#students");
}

/* ---------- Paid courses: Stripe Checkout ---------- */

function course_buy(int $id): void
{
    $u = require_login();
    $c = course_load($id);
    if ($c['status'] !== 'published' || !course_is_paid($c)) redirect("learn/$id");
    if (course_has_access($c, $u)) redirect("learn/$id");
    if (!stripe_ready()) { flash('Card payments are not switched on yet. Ask the course host to give you access.', 'error'); redirect("learn/$id"); }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = rtrim(cfg('base_url') ?: $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
    try {
        $s = stripe_api('POST', 'checkout/sessions', [
            'mode' => 'payment',
            'line_items' => [['quantity' => 1, 'price_data' => ['currency' => 'aed', 'unit_amount' => (int) round((float) $c['price_aed'] * 100), 'product_data' => ['name' => mb_substr($c['title'], 0, 120), 'description' => 'MANBAR course by ' . $c['full_name']]]]],
            'customer_email' => $u['email'],
            'client_reference_id' => $u['id'] . '-' . $id,
            'metadata' => ['course_id' => $id, 'user_id' => $u['id']],
            'success_url' => $base . url("learn/$id/paid") . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $base . url("learn/$id") . '?payment=cancelled',
        ]);
    } catch (Throwable $ex) { flash($ex->getMessage(), 'error'); redirect("learn/$id"); }
    insert('course_payments', ['course_id' => $id, 'user_id' => $u['id'], 'provider' => 'stripe', 'provider_ref' => $s['id'], 'amount' => $c['price_aed'], 'currency' => 'aed', 'status' => 'pending']);
    redirect($s['url']);
}

/** Stripe sends the buyer back here; we confirm with Stripe directly before giving access. */
function course_paid(int $id): void
{
    $u = require_login();
    $sid = input('session_id');
    if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sid)) redirect("learn/$id");
    try {
        $s = stripe_api('GET', 'checkout/sessions/' . $sid);
        if ((string) ($s['metadata']['user_id'] ?? '') !== (string) $u['id']) abort(403);
        course_payment_complete($sid, $s);
    } catch (Throwable $ex) { flash($ex->getMessage(), 'error'); redirect("learn/$id"); }
    if (course_is_enrolled($id, (int) $u['id'])) flash('Payment received. Welcome to the course! 🎉');
    else flash('Your payment is still processing. Refresh in a minute.', 'error');
    redirect("learn/$id");
}

/** Optional Stripe webhook (covers buyers who close the tab before returning). */
function stripe_webhook(): void
{
    $secret = (string) (cfg('stripe')['webhook_secret'] ?? '');
    $payload = (string) file_get_contents('php://input');
    $event = $secret ? stripe_verify_webhook($payload, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''), $secret) : null;
    if (!$event) { http_response_code(400); exit('bad signature'); }
    if (in_array($event['type'] ?? '', ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
        $s = $event['data']['object'] ?? [];
        if (!empty($s['id'])) course_payment_complete((string) $s['id'], $s);
    }
    http_response_code(200);
    echo 'ok';
    exit;
}
