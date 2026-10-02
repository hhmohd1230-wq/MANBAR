<?php
/* ---------- notifications ---------- */
function page_notifications(): void
{
    $u = require_login();
    $items = qall('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 60', [$u['id']]);
    qexec('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$u['id']]);
    render('notifications', compact('u', 'items'));
}
function api_counts(): void
{
    $u = require_login();
    json_out(['ok' => true,
        'notifications' => (int) qval('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$u['id']]),
        'messages' => (int) qval('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0', [$u['id']])]);
}

/* ---------- direct messages ---------- */
function page_messages(?int $with = null): void
{
    $u = require_login();
    $me = (int) $u['id'];
    // conversation partners with last message
    $rows = qall('SELECT m.*, CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS other_id FROM messages m WHERE m.sender_id = ? OR m.receiver_id = ? ORDER BY m.id DESC', [$me, $me, $me]);
    $threads = [];
    foreach ($rows as $r) {
        $o = (int) $r['other_id'];
        if (!isset($threads[$o])) $threads[$o] = ['last' => $r, 'unread' => 0];
        if ((int) $r['receiver_id'] === $me && !$r['is_read']) $threads[$o]['unread']++;
    }
    if ($with && !isset($threads[$with]) && $with !== $me) $threads[$with] = ['last' => null, 'unread' => 0];
    $people = [];
    foreach (array_keys($threads) as $oid) $people[$oid] = qrow('SELECT id, full_name, avatar_url, email, role, verified FROM users WHERE id = ?', [$oid]);
    $conv = [];
    $other = null;
    if ($with) {
        $other = $people[$with] ?? qrow('SELECT id, full_name, avatar_url, email, role, verified FROM users WHERE id = ?', [$with]) ?? abort(404);
        $conv = qall('SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY id ASC LIMIT 200', [$me, $with, $with, $me]);
        qexec('UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?', [$me, $with]);
        if (isset($threads[$with])) $threads[$with]['unread'] = 0;
    }
    render('messages', compact('u', 'threads', 'people', 'conv', 'other'));
}
function message_send(int $to): void
{
    $u = require_login();
    $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 2000);
    if ($to === (int) $u['id'] || !qval('SELECT COUNT(*) FROM users WHERE id = ?', [$to])) abort(422);
    if ($body !== '') {
        insert('messages', ['sender_id' => $u['id'], 'receiver_id' => $to, 'body' => $body]);
        notify($to, 'message', 'New message from ' . $u['full_name'], "messages/{$u['id']}");
    }
    if (wants_json()) json_out(['ok' => true]);
    redirect("messages/$to");
}

/* ---------- achievements ---------- */
function page_achievements(): void
{
    $u = require_login();
    $badges = qall('SELECT b.*, (SELECT COUNT(*) FROM user_badges ub WHERE ub.badge_id = b.id AND ub.user_id = ?) AS earned FROM badges b ORDER BY b.id', [$u['id']]);
    $board = qall("SELECT id, full_name, avatar_url, email, points, major, verified, role FROM users WHERE status = 'active' ORDER BY points DESC, id LIMIT 15");
    $log = qall('SELECT * FROM point_log WHERE user_id = ? ORDER BY id DESC LIMIT 12', [$u['id']]);
    $rank = 1 + (int) qval("SELECT COUNT(*) FROM users WHERE status = 'active' AND points > ?", [$u['points']]);
    render('achievements', ['u' => $u, 'badges' => $badges, 'board' => $board, 'log' => $log, 'rank' => $rank, 'lvl' => level_for((int) $u['points'])]);
}

/* ---------- global search ---------- */
function page_search(): void
{
    $u = require_login();
    $q = input('q');
    $res = ['posts' => [], 'people' => [], 'projects' => [], 'services' => [], 'courses' => []];
    if (mb_strlen($q) >= 2) {
        $like = "%$q%";
        $res['posts'] = fetch_posts('(p.title LIKE ? OR p.body LIKE ? OR p.tags LIKE ?)', [$like, $like, $like], 6);
        $res['people'] = qall("SELECT id, full_name, avatar_url, email, headline, verified FROM users WHERE status = 'active' AND (full_name LIKE ? OR headline LIKE ? OR major LIKE ?) LIMIT 8", [$like, $like, $like]);
        $res['projects'] = qall("SELECT id, title, description, status FROM projects WHERE hidden = 0 AND (title LIKE ? OR description LIKE ? OR needed_skills LIKE ?) LIMIT 6", [$like, $like, $like]);
        $res['services'] = qall("SELECT id, title, price, category FROM services WHERE status = 'active' AND (title LIKE ? OR description LIKE ?) LIMIT 6", [$like, $like]);
        $res['courses'] = qall("SELECT id, title, category, level FROM courses WHERE status = 'published' AND (title LIKE ? OR description LIKE ?) LIMIT 6", [$like, $like]);
    }
    render('search', compact('u', 'q', 'res'));
}

/* ---------- AI assistant endpoints ---------- */
function api_ai_assist(): void
{
    $u = require_login();
    $text = trim((string) ($_POST['text'] ?? ''));
    if (mb_strlen($text) < 4) json_out(['ok' => false, 'error' => 'Write a few words first.'], 422);
    $r = ai_assist($text, $u);
    json_out(['ok' => true] + $r);
}
function api_ai_guide(): void
{
    $u = require_login();
    $text = trim((string) ($_POST['text'] ?? ''));
    if (mb_strlen($text) < 3) json_out(['ok' => false, 'error' => 'Tell me what you want to do.'], 422);
    $r = ai_assist($text, $u);
    $p = $r['route']['primary'];
    $reply = "I'd put this under **{$p['label']}**. {$p['reason']}";
    json_out(['ok' => true, 'reply' => $reply, 'route' => $r['route'], 'corrected' => $r['fix']['corrected']]);
}
