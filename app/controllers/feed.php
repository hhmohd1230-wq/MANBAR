<?php
/* ======================= queries ======================= */
function fetch_posts(string $where = '1=1', array $params = [], int $limit = 10, int $offset = 0, string $order = 'p.pinned DESC, p.created_at DESC, p.id DESC'): array
{
    $me = uid();
    $sql = "SELECT p.*, u.full_name, u.avatar_url, u.role, u.verified, u.email, u.headline, u.points AS author_points,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.status = 'visible') AS comment_count,
        (SELECT kind FROM reactions r WHERE r.target_type = 'post' AND r.target_id = p.id AND r.user_id = ?) AS my_reaction,
        (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = ?) AS saved,
        (SELECT COUNT(*) FROM posts s WHERE s.share_of = p.id) AS share_count
        FROM posts p JOIN users u ON u.id = p.user_id
        WHERE p.status = 'visible' AND ($where) ORDER BY $order LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset;
    $posts = qall($sql, [$me, $me, ...$params]);
    return attach_reactions($posts, 'post');
}

/** Adds ['reactions' => [kind => count], 'reaction_total' => n] to each row. */
function attach_reactions(array $rows, string $type): array
{
    if (!$rows) return $rows;
    $ids = array_column($rows, 'id');
    $map = [];
    foreach (qall("SELECT target_id, kind, COUNT(*) AS n FROM reactions WHERE target_type = ? AND target_id IN (" . in_ph($ids) . ') GROUP BY target_id, kind', [$type, ...$ids]) as $r) {
        $map[$r['target_id']][$r['kind']] = (int) $r['n'];
    }
    foreach ($rows as &$row) {
        $row['reactions'] = $map[$row['id']] ?? [];
        $row['reaction_total'] = array_sum($row['reactions']);
        if (!empty($row['share_of'])) {
            $row['original'] = fetch_posts('p.id = ?', [$row['share_of']], 1)[0] ?? null;
        }
    }
    return $rows;
}

