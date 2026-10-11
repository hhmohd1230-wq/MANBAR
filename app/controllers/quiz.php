<?php
/** Quizzes: builder (single + bulk import), self-paced attempts with timer/autosave, auto-marking, host marking. */

function quiz_load(int $cid, int $qid): array
{
    ensure_learning_schema();
    return qrow('SELECT * FROM quizzes WHERE id = ? AND course_id = ?', [$qid, $cid]) ?? abort(404, 'Quiz not found.');
}

/* ---------- Builder (course host) ---------- */

function quiz_create(int $cid): void
{
    course_manage_or_403($cid);
    $title = mb_substr(input('title'), 0, 200) ?: 'Quiz ' . (1 + (int) qval('SELECT COUNT(*) FROM quizzes WHERE course_id = ?', [$cid]));
    $qid = insert('quizzes', ['course_id' => $cid, 'title' => $title, 'status' => 'draft', 'sort_order' => course_next_sort($cid)]);
    flash('Quiz created. Add questions one by one or paste many at once.');
    redirect("learn/$cid/quiz/$qid/edit");
}

function page_quiz_edit(int $cid, int $qid): void
{
    [$c, $u] = course_manage_or_403($cid);
    $quiz = quiz_load($cid, $qid);
    $questions = quiz_questions($qid);
    $preview = $_SESSION['quiz_import_preview'][$qid] ?? null;
    unset($_SESSION['quiz_import_preview'][$qid]);
    render('quiz_edit', compact('c', 'u', 'quiz', 'questions', 'preview'));
}

function quiz_settings_save(int $cid, int $qid): void
{
    course_manage_or_403($cid);
    $quiz = quiz_load($cid, $qid);
    $status = input('status') === 'published' ? 'published' : 'draft';
    if ($status === 'published' && !qval('SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?', [$qid])) { flash('Add at least one question before publishing.', 'error'); $status = 'draft'; }
    update('quizzes', [
        'title' => mb_substr(input('title'), 0, 200) ?: $quiz['title'],
        'description' => mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 2000) ?: null,
        'time_limit_min' => max(0, min(300, input_int('time_limit_min'))),
        'live_seconds' => max(5, min(120, input_int('live_seconds', 20))),
        'pass_percent' => max(0, min(100, input_int('pass_percent', 60))),
        'max_attempts' => max(0, min(20, input_int('max_attempts', 3))),
        'shuffle' => input('shuffle') ? 1 : 0,
        'essay_auto' => input('essay_auto') ? 1 : 0,
        'live_reveal' => input('live_reveal') ? 1 : 0,
        'status' => $status,
    ], 'id = ?', [$qid]);
    if ($status === 'published' && $quiz['status'] !== 'published') {
        $c = course_load($cid);
        foreach (qall('SELECT user_id FROM enrollments WHERE course_id = ?', [$cid]) as $e) notify((int) $e['user_id'], 'quiz', 'New quiz in “' . excerpt($c['title'], 40) . '”: ' . excerpt(input('title') ?: $quiz['title'], 50), "learn/$cid/quiz/$qid");
    }
    flash($status === 'published' ? 'Quiz saved and published.' : 'Quiz saved (draft: students can’t see it yet).');
    redirect("learn/$cid/quiz/$qid/edit");
}

function quiz_question_add(int $cid, int $qid): void
{
    course_manage_or_403($cid);
    quiz_load($cid, $qid);
    $type = input('type') === 'essay' ? 'essay' : 'mcq';
    $prompt = trim((string) ($_POST['prompt'] ?? ''));
    $options = array_values(array_filter(array_map('trim', (array) ($_POST['options'] ?? [])), fn($o) => $o !== ''));
    $correct = input_int('correct', -1);
    if ($prompt === '') { flash('Write the question.', 'error'); redirect("learn/$cid/quiz/$qid/edit#add"); }
    if ($type === 'mcq' && (count($options) < 2 || $correct < 0 || $correct >= count($options))) { flash('Multiple choice needs at least 2 options and the right one selected.', 'error'); redirect("learn/$cid/quiz/$qid/edit#add"); }
    quiz_insert_questions($qid, [[
        'type' => $type, 'prompt' => $prompt, 'options' => array_slice($options, 0, QUIZ_MAX_OPTIONS), 'correct' => $correct,
        'points' => max(1, min(100, input_int('points', $type === 'essay' ? 5 : 1))), 'keywords' => input('keywords'), 'explanation' => trim((string) ($_POST['explanation'] ?? '')),
    ]]);
    flash('Question added.');
    redirect("learn/$cid/quiz/$qid/edit#q-end");
}

