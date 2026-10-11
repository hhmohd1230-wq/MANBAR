<?php
/* Admin tools: user control panel (ban, warn, XP, badges, posts), scam reports and the scam case desk. */

const BAN_REASONS = ['Scam or fraud', 'Harassment or hate', 'Spam or fake account', 'Cheating / academic dishonesty', 'Repeated rule breaking', 'Other'];
const BAN_LENGTHS = ['1' => '24 hours', '3' => '3 days', '7' => '7 days', '30' => '30 days', '0' => 'Permanently'];
const SCAM_KINDS = ['Took my money and didn’t deliver', 'Asked me to pay outside MANBAR', 'Fake, copied or stolen work', 'Pretending to be someone else', 'Something else'];

function ensure_admin_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $pk = db_driver() === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $add = function (string $table, array $cols) {
        $have = table_columns($table);
        foreach ($cols as $c => $def) if (!in_array($c, $have, true)) qexec("ALTER TABLE $table ADD COLUMN $c $def");
    };
    $add('users', ['ban_reason' => 'VARCHAR(255) NULL', 'banned_until' => 'DATETIME NULL', 'banned_by' => 'INT NULL']);
    $add('reports', ['category' => "VARCHAR(12) NOT NULL DEFAULT 'content'", 'details' => 'TEXT NULL', 'order_id' => 'INT NULL', 'amount' => 'INT NULL',
        'admin_note' => 'VARCHAR(600) NULL', 'outcome' => 'VARCHAR(200) NULL', 'handled_by' => 'INT NULL', 'handled_at' => 'DATETIME NULL']);
    qexec("CREATE TABLE IF NOT EXISTS mod_actions (id $pk, admin_id INT NULL, user_id INT NOT NULL, action VARCHAR(30) NOT NULL, detail VARCHAR(500) NULL, created_at DATETIME NOT NULL)");
}

/** Log on the user's moderation timeline and in the global audit log. */
function mod_log(int $userId, string $action, string $detail = ''): void
{
    ensure_admin_schema();
    insert('mod_actions', ['admin_id' => uid() ?: null, 'user_id' => $userId, 'action' => $action, 'detail' => mb_substr($detail, 0, 500)]);
    $email = (string) qval('SELECT email FROM users WHERE id = ?', [$userId]);
    audit($action, $email . ($detail !== '' ? ' — ' . $detail : ''));
}

/** A suspended account whose ban has run out becomes active again. Returns true when the account is (now) active. */
function ban_lift_if_expired(array $u): bool
{
    if (($u['status'] ?? '') === 'active') return true;
    if (!empty($u['banned_until']) && strtotime($u['banned_until']) <= time()) {
        update('users', ['status' => 'active', 'ban_reason' => null, 'banned_until' => null, 'banned_by' => null], 'id = ?', [$u['id']]);
        return true;
    }
    return false;
}
function ban_message(array $u): string
{
    $until = !empty($u['banned_until']) ? ' until ' . date('j M Y, g:i a', strtotime($u['banned_until'])) : '';
    $why = !empty($u['ban_reason']) ? ' Reason: ' . rtrim($u['ban_reason'], '. ') . '.' : '';
    return 'This account is suspended' . $until . '.' . $why . ' Contact the MANBAR administrators if you think this is a mistake.';
}
function ban_label(array $u): string
{
    if ($u['status'] === 'active') return 'Active';
    return !empty($u['banned_until']) ? 'Banned until ' . date('j M', strtotime($u['banned_until'])) : 'Banned permanently';
}