function sidebar_widgets(): array
{
    $me = uid();
    $tags = [];
    foreach (qall("SELECT tags FROM posts WHERE status = 'visible' AND tags IS NOT NULL AND tags <> '' ORDER BY id DESC LIMIT 150") as $r) {
        foreach (csv_list($r['tags']) as $t) $tags[$t] = ($tags[$t] ?? 0) + 1;
    }
    arsort($tags);
    return [
        'tags' => array_slice($tags, 0, 8, true),
        'people' => qall("SELECT id, full_name, avatar_url, email, major, headline, verified FROM users
            WHERE id <> ? AND status = 'active' AND profile_complete = 1 AND id NOT IN (SELECT followed_id FROM follows WHERE follower_id = ?)
            ORDER BY " . sql_rand() . ' LIMIT 4', [$me, $me]),
        'events' => qall("SELECT id, title, created_at FROM posts WHERE type = 'event' AND status = 'visible' ORDER BY id DESC LIMIT 3"),
        'top' => qall("SELECT id, full_name, avatar_url, email, points FROM users WHERE status = 'active' ORDER BY points DESC LIMIT 5"),
    ];
}

/* ======================= pages ======================= */
function page_feed(): void
{
    $u = require_login();
    $type = input('type');
    $tag = mb_strtolower(input('tag'));
    $q = input('q');
    $sort = input('sort', 'new');
    $where = ['1=1'];
    $params = [];
    if ($type && isset(POST_TYPES[$type])) { $where[] = 'p.type = ?'; $params[] = $type; }
    if ($tag) { $where[] = "(',' || p.tags || ',') LIKE ?"; $params[] = "%,$tag,%"; }
    if ($q !== '') { $where[] = '(p.title LIKE ? OR p.body LIKE ? OR u.full_name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
    if ($sort === 'following') { $where[] = 'p.user_id IN (SELECT followed_id FROM follows WHERE follower_id = ?)'; $params[] = $u['id']; }
    $order = $sort === 'top'
        ? '(SELECT COUNT(*) FROM reactions r WHERE r.target_type = \'post\' AND r.target_id = p.id) + (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) * 2 DESC, p.id DESC'
        : 'p.pinned DESC, p.created_at DESC, p.id DESC';
    $size = (int) cfg('page_size');
    $page = max(1, input_int('page', 1));
    $sql = implode(' AND ', $where);
    if (db_driver() !== 'sqlite') $sql = str_replace("(',' || p.tags || ',')", "CONCAT(',', p.tags, ',')", $sql);
    $posts = fetch_posts($sql, $params, $size + 1, ($page - 1) * $size, $order);
    $more = count($posts) > $size;
    $posts = array_slice($posts, 0, $size);
    if (input('partial')) { foreach ($posts as $p) partial('post_card', ['p' => $p, 'u' => $u]); if ($more) echo '<!--more-->'; return; }
    $compose = input('compose');
    render('feed', ['u' => $u, 'posts' => $posts, 'more' => $more, 'page' => $page, 'type' => $type, 'tag' => $tag, 'q' => $q, 'sort' => $sort, 'compose' => $compose, 'w' => sidebar_widgets()]);
}

function page_saved(): void
{
    $u = require_login();
    $posts = fetch_posts('p.id IN (SELECT post_id FROM bookmarks WHERE user_id = ?)', [$u['id']], 50);
    render('saved', compact('u', 'posts'));
}

function page_post(int $id): void
{
    $u = require_login();
    $p = fetch_posts('p.id = ?', [$id], 1)[0] ?? null;
    if (!$p) abort(404, 'This post is not available.');
    $comments = fetch_comments($id);
    render('post', ['u' => $u, 'p' => $p, 'comments' => $comments, 'w' => sidebar_widgets()]);
}

function fetch_comments(int $postId): array
{
    $me = uid();
    $rows = qall("SELECT c.*, u.full_name, u.avatar_url, u.role, u.verified, u.email,
        (SELECT kind FROM reactions r WHERE r.target_type = 'comment' AND r.target_id = c.id AND r.user_id = ?) AS my_reaction
        FROM comments c JOIN users u ON u.id = c.user_id WHERE c.post_id = ? AND c.status = 'visible' ORDER BY c.id ASC", [$me, $postId]);
    $rows = attach_reactions($rows, 'comment');
    $tree = [];
    foreach ($rows as $r) if (!$r['parent_id']) { $r['replies'] = []; $tree[$r['id']] = $r; }
    foreach ($rows as $r) if ($r['parent_id']) {
        if (isset($tree[$r['parent_id']])) $tree[$r['parent_id']]['replies'][] = $r;
        else $tree[$r['id']] = $r + ['replies' => []];   // orphan -> top level
    }
    return array_values($tree);
}

/* ======================= actions ======================= */
function save_upload(string $field, string $sub = 'posts'): ?string
{
    if (empty($_FILES[$field]['tmp_name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed. Try a smaller image.');
    if ($f['size'] > cfg('upload_max_mb') * 1048576) throw new RuntimeException('Image is too large (max ' . cfg('upload_max_mb') . ' MB).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Only JPG, PNG, WebP or GIF images are allowed.');
    $dir = __DIR__ . "/../../public/uploads/$sub";
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(10)) . ".$ext";
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) throw new RuntimeException('Could not save the image.');
    return "uploads/$sub/$name";
}

function post_create(): void
{
    $u = require_login();
    $type = input('type', 'idea');
    $pt = POST_TYPES[$type] ?? null;
    if (!$pt) abort(422, 'Unknown post type.');
    if ($pt['staff'] && !is_teacher()) abort(403, 'Only teachers can post this type.');
    $title = mb_substr(input('title'), 0, 200);
    $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 5000);
    if (mb_strlen($title) < 3 || mb_strlen($body) < 10) {
        flash('Please add a title (3+ characters) and some details (10+ characters).', 'error');
        keep_old($_POST);
        redirect('feed?compose=' . $type);
    }
    try { $img = save_upload('image'); } catch (RuntimeException $ex) { flash($ex->getMessage(), 'error'); keep_old($_POST); redirect('feed?compose=' . $type); }
    $id = insert('posts', [
        'user_id' => $u['id'], 'type' => $type, 'title' => $title, 'body' => $body,
        'tags' => implode(',', parse_tags(input('tags'))) ?: null, 'image' => $img,
        'status' => 'visible',
    ]);
    clear_old();
    award_points((int) $u['id'], 10, 'Published a post');
    // notify followers
    foreach (qall('SELECT follower_id FROM follows WHERE followed_id = ?', [$u['id']]) as $f) {
        notify((int) $f['follower_id'], 'post', $u['full_name'] . ' posted: ' . excerpt($title, 70), "post/$id");
    }
    flash('Your ' . strtolower($pt['label']) . ' is live! +10 points');
    redirect("post/$id");
}

function post_update(int $id): void
{
    $u = require_login();
    $p = qrow('SELECT * FROM posts WHERE id = ?', [$id]) ?? abort(404);
    if ((int) $p['user_id'] !== (int) $u['id'] && !is_admin()) abort(403);
    $title = mb_substr(input('title'), 0, 200);
    $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 5000);
    if (mb_strlen($title) < 3 || mb_strlen($body) < 10) { flash('Title and details are required.', 'error'); redirect("post/$id"); }
    update('posts', ['title' => $title, 'body' => $body, 'tags' => implode(',', parse_tags(input('tags'))) ?: null, 'updated_at' => now()], 'id = ?', [$id]);
    flash('Post updated.');
    redirect("post/$id");
}

function post_delete(int $id): void
{
    $u = require_login();
    $p = qrow('SELECT * FROM posts WHERE id = ?', [$id]) ?? abort(404);
    if ((int) $p['user_id'] !== (int) $u['id'] && !is_admin()) abort(403);
    qexec('DELETE FROM posts WHERE id = ?', [$id]);
    if ((int) $p['user_id'] !== (int) $u['id']) qexec('INSERT INTO audit_log (admin_id, action, detail, created_at) VALUES (?,?,?,?)', [$u['id'], 'delete_post', "post #$id", now()]);
    flash('Post deleted.');
    redirect('feed');
}

function post_pin(int $id): void
{
    require_admin();
    $p = qrow('SELECT pinned FROM posts WHERE id = ?', [$id]) ?? abort(404);
    update('posts', ['pinned' => $p['pinned'] ? 0 : 1], 'id = ?', [$id]);
    redirect("post/$id");
}

/* ---------- JSON API ---------- */
function api_react(): void
{
    $u = require_login();
    $type = input('type');
    $tid = input_int('id');
    $kind = input('kind', 'like');
    if (!in_array($type, ['post', 'comment'], true) || !isset(REACTIONS[$kind])) json_out(['ok' => false, 'error' => 'Bad request'], 422);
    $row = $type === 'post' ? qrow('SELECT id, user_id, title FROM posts WHERE id = ?', [$tid]) : qrow('SELECT id, user_id, post_id FROM comments WHERE id = ?', [$tid]);
    if (!$row) json_out(['ok' => false, 'error' => 'Not found'], 404);
    $ex = qrow('SELECT id, kind FROM reactions WHERE user_id = ? AND target_type = ? AND target_id = ?', [$u['id'], $type, $tid]);
    $mine = null;
    if ($ex && $ex['kind'] === $kind) {
        qexec('DELETE FROM reactions WHERE id = ?', [$ex['id']]);
    } elseif ($ex) {
        update('reactions', ['kind' => $kind], 'id = ?', [$ex['id']]);
        $mine = $kind;
    } else {
        insert('reactions', ['user_id' => $u['id'], 'target_type' => $type, 'target_id' => $tid, 'kind' => $kind]);
        $mine = $kind;
        if ((int) $row['user_id'] !== (int) $u['id']) {
            award_points((int) $row['user_id'], 1, 'Received a reaction');
            $link = $type === 'post' ? "post/$tid" : 'post/' . $row['post_id'];
            notify((int) $row['user_id'], 'reaction', $u['full_name'] . ' reacted ' . REACTIONS[$kind]['emoji'] . ' to your ' . $type, $link);
        }
    }
    $counts = array_column(qall('SELECT kind, COUNT(*) AS n FROM reactions WHERE target_type = ? AND target_id = ? GROUP BY kind', [$type, $tid]), 'n', 'kind');
    json_out(['ok' => true, 'mine' => $mine, 'counts' => $counts, 'total' => array_sum($counts)]);
}

function api_comment(): void
{
    $u = require_login();
    $pid = input_int('post_id');
    $parent = input_int('parent_id') ?: null;
    $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 2000);
    $post = qrow("SELECT id, user_id, title FROM posts WHERE id = ? AND status = 'visible'", [$pid]);
    if (!$post || mb_strlen($body) < 1) json_out(['ok' => false, 'error' => 'Write something first.'], 422);
    if ($parent) {
        $pc = qrow('SELECT id, user_id, parent_id, post_id FROM comments WHERE id = ?', [$parent]);
        if (!$pc || (int) $pc['post_id'] !== $pid) json_out(['ok' => false, 'error' => 'Bad reply target'], 422);
        if ($pc['parent_id']) $parent = (int) $pc['parent_id'];   // keep threads one level deep
    }
    $cid = insert('comments', ['post_id' => $pid, 'user_id' => $u['id'], 'parent_id' => $parent, 'body' => $body]);
    award_points((int) $u['id'], 3, 'Commented');
    notify((int) $post['user_id'], 'comment', $u['full_name'] . ' commented on “' . excerpt($post['title'], 50) . '”', "post/$pid#c$cid");
    if (!empty($pc) && (int) $pc['user_id'] !== (int) $post['user_id']) notify((int) $pc['user_id'], 'reply', $u['full_name'] . ' replied to your comment', "post/$pid#c$cid");
    $c = qrow('SELECT c.*, u.full_name, u.avatar_url, u.role, u.verified, u.email FROM comments c JOIN users u ON u.id = c.user_id WHERE c.id = ?', [$cid]);
    $c += ['reactions' => [], 'reaction_total' => 0, 'my_reaction' => null, 'replies' => []];
    ob_start();
    partial('comment', ['c' => $c, 'u' => $u, 'p' => $post, 'reply' => (bool) $parent]);
    json_out(['ok' => true, 'html' => ob_get_clean(), 'parent' => $parent, 'count' => (int) qval("SELECT COUNT(*) FROM comments WHERE post_id = ? AND status = 'visible'", [$pid])]);
}