function quiz_question_delete(int $cid, int $qid, int $questionId): void
{
    course_manage_or_403($cid);
    quiz_load($cid, $qid);
    qexec('DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?', [$questionId, $qid]);
    flash('Question removed.');
    redirect("learn/$cid/quiz/$qid/edit#questions");
}

/** Bulk import: preview first (shows how the text was split), then save. Text comes from the textarea or a .txt/.docx upload. */
function quiz_import(int $cid, int $qid): void
{
    course_manage_or_403($cid);
    quiz_load($cid, $qid);
    $text = (string) ($_POST['text'] ?? '');
    try {
        if (!empty($_FILES['file']['name'])) {
            $f = $_FILES['file'];
            if (($f['error'] ?? 1) !== UPLOAD_ERR_OK) throw new RuntimeException('The file did not upload.');
            $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
            if ($ext === 'docx') $text = docx_to_text((string) $f['tmp_name']);
            elseif (in_array($ext, ['txt', 'md', 'csv'], true)) $text = (string) file_get_contents((string) $f['tmp_name']);
            else throw new RuntimeException('Upload a Word (.docx) or text (.txt) file, or paste the questions.');
            if (!mb_check_encoding($text, 'UTF-8')) $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1256,Windows-1252,ISO-8859-1');
        }
    } catch (Throwable $ex) { flash($ex->getMessage(), 'error'); redirect("learn/$cid/quiz/$qid/edit#import"); }
    $text = mb_substr($text, 0, 200000);
    if (trim($text) === '') { flash('Paste your questions or choose a file.', 'error'); redirect("learn/$cid/quiz/$qid/edit#import"); }
    [$questions, $problems] = parse_question_text($text);
    if (input('mode') === 'save') {
        if (!$questions) { flash('No complete questions found to save.', 'error'); redirect("learn/$cid/quiz/$qid/edit#import"); }
        $n = quiz_insert_questions($qid, $questions);
        flash("$n question" . ($n === 1 ? '' : 's') . ' added' . ($problems ? ' (' . count($problems) . ' skipped: see the notes)' : '') . '.');
        if ($problems) $_SESSION['quiz_import_preview'][$qid] = ['text' => '', 'questions' => [], 'problems' => $problems, 'saved' => true];
        redirect("learn/$cid/quiz/$qid/edit#questions");
    }
    $_SESSION['quiz_import_preview'][$qid] = ['text' => $text, 'questions' => $questions, 'problems' => $problems, 'saved' => false];
    redirect("learn/$cid/quiz/$qid/edit#import");
}

function quiz_delete(int $cid, int $qid): void
{
    course_manage_or_403($cid);
    quiz_load($cid, $qid);
    qexec('DELETE FROM quizzes WHERE id = ?', [$qid]);
    flash('Quiz deleted.');
    redirect("learn/$cid#quizzes");
}

/* ---------- Taking a quiz ---------- */

function page_quiz(int $cid, int $qid): void
{
    $u = require_login();
    $c = course_load($cid);
    $quiz = quiz_load($cid, $qid);
    $studentView = course_student_view($c, $u);
    $manage = course_can_manage($c, $u) && !$studentView;
    if ($quiz['status'] !== 'published' && !course_can_manage($c, $u)) abort(404);
    if (!course_has_access($c, $u) || course_preview_visitor($c, $u)) { flash((course_is_paid($c) ? 'Buy the course' : 'Join the course') . ' to take its quizzes.', 'error'); redirect("learn/$cid"); }
    course_step_gate($c, $u, "q$qid");
    $questions = quiz_questions($qid);
    $attempts = qall("SELECT * FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? AND is_preview = 0 ORDER BY id DESC", [$qid, $u['id']]);
    $open = null;
    foreach ($attempts as $a) if ($a['status'] === 'open') $open = $a;
    $used = count(array_filter($attempts, fn($a) => $a['status'] !== 'open'));
    $left = $quiz['max_attempts'] ? max(0, (int) $quiz['max_attempts'] - $used) : null;
    $path = $manage ? null : ($studentView ? course_path_preview($cid) : course_path($cid, (int) $u['id']));
    render('quiz', compact('u', 'c', 'quiz', 'questions', 'attempts', 'open', 'left', 'manage', 'studentView', 'path'));
}

