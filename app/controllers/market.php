<?php
/*
 * Student marketplace.
 * Two kinds of listing: an *offer* (a service you provide, optionally in Basic / Standard / Premium packages)
 * and a *request* ("looking for people": a number of spots that others apply to fill).
 * Listings have a cover picture, a "what you get" checklist, skills, and a portfolio of files, folders and links
 * (portfolio items can be marked as proof of past work). Sellers show trust signals computed from real orders.
 */

const SERVICE_FILE_MAX = 20;   // php.ini max_file_uploads: files per upload
const SERVICE_KINDS = ['offer' => 'Service', 'request' => 'Looking for people'];

function ensure_market_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    ensure_learning_schema();   // shares table_columns()
    $sqlite = db_driver() === 'sqlite';
    $pk = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $t = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $sc = table_columns('services');
    foreach ([
        'kind' => "VARCHAR(10) NOT NULL DEFAULT 'offer'", 'cover_image' => 'VARCHAR(255) NULL', 'slots' => 'INT NOT NULL DEFAULT 1',
        'includes' => 'TEXT NULL', 'skills' => 'VARCHAR(255) NULL', 'revisions' => 'INT NOT NULL DEFAULT 1', 'packages' => 'TEXT NULL',
    ] as $col => $def) if (!in_array($col, $sc, true)) qexec("ALTER TABLE services ADD COLUMN $col $def");
    $rc = table_columns('service_requests');
    foreach (['package' => 'VARCHAR(20) NULL', 'decided_at' => 'DATETIME NULL', 'completed_at' => 'DATETIME NULL'] as $col => $def)
        if (!in_array($col, $rc, true)) qexec("ALTER TABLE service_requests ADD COLUMN $col $def");
    qexec("CREATE TABLE IF NOT EXISTS service_files (id $pk, service_id INT NOT NULL, kind VARCHAR(10) NOT NULL, folder VARCHAR(120) NULL, title VARCHAR(200) NOT NULL,
        url VARCHAR(500) NULL, file_path VARCHAR(255) NULL, file_name VARCHAR(255) NULL, mime VARCHAR(120) NULL, size INT NOT NULL DEFAULT 0,
        is_proof INT NOT NULL DEFAULT 0, note VARCHAR(255) NULL, created_at DATETIME NOT NULL,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE)$t");
}

/* ---------- helpers ---------- */

function service_load(int $id): array
{
    ensure_market_schema();
    ensure_reputation_schema();
    return qrow('SELECT s.*, u.full_name, u.avatar_url, u.email, u.verified, u.reputation_score, u.role, u.headline, u.major, u.created_at AS member_since
        FROM services s JOIN users u ON u.id = s.user_id WHERE s.id = ?', [$id]) ?? abort(404, 'Listing not found.');
}
/** The owner can manage a listing. Admins can too, but only after switching on "Admin tools" (by default they browse as students). */
function service_can_edit(array $s, array $u): bool { return (int) $s['user_id'] === (int) $u['id'] || (($u['role'] ?? '') === 'admin' && !empty($_SESSION['market_admin'])); }
function market_admin_mode(): void
{
    $u = require_login();
    if (($u['role'] ?? '') !== 'admin') abort(403);
    $_SESSION['market_admin'] = input('mode') === 'admin';
    $back = input('back');
    redirect(preg_match('#^marketplace(/\d+)?$#', $back) ? $back : 'marketplace');
}
function service_edit_or_403(int $id): array
{
    $u = require_login();
    $s = service_load($id);
    if (!service_can_edit($s, $u)) abort(403, 'Only the owner can change this listing.');
    return [$s, $u];
}
/** Packages: [['name' => 'Basic', 'price' => 60, 'days' => 3, 'desc' => '…'], …] (empty = one simple price). */
function service_packages(array $s): array
{
    $p = json_decode((string) ($s['packages'] ?? ''), true);
    return is_array($p) ? array_values(array_filter($p, fn($x) => !empty($x['name']))) : [];
}
function service_from_price(array $s): int
{
    $p = service_packages($s);
    return $p ? (int) min(array_column($p, 'price')) : (int) $s['price'];
}
function service_price_label(array $s): string
{
    $from = service_from_price($s);
    if ($s['kind'] === 'request') return $from ? 'AED ' . $from : 'Volunteer';
    return $from ? (service_packages($s) ? 'From AED ' : 'AED ') . $from : 'Free';
}
function service_cover_css(array $s): string
{
    if (!empty($s['cover_image'])) return "background-image:url('" . str_replace(['"', "'", '(', ')', '<', '>'], '', url((string) $s['cover_image'])) . "');background-size:cover;background-position:center;";
    return theme_css(crc32((string) $s['category']) % 6);
}
function service_lines(?string $text): array { return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text)), fn($l) => $l !== '')); }
function service_skills(?string $text): array { return array_values(array_filter(array_map('trim', explode(',', (string) $text)), fn($l) => $l !== '')); }

