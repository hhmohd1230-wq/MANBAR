<?php
/**
 * Learning center: course materials, quizzes (self-paced + live), grading, course payments (Stripe Checkout), visits.
 * Tables are created on first use (ensure_learning_schema), so existing installs need no manual migration.
 */

const QUIZ_MAX_OPTIONS = 6;
const COURSE_FILE_MAX_MB = 25;
const LIVE_BASE_POINTS = 500;   // correct answer
const LIVE_SPEED_POINTS = 500;  // extra for answering fast

function table_columns(string $t): array
{
    return db_driver() === 'sqlite' ? array_column(qall("PRAGMA table_info($t)"), 'name') : array_column(qall("SHOW COLUMNS FROM $t"), 'Field');
}

function ensure_learning_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $sqlite = db_driver() === 'sqlite';
    $pk = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $t = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $cc = table_columns('courses');
    if (!in_array('price_aed', $cc, true)) qexec('ALTER TABLE courses ADD COLUMN price_aed DECIMAL(8,2) NOT NULL DEFAULT 0');
    if (!in_array('cover_image', $cc, true)) qexec('ALTER TABLE courses ADD COLUMN cover_image VARCHAR(255) NULL');   // the host's own course picture (catalog card + course page)

    qexec("CREATE TABLE IF NOT EXISTS course_materials (id $pk, course_id INT NOT NULL, lesson_id INT NULL, kind VARCHAR(10) NOT NULL, title VARCHAR(200) NOT NULL,
        url VARCHAR(500) NULL, file_path VARCHAR(255) NULL, file_name VARCHAR(255) NULL, mime VARCHAR(120) NULL, size INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS quizzes (id $pk, course_id INT NOT NULL, title VARCHAR(200) NOT NULL, description TEXT NULL,
        time_limit_min INT NOT NULL DEFAULT 0, live_seconds INT NOT NULL DEFAULT 20, pass_percent INT NOT NULL DEFAULT 60, max_attempts INT NOT NULL DEFAULT 3,
        shuffle INT NOT NULL DEFAULT 0, essay_auto INT NOT NULL DEFAULT 1, live_reveal INT NOT NULL DEFAULT 0, status VARCHAR(12) NOT NULL DEFAULT 'draft', created_at DATETIME NOT NULL,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS quiz_questions (id $pk, quiz_id INT NOT NULL, position INT NOT NULL DEFAULT 1, type VARCHAR(8) NOT NULL DEFAULT 'mcq',
        prompt TEXT NOT NULL, options TEXT NULL, correct_index INT NULL, points INT NOT NULL DEFAULT 1, keywords VARCHAR(500) NULL, explanation TEXT NULL,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS quiz_attempts (id $pk, quiz_id INT NOT NULL, user_id INT NOT NULL, started_at DATETIME NOT NULL, deadline_at DATETIME NULL,
        submitted_at DATETIME NULL, auto_score INT NOT NULL DEFAULT 0, essay_score INT NULL, max_score INT NOT NULL DEFAULT 0, status VARCHAR(12) NOT NULL DEFAULT 'open',
        graded_at DATETIME NULL, is_preview INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)$t");
    if (!in_array('live_reveal', table_columns('quizzes'), true)) qexec('ALTER TABLE quizzes ADD COLUMN live_reveal INT NOT NULL DEFAULT 0');   // live: show answers after each question
    if (!in_array('is_preview', table_columns('quiz_attempts'), true)) qexec('ALTER TABLE quiz_attempts ADD COLUMN is_preview INT NOT NULL DEFAULT 0');   // host test runs (never counted)
    // one shared order for lessons and quizzes in "Course content" (the host drags them around); old courses keep lessons first, quizzes after
    if (!in_array('sort_order', table_columns('lessons'), true)) { qexec('ALTER TABLE lessons ADD COLUMN sort_order INT NOT NULL DEFAULT 0'); qexec('UPDATE lessons SET sort_order = position'); }
    if (!in_array('sort_order', table_columns('quizzes'), true)) { qexec('ALTER TABLE quizzes ADD COLUMN sort_order INT NOT NULL DEFAULT 0'); qexec('UPDATE quizzes SET sort_order = 100000 + id'); }
    // summaries are lessons with kind = 'summary' (key points between steps, not numbered as lessons)
    if (!in_array('kind', table_columns('lessons'), true)) qexec("ALTER TABLE lessons ADD COLUMN kind VARCHAR(10) NOT NULL DEFAULT 'lesson'");
    // the student's place on the course path: the step they're on, and how many times they finished it
    $ec = table_columns('enrollments');
    if (!in_array('step_key', $ec, true)) qexec('ALTER TABLE enrollments ADD COLUMN step_key VARCHAR(12) NULL');
    if (!in_array('runs', $ec, true)) qexec('ALTER TABLE enrollments ADD COLUMN runs INT NOT NULL DEFAULT 0');
    qexec("CREATE TABLE IF NOT EXISTS quiz_answers (id $pk, attempt_id INT NOT NULL, question_id INT NOT NULL, choice INT NULL, essay TEXT NULL,
        is_correct INT NULL, points INT NULL, feedback VARCHAR(800) NULL, auto_marked INT NOT NULL DEFAULT 0,
        UNIQUE (attempt_id, question_id),
        FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE, FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS live_sessions (id $pk, quiz_id INT NOT NULL, host_id INT NOT NULL, code VARCHAR(8) NOT NULL UNIQUE, state VARCHAR(10) NOT NULL DEFAULT 'lobby',
        q_index INT NOT NULL DEFAULT -1, q_started_ms BIGINT NULL, created_at DATETIME NOT NULL, ended_at DATETIME NULL,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS live_players (id $pk, session_id INT NOT NULL, user_id INT NOT NULL, score INT NOT NULL DEFAULT 0, correct INT NOT NULL DEFAULT 0,
        joined_at DATETIME NOT NULL, UNIQUE (session_id, user_id),
        FOREIGN KEY (session_id) REFERENCES live_sessions(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS live_answers (id $pk, session_id INT NOT NULL, question_id INT NOT NULL, user_id INT NOT NULL, choice INT NOT NULL,
        is_correct INT NOT NULL DEFAULT 0, points INT NOT NULL DEFAULT 0, answered_ms INT NOT NULL DEFAULT 0, UNIQUE (session_id, question_id, user_id),
        FOREIGN KEY (session_id) REFERENCES live_sessions(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS course_payments (id $pk, course_id INT NOT NULL, user_id INT NOT NULL, provider VARCHAR(20) NOT NULL DEFAULT 'stripe',
        provider_ref VARCHAR(255) NULL, amount DECIMAL(8,2) NOT NULL DEFAULT 0, currency VARCHAR(8) NOT NULL DEFAULT 'aed', status VARCHAR(12) NOT NULL DEFAULT 'pending',
        note VARCHAR(255) NULL, created_at DATETIME NOT NULL, paid_at DATETIME NULL,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS course_visits (course_id INT NOT NULL, user_id INT NOT NULL, day DATE NOT NULL, hits INT NOT NULL DEFAULT 1,
        PRIMARY KEY (course_id, user_id, day),
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)$t");
}

/* ---------- Who can do what ---------- */

/** Teachers and admins, plus trusted members who reached the Gold rank. */
function can_create_courses(?array $u): bool
{
    if (!$u) return false;
    if (in_array($u['role'] ?? '', ['teacher', 'admin'], true)) return true;
    ensure_reputation_schema();
    return (int) ($u['reputation_score'] ?? qval('SELECT reputation_score FROM users WHERE id = ?', [$u['id']])) >= LEVELS[2][0];
}
function course_can_manage(array $c, array $u): bool { return (int) $c['author_id'] === (int) $u['id'] || ($u['role'] ?? '') === 'admin'; }
function course_is_enrolled(int $cid, int $uid): bool { return (bool) qval('SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND user_id = ?', [$cid, $uid]); }
/**
 * True while a host sees the course as students do. The author opts in with "Student view";
 * an admin opening someone else's course starts in student view and opts in to the admin tools.
 */
function course_student_view(array $c, array $u): bool
{
    if (!course_can_manage($c, $u)) return false;
    $flag = $_SESSION['student_view'][(int) $c['id']] ?? null;
    return course_is_author($c, $u) ? !empty($flag) : $flag !== false;
}
/** In Student view the host can preview as a joined student (default) or as a visitor who hasn't joined or paid yet. */
function course_preview_visitor(array $c, array $u): bool { return course_student_view($c, $u) && ($_SESSION['student_view_as'][(int) $c['id']] ?? 'joined') === 'visitor'; }
function course_is_author(array $c, array $u): bool { return (int) $c['author_id'] === (int) $u['id']; }
function course_has_access(array $c, array $u): bool { return course_can_manage($c, $u) || course_is_enrolled((int) $c['id'], (int) $u['id']); }
function course_is_paid(array $c): bool { return (float) ($c['price_aed'] ?? 0) > 0; }
function course_load(int $id): array
{
    ensure_learning_schema();
    return qrow('SELECT c.*, u.full_name, u.avatar_url, u.email, u.verified, u.headline FROM courses c JOIN users u ON u.id = c.author_id WHERE c.id = ?', [$id]) ?? abort(404, 'Course not found.');
}
/** Next slot at the end of "Course content" (lessons and quizzes share one order). */
function course_next_sort(int $cid): int
{
    return 1 + max((int) qval('SELECT COALESCE(MAX(sort_order), 0) FROM lessons WHERE course_id = ?', [$cid]), (int) qval('SELECT COALESCE(MAX(sort_order), 0) FROM quizzes WHERE course_id = ?', [$cid]));
}
/** Lessons and quizzes merged in the host's order: [['type' => 'lesson'|'quiz', 'row' => [...]], ...]. */
function course_outline(array $lessons, array $quizzes): array
{
    $items = [];
    foreach ($lessons as $l) $items[] = ['type' => 'lesson', 'row' => $l, 'k' => [(int) $l['sort_order'], 0, (int) $l['position'], (int) $l['id']]];
    foreach ($quizzes as $z) $items[] = ['type' => 'quiz', 'row' => $z, 'k' => [(int) $z['sort_order'], 1, 0, (int) $z['id']]];
    usort($items, fn($a, $b) => $a['k'] <=> $b['k']);
    return $items;
}
/* ---------- Course path: students go through the steps in the host's order ---------- */

/** The steps a student follows: lessons, summaries and published quizzes that have questions, in the host's order. */
function course_steps(int $cid): array
{
    $lessons = qall('SELECT id, kind, title, position, sort_order FROM lessons WHERE course_id = ?', [$cid]);
    $quizzes = qall("SELECT z.id, z.title, z.sort_order FROM quizzes z WHERE z.course_id = ? AND z.status = 'published' AND EXISTS (SELECT 1 FROM quiz_questions q WHERE q.quiz_id = z.id)", [$cid]);
    $steps = [];
    foreach (course_outline($lessons, $quizzes) as $it) {
        $r = $it['row'];
        $steps[] = $it['type'] === 'lesson'
            ? ['key' => 'l' . $r['id'], 'type' => $r['kind'] === 'summary' ? 'summary' : 'lesson', 'id' => (int) $r['id'], 'title' => $r['title'], 'url' => "learn/$cid/lesson/{$r['id']}"]
            : ['key' => 'q' . $r['id'], 'type' => 'quiz', 'id' => (int) $r['id'], 'title' => $r['title'], 'url' => "learn/$cid/quiz/{$r['id']}"];
    }
    return $steps;
}
function course_step_index(array $steps, ?string $key): ?int
{
    foreach ($steps as $i => $s) if ($s['key'] === $key) return $i;
    return null;
}
function course_step_label(array $s): string { return ['lesson' => 'Lesson', 'summary' => 'Summary', 'quiz' => 'Quiz'][$s['type']]; }

/**
 * Where a student is on the path: steps, done (key => true), cur (index of the step they're on; null once finished),
 * runs (times they finished the whole path), started, finished.
 */
function course_path(int $cid, int $uid): array
{
    $steps = course_steps($cid);
    $e = qrow('SELECT * FROM enrollments WHERE course_id = ? AND user_id = ?', [$cid, $uid]);
    $doneL = array_flip(array_map('intval', array_column(qall('SELECT lp.lesson_id FROM lesson_progress lp JOIN lessons l ON l.id = lp.lesson_id WHERE l.course_id = ? AND lp.user_id = ?', [$cid, $uid]), 'lesson_id')));
    $doneQ = array_flip(array_map('intval', array_column(qall("SELECT DISTINCT a.quiz_id FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id WHERE z.course_id = ? AND a.user_id = ? AND a.status <> 'open' AND a.is_preview = 0", [$cid, $uid]), 'quiz_id')));
    $done = [];
    foreach ($steps as $s) if ($s['type'] === 'quiz' ? isset($doneQ[$s['id']]) : isset($doneL[$s['id']])) $done[$s['key']] = true;
    $runs = (int) ($e['runs'] ?? 0);
    $key = $e['step_key'] ?? null;
    $cur = course_step_index($steps, $key);
    if ($cur === null && !($runs && $key === null)) {   // not started yet, or the host removed the step they were on
        foreach ($steps as $i => $s) if (empty($done[$s['key']])) { $cur = $i; break; }
    }
    $started = $key !== null || $done || $runs;
    // going through it again: show this run's progress (steps before the current one), not every step ever done
    if ($runs && $cur !== null) { $done = []; foreach ($steps as $i => $s) if ($i < $cur) $done[$s['key']] = true; }
    return ['steps' => $steps, 'done' => $done, 'cur' => $cur, 'runs' => $runs, 'finished' => $cur === null && (bool) $steps, 'started' => $started];
}
/** What the host sees in "Student view": a new student who hasn't started (nothing is saved). */
function course_path_preview(int $cid): array
{
    $steps = course_steps($cid);
    return ['steps' => $steps, 'done' => [], 'cur' => $steps ? 0 : null, 'runs' => 0, 'finished' => false, 'started' => false, 'preview' => true];
}
/** Steps open one by one on the first run; once the path is finished, every step stays open. */
function course_step_open(array $p, int $i): bool
{
    return !empty($p['preview']) || $p['runs'] > 0 || $p['finished'] || !empty($p['done'][$p['steps'][$i]['key']]) || ($p['cur'] !== null && $i <= $p['cur']);
}
/** Keep students on the path: a step that isn't open yet sends them back to the step they're on. */
function course_step_gate(array $c, array $u, string $key): void
{
    if (course_can_manage($c, $u) || !course_is_enrolled((int) $c['id'], (int) $u['id'])) return;
    $p = course_path((int) $c['id'], (int) $u['id']);
    $i = course_step_index($p['steps'], $key);
    if ($i === null || course_step_open($p, $i)) return;
    $at = $p['steps'][$p['cur']];
    flash('This course goes step by step. Finish “' . excerpt($at['title'], 60) . '” first.', 'error');
    redirect($at['url']);
}
/** A student finished step $key: move their place forward and return where to go next. */
function course_advance(int $cid, int $uid, string $key): string
{
    $p = course_path($cid, $uid);
    $i = course_step_index($p['steps'], $key);
    if ($i === null) return "learn/$cid";
    $next = $p['steps'][$i + 1]['url'] ?? "learn/$cid";
    if ($p['finished']) return $next;   // looking back after finishing: just move along
    $ptr = max($i + 1, (int) $p['cur']);
    if ($ptr < count($p['steps'])) {
        update('enrollments', ['step_key' => $p['steps'][$ptr]['key']], 'course_id = ? AND user_id = ?', [$cid, $uid]);
        return $next;
    }
    // that was the last step: this run of the course is complete
    $e = qrow('SELECT completed_at FROM enrollments WHERE course_id = ? AND user_id = ?', [$cid, $uid]);
    $first = empty($e['completed_at']);
    qexec('UPDATE enrollments SET step_key = NULL, runs = runs + 1' . ($first ? ', completed_at = ?' : '') . ' WHERE course_id = ? AND user_id = ?', $first ? [now(), $cid, $uid] : [$cid, $uid]);
    if ($first) { award_points($uid, 25, 'Completed a course'); flash('🎓 Course completed! +25 points'); }
    else flash('🎉 You finished the course again.');
    return "learn/$cid";
}

function course_manage_or_403(int $id): array
{
    $u = require_login();
    $c = course_load($id);
    if (!course_can_manage($c, $u)) abort(403, 'Only the course host can do this.');
    return [$c, $u];
}
function enroll_user(int $cid, int $uid, bool $notify = true): void
{
    if (course_is_enrolled($cid, $uid)) return;
    insert('enrollments', ['course_id' => $cid, 'user_id' => $uid]);
    award_points($uid, 5, 'Enrolled in a course');
    if ($notify) {
        $c = qrow('SELECT author_id, title FROM courses WHERE id = ?', [$cid]);
        $who = (string) qval('SELECT full_name FROM users WHERE id = ?', [$uid]);
        notify((int) $c['author_id'], 'enroll', $who . ' joined “' . excerpt($c['title'], 50) . '”', "learn/$cid/dashboard");
    }
}
function track_course_visit(int $cid, int $uid): void
{
    $day = date('Y-m-d');
    if (qexec('UPDATE course_visits SET hits = hits + 1 WHERE course_id = ? AND user_id = ? AND day = ?', [$cid, $uid, $day]) === 0) {
        try { qexec('INSERT INTO course_visits (course_id, user_id, day, hits) VALUES (?, ?, ?, 1)', [$cid, $uid, $day]); } catch (Throwable) {}
    }
}
function format_aed(float $v): string { return 'AED ' . rtrim(rtrim(number_format($v, 2), '0'), '.'); }

/* ---------- Links and files ---------- */

/** Embeddable player URL for YouTube / Vimeo links, else null (shown as a link card). */
function video_embed_url(?string $u): ?string
{
    if (!$u) return null;
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([\w-]{11})~', $u, $m)) return 'https://www.youtube-nocookie.com/embed/' . $m[1];
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $u, $m)) return 'https://player.vimeo.com/video/' . $m[1];
    return null;
}
function link_host(string $u): string { return preg_replace('/^www\./', '', (string) parse_url($u, PHP_URL_HOST)); }

const COURSE_FILE_TYPES = [
    'pdf' => ['application/pdf'],
    'doc' => ['application/msword', 'application/octet-stream', 'application/CDFV2'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream', 'application/CDFV2'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
    'xls' => ['application/vnd.ms-excel', 'application/octet-stream', 'application/CDFV2'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
    'txt' => ['text/plain'], 'csv' => ['text/plain', 'text/csv'],
    'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
    'png' => ['image/png'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'webp' => ['image/webp'], 'gif' => ['image/gif'],
];
const COURSE_FILE_ACCEPT = '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.csv,.zip,.png,.jpg,.jpeg,.webp,.gif';

/** How a material is shown: image (gallery), video (player), pdf / doc / ppt / xls / zip / txt (file cards) or link. */
function material_kind(array $m): string
{
    if ($m['kind'] === 'link') return video_embed_url($m['url']) ? 'video' : 'link';
    if (str_starts_with((string) $m['mime'], 'image/')) return 'image';
    $ext = strtolower(pathinfo((string) $m['file_name'], PATHINFO_EXTENSION));
    return match (true) { $ext === 'pdf' => 'pdf', in_array($ext, ['doc', 'docx'], true) => 'doc', in_array($ext, ['ppt', 'pptx'], true) => 'ppt', in_array($ext, ['xls', 'xlsx', 'csv'], true) => 'xls', $ext === 'zip' => 'zip', default => 'txt' };
}
/** Save every file from a multiple file input (files[]) to the course. Returns error messages for the ones that failed. */
function course_store_uploads(int $cid, ?int $lessonId, string $field = 'files'): array
{
    $errors = [];
    $F = $_FILES[$field] ?? null;
    if (!$F || !is_array($F['name'])) return $errors;
    foreach ($F['name'] as $i => $name) {
        if ($name === '' || ($F['error'][$i] ?? 0) === UPLOAD_ERR_NO_FILE) continue;
        $one = ['name' => $name, 'type' => $F['type'][$i], 'tmp_name' => $F['tmp_name'][$i], 'error' => $F['error'][$i], 'size' => $F['size'][$i]];
        try {
            $f = store_course_file($one, $cid);
            insert('course_materials', ['course_id' => $cid, 'lesson_id' => $lessonId, 'kind' => 'file', 'title' => mb_substr(pathinfo($f['file_name'], PATHINFO_FILENAME), 0, 200)] + $f);
        } catch (Throwable $ex) { $errors[] = basename((string) $name) . ': ' . $ex->getMessage(); }
    }
    return $errors;
}
/** Links typed one per line, optionally "Title | https://...". Returns how many were added. */
function course_store_links(int $cid, ?int $lessonId, string $text): int
{
    $n = 0;
    foreach (preg_split('/\R/', $text) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        [$title, $url] = str_contains($line, '|') ? array_map('trim', explode('|', $line, 2)) : ['', $line];
        if (!preg_match('#^https?://[^\s]+$#i', $url)) continue;
        insert('course_materials', ['course_id' => $cid, 'lesson_id' => $lessonId, 'kind' => 'link', 'title' => mb_substr($title ?: link_host($url), 0, 200), 'url' => mb_substr($url, 0, 500)]);
        $n++;
    }
    return $n;
}
/** Course picture as CSS: the host's cover image, or the course colour. */
function course_cover_css(array $c): string
{
    if (empty($c['cover_image'])) return theme_css((int) $c['theme']);
    return "background-image:url('" . str_replace(['"', "'", ')', '(', '<', '>'], '', url((string) $c['cover_image'])) . "');background-size:cover;background-position:center;";   // single quotes: it goes inside style="…"
}
/** People taking the course (newest first) and how many there are. */
function course_classmates(int $cid, int $limit = 8): array
{
    $rows = qall('SELECT u.id, u.full_name, u.email, u.avatar_url, u.major, u.headline, e.completed_at, e.created_at AS joined_at FROM enrollments e JOIN users u ON u.id = e.user_id WHERE e.course_id = ? ORDER BY e.created_at DESC LIMIT ' . (int) $limit, [$cid]);
    return [$rows, (int) qval('SELECT COUNT(*) FROM enrollments WHERE course_id = ?', [$cid])];
}

/** Store an uploaded course file outside the web root (files of paid courses must not be public). Returns column values. */
function store_course_file(array $f, int $cid, string $area = 'course_files'): array   // $area: course_files or service_files (marketplace portfolios)
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('The file did not upload. Try again (max ' . COURSE_FILE_MAX_MB . ' MB).');
    if ((int) $f['size'] > COURSE_FILE_MAX_MB * 1048576) throw new RuntimeException('Files can be up to ' . COURSE_FILE_MAX_MB . ' MB.');
    $original = trim(basename((string) $f['name']));
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!isset(COURSE_FILE_TYPES[$ext])) throw new RuntimeException('Use a PDF, Word, PowerPoint, Excel, text, image or ZIP file.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $f['tmp_name']) ?: 'application/octet-stream';
    if (!in_array($mime, COURSE_FILE_TYPES[$ext], true)) throw new RuntimeException('This file’s content doesn’t match its extension.');
    $dir = dirname(__DIR__) . "/storage/$area/$cid";
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('The course file folder is not available.');
    $deny = dirname($dir) . '/.htaccess';   // second lock in case the root .htaccess rules are not active on a host
    if (!is_file($deny)) @file_put_contents($deny, "Require all denied\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    $stored = date('Ymd') . '-' . bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file((string) $f['tmp_name'], "$dir/$stored")) throw new RuntimeException('The file could not be saved.');
    return ['file_path' => "$cid/$stored", 'file_name' => mb_substr($original, 0, 255), 'mime' => $mime === 'application/octet-stream' || $mime === 'application/CDFV2' || $mime === 'application/zip' ? course_mime_for($ext) : $mime, 'size' => (int) $f['size']];
}
function course_mime_for(string $ext): string
{
    return [
        'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'zip' => 'application/zip',
    ][$ext] ?? 'application/octet-stream';
}
function file_size_label(int $b): string { return $b >= 1048576 ? number_format($b / 1048576, 1) . ' MB' : ($b >= 1024 ? number_format($b / 1024) . ' KB' : $b . ' B'); }
function material_icon(array $m): string
{
    if ($m['kind'] === 'link') return video_embed_url($m['url']) ? 'play' : 'link';
    $ext = strtolower(pathinfo((string) $m['file_name'], PATHINFO_EXTENSION));
    return in_array($ext, ['png', 'jpg', 'jpeg'], true) ? 'image' : 'book';
}

/* ---------- Bulk question import: paste text or a .txt/.docx file → separate questions ---------- */

/** Plain text from a .docx (ZipArchive). Auto-numbered Word lists get "1." / "A)" prefixes so they still split. */
function docx_to_text(string $path): string
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new RuntimeException('This Word file could not be opened.');
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if ($xml === false) throw new RuntimeException('This Word file has no readable text.');
    $out = [];
    $q = 0; $opt = 0;
    preg_match_all('~<w:p[ >].*?</w:p>~s', $xml, $paras);
    foreach ($paras[0] as $p) {
        preg_match_all('~<w:t[^>]*>(.*?)</w:t>~s', $p, $runs);
        $line = html_entity_decode(implode('', $runs[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
        if (preg_match('~<w:numPr>.*?<w:ilvl w:val="(\d+)"~s', $p, $lv) && trim($line) !== '') {
            if ((int) $lv[1] === 0) { $q++; $opt = 0; $line = $q . '. ' . $line; }
            else { $line = chr(65 + min(25, $opt++)) . ') ' . $line; }
        }
        $out[] = $line;
    }
    return implode("\n", $out);
}

/**
 * Split pasted text into questions. Accepted per question (blank lines optional):
 *   1. Question text (2 pts)        ← "1." / "Q1:" / "Question 1)" start a question; points optional
 *   A) option   B) option *         ← "*" or "(correct)" marks the right option, or use "Answer: B"
 *   Answer: B
 *   Explanation: why                 ← optional
 *   Essay: Explain …   or a question with no options → essay; "Keywords: a, b, c" enables auto-marking
 * Returns [questions[], problems[]].
 */
function parse_question_text(string $text): array
{
    $lines = preg_split('/\R/u', str_replace("\xC2\xA0", ' ', $text));
    // When questions are numbered, anything before the first one is a title/instructions, not a question.
    $start = null;
    foreach ($lines as $i => $l) {
        if (preg_match('/^\s*(?:Q(?:uestion)?\s*)?\d{1,3}\s*[\.\):\-]\s*\S/iu', $l) || preg_match('/^\s*(?:Essay|Written|Long answer)\s*(?:question)?\s*[:\-]/iu', $l)) { $start = $i; break; }
    }
    if ($start) $lines = array_slice($lines, $start);
    $blocks = [];
    $cur = null;
    $flush = function () use (&$cur, &$blocks) { if ($cur && trim($cur['prompt']) !== '') $blocks[] = $cur; $cur = null; };
    $new = fn(string $prompt, bool $essay = false) => ['prompt' => $prompt, 'options' => [], 'correct' => null, 'answer' => null, 'essay' => $essay, 'expl' => '', 'keywords' => '', 'points' => null];
    foreach ($lines as $raw) {
        $line = trim($raw);
        if ($line === '') continue;
        if (preg_match('/^(?:Q(?:uestion)?\s*)?(\d{1,3})\s*[\.\):\-]\s*(.+)$/iu', $line, $m)) {
            $flush(); $cur = $new($m[2]); continue;
        }
        if (preg_match('/^(?:Essay|Written|Long answer)\s*(?:question)?\s*[:\-]\s*(.+)$/iu', $line, $m)) { $flush(); $cur = $new($m[1], true); continue; }
        if (!$cur) { $cur = $new($line); continue; }
        if (preg_match('/^(?:Answer|Ans|Correct(?:\s+answer)?)\s*[:\-]\s*\(?([A-Ha-h])\)?\b/u', $line, $m)) { $cur['answer'] = ord(strtoupper($m[1])) - 65; continue; }
        if (preg_match('/^(?:Explanation|Explain|Why|Reason)\s*[:\-]\s*(.+)$/iu', $line, $m)) { $cur['expl'] = $m[1]; continue; }
        if (preg_match('/^(?:Keywords?|Key\s*points?)\s*[:\-]\s*(.+)$/iu', $line, $m)) { $cur['keywords'] = $m[1]; $cur['essay'] = $cur['essay'] || !$cur['options']; continue; }
        if (preg_match('/^(?:Points?|Marks?)\s*[:\-]\s*(\d{1,3})$/iu', $line, $m)) { $cur['points'] = (int) $m[1]; continue; }
        if (preg_match('/^(\*)?\s*\(?([A-Ha-h])\s*[\.\)\]:\-]\s*(.+)$/u', $line, $m) && !$cur['essay']) {
            $opt = $m[3]; $star = $m[1] === '*';
            if (preg_match('/\s*(?:\*|\((?:correct|right|✓)\))\s*$/iu', $opt)) { $star = true; $opt = preg_replace('/\s*(?:\*|\((?:correct|right|✓)\))\s*$/iu', '', $opt); }
            if ($star) $cur['correct'] = count($cur['options']);
            $cur['options'][] = trim($opt);
            continue;
        }
        if ($cur['options']) { $cur['options'][count($cur['options']) - 1] .= ' ' . $line; continue; }   // wrapped option text
        $cur['prompt'] .= "\n" . $line;   // multi-line question
    }
    $flush();

    $out = []; $problems = [];
    foreach ($blocks as $i => $b) {
        $n = $i + 1;
        $prompt = trim($b['prompt']);
        $points = $b['points'];
        if (preg_match('/\(\s*(\d{1,3})\s*(?:pts?|points?|marks?)\s*\)\s*$/iu', $prompt, $m)) { $points = (int) $m[1]; $prompt = trim(preg_replace('/\(\s*\d{1,3}\s*(?:pts?|points?|marks?)\s*\)\s*$/iu', '', $prompt)); }
        $isEssay = $b['essay'] || !$b['options'];
        if ($isEssay) {
            $out[] = ['type' => 'essay', 'prompt' => $prompt, 'options' => [], 'correct' => null, 'points' => max(1, min(100, $points ?? 5)), 'keywords' => trim($b['keywords']), 'explanation' => trim($b['expl'])];
            continue;
        }
        $correct = $b['answer'] ?? $b['correct'];
        if (count($b['options']) < 2) { $problems[] = "Question $n needs at least 2 options."; continue; }
        if (count($b['options']) > QUIZ_MAX_OPTIONS) { $problems[] = "Question $n has more than " . QUIZ_MAX_OPTIONS . ' options.'; continue; }
        if ($correct === null || $correct >= count($b['options'])) { $problems[] = "Question $n (“" . excerpt($prompt, 40) . '”): mark the right option with * or add “Answer: B”.'; continue; }
        $out[] = ['type' => 'mcq', 'prompt' => $prompt, 'options' => $b['options'], 'correct' => $correct, 'points' => max(1, min(100, $points ?? 1)), 'keywords' => '', 'explanation' => trim($b['expl'])];
    }
    return [$out, $problems];
}

function quiz_questions(int $qid): array
{
    $rows = qall('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY position, id', [$qid]);
    foreach ($rows as &$r) $r['options'] = $r['options'] ? (json_decode($r['options'], true) ?: []) : [];
    return $rows;
}
function quiz_insert_questions(int $qid, array $questions): int
{
    $pos = (int) qval('SELECT COALESCE(MAX(position), 0) FROM quiz_questions WHERE quiz_id = ?', [$qid]);
    foreach ($questions as $q) {
        insert('quiz_questions', [
            'quiz_id' => $qid, 'position' => ++$pos, 'type' => $q['type'], 'prompt' => mb_substr($q['prompt'], 0, 4000),
            'options' => $q['type'] === 'mcq' ? json_encode(array_map(fn($o) => mb_substr($o, 0, 500), $q['options']), JSON_UNESCAPED_UNICODE) : null,
            'correct_index' => $q['type'] === 'mcq' ? (int) $q['correct'] : null, 'points' => (int) $q['points'],
            'keywords' => $q['keywords'] !== '' ? mb_substr($q['keywords'], 0, 500) : null, 'explanation' => $q['explanation'] !== '' ? mb_substr($q['explanation'], 0, 2000) : null,
        ]);
    }
    return count($questions);
}

/* ---------- Grading ---------- */

/** Keyword auto-mark for essays: share of keywords found (word-boundary, case-insensitive) × points. */
function essay_keyword_score(string $answer, string $keywords, int $points): array
{
    $keys = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/u', $keywords))));
    if (!$keys || trim($answer) === '') return [0, $keys ? 'No answer written.' : ''];
    $hay = mb_strtolower($answer);
    $found = array_values(array_filter($keys, fn($k) => preg_match('/(?<![\p{L}\p{N}])' . preg_quote(mb_strtolower($k), '/') . '(?![\p{L}\p{N}])/u', $hay)));
    $score = (int) round($points * count($found) / count($keys));
    return [$score, 'Auto-marked: covered ' . count($found) . ' of ' . count($keys) . ' key points' . ($found ? ' (' . implode(', ', array_slice($found, 0, 6)) . ')' : '') . '.'];
}

/** Grade a submitted attempt: MCQ automatically, essays by keywords (if enabled) or left for the host. */
function grade_attempt(int $aid): array
{
    $a = qrow('SELECT * FROM quiz_attempts WHERE id = ?', [$aid]);
    $quiz = qrow('SELECT * FROM quizzes WHERE id = ?', [$a['quiz_id']]);
    $qs = quiz_questions((int) $a['quiz_id']);
    $answers = [];
    foreach (qall('SELECT * FROM quiz_answers WHERE attempt_id = ?', [$aid]) as $r) $answers[(int) $r['question_id']] = $r;
    $auto = 0; $essay = 0; $max = 0; $pending = 0;
    foreach ($qs as $q) {
        $max += (int) $q['points'];
        $ans = $answers[(int) $q['id']] ?? null;
        if (!$ans) { insert('quiz_answers', ['attempt_id' => $aid, 'question_id' => $q['id'], 'is_correct' => 0, 'points' => 0, 'auto_marked' => 1]); continue; }
        if ($q['type'] === 'mcq') {
            $ok = $ans['choice'] !== null && (int) $ans['choice'] === (int) $q['correct_index'];
            $pts = $ok ? (int) $q['points'] : 0;
            update('quiz_answers', ['is_correct' => $ok ? 1 : 0, 'points' => $pts, 'auto_marked' => 1], 'id = ?', [$ans['id']]);
            $auto += $pts;
        } elseif ($ans['points'] !== null && !$ans['auto_marked']) {
            $essay += (int) $ans['points'];   // already marked by the host
        } elseif (trim((string) $ans['essay']) === '') {
            update('quiz_answers', ['points' => 0, 'is_correct' => 0, 'auto_marked' => 1, 'feedback' => 'No answer written.'], 'id = ?', [$ans['id']]);   // nothing to mark
        } elseif ($quiz['essay_auto'] && $q['keywords']) {
            [$pts, $fb] = essay_keyword_score((string) $ans['essay'], (string) $q['keywords'], (int) $q['points']);
            update('quiz_answers', ['points' => $pts, 'feedback' => $fb, 'auto_marked' => 1, 'is_correct' => $pts >= $q['points'] * .5 ? 1 : 0], 'id = ?', [$ans['id']]);
            $essay += $pts;
        } else {
            $pending++;
        }
    }
    $status = $pending ? 'submitted' : 'graded';
    update('quiz_attempts', ['auto_score' => $auto, 'essay_score' => $essay, 'max_score' => $max, 'status' => $status, 'graded_at' => $pending ? null : now()], 'id = ?', [$aid]);
    return ['score' => $auto + $essay, 'max' => $max, 'pending' => $pending, 'status' => $status];
}
function attempt_percent(array $a): int { return $a['max_score'] ? (int) round(((int) $a['auto_score'] + (int) $a['essay_score']) / (int) $a['max_score'] * 100) : 0; }

/* ---------- Live sessions ---------- */

function live_now_ms(): int { return (int) floor(microtime(true) * 1000); }
function live_new_code(): string
{
    do { $c = (string) random_int(100000, 999999); } while (qval('SELECT COUNT(*) FROM live_sessions WHERE code = ?', [$c]));
    return $c;
}
/** Questions used live (multiple choice only). */
function live_questions(int $qid): array { return array_values(array_filter(quiz_questions($qid), fn($q) => $q['type'] === 'mcq')); }

/** Moves a question past its timer to "reveal" (called on every state read, so no background job is needed). */
function live_tick(array $s, int $seconds): array
{
    if ($s['state'] === 'question' && live_now_ms() - (int) $s['q_started_ms'] >= $seconds * 1000) {
        update('live_sessions', ['state' => 'reveal'], 'id = ? AND state = ?', [$s['id'], 'question']);
        $s['state'] = 'reveal';
    }
    return $s;
}
function live_leaderboard(int $sid, int $limit = 10): array
{
    return qall('SELECT p.user_id, p.score, p.correct, u.full_name, u.avatar_url, u.email FROM live_players p JOIN users u ON u.id = p.user_id WHERE p.session_id = ? ORDER BY p.score DESC, p.joined_at LIMIT ' . (int) $limit, [$sid]);
}

/* ---------- Stripe Checkout (card payments for paid courses) ---------- */

function stripe_ready(): bool { return (string) (cfg('stripe')['secret_key'] ?? '') !== ''; }

function stripe_api(string $method, string $path, array $params = []): array
{
    $key = (string) (cfg('stripe')['secret_key'] ?? '');
    if ($key === '') throw new RuntimeException('Card payments are not switched on yet.');
    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($path, '/') . ($method === 'GET' && $params ? '?' . http_build_query($params) : ''));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_USERPWD => $key . ':', CURLOPT_CUSTOMREQUEST => $method]);
    if ($method !== 'GET') curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false) throw new RuntimeException('Could not reach the payment service (' . $err . ').');
    $d = json_decode((string) $body, true) ?: [];
    if ($code >= 400) throw new RuntimeException('Payment service: ' . ($d['error']['message'] ?? "error $code"));
    return $d;
}

/** Mark a payment paid and enrol the buyer (idempotent; used by the return URL and the webhook). */
function course_payment_complete(string $sessionId, array $session): void
{
    $p = qrow("SELECT * FROM course_payments WHERE provider = 'stripe' AND provider_ref = ?", [$sessionId]);
    if (!$p || ($session['payment_status'] ?? '') !== 'paid') return;
    if ((string) ($session['metadata']['course_id'] ?? '') !== (string) $p['course_id'] || (string) ($session['metadata']['user_id'] ?? '') !== (string) $p['user_id']) return;
    if ($p['status'] !== 'paid') update('course_payments', ['status' => 'paid', 'paid_at' => now(), 'amount' => ((int) ($session['amount_total'] ?? 0)) / 100], 'id = ?', [$p['id']]);
    enroll_user((int) $p['course_id'], (int) $p['user_id']);
}

function stripe_verify_webhook(string $payload, string $header, string $secret): ?array
{
    $parts = [];
    foreach (explode(',', $header) as $kv) { [$k, $v] = array_pad(explode('=', trim($kv), 2), 2, ''); $parts[$k][] = $v; }
    $t = (int) ($parts['t'][0] ?? 0);
    if (!$t || abs(time() - $t) > 300) return null;
    $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
    foreach ($parts['v1'] ?? [] as $sig) if (hash_equals($expected, $sig)) return json_decode($payload, true);
    return null;
}