function api_comment_delete(): void
{
    $u = require_login();
    $c = qrow('SELECT * FROM comments WHERE id = ?', [input_int('id')]) ?? json_out(['ok' => false, 'error' => 'Not found'], 404);
    if ((int) $c['user_id'] !== (int) $u['id'] && !is_admin()) json_out(['ok' => false, 'error' => 'Not allowed'], 403);
    qexec('DELETE FROM comments WHERE parent_id = ?', [$c['id']]);
    qexec('DELETE FROM comments WHERE id = ?', [$c['id']]);
    json_out(['ok' => true, 'count' => (int) qval("SELECT COUNT(*) FROM comments WHERE post_id = ? AND status = 'visible'", [$c['post_id']])]);
}

function api_bookmark(): void
{
    $u = require_login();
    $pid = input_int('id');
    if (!qval('SELECT COUNT(*) FROM posts WHERE id = ?', [$pid])) json_out(['ok' => false], 404);
    if (qval('SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND post_id = ?', [$u['id'], $pid])) {
        qexec('DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?', [$u['id'], $pid]);
        json_out(['ok' => true, 'saved' => false]);
    }
    insert('bookmarks', ['user_id' => $u['id'], 'post_id' => $pid]);
    json_out(['ok' => true, 'saved' => true]);
}

