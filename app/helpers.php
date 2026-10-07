<?php
/** Global helpers: config, escaping, URLs, session, CSRF, flash, rendering. */

function cfg(?string $key = null)
{
    static $c = null;
    $c ??= require __DIR__ . '/../config/config.php';
    return $key === null ? $c : ($c[$key] ?? null);
}

function now(): string { return date('Y-m-d H:i:s'); }

function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/* ---------- URLs ---------- */
function base_path(): string
{
    static $b = null;
    if ($b === null) {
        $sn = $_SERVER['SCRIPT_NAME'] ?? '/';
        // Only trust SCRIPT_NAME when it points at a .php file (the built-in server may echo the request path).
        $b = (PHP_SAPI === 'cli-server' || !str_ends_with($sn, '.php')) ? '' : rtrim(str_replace('\\', '/', dirname($sn)), '/');
    }
    return $b;
}
function url(string $path = ''): string { return base_path() . '/' . ltrim($path, '/'); }
function asset(string $path): string
{
    $f = __DIR__ . '/../public/assets/' . ltrim($path, '/');
    return url('assets/' . ltrim($path, '/')) . (is_file($f) ? '?v=' . filemtime($f) : '');
}
function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}
function json_out(array $d, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- Session / flash / CSRF ---------- */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('MANBARSESS');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'secure' => $https, 'samesite' => 'Lax']);
    session_start();
}
function flash(?string $msg = null, string $type = 'success')
{
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(csrf_token(), (string) $t)) {
        if (wants_json()) json_out(['ok' => false, 'error' => 'Session expired. Refresh the page.'], 419);
        abort(419, 'Your session expired. Please go back, refresh and try again.');
    }
}
function old(string $k, $d = '') { return $_SESSION['old'][$k] ?? $d; }
function keep_old(array $d): void { $_SESSION['old'] = $d; }
function clear_old(): void { unset($_SESSION['old']); }

function input(string $k, $d = ''): string { return trim((string) ($_POST[$k] ?? $_GET[$k] ?? $d)); }
function input_int(string $k, int $d = 0): int { return (int) ($_POST[$k] ?? $_GET[$k] ?? $d); }

/* ---------- Rendering ---------- */
function render(string $view, array $data = [], string $layout = 'layout'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . "/views/$view.php";
    $content = ob_get_clean();
    if ($layout === '') { echo $content; return; }
    require __DIR__ . "/views/$layout.php";
}
function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . "/views/partials/$name.php";
}
function abort(int $code, string $msg = ''): never
{
    http_response_code($code);
    $titles = [403 => 'No access', 404 => 'Page not found', 419 => 'Session expired', 500 => 'Something went wrong'];
    if (wants_json()) json_out(['ok' => false, 'error' => $msg ?: ($titles[$code] ?? 'Error')], $code);
    render('error', ['code' => $code, 'title' => $titles[$code] ?? 'Error', 'msg' => $msg], isset($_SESSION['uid']) ? 'layout' : 'layout_public');
    exit;
}

/* ---------- Text helpers ---------- */
function time_ago(?string $dt): string
{
    if (!$dt) return '';
    $d = time() - strtotime($dt);
    if ($d < 45) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 86400 * 7) return floor($d / 86400) . 'd ago';
    return date('M j, Y', strtotime($dt));
}
function excerpt(string $s, int $n = 180): string
{
    $s = trim(preg_replace('/\s+/', ' ', $s));
    return mb_strlen($s) > $n ? mb_substr($s, 0, $n - 1) . '…' : $s;
}
/** Escape then turn #tags and newlines into safe HTML. */
function rich(string $s): string
{
    $s = e($s);
    $s = preg_replace_callback('/(?<![\w&])#([\p{L}\p{N}_]{2,30})/u', fn($m) => '<a class="hashtag" href="' . e(url('feed?tag=' . strtolower($m[1]))) . '">#' . $m[1] . '</a>', $s);
    $s = preg_replace('~(?<!["\'>])\bhttps?://[^\s<]+~i', '<a href="$0" target="_blank" rel="noopener nofollow">$0</a>', $s);
    return nl2br($s);
}
function parse_tags(string $raw): array
{
    $t = preg_split('/[,\s#]+/u', mb_strtolower($raw), -1, PREG_SPLIT_NO_EMPTY);
    $t = array_values(array_unique(array_map(fn($x) => mb_substr(preg_replace('/[^\p{L}\p{N}_-]/u', '', $x), 0, 30), $t)));
    return array_slice(array_filter($t), 0, 6);
}
function csv_list(?string $s): array { return $s === null || $s === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $s)))); }

