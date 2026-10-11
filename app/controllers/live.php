<?php
/** Live quiz sessions (host + players, join code, timer, speed points, leaderboard). Clients poll /state about once a second. */

function live_load(string $code): array
{
    ensure_learning_schema();
    if (!preg_match('/^\d{6}$/', $code)) abort(404, 'Check the 6-digit code.');
    $s = qrow('SELECT s.*, z.title, z.course_id, z.live_seconds, z.live_reveal FROM live_sessions s JOIN quizzes z ON z.id = s.quiz_id WHERE s.code = ?', [$code]) ?? abort(404, 'No live quiz with that code.');
    return live_tick($s, (int) $s['live_seconds']);
}

function live_start(int $cid, int $qid): void
{
    [$c, $u] = course_manage_or_403($cid);
    quiz_load($cid, $qid);
    if (!live_questions($qid)) { flash('Live quizzes use multiple-choice questions. Add some first.', 'error'); redirect("learn/$cid/quiz/$qid/edit"); }
    qexec("UPDATE live_sessions SET state = 'ended', ended_at = ? WHERE quiz_id = ? AND state <> 'ended'", [now(), $qid]);
    $code = live_new_code();
    insert('live_sessions', ['quiz_id' => $qid, 'host_id' => $u['id'], 'code' => $code]);
    foreach (qall('SELECT user_id FROM enrollments WHERE course_id = ?', [$cid]) as $e) notify((int) $e['user_id'], 'quiz', 'Live quiz starting now in “' . excerpt($c['title'], 40) . '”: tap to join', "live/$code");
    redirect("live/$code/host");
}

/** Live quizzes the user can join right now (courses they are in, started in the last 3 hours), plus the ones they host. */
/**
 * Quizzes hub (/live): live games running now, your recent attempts, and every quiz in the courses you
 * joined or host, filterable by course. Students start, retry or review; hosts start a live game, test or see results.
 */
