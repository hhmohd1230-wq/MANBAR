<?php
function page_learn(): void
{
    $u = require_login();
    $cat = input('cat');
    $q = input('q');
    $where = ["c.status = 'published'"];
    $params = [];
    if (isset(COURSE_CATEGORIES[$cat])) { $where[] = 'c.category = ?'; $params[] = $cat; }
    if ($q !== '') { $where[] = '(c.title LIKE ? OR c.description LIKE ?)'; array_push($params, "%$q%", "%$q%"); }
    $courses = qall('SELECT c.*, u.full_name, u.avatar_url, u.email, u.verified,
        (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lessons,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students
        FROM courses c JOIN users u ON u.id = c.author_id WHERE ' . implode(' AND ', $where) . ' ORDER BY c.id DESC LIMIT 60', $params);
    $mine = qall("SELECT c.id, c.title, c.theme, c.category,
        (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS total,
        (SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON l.id = lp.lesson_id WHERE l.course_id = c.id AND lp.user_id = ?) AS done
        FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE e.user_id = ? ORDER BY e.created_at DESC", [$u['id'], $u['id']]);
    render('learn', compact('u', 'courses', 'mine', 'cat', 'q'));
}

function page_course(int $id, ?int $lessonId = null): void
{
    $u = require_login();
    $c = qrow('SELECT c.*, u.full_name, u.avatar_url, u.email, u.verified, u.headline FROM courses c JOIN users u ON u.id = c.author_id WHERE c.id = ?', [$id]) ?? abort(404, 'Course not found.');
    if ($c['status'] !== 'published' && !is_admin() && (int) $c['author_id'] !== (int) $u['id']) abort(404);
    $lessons = qall('SELECT * FROM lessons WHERE course_id = ? ORDER BY position, id', [$id]);
    $enrolled = qrow('SELECT * FROM enrollments WHERE course_id = ? AND user_id = ?', [$id, $u['id']]);
    $done = array_column(qall('SELECT lesson_id FROM lesson_progress WHERE user_id = ?', [$u['id']]), 'lesson_id');
    $done = array_map('intval', $done);
    $isAuthor = (int) $c['author_id'] === (int) $u['id'];
    $lesson = null;
    if ($lessonId) { foreach ($lessons as $l) if ((int) $l['id'] === $lessonId) $lesson = $l; if (!$lesson) abort(404); if (!$enrolled && !$isAuthor && !is_admin()) { flash('Enroll to open the lessons.', 'error'); redirect("learn/$id"); } }
    $students = (int) qval('SELECT COUNT(*) FROM enrollments WHERE course_id = ?', [$id]);
    render('course', compact('u', 'c', 'lessons', 'enrolled', 'done', 'isAuthor', 'lesson', 'students'));
}

function course_enroll(int $id): void
{
    $u = require_login();
    qrow("SELECT id FROM courses WHERE id = ? AND status = 'published'", [$id]) ?? abort(404);
    if (!qval('SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND user_id = ?', [$id, $u['id']])) {
        insert('enrollments', ['course_id' => $id, 'user_id' => $u['id']]);
        award_points((int) $u['id'], 5, 'Enrolled in a course');
        $c = qrow('SELECT author_id, title FROM courses WHERE id = ?', [$id]);
        notify((int) $c['author_id'], 'enroll', $u['full_name'] . ' enrolled in “' . excerpt($c['title'], 50) . '”', "learn/$id");
        flash('You are enrolled! Start with the first lesson.');
    }
    redirect("learn/$id");
}

function lesson_complete(int $id, int $lid): void
{
    $u = require_login();
    if (!qval('SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND user_id = ?', [$id, $u['id']])) abort(403);
    $l = qrow('SELECT * FROM lessons WHERE id = ? AND course_id = ?', [$lid, $id]) ?? abort(404);
    if (!qval('SELECT COUNT(*) FROM lesson_progress WHERE lesson_id = ? AND user_id = ?', [$lid, $u['id']])) {
        insert('lesson_progress', ['lesson_id' => $lid, 'user_id' => $u['id']]);
        award_points((int) $u['id'], 3, 'Completed a lesson');
    }
    $total = (int) qval('SELECT COUNT(*) FROM lessons WHERE course_id = ?', [$id]);
    $done = (int) qval('SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON l.id = lp.lesson_id WHERE l.course_id = ? AND lp.user_id = ?', [$id, $u['id']]);
    if ($total && $done >= $total) {
        $e = qrow('SELECT completed_at FROM enrollments WHERE course_id = ? AND user_id = ?', [$id, $u['id']]);
        if (!$e['completed_at']) {
            update('enrollments', ['completed_at' => now()], 'course_id = ? AND user_id = ?', [$id, $u['id']]);
            award_points((int) $u['id'], 25, 'Completed a course');
            flash('🎓 Course completed! +25 points');
        }
    }
    $next = qval('SELECT id FROM lessons WHERE course_id = ? AND (position > ? OR (position = ? AND id > ?)) ORDER BY position, id LIMIT 1', [$id, $l['position'], $l['position'], $l['id']]);
    redirect($next ? "learn/$id/lesson/$next" : "learn/$id");
}

function page_course_new(): void { render('course_new', ['u' => require_teacher()]); }

function course_create(): void
{
    $u = require_teacher();
    $title = mb_substr(input('title'), 0, 200);
    $desc = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 4000);
    if (mb_strlen($title) < 5 || mb_strlen($desc) < 20) { flash('Add a title and a description (20+ characters).', 'error'); redirect('learn/new'); }
    $id = insert('courses', [
        'author_id' => $u['id'], 'title' => $title, 'description' => $desc,
        'category' => isset(COURSE_CATEGORIES[input('category')]) ? input('category') : 'general',
        'level' => in_array(input('level'), ['beginner', 'intermediate', 'advanced'], true) ? input('level') : 'beginner',
        'theme' => random_int(0, 5),
    ]);
    award_points((int) $u['id'], 15, 'Published a course');
    flash('Course created. Now add your lessons.');
    redirect("learn/$id");
}

function lesson_add(int $id): void
{
    $u = require_login();
    $c = qrow('SELECT * FROM courses WHERE id = ?', [$id]) ?? abort(404);
    if ((int) $c['author_id'] !== (int) $u['id'] && !is_admin()) abort(403);
    $title = mb_substr(input('title'), 0, 200);
    $content = mb_substr(trim((string) ($_POST['content'] ?? '')), 0, 20000);
    if ($title === '' || $content === '') { flash('Lesson title and content are required.', 'error'); redirect("learn/$id"); }
    $pos = 1 + (int) qval('SELECT COALESCE(MAX(position), 0) FROM lessons WHERE course_id = ?', [$id]);
    $video = input('video_url');
    insert('lessons', ['course_id' => $id, 'position' => $pos, 'title' => $title, 'content' => $content, 'video_url' => preg_match('#^https?://#i', $video) ? mb_substr($video, 0, 250) : null]);
    flash('Lesson added.');
    redirect("learn/$id");
}

function course_delete(int $id): void
{
    $u = require_login();
    $c = qrow('SELECT * FROM courses WHERE id = ?', [$id]) ?? abort(404);
    if ((int) $c['author_id'] !== (int) $u['id'] && !is_admin()) abort(403);
    if (input('lesson')) { qexec('DELETE FROM lessons WHERE id = ? AND course_id = ?', [input_int('lesson'), $id]); redirect("learn/$id"); }
    qexec('DELETE FROM courses WHERE id = ?', [$id]);
    flash('Course deleted.');
    redirect('learn');
}