/* ---------- user control panel ---------- */
function page_admin_user(int $id): void
{
    $u = require_admin();
    ensure_admin_schema();
    $get = fn() => qrow('SELECT u.*, un.short_name AS uni_short FROM users u JOIN universities un ON un.id = u.university_id WHERE u.id = ?', [$id]);
    $t = $get() ?? abort(404);
    if ($t['status'] !== 'active' && ban_lift_if_expired($t)) $t = $get();   // the ban ran out
    $bd = reputation_breakdown($id);
    $score = (int) $bd['score'];
    $rank = level_for($score);
    sync_badge_catalog();
    $badges = qall('SELECT b.*, ub.created_at AS earned_at FROM badges b LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = ? ORDER BY ub.created_at IS NULL, b.id', [$id]);
    $posts = qall('SELECT id, type, title, status, pinned, created_at, (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments FROM posts p WHERE user_id = ? ORDER BY id DESC LIMIT 30', [$id]);
    $reports = qall("SELECT r.*, a.full_name AS reporter FROM reports r JOIN users a ON a.id = r.reporter_id
        WHERE (r.target_type = 'user' AND r.target_id = ?) OR (r.target_type = 'post' AND r.target_id IN (SELECT id FROM posts WHERE user_id = ?))
           OR (r.target_type = 'service' AND r.target_id IN (SELECT id FROM services WHERE user_id = ?)) OR (r.target_type = 'comment' AND r.target_id IN (SELECT id FROM comments WHERE user_id = ?))
        ORDER BY r.id DESC LIMIT 20", [$id, $id, $id, $id]);
    $history = qall('SELECT m.*, a.full_name AS admin FROM mod_actions m LEFT JOIN users a ON a.id = m.admin_id WHERE m.user_id = ? ORDER BY m.id DESC LIMIT 25', [$id]);
    $counts = [
        'posts' => (int) qval('SELECT COUNT(*) FROM posts WHERE user_id = ?', [$id]),
        'comments' => (int) qval('SELECT COUNT(*) FROM comments WHERE user_id = ?', [$id]),
        'services' => (int) qval('SELECT COUNT(*) FROM services WHERE user_id = ?', [$id]),
        'orders' => (int) qval("SELECT COUNT(*) FROM service_requests r JOIN services s ON s.id = r.service_id WHERE s.user_id = ? AND r.status = 'completed'", [$id]),
        'badges' => count(array_filter($badges, fn($b) => $b['earned_at'])),
        'reports' => count($reports),
    ];
    $levels = [];
    foreach (LEVELS as $i => [$min, $name]) for ($s = 1; $s <= RANK_STARS_PER_LEAGUE; $s++) $levels[] = ['xp' => $min + ($s - 1) * RANK_STAR_XP, 'label' => $name . ' ' . str_repeat('★', $s)];
    render('admin/user', compact('u', 't', 'bd', 'score', 'rank', 'badges', 'posts', 'reports', 'history', 'counts', 'levels'), 'admin/layout');
}

function admin_user_tools(int $id): void
{
    $a = require_admin();
    ensure_admin_schema();
    $t = qrow('SELECT * FROM users WHERE id = ?', [$id]) ?? abort(404);
    $self = $id === (int) $a['id'];
    $back = 'admin/users/' . $id;
    $note = mb_substr(trim(input('note')), 0, 400);
    switch (input('tool')) {
        case 'ban':
            if ($self) { flash('You can’t ban your own account.', 'error'); redirect($back); }
            $reason = in_array(input('reason'), BAN_REASONS, true) ? input('reason') : 'Other';
            $days = (int) input('days');
            $until = $days > 0 ? date('Y-m-d H:i:s', time() + $days * 86400) : null;
            $why = $reason . ($note !== '' ? ': ' . $note : '');
            update('users', ['status' => 'suspended', 'ban_reason' => mb_substr($why, 0, 255), 'banned_until' => $until, 'banned_by' => $a['id']], 'id = ?', [$id]);
            mod_log($id, 'ban', ($until ? BAN_LENGTHS[(string) $days] : 'Permanently') . ' — ' . $why);
            flash($t['full_name'] . ' is banned ' . ($until ? 'until ' . date('j M Y', strtotime($until)) : 'permanently') . '. They are signed out on their next click.');
            break;
        case 'unban':
            update('users', ['status' => 'active', 'ban_reason' => null, 'banned_until' => null, 'banned_by' => null], 'id = ?', [$id]);
            mod_log($id, 'unban', $note);
            notify($id, 'system', 'Your MANBAR account is active again. Welcome back!', 'feed');
            flash('The ban was lifted. ' . $t['full_name'] . ' can sign in again.');
            break;
        case 'warn':
            if (mb_strlen($note) < 5) { flash('Write the warning message first.', 'error'); redirect($back); }
            notify($id, 'system', '⚠️ Warning from the MANBAR moderators: ' . $note, 'feed');
            mod_log($id, 'warn', $note);
            flash('Warning sent to ' . $t['full_name'] . '.');
            break;
        case 'xp':
            $before = (int) reputation_breakdown($id)['score'];
            $amount = (int) input('amount');
            $delta = input('mode') === 'set' ? $amount - $before : (input('mode') === 'remove' ? -abs($amount) : abs($amount));
            $delta = max(-$before, min(100000, $delta));   // rank XP never drops below 0
            if ($delta === 0) { flash('Nothing to change: the amount is 0.', 'error'); redirect($back); }
            $reason = $note !== '' ? $note : 'Adjusted by a moderator';
            insert('point_log', ['user_id' => $id, 'points' => $delta, 'reason' => mb_substr('Admin: ' . $reason, 0, 120)]);
            qexec('UPDATE users SET points = points + ? WHERE id = ?', [$delta, $id]);
            check_badges($id);   // also refreshes the rank score
            $after = (int) qval('SELECT reputation_score FROM users WHERE id = ?', [$id]);
            $lv = level_for($after);
            if (input('notify')) notify($id, $delta > 0 ? 'rank_up' : 'system', ($delta > 0 ? '🎉 A moderator added ' . $delta : 'A moderator removed ' . abs($delta)) . ' Rank XP: ' . $reason . '. You are now ' . $lv['name'] . ' ' . str_repeat('★', $lv['star']) . '.', 'achievements');
            mod_log($id, 'xp', ($delta > 0 ? '+' : '') . $delta . ' XP (' . $before . ' → ' . $after . ') — ' . $reason);
            flash('Rank XP ' . ($delta > 0 ? '+' : '') . $delta . ': ' . $t['full_name'] . ' is now ' . $lv['name'] . ' ' . str_repeat('★', $lv['star']) . ' with ' . number_format($after) . ' XP.');
            break;
        case 'badge':
            $b = qrow('SELECT * FROM badges WHERE code = ?', [input('code')]) ?? abort(404);
            if (qval('SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = ?', [$id, $b['id']])) {
                qexec('DELETE FROM user_badges WHERE user_id = ? AND badge_id = ?', [$id, $b['id']]);
                mod_log($id, 'badge_remove', $b['name']);
                flash('Removed the “' . $b['name'] . '” badge.');
            } else {
                insert('user_badges', ['user_id' => $id, 'badge_id' => $b['id']]);
                notify($id, 'badge', '🏅 The MANBAR team gave you the “' . $b['name'] . '” badge!', 'achievements');
                mod_log($id, 'badge_give', $b['name']);
                flash('Gave the “' . $b['name'] . '” badge to ' . $t['full_name'] . '.');
            }
            break;
        case 'post':
            $p = qrow('SELECT * FROM posts WHERE id = ? AND user_id = ?', [input_int('post'), $id]) ?? abort(404);
            $do = input('do');
            if ($do === 'delete') {
                qexec('DELETE FROM comments WHERE post_id = ?', [$p['id']]);
                qexec("DELETE FROM reactions WHERE target_type = 'post' AND target_id = ?", [$p['id']]);
                qexec('DELETE FROM posts WHERE id = ?', [$p['id']]);
            } else update('posts', ['status' => $do === 'hide' ? 'hidden' : 'visible'], 'id = ?', [$p['id']]);
            if ($do !== 'restore' && $note !== '') notify($id, 'system', 'Your post “' . excerpt($p['title'], 60) . '” was ' . ($do === 'delete' ? 'removed' : 'hidden') . ' by the moderators: ' . $note, 'feed');
            mod_log($id, 'post_' . $do, '“' . excerpt($p['title'], 70) . '”' . ($note !== '' ? ' — ' . $note : ''));
            flash(['delete' => 'Post deleted.', 'hide' => 'Post hidden from the feed.', 'restore' => 'Post restored.'][$do] ?? 'Done.');
            break;
        default: abort(422);
    }
    redirect($back);
}

/* ---------- scam reports from users ---------- */
function report_scam(): void
{
    $u = require_login();
    ensure_admin_schema();
    $target = qrow('SELECT id, full_name FROM users WHERE id = ?', [input_int('user')]) ?? abort(404);
    $back = preg_match('#^[a-z]+(/[a-z0-9_-]+)*$#', input('back')) ? input('back') : 'profile/' . $target['id'];   // only our own pages
    if ((int) $target['id'] === (int) $u['id']) { flash('You can’t report yourself.', 'error'); redirect($back); }
    $kind = in_array(input('kind'), SCAM_KINDS, true) ? input('kind') : 'Something else';
    $details = mb_substr(trim(input('details')), 0, 3000);
    if (mb_strlen($details) < 15) { flash('Please describe what happened (at least a sentence) so the moderators can help.', 'error'); redirect($back); }
    if (qval("SELECT COUNT(*) FROM reports WHERE reporter_id = ? AND target_type = 'user' AND target_id = ? AND category = 'scam' AND status = 'open'", [$u['id'], $target['id']])) {
        flash('You already have an open scam report about ' . $target['full_name'] . '. The moderators are on it.', 'error'); redirect($back);
    }
    $order = input_int('order') ?: null;
    if ($order && !qval('SELECT COUNT(*) FROM service_requests r JOIN services s ON s.id = r.service_id WHERE r.id = ? AND ((r.buyer_id = ? AND s.user_id = ?) OR (r.buyer_id = ? AND s.user_id = ?))', [$order, $u['id'], $target['id'], $target['id'], $u['id']])) $order = null;
    $rid = insert('reports', ['reporter_id' => $u['id'], 'target_type' => 'user', 'target_id' => $target['id'], 'reason' => $kind, 'status' => 'open', 'category' => 'scam',
        'details' => $details, 'order_id' => $order, 'amount' => max(0, input_int('amount')) ?: null]);
    foreach (qall("SELECT id FROM users WHERE role = 'admin' AND status = 'active'") as $adm) notify((int) $adm['id'], 'report', '🚨 New scam report: ' . $u['full_name'] . ' reported ' . $target['full_name'] . ' (' . $kind . ').', 'admin/cases/' . $rid);
    flash('Thank you. Your scam report was sent to the MANBAR moderators. If money is on hold for this order, it stays frozen while they review it.');
    redirect($back);
}

/** Orders between two people, for the scam report form. */
function scam_orders(int $me, int $other): array
{
    return qall('SELECT r.id, s.title, r.status, COALESCE(r.agreed_price, s.price) AS price FROM service_requests r JOIN services s ON s.id = r.service_id
        WHERE (r.buyer_id = ? AND s.user_id = ?) OR (r.buyer_id = ? AND s.user_id = ?) ORDER BY r.id DESC LIMIT 10', [$me, $other, $other, $me]);
}

/* ---------- scam case desk ---------- */
function page_admin_case(int $id): void
{
    $u = require_admin();
    ensure_admin_schema();
    if (function_exists('ensure_pay_schema')) ensure_pay_schema();
    $r = qrow('SELECT r.*, a.full_name AS reporter_name, a.email AS reporter_email, a.avatar_url AS reporter_avatar, a.id AS reporter_uid, h.full_name AS handler FROM reports r JOIN users a ON a.id = r.reporter_id LEFT JOIN users h ON h.id = r.handled_by WHERE r.id = ?', [$id]) ?? abort(404);
    if ($r['target_type'] !== 'user') redirect('admin/moderation');
    $acc = qrow('SELECT * FROM users WHERE id = ?', [$r['target_id']]) ?? abort(404);
    $bd = reputation_breakdown((int) $acc['id']);
    $profile = [
        'completed' => (int) qval("SELECT COUNT(*) FROM service_requests r JOIN services s ON s.id = r.service_id WHERE s.user_id = ? AND r.status = 'completed'", [$acc['id']]),
        'rating' => qval('SELECT ROUND(AVG(rv.rating), 1) FROM service_reviews rv JOIN services s ON s.id = rv.service_id WHERE s.user_id = ?', [$acc['id']]),
        'reviews' => (int) qval('SELECT COUNT(*) FROM service_reviews rv JOIN services s ON s.id = rv.service_id WHERE s.user_id = ?', [$acc['id']]),
        'scam_reports' => (int) qval("SELECT COUNT(*) FROM reports WHERE category = 'scam' AND target_type = 'user' AND target_id = ?", [$acc['id']]),
        'other_reports' => (int) qval("SELECT COUNT(DISTINCT reporter_id) FROM reports WHERE category = 'scam' AND target_type = 'user' AND target_id = ? AND id <> ?", [$acc['id'], $id]),
        'messages' => (int) qval('SELECT COUNT(*) FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)', [$r['reporter_id'], $acc['id'], $acc['id'], $r['reporter_id']]),
        'age_days' => max(0, (int) floor((time() - strtotime($acc['created_at'])) / 86400)),
        'trust' => $bd['trust_percent'],
    ];
    $order = $r['order_id'] ? qrow('SELECT r.*, s.title, s.id AS sid, s.user_id AS seller_id FROM service_requests r JOIN services s ON s.id = r.service_id WHERE r.id = ?', [$r['order_id']]) : null;
    $payments = $order ? qall('SELECT * FROM market_payments WHERE request_id = ? ORDER BY id', [$order['id']]) : [];
    $services = qall('SELECT id, title, status, price FROM services WHERE user_id = ? ORDER BY id DESC', [$acc['id']]);
    render('admin/case', compact('u', 'r', 'acc', 'profile', 'order', 'payments', 'services'), 'admin/layout');
}

function admin_case_action(int $id): void
{
    $a = require_admin();
    ensure_admin_schema();
    $r = qrow('SELECT * FROM reports WHERE id = ?', [$id]) ?? abort(404);
    $acc = qrow('SELECT * FROM users WHERE id = ?', [$r['target_id']]) ?? abort(404);
    $outcome = input('outcome');
    $note = mb_substr(trim(input('note')), 0, 600);
    $done = [];
    if ($outcome === 'dismiss') {
        $done[] = 'Closed as not a scam';
    } else {
        if (in_array($outcome, ['warn', 'hide', 'ban'], true)) {
            notify((int) $acc['id'], 'system', '⚠️ Warning from the MANBAR moderators about a scam report' . ($note !== '' ? ': ' . $note : '. Please follow the marketplace rules.'), 'marketplace');
            mod_log((int) $acc['id'], 'warn', 'Scam case #' . $id . ($note !== '' ? ' — ' . $note : ''));
            $done[] = 'Warned the seller';
        }
        if (in_array($outcome, ['hide', 'ban'], true)) {
            $n = qexec("UPDATE services SET status = 'hidden' WHERE user_id = ? AND status <> 'hidden'", [$acc['id']]);
            mod_log((int) $acc['id'], 'hide_listings', 'Scam case #' . $id);
            $done[] = 'Hid their marketplace listings';
        }
        if ($outcome === 'ban' && (int) $acc['id'] !== (int) $a['id']) {
            $days = (int) input('days');
            $until = $days > 0 ? date('Y-m-d H:i:s', time() + $days * 86400) : null;
            update('users', ['status' => 'suspended', 'ban_reason' => 'Scam or fraud' . ($note !== '' ? ': ' . mb_substr($note, 0, 200) : ''), 'banned_until' => $until, 'banned_by' => $a['id']], 'id = ?', [$acc['id']]);
            mod_log((int) $acc['id'], 'ban', ($until ? BAN_LENGTHS[(string) $days] ?? $days . ' days' : 'Permanently') . ' — Scam case #' . $id);
            $done[] = 'Banned the seller ' . ($until ? 'until ' . date('j M', strtotime($until)) : 'permanently');
        }
    }
    if (input('refund') && $r['order_id']) {
        $held = qall("SELECT * FROM market_payments WHERE request_id = ? AND status = 'held'", [$r['order_id']]);
        foreach ($held as $p) update('market_payments', ['status' => 'refunded', 'released_at' => now()], 'id = ?', [$p['id']]);
        if ($held) {
            update('service_requests', ['status' => 'cancelled'], 'id = ?', [$r['order_id']]);
            $sum = array_sum(array_column($held, 'amount'));
            notify((int) $r['reporter_id'], 'service_completed', '💸 AED ' . $sum . ' from your order was refunded to you after the scam review.', 'marketplace/orders');
            mod_log((int) $acc['id'], 'refund', 'AED ' . $sum . ' refunded to the buyer (case #' . $id . ')');
            $done[] = 'Refunded AED ' . $sum . ' to the buyer';
        }
    }
    update('reports', ['status' => $outcome === 'dismiss' ? 'dismissed' : 'resolved', 'outcome' => mb_substr(implode(' · ', $done), 0, 200), 'admin_note' => $note ?: null, 'handled_by' => $a['id'], 'handled_at' => now()], 'id = ?', [$id]);
    notify((int) $r['reporter_id'], 'system', 'Update on your scam report about ' . $acc['full_name'] . ': ' . ($outcome === 'dismiss' ? 'the moderators reviewed it and found no scam.' : 'the moderators took action.') . ($note !== '' ? ' Note: ' . $note : ''), 'notifications');
    audit('scam_case', '#' . $id . ' — ' . implode(' · ', $done));
    flash('Case #' . $id . ' closed: ' . implode(' · ', $done ?: ['no action']) . '.');
    redirect('admin/moderation?tab=scam');
}
