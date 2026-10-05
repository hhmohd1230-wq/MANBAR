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
function ensure_message_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $sqlite = db_driver() === 'sqlite';
    $cols = $sqlite ? array_column(qall('PRAGMA table_info(messages)'), 'name') : array_column(qall('SHOW COLUMNS FROM messages'), 'Field');
    $new = [
        'attachment_path' => 'VARCHAR(500) NULL',
        'attachment_name' => 'VARCHAR(255) NULL',
        'attachment_type' => 'VARCHAR(100) NULL',
        'attachment_size' => 'INT NULL',
    ];
    foreach ($new as $name => $type) if (!in_array($name, $cols, true)) qexec("ALTER TABLE messages ADD COLUMN $name $type");
    $tail = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    qexec("CREATE TABLE IF NOT EXISTS message_typing (
        user_id INT NOT NULL,
        receiver_id INT NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (user_id, receiver_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )$tail");
}

function message_attachment_upload(string $field = 'attachment'): ?array
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $friendly = ($f['error'] ?? 0) === UPLOAD_ERR_INI_SIZE ? 'This file is larger than the server upload limit.' : 'The attachment could not be uploaded.';
        throw new RuntimeException($friendly);
    }
    $max = (int) (cfg('message_upload_max_mb') ?: 12) * 1024 * 1024;
    if ((int) $f['size'] <= 0 || (int) $f['size'] > $max) throw new RuntimeException('Attachments can be up to ' . (int) (cfg('message_upload_max_mb') ?: 12) . ' MB.');

    $original = trim(basename((string) $f['name']));
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $types = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'gif' => ['image/gif'], 'webp' => ['image/webp'],
        'pdf' => ['application/pdf'], 'txt' => ['text/plain'], 'csv' => ['text/plain', 'text/csv', 'application/vnd.ms-excel'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/octet-stream'],
        'webm' => ['audio/webm', 'video/webm'],
        'ogg' => ['audio/ogg', 'application/ogg'],
        'm4a' => ['audio/mp4', 'video/mp4'],
        'wav' => ['audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave'],
        'aac' => ['audio/aac', 'audio/x-aac'],
    ];
    if (!isset($types[$ext])) throw new RuntimeException('Use an image, voice recording, PDF, Office document, text/CSV file, or ZIP archive.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $f['tmp_name']) ?: 'application/octet-stream';
    if (!in_array($mime, $types[$ext], true)) throw new RuntimeException('This file type does not match its extension.');

    $audioTypes = ['webm' => 'audio/webm', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4', 'wav' => 'audio/wav', 'aac' => 'audio/aac'];
    if (isset($audioTypes[$ext])) $mime = $audioTypes[$ext];

    $dir = dirname(__DIR__, 2) . '/public/uploads/messages';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('The message upload folder is not available.');
    $stored = date('Ymd') . '-' . bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file((string) $f['tmp_name'], "$dir/$stored")) throw new RuntimeException('The attachment could not be saved.');
    return [
        'attachment_path' => '/uploads/messages/' . $stored,
        'attachment_name' => mb_substr($original, 0, 255),
        'attachment_type' => $mime,
        'attachment_size' => (int) $f['size'],
    ];
}

function message_file_size(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024) . ' KB';
    return $bytes . ' B';
}

function message_payload(array $m, int $me): array
{
    $path = (string) ($m['attachment_path'] ?? '');
    return [
        'id' => (int) $m['id'],
        'sender_id' => (int) $m['sender_id'],
        'receiver_id' => (int) $m['receiver_id'],
        'mine' => (int) $m['sender_id'] === $me,
        'body' => (string) ($m['body'] ?? ''),
        'created_at' => (string) $m['created_at'],
        'time' => date('H:i', strtotime((string) $m['created_at'])),
        'attachment' => $path === '' ? null : [
            'url' => url(ltrim($path, '/')),
            'name' => (string) ($m['attachment_name'] ?? 'Attachment'),
            'type' => (string) ($m['attachment_type'] ?? 'application/octet-stream'),
            'size' => (int) ($m['attachment_size'] ?? 0),
            'size_label' => message_file_size((int) ($m['attachment_size'] ?? 0)),
            'image' => str_starts_with((string) ($m['attachment_type'] ?? ''), 'image/'),
            'audio' => str_starts_with((string) ($m['attachment_type'] ?? ''), 'audio/'),
        ],
    ];
}