/* ---------- Icons / avatars ---------- */
function icon(string $name, int $size = 20, string $cls = ''): string
{
    return '<svg class="ic ' . e($cls) . '" width="' . $size . '" height="' . $size . '" aria-hidden="true"><use href="' . e(asset('img/icons.svg')) . '#i-' . e($name) . '"/></svg>';
}
function initials(string $name): string
{
    $p = preg_split('/\s+/', trim($name));
    $a = mb_strtoupper(mb_substr($p[0] ?? '?', 0, 1));
    $b = count($p) > 1 ? mb_strtoupper(mb_substr(end($p), 0, 1)) : '';
    return $a . $b;
}
function avatar(array $u, int $size = 40, string $cls = ''): string
{
    $name = $u['full_name'] ?? $u['name'] ?? '?';
    $hue = (crc32((string) ($u['email'] ?? $name)) % 60) + 100;   // greens → teals
    $style = "width:{$size}px;height:{$size}px;font-size:" . round($size * .38) . "px;";
    if (!empty($u['avatar_url'])) {
        $src = preg_match('#^https?://#', $u['avatar_url']) ? $u['avatar_url'] : url($u['avatar_url']);
        return '<img class="avatar ' . e($cls) . '" style="' . $style . '" src="' . e($src) . '" alt="' . e($name) . '" referrerpolicy="no-referrer" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement(\'span\'),{className:\'avatar ' . e($cls) . '\',textContent:\'' . e(initials($name)) . '\',style:\'' . $style . 'background:hsl(' . $hue . ' 45% 42%)\'}))">';
    }
    return '<span class="avatar ' . e($cls) . '" style="' . $style . 'background:hsl(' . $hue . ' 45% 42%)">' . e(initials($name)) . '</span>';
}
function role_label(string $r): string { return ['student' => 'Student', 'teacher' => 'Teacher', 'admin' => 'Admin'][$r] ?? ucfirst($r); }
function verified_badge(array $u): string
{
    $marks = '';
    if (!empty($u['verified'])) $marks .= '<span class="verified" title="Verified university member">' . icon('check-circle', 15) . '</span>';
    $score = (int) ($u['reputation_score'] ?? 0);
    if ($score >= 500) {
        $level = level_for($score);
        $key = rank_key((int) $level['n']);
        $marks .= '<span class="rank-trust-mark ' . e($key) . '" title="Trusted ' . e($level['name']) . ' member">' . icon('award', 15) . '</span>';
    }
    return $marks;
}

function audit(string $action, string $detail = ''): void
{
    qexec('INSERT INTO audit_log (admin_id, action, detail, created_at) VALUES (?,?,?,?)', [uid() ?: null, $action, mb_substr($detail, 0, 250), now()]);
}

function req_path(): string
{
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $b = base_path();
    if ($b !== '' && str_starts_with($p, $b)) $p = substr($p, strlen($b));
    return '/' . trim($p, '/');
}
function nav_on(string ...$prefixes): string
{
    $p = req_path();
    foreach ($prefixes as $x) if ($p === $x || str_starts_with($p, $x . '/')) return ' on';
    return '';
}

function stars(float $r): string
{
    $full = (int) round($r);
    return '<span class="stars" title="' . number_format($r, 1) . ' / 5">' . str_repeat('★', $full) . '<i>' . str_repeat('★', 5 - $full) . '</i></span>';
}
const SERVICE_ICONS = ['design' => 'wand', 'programming' => 'layers', 'writing' => 'edit', 'tutoring' => 'cap', 'media' => 'image', 'business' => 'chart', 'other' => 'tag'];