/** Save uploaded files (files[] and a folder input folder[]) as portfolio items. Returns [saved, skipped]. */
function service_store_uploads(int $sid, bool $proof, string $note): array
{
    $saved = 0; $skipped = [];
    foreach (['files', 'folder'] as $field) {
        $F = $_FILES[$field] ?? null;
        if (!$F || !is_array($F['name'])) continue;
        foreach ($F['name'] as $i => $name) {
            if ($name === '' || ($F['error'][$i] ?? 0) === UPLOAD_ERR_NO_FILE) continue;
            $path = (string) ($F['full_path'][$i] ?? $name);   // "My folder/sub/file.pdf" for folder uploads (PHP 8.1+)
            $folder = $field === 'folder' && str_contains($path, '/') ? mb_substr(explode('/', $path)[0], 0, 120) : null;
            $one = ['name' => $name, 'type' => $F['type'][$i], 'tmp_name' => $F['tmp_name'][$i], 'error' => $F['error'][$i], 'size' => $F['size'][$i]];
            try {
                $f = store_course_file($one, $sid, 'service_files');
                insert('service_files', ['service_id' => $sid, 'kind' => 'file', 'folder' => $folder, 'title' => mb_substr(pathinfo($f['file_name'], PATHINFO_FILENAME), 0, 200), 'is_proof' => $proof ? 1 : 0, 'note' => $note ?: null] + $f);
                $saved++;
            } catch (Throwable $ex) { $skipped[] = basename($path); }
        }
    }
    return [$saved, $skipped];
}
function service_store_links(int $sid, string $text, bool $proof, string $note): int
{
    $n = 0;
    foreach (service_lines($text) as $line) {
        [$title, $link] = str_contains($line, '|') ? array_map('trim', explode('|', $line, 2)) : ['', $line];
        if (!preg_match('#^https?://[^\s]+$#i', $link)) continue;
        insert('service_files', ['service_id' => $sid, 'kind' => 'link', 'title' => mb_substr($title ?: link_host($link), 0, 200), 'url' => mb_substr($link, 0, 500), 'is_proof' => $proof ? 1 : 0, 'note' => $note ?: null]);
        $n++;
    }
    return $n;
}

/**
 * How much buyers can trust a provider, from real activity:
 * completed orders, on-time delivery, response time, reviews, repeat clients, teammate vouches and how long they've offered services.
 */
function seller_trust(int $uid): array
{
    ensure_market_schema();
    $o = qrow("SELECT
        SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) AS done,
        SUM(CASE WHEN r.status IN ('accepted','offered','delivered') THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN r.status IN ('accepted','offered','delivered','completed','declined') THEN 1 ELSE 0 END) AS decided,
        SUM(CASE WHEN r.status = 'declined' THEN 1 ELSE 0 END) AS declined,
        SUM(CASE WHEN r.status = 'completed' AND r.completed_at IS NOT NULL AND r.decided_at IS NOT NULL THEN 1 ELSE 0 END) AS timed,
        SUM(CASE WHEN r.status = 'completed' AND r.completed_at IS NOT NULL AND r.decided_at IS NOT NULL AND TIMESTAMPDIFF(HOUR, r.decided_at, r.completed_at) <= s.delivery_days * 24 THEN 1 ELSE 0 END) AS ontime,
        AVG(CASE WHEN r.decided_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, r.created_at, r.decided_at) END) AS resp_min,
        COUNT(DISTINCT CASE WHEN r.status = 'completed' THEN r.buyer_id END) AS clients
        FROM service_requests r JOIN services s ON s.id = r.service_id WHERE s.user_id = ? AND s.kind = 'offer'", [$uid]) ?: [];
    $repeat = (int) qval("SELECT COUNT(*) FROM (SELECT r.buyer_id FROM service_requests r JOIN services s ON s.id = r.service_id WHERE s.user_id = ? AND r.status = 'completed' GROUP BY r.buyer_id HAVING COUNT(*) > 1) x", [$uid]);
    $rev = qrow('SELECT COUNT(*) AS n, COALESCE(AVG(r.rating), 0) AS avg FROM service_reviews r JOIN services s ON s.id = r.service_id WHERE s.user_id = ?', [$uid]);
    $vouch = qrow('SELECT COUNT(*) AS n, COALESCE(AVG(rating), 0) AS avg FROM project_vouches WHERE subject_id = ?', [$uid]);
    $since = qval('SELECT MIN(created_at) FROM services WHERE user_id = ?', [$uid]);
    $user = qrow('SELECT verified, role, reputation_score, created_at FROM users WHERE id = ?', [$uid]);
    $done = (int) ($o['done'] ?? 0);
    $decided = (int) ($o['decided'] ?? 0);
    $onTime = (int) ($o['timed'] ?? 0) ? (int) round($o['ontime'] / $o['timed'] * 100) : null;
    $completion = $decided ? (int) round(($decided - (int) $o['declined'] - (int) $o['active']) / max(1, $decided - (int) $o['active']) * 100) : null;
    $respH = $o['resp_min'] !== null ? (float) $o['resp_min'] / 60 : null;
    // score out of 100
    $score = 0;
    $score += $rev['n'] ? ($rev['avg'] / 5) * 30 : 0;
    $score += min($done, 20) / 20 * 20;
    $score += $onTime !== null ? $onTime / 100 * 15 : 0;
    $score += $respH !== null ? max(0, 10 - min(10, $respH / 4.8)) : 0;   // within a few hours = full marks, 2 days = 0
    $score += min((int) $vouch['n'], 5) / 5 * 10;
    $score += (!empty($user['verified']) ? 5 : 0) + (in_array($user['role'] ?? '', ['teacher', 'admin'], true) ? 5 : 0);
    $score += min($repeat, 3) / 3 * 5;
    $score += min(1, (int) ($user['reputation_score'] ?? 0) / 900) * 5;
    $score = (int) round(min(100, $score));
    $label = match (true) { $done === 0 && !$rev['n'] => 'New provider', $score >= 80 => 'Top rated', $score >= 65 => 'Highly trusted', $score >= 45 => 'Trusted', default => 'Rising' };
    return [
        'score' => $score, 'label' => $label, 'key' => strtolower(str_replace(' ', '-', $label)),
        'done' => $done, 'active' => (int) ($o['active'] ?? 0), 'clients' => (int) ($o['clients'] ?? 0), 'repeat' => $repeat,
        'on_time' => $onTime, 'completion' => $completion, 'response_h' => $respH,
        'rating' => round((float) $rev['avg'], 1), 'reviews' => (int) $rev['n'], 'vouches' => (int) $vouch['n'], 'vouch_avg' => round((float) $vouch['avg'], 1),
        'since' => $since, 'member_since' => $user['created_at'] ?? null,
    ];
}
function response_label(?float $h): string
{
    if ($h === null) return 'No requests yet';
    return match (true) { $h < 1 => 'within an hour', $h < 24 => 'within ' . max(1, (int) ceil($h)) . ' hours', default => 'within ' . (int) ceil($h / 24) . ' days' };
}
function since_label(?string $dt): string
{
    if (!$dt) return '';
    $days = (int) floor((time() - strtotime($dt)) / 86400);
    $span = match (true) { $days < 7 => 'this week', $days < 31 => (int) ceil($days / 7) . ' week' . ((int) ceil($days / 7) === 1 ? '' : 's'), $days < 365 => (int) floor($days / 30) . ' month' . ((int) floor($days / 30) === 1 ? '' : 's'), default => (int) floor($days / 365) . ' year' . ((int) floor($days / 365) === 1 ? '' : 's') };
    return date('M Y', strtotime($dt)) . ' · ' . $span;
}