/** Host test run: same screens and marking as students, but never counted (results, averages, attempts, XP). */
function quiz_test(int $cid, int $qid): void
{
    [$c, $u] = course_manage_or_403($cid);
    $quiz = quiz_load($cid, $qid);
    if (!qval('SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?', [$qid])) { flash('Add some questions first, then test the quiz.', 'error'); redirect("learn/$cid/quiz/$qid/edit"); }
    qexec('DELETE FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? AND is_preview = 1', [$qid, $u['id']]);   // keep only the latest test run
    $aid = insert('quiz_attempts', [
        'quiz_id' => $qid, 'user_id' => $u['id'], 'started_at' => now(), 'is_preview' => 1,
        'deadline_at' => $quiz['time_limit_min'] ? date('Y-m-d H:i:s', time() + $quiz['time_limit_min'] * 60) : null,
    ]);
    redirect("quiz/attempt/$aid");
}

function quiz_start(int $cid, int $qid): void
{
    $u = require_login();
    $c = course_load($cid);
    $quiz = quiz_load($cid, $qid);
    if (course_can_manage($c, $u)) quiz_test($cid, $qid);   // hosts always get an uncounted test run
    if (!course_has_access($c, $u) || $quiz['status'] !== 'published') abort(403);
    course_step_gate($c, $u, "q$qid");
    $open = qrow("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? AND status = 'open' AND is_preview = 0", [$qid, $u['id']]);
    if ($open) redirect('quiz/attempt/' . $open['id']);
    $used = (int) qval("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? AND status <> 'open' AND is_preview = 0", [$qid, $u['id']]);
    if ($quiz['max_attempts'] && $used >= $quiz['max_attempts']) { flash('You have used all your attempts for this quiz.', 'error'); redirect("learn/$cid/quiz/$qid"); }
    if (!qval('SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?', [$qid])) { flash('This quiz has no questions yet.', 'error'); redirect("learn/$cid/quiz/$qid"); }
    $aid = insert('quiz_attempts', [
        'quiz_id' => $qid, 'user_id' => $u['id'], 'started_at' => now(),
        'deadline_at' => $quiz['time_limit_min'] ? date('Y-m-d H:i:s', time() + $quiz['time_limit_min'] * 60) : null,
    ]);
    redirect("quiz/attempt/$aid");
}

/** Attempt owned by the current user (or visible to the course host when $host). */
function attempt_load(int $aid, bool $host = false): array
{
    $u = require_login();
    ensure_learning_schema();
    $a = qrow('SELECT a.*, z.course_id, z.title AS quiz_title, z.pass_percent, z.shuffle, z.time_limit_min FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id WHERE a.id = ?', [$aid]) ?? abort(404);
    $c = course_load((int) $a['course_id']);
    $isOwner = (int) $a['user_id'] === (int) $u['id'];
    if (!$isOwner && !($host && course_can_manage($c, $u)) && !course_can_manage($c, $u)) abort(403);
    return [$a, $c, $u, $isOwner];
}
function attempt_questions(array $a): array
{
    $qs = quiz_questions((int) $a['quiz_id']);
    if ($a['shuffle']) { mt_srand((int) $a['id']); shuffle($qs); mt_srand(); }   // same order for this attempt every time
    return $qs;
}
function attempt_expired(array $a): bool { return $a['deadline_at'] && time() > strtotime($a['deadline_at']) + 20; }   // 20 s grace for slow networks

function page_quiz_take(int $aid): void
{
    [$a, $c, $u, $isOwner] = attempt_load($aid);
    if (!$isOwner) redirect("quiz/attempt/$aid/result");
    if ($a['status'] !== 'open') redirect("quiz/attempt/$aid/result");
    if (attempt_expired($a)) { quiz_finish($a); redirect("quiz/attempt/$aid/result"); }
    $questions = attempt_questions($a);
    $saved = [];
    foreach (qall('SELECT * FROM quiz_answers WHERE attempt_id = ?', [$aid]) as $r) $saved[(int) $r['question_id']] = $r;
    $remaining = $a['deadline_at'] ? max(0, strtotime($a['deadline_at']) - time()) : null;
    render('quiz_take', compact('a', 'c', 'u', 'questions', 'saved', 'remaining'));
}