function api_share(): void
{
    $u = require_login();
    $pid = input_int('id');
    $orig = qrow("SELECT * FROM posts WHERE id = ? AND status = 'visible'", [$pid]) ?? json_out(['ok' => false, 'error' => 'Not found'], 404);
    if ($orig['share_of']) $pid = (int) $orig['share_of'];
    $note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 500);
    $id = insert('posts', ['user_id' => $u['id'], 'type' => $orig['type'], 'title' => 'Shared: ' . mb_substr($orig['title'], 0, 180), 'body' => $note !== '' ? $note : 'Worth a look 👇', 'share_of' => $pid, 'status' => 'visible']);
    award_points((int) $u['id'], 2, 'Shared a post');
    notify((int) $orig['user_id'], 'share', $u['full_name'] . ' shared your post', "post/$id");
    json_out(['ok' => true, 'url' => url("post/$id")]);
}

function api_report(): void
{
    $u = require_login();
    $type = input('type');
    if (!in_array($type, ['post', 'comment', 'service', 'project'], true)) json_out(['ok' => false], 422);
    $reason = mb_substr(input('reason', 'Inappropriate content'), 0, 250);
    insert('reports', ['reporter_id' => $u['id'], 'target_type' => $type, 'target_id' => input_int('id'), 'reason' => $reason]);
    json_out(['ok' => true, 'message' => 'Thanks — our moderators will review this.']);
}
