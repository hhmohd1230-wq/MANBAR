<?php
/* Settings (account, notifications, billing, appearance) and the notification pop-up in the top bar. */

const NOTIF_GROUPS = [
    'social' => ['Reactions, comments & follows', 'When people react to your posts, reply to you or follow you', 'heart', 'rose', 'Social'],
    'messages' => ['Messages', 'New direct messages and order chats', 'mail', 'blue', 'Messages'],
    'projects' => ['Projects', 'Applications, team updates and decisions on your projects', 'rocket', 'violet', 'Projects'],
    'market' => ['Marketplace & orders', 'Requests, offers, payments, deliveries and reviews', 'store', 'green', 'Marketplace'],
    'learning' => ['Learning center', 'Enrollments, quizzes and finished courses', 'book', 'teal', 'Learning'],
    'mentors' => ['Mentorship', 'Mentor requests, sessions and feedback', 'compass', 'orange', 'Mentorship'],
    'achievements' => ['Badges & rank', 'New badges and rank progress', 'award', 'amber', 'Badges'],
];
const BILLING_BRANDS = ['visa' => 'Visa', 'mastercard' => 'Mastercard', 'amex' => 'American Express'];

/** Which group a notification type belongs to, plus its icon and colour. */
function notif_meta(string $type): array
{
    $icons = ['welcome' => 'sparkles', 'badge' => 'award', 'reaction' => 'heart', 'comment' => 'chat', 'reply' => 'chat', 'follow' => 'user-plus', 'post' => 'bulb', 'share' => 'share',
        'application' => 'user-plus', 'accepted' => 'check-circle', 'rejected' => 'x', 'left' => 'logout', 'project_msg' => 'message', 'message' => 'mail', 'enroll' => 'book', 'completed' => 'trophy',
        'quiz' => 'check-circle', 'review' => 'star', 'service_request' => 'store', 'service_offer' => 'tag', 'service_accepted' => 'wallet', 'service_declined' => 'x', 'service_delivered' => 'send',
        'service_completed' => 'check-circle', 'mentor_request' => 'compass', 'mentor_accept' => 'check-circle', 'mentor_decline' => 'x', 'mentor_schedule' => 'calendar', 'mentor_feedback' => 'star'];
    $group = match (true) {
        in_array($type, ['reaction', 'comment', 'reply', 'follow', 'post', 'share'], true) => 'social',
        $type === 'message' => 'messages',
        in_array($type, ['application', 'accepted', 'rejected', 'left', 'project_msg'], true) => 'projects',
        str_starts_with($type, 'service') || $type === 'review' => 'market',
        in_array($type, ['enroll', 'completed', 'quiz'], true) || str_starts_with($type, 'course') || str_starts_with($type, 'live') => 'learning',
        str_starts_with($type, 'mentor') => 'mentors',
        $type === 'badge' || str_starts_with($type, 'rank') => 'achievements',
        default => 'system',
    };
    $tone = NOTIF_GROUPS[$group][3] ?? 'green';
    return ['group' => $group, 'icon' => $icons[$type] ?? (NOTIF_GROUPS[$group][2] ?? 'bell'), 'tone' => $tone, 'label' => NOTIF_GROUPS[$group][4] ?? 'MANBAR'];
}

function ensure_settings_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $pk = db_driver() === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    qexec('CREATE TABLE IF NOT EXISTS user_prefs (user_id INT NOT NULL, pkey VARCHAR(40) NOT NULL, pval VARCHAR(255) NOT NULL, PRIMARY KEY (user_id, pkey))');
    // only what is safe to keep: brand, last 4 digits, expiry and a masked account hint. Never full numbers or CVCs.
    qexec("CREATE TABLE IF NOT EXISTS billing_methods (id $pk, user_id INT NOT NULL, kind VARCHAR(12) NOT NULL, brand VARCHAR(16) NULL, last4 VARCHAR(4) NULL,
        exp_month INT NULL, exp_year INT NULL, holder VARCHAR(60) NULL, hint VARCHAR(60) NULL, is_default INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL)");
}

function user_prefs(int $uid): array
{
    static $cache = [];
    if (!isset($cache[$uid])) { ensure_settings_schema(); $cache[$uid] = array_column(qall('SELECT pkey, pval FROM user_prefs WHERE user_id = ?', [$uid]), 'pval', 'pkey'); }
    return $cache[$uid];
}
function set_user_pref(int $uid, string $key, string $val): void
{
    ensure_settings_schema();
    qexec('DELETE FROM user_prefs WHERE user_id = ? AND pkey = ?', [$uid, $key]);
    qexec('INSERT INTO user_prefs (user_id, pkey, pval) VALUES (?, ?, ?)', [$uid, $key, $val]);
}

/** False when the person turned this kind of notification off in Settings. */
function notif_allowed(int $uid, string $type): bool
{
    $g = notif_meta($type)['group'];
    return $g === 'system' || (user_prefs($uid)['notif_' . $g] ?? '1') !== '0';
}

function billing_methods(int $uid): array
{
    ensure_settings_schema();
    return qall('SELECT * FROM billing_methods WHERE user_id = ? ORDER BY is_default DESC, id DESC', [$uid]);
}
function billing_label(array $m): string
{
    return match ($m['kind']) {
        'card' => (BILLING_BRANDS[$m['brand']] ?? 'Card') . ' •••• ' . $m['last4'],
        'paypal' => 'PayPal · ' . $m['hint'],
        'tabby' => 'tabby · ' . $m['hint'],
        default => ucfirst($m['kind']),
    };
}