function quiz_store_answer(int $aid, array $q, $choice, ?string $essay): void
{
    $row = ['attempt_id' => $aid, 'question_id' => $q['id']];
    if ($q['type'] === 'mcq') {
        $v = ($choice === null || $choice === '') ? null : (int) $choice;
        if ($v !== null && ($v < 0 || $v >= count($q['options']))) $v = null;
        $row['choice'] = $v;
    } else {
        $row['essay'] = mb_substr((string) $essay, 0, 10000);
    }
    $exists = qval('SELECT id FROM quiz_answers WHERE attempt_id = ? AND question_id = ?', [$aid, $q['id']]);
    if ($exists) update('quiz_answers', array_diff_key($row, ['attempt_id' => 1, 'question_id' => 1]), 'id = ?', [$exists]);
    else insert('quiz_answers', $row);
}

/** Autosave one answer (fetch from the quiz page). */
function quiz_answer_save(int $aid): void
{
    [$a, , , $isOwner] = attempt_load($aid);
    if (!$isOwner || $a['status'] !== 'open') json_out(['ok' => false, 'error' => 'This attempt is closed.'], 409);
    if (attempt_expired($a)) json_out(['ok' => false, 'error' => 'Time is up.', 'expired' => true], 409);
    $qid = input_int('question');
    $q = null;
    foreach (quiz_questions((int) $a['quiz_id']) as $x) if ((int) $x['id'] === $qid) $q = $x;
    if (!$q) json_out(['ok' => false, 'error' => 'Unknown question.'], 422);
    quiz_store_answer($aid, $q, $_POST['choice'] ?? null, $_POST['essay'] ?? null);
    json_out(['ok' => true]);
}

function quiz_finish(array $a): array
{
    update('quiz_attempts', ['submitted_at' => now(), 'status' => 'submitted'], 'id = ? AND status = ?', [$a['id'], 'open']);
    $r = grade_attempt((int) $a['id']);
    $pts = $r['max'] ? (int) round(10 * ($r['score'] / $r['max'])) : 0;
    if ($pts && empty($a['is_preview'])) award_points((int) $a['user_id'], $pts, 'Finished a quiz');
    return $r;
}

function quiz_submit(int $aid): void
{
    [$a, $c, $u, $isOwner] = attempt_load($aid);
    if (!$isOwner || $a['status'] !== 'open') redirect("quiz/attempt/$aid/result");
    if (!attempt_expired($a)) {   // after the deadline, only answers autosaved in time count
        foreach (quiz_questions((int) $a['quiz_id']) as $q) {
            $k = 'q' . $q['id'];
            if ($q['type'] === 'mcq' && array_key_exists($k, $_POST)) quiz_store_answer($aid, $q, $_POST[$k], null);
            if ($q['type'] === 'essay' && array_key_exists($k, $_POST)) quiz_store_answer($aid, $q, null, (string) $_POST[$k]);
        }
    }
    $r = quiz_finish($a);
    if ($r['pending'] && empty($a['is_preview'])) {
        notify((int) $c['author_id'], 'quiz', $u['full_name'] . ' submitted “' . excerpt($a['quiz_title'], 50) . '”: essays to mark', "quiz/attempt/$aid/grade");
    }
    redirect("quiz/attempt/$aid/result");
}

function page_quiz_result(int $aid): void
{
    [$a, $c, $u, $isOwner] = attempt_load($aid, true);
    if ($a['status'] === 'open') redirect($isOwner ? "quiz/attempt/$aid" : "learn/{$c['id']}/dashboard");
    $questions = attempt_questions($a);
    $answers = [];
    foreach (qall('SELECT * FROM quiz_answers WHERE attempt_id = ?', [$aid]) as $r) $answers[(int) $r['question_id']] = $r;
    $student = qrow('SELECT id, full_name, email, avatar_url FROM users WHERE id = ?', [$a['user_id']]);
    // the next step on the course path, for the student's "Continue" button
    $steps = $isOwner && empty($a['is_preview']) ? course_steps((int) $c['id']) : [];
    $si = course_step_index($steps, 'q' . $a['quiz_id']);
    $nextStep = $si === null ? null : ($steps[$si + 1] ?? false);   // false = this quiz is the last step
    render('quiz_result', compact('a', 'c', 'u', 'questions', 'answers', 'isOwner', 'student', 'nextStep'));
}