function message_attachment_html(array $m): string
{
    if (empty($m['attachment_path'])) return '';
    $src = url(ltrim((string) $m['attachment_path'], '/'));
    $name = (string) ($m['attachment_name'] ?: 'Attachment');
    if (str_starts_with((string) ($m['attachment_type'] ?? ''), 'image/')) {
        return '<a class="dm-image" href="' . e($src) . '" target="_blank" rel="noopener"><img src="' . e($src) . '" alt="' . e($name) . '" loading="lazy"></a>';
    }
    if (str_starts_with((string) ($m['attachment_type'] ?? ''), 'audio/')) {
        return '<div class="dm-audio"><span class="dm-audio-icon">' . icon('mic', 20) . '</span><span class="dm-audio-main"><audio controls preload="metadata" src="' . e($src) . '">Your browser cannot play this voice message.</audio><small>Voice message · ' . e(message_file_size((int) ($m['attachment_size'] ?? 0))) . '</small></span><a class="dm-audio-download" href="' . e($src) . '" download aria-label="Download voice message">' . icon('download', 17) . '</a></div>';
    }
    return '<a class="dm-file" href="' . e($src) . '" download><span class="dm-file-icon">' . icon('file', 21) . '</span><span><b>' . e($name) . '</b><small>' . e(message_file_size((int) ($m['attachment_size'] ?? 0))) . '</small></span>' . icon('download', 18) . '</a>';
}

function page_messages(?int $with = null): void
{
    $u = require_login();
    ensure_message_schema();
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
    ensure_message_schema();
    $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 2000);
    if ($to === (int) $u['id'] || !qval('SELECT COUNT(*) FROM users WHERE id = ?', [$to])) abort(422);
    try {
        $attachment = message_attachment_upload();
    } catch (RuntimeException $ex) {
        if (wants_json()) json_out(['ok' => false, 'error' => $ex->getMessage()], 422);
        flash($ex->getMessage(), 'error');
        redirect("messages/$to");
    }
    if ($body === '' && !$attachment) {
        if (wants_json()) json_out(['ok' => false, 'error' => 'Write a message, attach a file, or record a voice message.'], 422);
        redirect("messages/$to");
    }
    $id = insert('messages', ['sender_id' => $u['id'], 'receiver_id' => $to, 'body' => $body] + ($attachment ?? []));
    notify($to, 'message', 'New message from ' . $u['full_name'], "messages/{$u['id']}");
    $message = qrow('SELECT * FROM messages WHERE id = ?', [$id]);
    if (wants_json()) json_out(['ok' => true, 'message' => message_payload($message, (int) $u['id'])]);
    redirect("messages/$to");
}

function api_messages(int $with): void
{
    $u = require_login();
    ensure_message_schema();
    $me = (int) $u['id'];
    if ($with === $me || !qval("SELECT COUNT(*) FROM users WHERE id = ? AND status = 'active'", [$with])) json_out(['ok' => false, 'error' => 'Conversation not found.'], 404);
    $after = max(0, input_int('after'));
    $messages = qall('SELECT * FROM messages WHERE id > ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY id ASC LIMIT 100', [$after, $me, $with, $with, $me]);
    if ($messages) qexec('UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND id > ?', [$me, $with, $after]);
    $cutoff = date('Y-m-d H:i:s', time() - 6);
    $typing = (bool) qval('SELECT COUNT(*) FROM message_typing WHERE user_id = ? AND receiver_id = ? AND updated_at >= ?', [$with, $me, $cutoff]);
    json_out(['ok' => true, 'messages' => array_map(fn($m) => message_payload($m, $me), $messages), 'typing' => $typing]);
}

function api_message_typing(int $with): void
{
    $u = require_login();
    ensure_message_schema();
    $me = (int) $u['id'];
    if ($with === $me || !qval("SELECT COUNT(*) FROM users WHERE id = ? AND status = 'active'", [$with])) json_out(['ok' => false, 'error' => 'Conversation not found.'], 404);
    if (db_driver() === 'sqlite') {
        qexec('INSERT INTO message_typing (user_id, receiver_id, updated_at) VALUES (?,?,?) ON CONFLICT(user_id, receiver_id) DO UPDATE SET updated_at = excluded.updated_at', [$me, $with, now()]);
    } else {
        qexec('INSERT INTO message_typing (user_id, receiver_id, updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)', [$me, $with, now()]);
    }
    json_out(['ok' => true]);
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