/* ---------- notification pop-up ---------- */
function api_notif_peek(): void
{
    $u = require_login();
    $items = qall('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 12', [$u['id']]);
    $unread = (int) qval('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$u['id']]);
    ob_start();
    partial('notif_items', ['items' => $items, 'compact' => true]);
    json_out(['ok' => true, 'html' => ob_get_clean(), 'unread' => $unread]);
}

function notif_read_all(): void
{
    $u = require_login();
    qexec('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', [$u['id']]);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) json_out(['ok' => true, 'unread' => 0]);
    flash('All notifications marked as read.');
    redirect('notifications');
}

function notif_open(string $id): void
{
    $u = require_login();
    $n = qrow('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [(int) $id, $u['id']]);
    if (!$n) redirect('notifications');
    qexec('UPDATE notifications SET is_read = 1 WHERE id = ?', [$n['id']]);
    redirect($n['link'] ?: 'notifications');
}

/* ---------- settings page ---------- */
function page_settings(): void
{
    $u = require_login();
    ensure_settings_schema();
    $tab = in_array($_GET['tab'] ?? '', ['account', 'notifications', 'billing', 'appearance'], true) ? $_GET['tab'] : 'account';
    $prefs = user_prefs((int) $u['id']);
    $methods = billing_methods((int) $u['id']);
    $wallet = function_exists('wallet') ? wallet((int) $u['id']) : null;
    $history = [];
    if ($tab === 'billing') {
        if (function_exists('ensure_pay_schema')) ensure_pay_schema();
        if (function_exists('ensure_learning_schema')) ensure_learning_schema();
        $history = qall("SELECT p.amount, p.method, p.status, p.ref, p.created_at, s.title, 'order' AS kind FROM market_payments p JOIN service_requests r ON r.id = p.request_id JOIN services s ON s.id = r.service_id WHERE p.payer_id = ?
            UNION ALL SELECT c.amount, REPLACE(c.provider, 'demo-', '') AS method, c.status, c.provider_ref AS ref, c.created_at, co.title, 'course' AS kind FROM course_payments c JOIN courses co ON co.id = c.course_id WHERE c.user_id = ?
            ORDER BY created_at DESC LIMIT 12", [$u['id'], $u['id']]);
    }
    render('settings', compact('u', 'tab', 'prefs', 'methods', 'wallet', 'history'));
}

function settings_notifications_save(): void
{
    $u = require_login();
    foreach (array_keys(NOTIF_GROUPS) as $g) set_user_pref((int) $u['id'], 'notif_' . $g, isset($_POST['notif'][$g]) ? '1' : '0');
    flash('Notification settings saved.');
    redirect('settings?tab=notifications');
}

function billing_add(): void
{
    $u = require_login();
    ensure_settings_schema();
    $kind = input('kind');
    $row = ['user_id' => $u['id'], 'kind' => $kind];
    if ($kind === 'card') {
        $brand = input('brand'); $last4 = input('last4'); $mm = (int) input('exp_month'); $yy = (int) input('exp_year');
        $holder = mb_substr(input('holder'), 0, 60);
        if (!isset(BILLING_BRANDS[$brand]) || !preg_match('/^\d{4}$/', $last4) || $mm < 1 || $mm > 12 || $yy < (int) date('Y') || ($yy === (int) date('Y') && $mm < (int) date('n')) || $holder === '') {
            flash('Please check the card details and try again.', 'error'); redirect('settings?tab=billing');
        }
        $row += ['brand' => $brand, 'last4' => $last4, 'exp_month' => $mm, 'exp_year' => $yy, 'holder' => $holder];
    } elseif ($kind === 'paypal') {
        $email = mb_strtolower(input('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('Enter the email of your PayPal account.', 'error'); redirect('settings?tab=billing'); }
        [$name, $dom] = explode('@', $email, 2);
        $row['hint'] = mb_substr($name, 0, 1) . str_repeat('•', 3) . '@' . $dom;   // keep only a masked hint
    } elseif ($kind === 'tabby') {
        $phone = preg_replace('/\D/', '', input('phone'));
        if (!preg_match('/^(971|0)?5\d{8}$/', $phone)) { flash('Enter a UAE mobile number for tabby, like 05X XXX XXXX.', 'error'); redirect('settings?tab=billing'); }
        $row['hint'] = '+971 5• ••• ••' . substr($phone, -2);
    } else abort(400);
    $row['is_default'] = qval('SELECT COUNT(*) FROM billing_methods WHERE user_id = ?', [$u['id']]) ? (isset($_POST['make_default']) ? 1 : 0) : 1;
    if ($row['is_default']) qexec('UPDATE billing_methods SET is_default = 0 WHERE user_id = ?', [$u['id']]);
    insert('billing_methods', $row);
    flash(billing_label($row + ['brand' => $row['brand'] ?? '', 'last4' => $row['last4'] ?? '', 'hint' => $row['hint'] ?? '']) . ' was added to your billing.');
    redirect('settings?tab=billing');
}

function billing_action(string $id): void
{
    $u = require_login();
    ensure_settings_schema();
    $m = qrow('SELECT * FROM billing_methods WHERE id = ? AND user_id = ?', [(int) $id, $u['id']]);
    if (!$m) abort(404);
    if (input('do') === 'default') {
        qexec('UPDATE billing_methods SET is_default = CASE WHEN id = ? THEN 1 ELSE 0 END WHERE user_id = ?', [$m['id'], $u['id']]);
        flash(billing_label($m) . ' is now your default payment method.');
    } elseif (input('do') === 'remove') {
        qexec('DELETE FROM billing_methods WHERE id = ?', [$m['id']]);
        if ($m['is_default']) qexec('UPDATE billing_methods SET is_default = 1 WHERE id = (SELECT id FROM (SELECT id FROM billing_methods WHERE user_id = ? ORDER BY id DESC LIMIT 1) t)', [$u['id']]);
        flash(billing_label($m) . ' was removed.');
    }
    redirect('settings?tab=billing');
}