function page_live_join(): void
{
    $u = require_login();
    ensure_learning_schema();
    $me = (int) $u['id'];
    $since = date('Y-m-d H:i:s', time() - 3 * 3600);
    $open = qall("SELECT s.code, s.state, s.created_at, s.host_id, z.title, c.title AS course, c.id AS course_id, c.cover_image, c.theme,
        (SELECT COUNT(*) FROM live_players p WHERE p.session_id = s.id) AS players
        FROM live_sessions s JOIN quizzes z ON z.id = s.quiz_id JOIN courses c ON c.id = z.course_id
        WHERE s.state <> 'ended' AND s.created_at >= ? AND (s.host_id = ? OR c.author_id = ? OR EXISTS (SELECT 1 FROM enrollments e WHERE e.course_id = c.id AND e.user_id = ?))
        ORDER BY s.id DESC", [$since, $me, $me, $me]);
    // my courses: joined or hosted
    $courses = qall("SELECT c.id, c.title, c.cover_image, c.theme, c.author_id, c.category, u.full_name, u.avatar_url, u.email
        FROM courses c JOIN users u ON u.id = c.author_id
        WHERE c.author_id = ? OR EXISTS (SELECT 1 FROM enrollments e WHERE e.course_id = c.id AND e.user_id = ?) ORDER BY c.title", [$me, $me]);
    $byId = [];
    foreach ($courses as $c) $byId[(int) $c['id']] = $c + ['host' => (int) $c['author_id'] === $me, 'quizzes' => []];
    $quizzes = $byId ? qall('SELECT z.*, (SELECT COUNT(*) FROM quiz_questions q WHERE q.quiz_id = z.id) AS questions,
        (SELECT MAX(CASE WHEN a.max_score > 0 THEN ROUND((a.auto_score + COALESCE(a.essay_score, 0)) * 100 / a.max_score) END) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.user_id = ? AND a.status <> \'open\' AND a.is_preview = 0) AS best,
        (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.user_id = ? AND a.status <> \'open\' AND a.is_preview = 0) AS tries,
        (SELECT MAX(a.id) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.user_id = ? AND a.status <> \'open\' AND a.is_preview = 0) AS last_aid,
        (SELECT MAX(a.id) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.user_id = ? AND a.status = \'open\' AND a.is_preview = 0) AS open_aid,
        (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.status <> \'open\' AND a.is_preview = 0) AS results,
        (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.status = \'submitted\' AND a.is_preview = 0) AS to_mark
        FROM quizzes z WHERE z.course_id IN (' . implode(',', array_keys($byId)) . ') ORDER BY z.course_id, z.sort_order, z.id', [$me, $me, $me, $me]) : [];
    $paths = [];
    foreach ($quizzes as $z) {
        $c = &$byId[(int) $z['course_id']];
        if (!$c['host'] && $z['status'] !== 'published') { unset($c); continue; }
        // students follow the course path: a quiz can still be locked behind earlier steps
        $z['locked'] = false;
        if (!$c['host']) {
            $paths[$c['id']] ??= course_path((int) $c['id'], $me);
            $i = course_step_index($paths[$c['id']]['steps'], 'q' . $z['id']);
            $z['locked'] = $i !== null && !course_step_open($paths[$c['id']], $i);
        }
        $c['quizzes'][] = $z;
        unset($c);
    }
    $byId = array_filter($byId, fn($c) => $c['quizzes']);
    $recent = qall("SELECT a.id, a.status, a.submitted_at, a.auto_score, a.essay_score, a.max_score, z.title, z.pass_percent, c.title AS course, c.id AS course_id
        FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id JOIN courses c ON c.id = z.course_id
        WHERE a.user_id = ? AND a.status <> 'open' AND a.is_preview = 0 ORDER BY a.submitted_at DESC LIMIT 6", [$me]);
    $all = array_merge(...array_values(array_map(fn($c) => $c['quizzes'], $byId)) ?: [[]]);
    $taken = array_filter($all, fn($z) => $z['best'] !== null);
    $stats = ['quizzes' => count($all), 'taken' => count($taken), 'avg' => $taken ? (int) round(array_sum(array_column($taken, 'best')) / count($taken)) : null, 'live' => count($open)];
    $pick = input_int('course');
    if ($pick && !isset($byId[$pick])) $pick = 0;
    render('live_join', ['code' => input('code'), 'open' => $open, 'u' => $u, 'courses' => $byId, 'recent' => $recent, 'stats' => $stats, 'pick' => $pick]);
}

function live_join(): void
{
    require_login();
    $code = preg_replace('/\D+/', '', input('code'));
    if (strlen($code) !== 6 || !qval('SELECT COUNT(*) FROM live_sessions WHERE code = ?', [$code])) { flash('No live quiz with that code. Check it and try again.', 'error'); redirect('live'); }
    redirect("live/$code");
}

/** Players must have course access; on free courses joining with the code also enrolls them. */
function live_player_gate(array $s, array $u): void
{
    $c = course_load((int) $s['course_id']);
    if (course_has_access($c, $u)) return;
    if (course_is_paid($c)) abort(403, 'This live quiz is for students of a paid course.');
    enroll_user((int) $c['id'], (int) $u['id']);
}

function page_live_play(string $code): void
{
    $u = require_login();
    $s = live_load($code);
    if ((int) $s['host_id'] === (int) $u['id'] && input('player') !== '1') redirect("live/$code/host");   // ?player=1: host checks the student screen
    live_player_gate($s, $u);
    if ($s['state'] !== 'ended' && !qval('SELECT COUNT(*) FROM live_players WHERE session_id = ? AND user_id = ?', [$s['id'], $u['id']])) {
        try { insert('live_players', ['session_id' => $s['id'], 'user_id' => $u['id'], 'joined_at' => now()]); } catch (Throwable) {}
    }
    render('live_play', ['s' => $s, 'u' => $u]);
}

function page_live_host(string $code): void
{
    $u = require_login();
    $s = live_load($code);
    $c = course_load((int) $s['course_id']);
    if (!course_can_manage($c, $u)) abort(403, 'Only the host can run this live quiz.');
    render('live_host', ['s' => $s, 'u' => $u, 'c' => $c, 'total' => count(live_questions((int) $s['quiz_id']))]);
}

/**
 * Shared state for host and players. The host always sees answers and counts.
 * Players see the right answer and scores after each question only when the quiz enables "live_reveal";
 * otherwise everything (score, rank, right answers) is shown once the quiz ends.
 */
function live_state_payload(array $s, ?int $uid, bool $host): array
{
    $qs = live_questions((int) $s['quiz_id']);
    $i = (int) $s['q_index'];
    $q = $qs[$i] ?? null;
    $now = live_now_ms();
    $ended = $s['state'] === 'ended';
    $reveal = !empty($s['live_reveal']);
    $showScores = $host || $reveal || $ended;
    $out = [
        'ok' => true, 'state' => $s['state'], 'index' => $i, 'total' => count($qs), 'title' => $s['title'],
        'seconds' => (int) $s['live_seconds'], 'reveal_each' => $reveal,
        'remaining_ms' => $s['state'] === 'question' ? max(0, (int) $s['live_seconds'] * 1000 - ($now - (int) $s['q_started_ms'])) : 0,
        'players' => (int) qval('SELECT COUNT(*) FROM live_players WHERE session_id = ?', [$s['id']]),
        'leaderboard' => $showScores ? array_map(fn($r) => ['name' => $r['full_name'], 'score' => (int) $r['score'], 'correct' => (int) $r['correct'], 'me' => $uid && (int) $r['user_id'] === $uid, 'initials' => initials($r['full_name'])], live_leaderboard((int) $s['id'], $host ? 50 : 5)) : [],
    ];
    if ($host) $out['names'] = array_column(qall('SELECT u.full_name FROM live_players p JOIN users u ON u.id = p.user_id WHERE p.session_id = ? ORDER BY p.joined_at', [$s['id']]), 'full_name');
    if ($q && in_array($s['state'], ['question', 'reveal'], true)) {
        $out['question'] = ['id' => (int) $q['id'], 'prompt' => $q['prompt'], 'options' => $q['options'], 'points' => (int) $q['points']];
        $out['answered'] = (int) qval('SELECT COUNT(*) FROM live_answers WHERE session_id = ? AND question_id = ?', [$s['id'], $q['id']]);
        if ($host || ($s['state'] === 'reveal' && $reveal)) {
            $counts = array_fill(0, count($q['options']), 0);
            foreach (qall('SELECT choice, COUNT(*) AS n FROM live_answers WHERE session_id = ? AND question_id = ? GROUP BY choice', [$s['id'], $q['id']]) as $r) if (isset($counts[(int) $r['choice']])) $counts[(int) $r['choice']] = (int) $r['n'];
            $out['counts'] = $counts;
            $out['correct'] = (int) $q['correct_index'];   // host: always; players: only when revealing each question
            $out['explanation'] = $q['explanation'];
        }
    }
    if ($uid && !$host) {
        $me = qrow('SELECT score, correct FROM live_players WHERE session_id = ? AND user_id = ?', [$s['id'], $uid]);
        $out['me'] = $showScores ? ['score' => (int) ($me['score'] ?? 0), 'correct' => (int) ($me['correct'] ?? 0),
            'rank' => $me ? 1 + (int) qval('SELECT COUNT(*) FROM live_players WHERE session_id = ? AND score > ?', [$s['id'], $me['score']]) : null] : null;
        if ($q && !$ended) {
            $a = qrow('SELECT choice, is_correct, points FROM live_answers WHERE session_id = ? AND question_id = ? AND user_id = ?', [$s['id'], $q['id'], $uid]);
            $shown = $s['state'] === 'reveal' && $reveal;
            $out['my_answer'] = $a ? ['choice' => (int) $a['choice'], 'correct' => $shown ? (bool) $a['is_correct'] : null, 'points' => $shown ? (int) $a['points'] : null] : null;
        }
        if ($ended) {   // full review: every question, my answer, the right answer
            $mine = [];
            foreach (qall('SELECT question_id, choice, is_correct, points FROM live_answers WHERE session_id = ? AND user_id = ?', [$s['id'], $uid]) as $r) $mine[(int) $r['question_id']] = $r;
            $out['review'] = array_map(fn($x) => ['prompt' => $x['prompt'], 'options' => $x['options'], 'correct' => (int) $x['correct_index'], 'explanation' => $x['explanation'],
                'mine' => isset($mine[(int) $x['id']]) ? (int) $mine[(int) $x['id']]['choice'] : null, 'points' => (int) ($mine[(int) $x['id']]['points'] ?? 0)], $qs);
        }
    }
    return $out;
}

function live_state(string $code): void
{
    $u = require_login();
    $s = live_load($code);
    json_out(live_state_payload($s, (int) $u['id'], false));
}

function live_host_state(string $code): void
{
    $u = require_login();
    $s = live_load($code);
    if (!course_can_manage(course_load((int) $s['course_id']), $u)) json_out(['ok' => false], 403);
    json_out(live_state_payload($s, (int) $u['id'], true));
}

function live_answer(string $code): void
{
    $u = require_login();
    $s = live_load($code);
    if ($s['state'] !== 'question') json_out(['ok' => false, 'error' => 'This question is closed.'], 409);
    $qs = live_questions((int) $s['quiz_id']);
    $q = $qs[(int) $s['q_index']] ?? null;
    $choice = input_int('choice', -1);
    if (!$q || input_int('question') !== (int) $q['id'] || $choice < 0 || $choice >= count($q['options'])) json_out(['ok' => false, 'error' => 'That answer is for another question.'], 409);
    if (!qval('SELECT COUNT(*) FROM live_players WHERE session_id = ? AND user_id = ?', [$s['id'], $u['id']])) json_out(['ok' => false, 'error' => 'Join the quiz first.'], 403);
    $elapsed = max(0, live_now_ms() - (int) $s['q_started_ms']);
    $limit = (int) $s['live_seconds'] * 1000;
    $ok = $choice === (int) $q['correct_index'];
    $pts = $ok ? LIVE_BASE_POINTS + (int) round(LIVE_SPEED_POINTS * max(0, 1 - $elapsed / max(1, $limit))) : 0;
    try {
        insert('live_answers', ['session_id' => $s['id'], 'question_id' => $q['id'], 'user_id' => $u['id'], 'choice' => $choice, 'is_correct' => $ok ? 1 : 0, 'points' => $pts, 'answered_ms' => $elapsed]);
    } catch (Throwable) { json_out(['ok' => false, 'error' => 'You already answered.'], 409); }
    if ($pts) qexec('UPDATE live_players SET score = score + ?, correct = correct + 1 WHERE session_id = ? AND user_id = ?', [$pts, $s['id'], $u['id']]);
    // close early when everyone has answered
    $players = (int) qval('SELECT COUNT(*) FROM live_players WHERE session_id = ?', [$s['id']]);
    if ($players && (int) qval('SELECT COUNT(*) FROM live_answers WHERE session_id = ? AND question_id = ?', [$s['id'], $q['id']]) >= $players) update('live_sessions', ['state' => 'reveal'], 'id = ? AND state = ?', [$s['id'], 'question']);
    json_out(['ok' => true]);
}

/** Host buttons: start / next (open the next question) / reveal (close now) / end. */
function live_control(string $code): void
{
    $u = require_login();
    $s = live_load($code);
    if (!course_can_manage(course_load((int) $s['course_id']), $u)) json_out(['ok' => false], 403);
    $total = count(live_questions((int) $s['quiz_id']));
    switch (input('action')) {
        case 'next':
            if ($s['state'] === 'ended') break;
            $i = (int) $s['q_index'] + 1;
            if ($i >= $total) { update('live_sessions', ['state' => 'ended', 'ended_at' => now()], 'id = ?', [$s['id']]); live_award((int) $s['id']); break; }
            update('live_sessions', ['state' => 'question', 'q_index' => $i, 'q_started_ms' => live_now_ms()], 'id = ?', [$s['id']]);
            break;
        case 'reveal':
            if ($s['state'] === 'question') update('live_sessions', ['state' => 'reveal'], 'id = ?', [$s['id']]);
            break;
        case 'end':
            if ($s['state'] !== 'ended') { update('live_sessions', ['state' => 'ended', 'ended_at' => now()], 'id = ?', [$s['id']]); live_award((int) $s['id']); }
            break;
    }
    live_host_state($code);
}

/** Small XP thank-you for the podium. */
function live_award(int $sid): void
{
    foreach (live_leaderboard($sid, 3) as $i => $p) if ((int) $p['score'] > 0) award_points((int) $p['user_id'], [15, 10, 5][$i], 'Top 3 in a live quiz');
}