/* ---------- pages ---------- */

function page_marketplace(): void
{
    $u = require_login();
    ensure_market_schema();
    ensure_reputation_schema();
    $cat = input('cat');
    $q = input('q');
    $kind = isset(SERVICE_KINDS[input('kind')]) ? input('kind') : '';
    $sort = input('sort', 'new');
    $where = ["s.status = 'active'"];
    $params = [];
    if (isset(SERVICE_CATEGORIES[$cat])) { $where[] = 's.category = ?'; $params[] = $cat; }
    if ($kind) { $where[] = 's.kind = ?'; $params[] = $kind; }
    if ($q !== '') { $where[] = '(s.title LIKE ? OR s.description LIKE ? OR s.skills LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
    $order = ['new' => 's.id DESC', 'cheap' => 's.price ASC, s.id DESC', 'top' => 'rating DESC, reviews DESC, s.id DESC', 'orders' => 'orders DESC, s.id DESC'][$sort] ?? 's.id DESC';
    $services = qall('SELECT s.*, u.full_name, u.avatar_url, u.email, u.verified, u.reputation_score, u.role,
        (SELECT COALESCE(AVG(r.rating), 0) FROM service_reviews r WHERE r.service_id = s.id) AS rating,
        (SELECT COUNT(*) FROM service_reviews r WHERE r.service_id = s.id) AS reviews,
        (SELECT COUNT(*) FROM service_requests r WHERE r.service_id = s.id AND r.status = \'completed\') AS orders,
        (SELECT COUNT(*) FROM service_requests r WHERE r.service_id = s.id AND r.status IN (\'accepted\', \'completed\')) AS joined,
        (SELECT COUNT(*) FROM service_files f WHERE f.service_id = s.id) AS files
        FROM services s JOIN users u ON u.id = s.user_id WHERE ' . implode(' AND ', $where) . " ORDER BY $order LIMIT 60", $params);
    $trust = [];
    foreach (array_unique(array_map('intval', array_column($services, 'user_id'))) as $sid) $trust[$sid] = seller_trust($sid);
    // the most trusted providers, for the strip at the top
    $top = [];
    foreach (qall("SELECT DISTINCT u.id, u.full_name, u.avatar_url, u.email, u.verified, u.reputation_score, u.role, u.headline, u.major FROM services s JOIN users u ON u.id = s.user_id WHERE s.status = 'active' AND s.kind = 'offer'") as $p) {
        $p['trust'] = $trust[(int) $p['id']] ?? seller_trust((int) $p['id']);
        if ($p['trust']['done']) $top[] = $p;
    }
    usort($top, fn($a, $b) => [$b['trust']['score'], $b['trust']['done']] <=> [$a['trust']['score'], $a['trust']['done']]);
    $top = array_slice($top, 0, 4);
    $counts = array_column(qall("SELECT category, COUNT(*) AS n FROM services WHERE status = 'active'" . ($kind ? ' AND kind = ?' : '') . ' GROUP BY category', $kind ? [$kind] : []), 'n', 'category');
    $stats = qrow("SELECT (SELECT COUNT(*) FROM services WHERE status = 'active') AS listings, (SELECT COUNT(DISTINCT user_id) FROM services WHERE status = 'active') AS providers,
        (SELECT COUNT(*) FROM service_requests WHERE status = 'completed') AS delivered, (SELECT COUNT(*) FROM services WHERE status = 'active' AND kind = 'request') AS requests");
    $isAdmin = is_admin();
    $adminMode = $isAdmin && !empty($_SESSION['market_admin']);
    render('marketplace', compact('u', 'services', 'cat', 'q', 'sort', 'kind', 'counts', 'trust', 'top', 'stats', 'isAdmin', 'adminMode'));
}

function page_service_new(): void
{
    $u = require_login();
    ensure_market_schema();
    $s = ['id' => 0, 'kind' => input('kind') === 'request' ? 'request' : 'offer', 'title' => '', 'description' => '', 'category' => 'other', 'price' => 0, 'delivery_days' => 3,
        'slots' => 1, 'includes' => '', 'skills' => '', 'revisions' => 1, 'packages' => '', 'cover_image' => null];
    render('service_new', compact('u', 's'));
}
function page_service_edit(int $id): void
{
    [$s, $u] = service_edit_or_403($id);
    render('service_new', compact('u', 's'));
}

/** Read the listing form (create and edit). */
function service_form_data(): array
{
    $kind = input('kind') === 'request' ? 'request' : 'offer';
    $packages = [];
    if ($kind === 'offer' && input('use_packages')) {
        foreach (['basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium'] as $k => $default) {
            $name = mb_substr(trim((string) ($_POST["pkg_{$k}_name"] ?? $default)), 0, 30) ?: $default;
            $desc = mb_substr(trim((string) ($_POST["pkg_{$k}_desc"] ?? '')), 0, 300);
            $price = max(0, min(100000, (int) ($_POST["pkg_{$k}_price"] ?? 0)));
            $days = max(1, min(90, (int) ($_POST["pkg_{$k}_days"] ?? 3)));
            if ($desc !== '' || $price > 0) $packages[] = ['key' => $k, 'name' => $name, 'price' => $price, 'days' => $days, 'desc' => $desc];
        }
    }
    return [
        'kind' => $kind,
        'title' => mb_substr(input('title'), 0, 200),
        'description' => mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 4000),
        'category' => isset(SERVICE_CATEGORIES[input('category')]) ? input('category') : 'other',
        'price' => $packages ? (int) min(array_column($packages, 'price')) : max(0, min(100000, input_int($kind === 'request' ? 'price_req' : 'price'))),
        'delivery_days' => $packages ? (int) min(array_column($packages, 'days')) : max(1, min(90, input_int($kind === 'request' ? 'days_req' : 'delivery_days', 3))),
        'slots' => max(1, min(50, input_int('slots', 1))),
        'revisions' => max(0, min(20, input_int('revisions', 1))),
        'includes' => mb_substr(implode("\n", service_lines((string) ($_POST['includes'] ?? ''))), 0, 2000),
        'skills' => mb_substr(implode(', ', array_slice(service_skills(input('skills')), 0, 12)), 0, 255),
        'packages' => $packages ? json_encode($packages, JSON_UNESCAPED_UNICODE) : null,
    ];
}
/** Cover picture, portfolio files, folders and links that come with the listing form. */
function service_form_media(int $id, ?string $oldCover): void
{
    try {
        $cover = save_upload('cover', 'service-covers');
        if ($cover) {
            update('services', ['cover_image' => $cover], 'id = ?', [$id]);
            service_delete_cover($oldCover);
        }
    } catch (Throwable $ex) { flash('The cover was not saved: ' . $ex->getMessage(), 'error'); }
    $proof = (bool) input('is_proof');
    $note = mb_substr(input('proof_note'), 0, 255);
    [, $skipped] = service_store_uploads($id, $proof, $note);
    service_store_links($id, (string) ($_POST['links'] ?? ''), $proof, $note);
    if ($skipped) flash(count($skipped) . ' file' . (count($skipped) === 1 ? ' was' : 's were') . ' skipped (type or size not allowed): ' . implode(', ', array_slice($skipped, 0, 5)), 'error');
}
function service_delete_cover(?string $path): void
{
    if ($path && preg_match('#^uploads/service-covers/[a-f0-9]{20}\.(?:jpg|png|webp|gif)$#', $path)) @unlink(dirname(__DIR__, 2) . '/public/' . $path);
}

function service_create(): void
{
    $u = require_login();
    ensure_market_schema();
    $d = service_form_data();
    if (mb_strlen($d['title']) < 5 || mb_strlen($d['description']) < 20) { flash('Add a clear title and a description (20+ characters).', 'error'); redirect('marketplace/new' . ($d['kind'] === 'request' ? '?kind=request' : '')); }
    $id = insert('services', ['user_id' => $u['id']] + $d);
    service_form_media($id, null);
    award_points((int) $u['id'], 10, $d['kind'] === 'request' ? 'Posted a request' : 'Listed a service');
    flash($d['kind'] === 'request' ? 'Your request is live. People can now apply. +10 points' : 'Your service is live in the marketplace. +10 points');
    redirect("marketplace/$id");
}

function service_save(int $id): void
{
    [$s] = service_edit_or_403($id);
    $d = service_form_data();
    if (mb_strlen($d['title']) < 5 || mb_strlen($d['description']) < 20) { flash('Add a clear title and a description (20+ characters).', 'error'); redirect("marketplace/$id/edit"); }
    update('services', $d, 'id = ?', [$id]);
    service_form_media($id, $s['cover_image']);
    flash('Listing updated.');
    redirect("marketplace/$id");
}

function service_cover(int $id): void
{
    [$s] = service_edit_or_403($id);
    if (input('remove')) { update('services', ['cover_image' => null], 'id = ?', [$id]); service_delete_cover($s['cover_image']); flash('Cover removed.'); redirect("marketplace/$id"); }
    try { $new = save_upload('cover', 'service-covers') ?? throw new RuntimeException('Choose an image.'); }
    catch (Throwable $ex) { flash($ex->getMessage(), 'error'); redirect("marketplace/$id"); }
    update('services', ['cover_image' => $new], 'id = ?', [$id]);
    service_delete_cover($s['cover_image']);
    flash('Cover updated.');
    redirect("marketplace/$id");
}

function service_files_add(int $id): void
{
    service_edit_or_403($id);
    $proof = (bool) input('is_proof');
    $note = mb_substr(input('proof_note'), 0, 255);
    [$saved, $skipped] = service_store_uploads($id, $proof, $note);
    $links = service_store_links($id, (string) ($_POST['links'] ?? ''), $proof, $note);
    if ($skipped) flash(($saved + $links) . ' added. Skipped (type or size not allowed): ' . implode(', ', array_slice($skipped, 0, 5)), 'error');
    elseif (!$saved && !$links) flash('Choose files or a folder, or paste links.', 'error');
    else flash('Added to your portfolio.');
    redirect("marketplace/$id#portfolio");
}

function service_file_delete(int $id, int $fid): void
{
    service_edit_or_403($id);
    $f = qrow('SELECT * FROM service_files WHERE id = ? AND service_id = ?', [$fid, $id]) ?? abort(404);
    if ($f['file_path']) @unlink(dirname(__DIR__, 2) . '/storage/service_files/' . $f['file_path']);
    qexec('DELETE FROM service_files WHERE id = ?', [$fid]);
    redirect("marketplace/$id#portfolio");
}

/** Portfolio files are visible to every signed-in member (they're the seller's showcase). */
function service_file(int $id, int $fid): void
{
    require_login();
    ensure_market_schema();
    $f = qrow("SELECT * FROM service_files WHERE id = ? AND service_id = ? AND kind = 'file'", [$fid, $id]) ?? abort(404);
    $path = dirname(__DIR__, 2) . '/storage/service_files/' . $f['file_path'];
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

function page_service(int $id): void
{
    $u = require_login();
    $s = service_load($id);
    ensure_pay_schema();
    if ($s['status'] === 'hidden' && !is_admin() && (int) $s['user_id'] !== (int) $u['id']) abort(404);
    $reviews = qall('SELECT r.*, u.full_name, u.avatar_url, u.email, u.major FROM service_reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.service_id = ? ORDER BY r.id DESC', [$id]);
    $avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
    $mine = (int) $s['user_id'] === (int) $u['id'];
    $canEdit = service_can_edit($s, $u);
    $myReq = qrow('SELECT * FROM service_requests WHERE service_id = ? AND buyer_id = ? ORDER BY id DESC', [$id, $u['id']]);
    $canReview = $s['kind'] === 'offer' && $myReq && $myReq['status'] === 'completed' && !qval('SELECT COUNT(*) FROM service_reviews WHERE service_id = ? AND reviewer_id = ?', [$id, $u['id']]);
    $incoming = $mine ? qall('SELECT r.*, u.full_name, u.avatar_url, u.email, u.major FROM service_requests r JOIN users u ON u.id = r.buyer_id WHERE r.service_id = ? ORDER BY r.id DESC', [$id]) : [];
    $files = qall('SELECT * FROM service_files WHERE service_id = ? ORDER BY is_proof DESC, folder IS NOT NULL, id', [$id]);
    $members = $s['kind'] === 'request' ? qall("SELECT u.id, u.full_name, u.avatar_url, u.email, u.major FROM service_requests r JOIN users u ON u.id = r.buyer_id WHERE r.service_id = ? AND r.status IN ('accepted','completed') ORDER BY r.decided_at", [$id]) : [];
    $trust = seller_trust((int) $s['user_id']);
    $orders = (int) qval("SELECT COUNT(*) FROM service_requests WHERE service_id = ? AND status = 'completed'", [$id]);
    $vouches = qall('SELECT v.body, v.rating, u.full_name, u.avatar_url, u.email, p.title AS project FROM project_vouches v JOIN users u ON u.id = v.author_id LEFT JOIN projects p ON p.id = v.project_id WHERE v.subject_id = ? ORDER BY v.rating DESC, v.id DESC LIMIT 2', [$s['user_id']]);
    $more = qall("SELECT s.*, (SELECT COALESCE(AVG(r.rating),0) FROM service_reviews r WHERE r.service_id = s.id) AS rating FROM services s WHERE s.user_id = ? AND s.id <> ? AND s.status = 'active' ORDER BY s.id DESC LIMIT 3", [$s['user_id'], $id]);
    $dist = array_fill(1, 5, 0);
    foreach ($reviews as $r) $dist[(int) $r['rating']]++;
    // money on each order: paid so far (held or released) and what is still due
    $paidBy = [];
    foreach (qall("SELECT p.request_id, SUM(p.amount) AS paid, SUM(CASE WHEN p.status = 'held' THEN p.amount ELSE 0 END) AS held FROM market_payments p JOIN service_requests r ON r.id = p.request_id WHERE r.service_id = ? GROUP BY p.request_id", [$id]) as $p)
        $paidBy[(int) $p['request_id']] = ['paid' => (int) $p['paid'], 'held' => (int) $p['held']];
    $isAdmin = is_admin();
    $adminMode = $isAdmin && !empty($_SESSION['market_admin']);
    render('service', compact('u', 's', 'reviews', 'avg', 'mine', 'canEdit', 'myReq', 'canReview', 'incoming', 'files', 'members', 'trust', 'orders', 'vouches', 'more', 'dist', 'paidBy', 'isAdmin', 'adminMode'));
}

function service_request(int $id): void
{
    $u = require_login();
    $s = service_load($id);
    if ($s['status'] !== 'active') abort(404);
    if ((int) $s['user_id'] === (int) $u['id']) { flash('You cannot request your own listing.', 'error'); redirect("marketplace/$id"); }
    if (qval("SELECT COUNT(*) FROM service_requests WHERE service_id = ? AND buyer_id = ? AND status IN ('pending','accepted')", [$id, $u['id']])) { flash('You already have an open request here.', 'error'); redirect("marketplace/$id"); }
    if ($s['kind'] === 'request' && (int) qval("SELECT COUNT(*) FROM service_requests WHERE service_id = ? AND status IN ('accepted','completed')", [$id]) >= (int) $s['slots']) { flash('All spots are already filled.', 'error'); redirect("marketplace/$id"); }
    $pkg = null;
    foreach (service_packages($s) as $p) if ($p['key'] === input('package')) $pkg = $p['key'];
    $msg = mb_substr(input('message'), 0, 1500);
    insert('service_requests', ['service_id' => $id, 'buyer_id' => $u['id'], 'message' => $msg, 'package' => $pkg]);
    // start the conversation in Messages, so both sides can talk the details through
    ensure_pay_schema();
    order_chat((int) $u['id'], (int) $s['user_id'], ($s['kind'] === 'request' ? '🙋 I applied to “' : '📦 New request for “') . excerpt($s['title'], 60) . '”' . ($pkg ? ' (' . ucfirst($pkg) . ' package)' : '') . ($msg !== '' ? ":\n" . $msg : '.'));
    notify((int) $s['user_id'], 'service_request', $u['full_name'] . ($s['kind'] === 'request' ? ' applied to “' : ' requested “') . excerpt($s['title'], 50) . '”', "marketplace/$id");
    flash($s['kind'] === 'request' ? 'Application sent. You can chat with them in Messages.' : 'Request sent. Chat with the seller in Messages; they’ll send you an offer with the price and payment terms.');
    redirect("marketplace/$id");
}

function service_request_decide(int $id, int $rid): void
{
    $u = require_login();
    $s = service_load($id);
    if ((int) $s['user_id'] !== (int) $u['id']) abort(403);
    $r = qrow('SELECT * FROM service_requests WHERE id = ? AND service_id = ?', [$rid, $id]) ?? abort(404);
    $to = ['accept' => 'accepted', 'decline' => 'declined', 'complete' => 'completed'][input('decision')] ?? abort(422);
    if ($to === 'accepted' && $s['kind'] === 'request' && (int) qval("SELECT COUNT(*) FROM service_requests WHERE service_id = ? AND status IN ('accepted','completed')", [$id]) >= (int) $s['slots']) {
        flash('All ' . (int) $s['slots'] . ' spots are filled. Raise the number of people in Edit to accept more.', 'error'); redirect("marketplace/$id");
    }
    $data = ['status' => $to];
    if (in_array($to, ['accepted', 'declined'], true) && !$r['decided_at']) $data['decided_at'] = now();
    if ($to === 'completed') $data['completed_at'] = now();
    update('service_requests', $data, 'id = ?', [$rid]);
    $txt = $s['kind'] === 'request'
        ? ['accepted' => 'accepted you for', 'declined' => 'could not take you for', 'completed' => 'wrapped up'][$to]
        : ['accepted' => 'accepted your request for', 'declined' => 'declined your request for', 'completed' => 'marked as delivered:'][$to];
    notify((int) $r['buyer_id'], 'service_' . $to, $u['full_name'] . " $txt “" . excerpt($s['title'], 50) . '”', "marketplace/$id");
    if ($to === 'completed' && $s['kind'] === 'offer') award_points((int) $u['id'], 10, 'Delivered a service');
    flash(['accepted' => 'Accepted.', 'declined' => 'Declined.', 'completed' => $s['kind'] === 'request' ? 'Marked as done.' : 'Marked as delivered. The buyer can now leave a review.'][$to]);
    redirect("marketplace/$id");
}

/**
 * The person-to-person order flow for services:
 * pending → (seller) offer → (buyer) accept, paying now if the terms say so → accepted (in progress)
 * → (seller) deliver → (buyer) confirm, paying the rest if due → completed, money released to the seller.
 */
function service_order_action(int $id, int $rid): void
{
    $u = require_login();
    ensure_pay_schema();
    $s = service_load($id);
    $r = qrow('SELECT * FROM service_requests WHERE id = ? AND service_id = ?', [$rid, $id]) ?? abort(404);
    $isSeller = (int) $s['user_id'] === (int) $u['id'];
    $isBuyer = (int) $r['buyer_id'] === (int) $u['id'];
    $act = input('act');
    $back = "marketplace/$id";
    $r += ['seller_id' => $s['user_id'], 'title' => $s['title'], 'sid' => $id];
    switch ($act) {
        case 'offer':   // seller proposes the deal
            if (!$isSeller || !in_array($r['status'], ['pending', 'offered'], true)) abort(403);
            $terms = isset(PAY_TERMS[input('terms')]) ? input('terms') : 'before';
            $price = max(0, min(100000, input_int('price')));
            if ($price === 0) $terms = 'free';
            elseif ($terms === 'free') $terms = 'before';
            $days = max(1, min(90, input_int('days', (int) $s['delivery_days'])));
            update('service_requests', ['status' => 'offered', 'agreed_price' => $price, 'agreed_days' => $days, 'pay_terms' => $terms, 'offer_note' => mb_substr(input('note'), 0, 500) ?: null, 'decided_at' => $r['decided_at'] ?: now()], 'id = ?', [$rid]);
            order_chat((int) $u['id'], (int) $r['buyer_id'], '💼 Offer for “' . excerpt($s['title'], 60) . '”: ' . ($price ? 'AED ' . $price : 'free') . ', ' . $days . '-day delivery, ' . strtolower(PAY_TERMS[$terms][0]) . '. Review and accept it on the listing page.');
            notify((int) $r['buyer_id'], 'service_offer', $u['full_name'] . ' sent you an offer for “' . excerpt($s['title'], 50) . '”', $back);
            flash('Offer sent. The buyer can accept it from the listing page.');
            break;
        case 'accept':   // buyer agrees; pays now when the terms ask for it
            if (!$isBuyer || $r['status'] !== 'offered') abort(403);
            [$due] = order_due($r);
            if ($due > 0) redirect("checkout/order/$rid");
            update('service_requests', ['status' => 'accepted'], 'id = ?', [$rid]);
            order_chat((int) $u['id'], (int) $s['user_id'], '✅ I accepted your offer for “' . excerpt($s['title'], 60) . '”. Let’s start!');
            notify((int) $s['user_id'], 'service_accepted', $u['full_name'] . ' accepted your offer. You can start the work.', $back);
            flash('Offer accepted. ' . ($r['pay_terms'] === 'cash' ? 'You’ll pay in cash when you meet.' : ((int) $r['agreed_price'] ? 'You’ll pay when the work is delivered.' : 'Enjoy!')));
            break;
        case 'decline':   // seller declines, or the buyer cancels, before work starts
            if (!($isBuyer || $isSeller) || !in_array($r['status'], ['pending', 'offered'], true)) abort(403);
            update('service_requests', ['status' => $isSeller ? 'declined' : 'cancelled', 'decided_at' => $r['decided_at'] ?: now()], 'id = ?', [$rid]);
            order_chat((int) $u['id'], $isSeller ? (int) $r['buyer_id'] : (int) $s['user_id'], '❌ ' . ($isSeller ? 'I can’t take this order' : 'I cancelled my request') . ' for “' . excerpt($s['title'], 60) . '”.');
            notify($isSeller ? (int) $r['buyer_id'] : (int) $s['user_id'], 'service_declined', $u['full_name'] . ($isSeller ? ' declined your request' : ' cancelled the request') . ' for “' . excerpt($s['title'], 50) . '”', $back);
            flash($isSeller ? 'Request declined.' : 'Request cancelled.');
            break;
        case 'deliver':   // seller hands over the work
            if (!$isSeller || $r['status'] !== 'accepted') abort(403);
            update('service_requests', ['status' => 'delivered', 'delivered_at' => now()], 'id = ?', [$rid]);
            order_chat((int) $u['id'], (int) $r['buyer_id'], '📦 I delivered “' . excerpt($s['title'], 60) . '”. Please check it and confirm on the listing page.' . (input('note') !== '' ? "\n" . mb_substr(input('note'), 0, 500) : ''));
            notify((int) $r['buyer_id'], 'service_delivered', $u['full_name'] . ' delivered your order. Check it and confirm.', $back);
            flash('Marked as delivered. The buyer will confirm and release the payment.');
            break;
        case 'confirm':   // buyer accepts the delivery: pay the rest if due, then the money is released
            if (!$isBuyer || $r['status'] !== 'delivered') abort(403);
            [$due] = order_due($r);
            if ($due > 0) redirect("checkout/order/$rid");
            if ($r['pay_terms'] === 'cash' && (int) $r['agreed_price'] > 0)
                insert('market_payments', ['request_id' => $rid, 'payer_id' => $u['id'], 'payee_id' => $s['user_id'], 'amount' => (int) $r['agreed_price'], 'method' => 'cash', 'stage' => 'full', 'status' => 'cash', 'ref' => pay_ref()]);
            order_complete($r, $u);
            flash('Delivery confirmed. ' . ($r['pay_terms'] === 'before' ? 'Your payment was released to the seller.' : 'Thanks!') . ' You can now leave a review.');
            $back .= '#reviews';
            break;
        default: abort(422);
    }
    redirect($back);
}

function service_review(int $id): void
{
    $u = require_login();
    $s = service_load($id);
    $ok = qval("SELECT COUNT(*) FROM service_requests WHERE service_id = ? AND buyer_id = ? AND status = 'completed'", [$id, $u['id']]);
    if (!$ok || qval('SELECT COUNT(*) FROM service_reviews WHERE service_id = ? AND reviewer_id = ?', [$id, $u['id']])) { flash('You can only review a completed order once.', 'error'); redirect("marketplace/$id"); }
    $rating = max(1, min(5, input_int('rating', 5)));
    insert('service_reviews', ['service_id' => $id, 'reviewer_id' => $u['id'], 'rating' => $rating, 'comment' => mb_substr(input('comment'), 0, 1000)]);
    notify((int) $s['user_id'], 'review', $u['full_name'] . ' left you a ' . $rating . '★ review', "marketplace/$id");
    if ($rating >= 4) award_points((int) $s['user_id'], $rating === 5 ? 10 : 5, 'Received a ' . $rating . '-star review'); else check_badges((int) $s['user_id']);
    flash('Thanks for your review!');
    redirect("marketplace/$id#reviews");
}

function service_toggle(int $id): void
{
    [$s] = service_edit_or_403($id);
    if (input('delete')) {
        foreach (qall("SELECT file_path FROM service_files WHERE service_id = ? AND kind = 'file'", [$id]) as $f) @unlink(dirname(__DIR__, 2) . '/storage/service_files/' . $f['file_path']);
        @rmdir(dirname(__DIR__, 2) . "/storage/service_files/$id");
        service_delete_cover($s['cover_image']);
        qexec('DELETE FROM services WHERE id = ?', [$id]);
        flash('Listing deleted.');
        redirect('marketplace');
    }
    update('services', ['status' => $s['status'] === 'active' ? 'paused' : 'active'], 'id = ?', [$id]);
    redirect("marketplace/$id");
}

function page_orders(): void
{
    $u = require_login();
    ensure_market_schema();
    $bought = qall('SELECT r.*, s.title, s.price, s.kind, s.id AS sid, u.full_name AS provider FROM service_requests r JOIN services s ON s.id = r.service_id JOIN users u ON u.id = s.user_id WHERE r.buyer_id = ? ORDER BY r.id DESC', [$u['id']]);
    $sold = qall('SELECT r.*, s.title, s.price, s.kind, s.id AS sid, u.full_name AS buyer, u.avatar_url, u.email FROM service_requests r JOIN services s ON s.id = r.service_id JOIN users u ON u.id = r.buyer_id WHERE s.user_id = ? ORDER BY r.id DESC', [$u['id']]);
    $mine = qall('SELECT s.*, (SELECT COUNT(*) FROM service_requests r WHERE r.service_id = s.id) AS orders FROM services s WHERE s.user_id = ? ORDER BY s.id DESC', [$u['id']]);
    $trust = seller_trust((int) $u['id']);
    ensure_pay_schema();
    $wallet = wallet((int) $u['id']);
    render('orders', compact('u', 'bought', 'sold', 'mine', 'trust', 'wallet'));
}