/* ---------- tiny SVG charts (no JS libraries needed) ---------- */
function chart_lines(array $series, array $colors, array $labels, int $w = 760, int $h = 270): string
{
    $pl = 38; $pr = 14; $pt = 14; $pb = 30;
    $max = max(1, ...array_map(fn($s) => max($s), $series));
    $max = (int) (ceil($max / 4) * 4) ?: 4;
    $n = count($labels);
    $x = fn($i) => $pl + ($w - $pl - $pr) * ($n > 1 ? $i / ($n - 1) : 0);
    $y = fn($v) => $pt + ($h - $pt - $pb) * (1 - $v / $max);
    $o = '<svg class="chart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Activity chart"><defs>';
    foreach ($colors as $i => $c) $o .= '<linearGradient id="ga' . $i . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . $c . '" stop-opacity=".28"/><stop offset="1" stop-color="' . $c . '" stop-opacity="0"/></linearGradient>';
    $o .= '</defs>';
    for ($g = 0; $g <= 4; $g++) {
        $v = $max / 4 * $g; $yy = $y($v);
        $o .= '<line x1="' . $pl . '" x2="' . ($w - $pr) . '" y1="' . $yy . '" y2="' . $yy . '" stroke="#dcebe2" stroke-dasharray="' . ($g ? '4 5' : '0') . '"/><text x="' . ($pl - 8) . '" y="' . ($yy + 4) . '" text-anchor="end" font-size="11" fill="#8da498">' . (int) $v . '</text>';
    }
    foreach ($labels as $i => $l) if ($i % 2 === 0 || $i === $n - 1) $o .= '<text x="' . $x($i) . '" y="' . ($h - 8) . '" text-anchor="middle" font-size="11" fill="#8da498">' . e($l) . '</text>';
    foreach (array_values($series) as $si => $vals) {
        $pts = [];
        foreach ($vals as $i => $v) $pts[] = [$x($i), $y($v)];
        $d = 'M' . round($pts[0][0], 1) . ',' . round($pts[0][1], 1);
        for ($i = 0; $i < count($pts) - 1; $i++) {
            $p0 = $pts[max(0, $i - 1)]; $p1 = $pts[$i]; $p2 = $pts[$i + 1]; $p3 = $pts[min(count($pts) - 1, $i + 2)];
            $c1 = [$p1[0] + ($p2[0] - $p0[0]) / 6, $p1[1] + ($p2[1] - $p0[1]) / 6];
            $c2 = [$p2[0] - ($p3[0] - $p1[0]) / 6, $p2[1] - ($p3[1] - $p1[1]) / 6];
            $d .= ' C' . round($c1[0], 1) . ',' . round($c1[1], 1) . ' ' . round($c2[0], 1) . ',' . round($c2[1], 1) . ' ' . round($p2[0], 1) . ',' . round($p2[1], 1);
        }
        $area = $d . ' L' . round($pts[count($pts) - 1][0], 1) . ',' . ($h - $pb) . ' L' . round($pts[0][0], 1) . ',' . ($h - $pb) . ' Z';
        $o .= '<path d="' . $area . '" fill="url(#ga' . $si . ')"/><path d="' . $d . '" fill="none" stroke="' . $colors[$si] . '" stroke-width="3" stroke-linecap="round"/>';
        foreach ($pts as $i => [$px, $py]) $o .= '<circle cx="' . round($px, 1) . '" cy="' . round($py, 1) . '" r="3.5" fill="#fff" stroke="' . $colors[$si] . '" stroke-width="2"><title>' . e($labels[$i]) . ': ' . $vals[$i] . '</title></circle>';
    }
    return $o . '</svg>';
}

function chart_donut(array $data, array $colors, int $size = 170): string
{
    $total = array_sum($data) ?: 1;
    $r = 62; $c = 2 * M_PI * $r; $off = 0;
    $o = '<svg viewBox="0 0 170 170" width="' . $size . '" height="' . $size . '" role="img" aria-label="Distribution"><circle cx="85" cy="85" r="' . $r . '" fill="none" stroke="#e8f5ed" stroke-width="22"/>';
    $i = 0;
    foreach ($data as $label => $v) {
        $len = $v / $total * $c;
        $o .= '<circle cx="85" cy="85" r="' . $r . '" fill="none" stroke="' . $colors[$i % count($colors)] . '" stroke-width="22" stroke-dasharray="' . round(max(0, $len - 2), 2) . ' ' . round($c, 2) . '" stroke-dashoffset="' . round(-$off, 2) . '" transform="rotate(-90 85 85)"><title>' . e($label) . ': ' . $v . '</title></circle>';
        $off += $len; $i++;
    }
    return $o . '<text x="85" y="82" text-anchor="middle" font-size="28" font-weight="800" fill="#0b3321" font-family="Plus Jakarta Sans,sans-serif">' . array_sum($data) . '</text><text x="85" y="102" text-anchor="middle" font-size="12" fill="#5f7568">total</text></svg>';
}