/* ---------- Host: results and essay marking ---------- */

function page_quiz_results(int $cid, int $qid): void
{
    [$c, $u] = course_manage_or_403($cid);
    $quiz = quiz_load($cid, $qid);
    $attempts = qall("SELECT a.*, u.full_name, u.email, u.avatar_url FROM quiz_attempts a JOIN users u ON u.id = a.user_id WHERE a.quiz_id = ? AND a.status <> 'open' AND a.is_preview = 0 ORDER BY a.submitted_at DESC", [$qid]);
    $stats = qall("SELECT q.id, q.position, q.type, q.prompt, q.options, q.correct_index, q.points, COUNT(qa.id) AS answered, SUM(CASE WHEN qa.is_correct = 1 THEN 1 ELSE 0 END) AS correct, AVG(qa.points) AS avg_points
        FROM quiz_questions q LEFT JOIN (SELECT qa.* FROM quiz_answers qa JOIN quiz_attempts a ON a.id = qa.attempt_id WHERE a.status <> 'open' AND a.is_preview = 0) qa ON qa.question_id = q.id
        WHERE q.quiz_id = ? GROUP BY q.id, q.position, q.type, q.prompt, q.options, q.correct_index, q.points ORDER BY q.position, q.id", [$qid]);
    $choices = [];
    foreach (qall("SELECT qa.question_id, qa.choice, COUNT(*) AS n FROM quiz_answers qa JOIN quiz_attempts a ON a.id = qa.attempt_id WHERE a.quiz_id = ? AND a.status <> 'open' AND a.is_preview = 0 AND qa.choice IS NOT NULL GROUP BY qa.question_id, qa.choice", [$qid]) as $r) $choices[(int) $r['question_id']][(int) $r['choice']] = (int) $r['n'];
    render('quiz_results', compact('c', 'u', 'quiz', 'attempts', 'stats', 'choices'));
}

function page_attempt_grade(int $aid): void
{
    [$a, $c, $u] = attempt_load($aid, true);
    if (!course_can_manage($c, $u)) abort(403);
    $questions = attempt_questions($a);
    $answers = [];
    foreach (qall('SELECT * FROM quiz_answers WHERE attempt_id = ?', [$aid]) as $r) $answers[(int) $r['question_id']] = $r;
    $student = qrow('SELECT id, full_name, email, avatar_url FROM users WHERE id = ?', [$a['user_id']]);
    render('quiz_grade', compact('a', 'c', 'u', 'questions', 'answers', 'student'));
}

function attempt_grade_save(int $aid): void
{
    [$a, $c, $u] = attempt_load($aid, true);
    if (!course_can_manage($c, $u)) abort(403);
    foreach (quiz_questions((int) $a['quiz_id']) as $q) {
        if ($q['type'] !== 'essay') continue;
        $k = 'p' . $q['id'];
        if (!array_key_exists($k, $_POST)) continue;
        $pts = max(0, min((int) $q['points'], (int) $_POST[$k]));
        $fb = mb_substr(trim((string) ($_POST['f' . $q['id']] ?? '')), 0, 800) ?: null;
        $id = qval('SELECT id FROM quiz_answers WHERE attempt_id = ? AND question_id = ?', [$aid, $q['id']]);
        $row = ['points' => $pts, 'feedback' => $fb, 'auto_marked' => 0, 'is_correct' => $pts >= $q['points'] * .5 ? 1 : 0];
        if ($id) update('quiz_answers', $row, 'id = ?', [$id]);
        else insert('quiz_answers', $row + ['attempt_id' => $aid, 'question_id' => $q['id'], 'essay' => '']);
    }
    $r = grade_attempt($aid);
    if (!$r['pending']) {
        notify((int) $a['user_id'], 'quiz', 'Your “' . excerpt($a['quiz_title'], 50) . '” result is ready: ' . $r['score'] . '/' . $r['max'], "quiz/attempt/$aid/result");
        flash('Marks saved. The student has been notified.');
    } else flash('Marks saved.');
    $next = qval("SELECT a.id FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id WHERE z.course_id = ? AND a.status = 'submitted' AND a.is_preview = 0 AND a.id <> ? ORDER BY a.submitted_at LIMIT 1", [$c['id'], $aid]);
    redirect($next ? "quiz/attempt/$next/grade" : "learn/{$c['id']}/dashboard#marking");
}
