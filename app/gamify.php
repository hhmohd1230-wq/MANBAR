<?php
/** Points, levels, badges, notifications. */

const LEVELS = [
    [0, 'Newcomer'], [50, 'Explorer'], [150, 'Contributor'], [300, 'Innovator'], [600, 'Pioneer'], [1000, 'Legend'],
];

function level_for(int $points): array
{
    $idx = 0;
    foreach (LEVELS as $i => [$min]) if ($points >= $min) $idx = $i;
    $next = LEVELS[$idx + 1] ?? null;
    $cur = LEVELS[$idx];
    $pct = $next ? (int) round(($points - $cur[0]) / ($next[0] - $cur[0]) * 100) : 100;
    return ['n' => $idx + 1, 'name' => $cur[1], 'next' => $next[1] ?? null, 'next_at' => $next[0] ?? null, 'pct' => $pct];
}

function award_points(int $uid, int $pts, string $reason): void
{
    if ($pts === 0) return;
    insert('point_log', ['user_id' => $uid, 'points' => $pts, 'reason' => $reason]);
    qexec('UPDATE users SET points = points + ? WHERE id = ?', [$pts, $uid]);
    check_badges($uid);
}

function notify(int $uid, string $type, string $text, ?string $link = null): void
{
    if ($uid === (int) ($_SESSION['uid'] ?? 0) && $type !== 'welcome') return;   // don't notify yourself
    insert('notifications', ['user_id' => $uid, 'type' => $type, 'text' => mb_substr($text, 0, 250), 'link' => $link]);
}

function give_badge(int $uid, string $code): void
{
    $b = qrow('SELECT id, name FROM badges WHERE code = ?', [$code]);
    if (!$b || qval('SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = ?', [$uid, $b['id']])) return;
    insert('user_badges', ['user_id' => $uid, 'badge_id' => $b['id']]);
    notify($uid, 'badge', 'You earned the “' . $b['name'] . '” badge!', 'achievements');
    $_SESSION['toast_badge'] = $b['name'];
}

function check_badges(int $uid): void
{
    $c = fn(string $sql, array $p = []) => (int) qval($sql, $p);
    $u = qrow('SELECT * FROM users WHERE id = ?', [$uid]);
    if (!$u) return;
    if ($u['profile_complete']) give_badge($uid, 'profile_pro');
    if ($c('SELECT COUNT(*) FROM posts WHERE user_id = ?', [$uid]) >= 1) give_badge($uid, 'first_post');
    if ($c("SELECT COUNT(*) FROM posts WHERE user_id = ? AND type = 'idea'", [$uid]) >= 5) give_badge($uid, 'idea_machine');
    if ($c('SELECT COUNT(*) FROM comments WHERE user_id = ?', [$uid]) >= 10) give_badge($uid, 'conversationalist');
    if ($c('SELECT COUNT(*) FROM project_members WHERE user_id = ? ', [$uid]) >= 1) give_badge($uid, 'team_player');
    if ($c('SELECT COUNT(*) FROM projects WHERE owner_id = ?', [$uid]) >= 1) give_badge($uid, 'project_leader');
    if ($c('SELECT COUNT(*) FROM services WHERE user_id = ?', [$uid]) >= 1) give_badge($uid, 'service_pro');
    if ($c('SELECT COUNT(*) FROM service_reviews r JOIN services s ON s.id = r.service_id WHERE s.user_id = ? AND r.rating = 5', [$uid]) >= 1) give_badge($uid, 'five_star');
    if ($c('SELECT COUNT(*) FROM enrollments WHERE user_id = ?', [$uid]) >= 1) give_badge($uid, 'learner');
    if ($c('SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND completed_at IS NOT NULL', [$uid]) >= 1) give_badge($uid, 'graduate');
    if ($c('SELECT COUNT(*) FROM mentorship_requests WHERE student_id = ? AND status IN (\'accepted\',\'completed\')', [$uid]) >= 1) give_badge($uid, 'mentee');
    if ($c('SELECT COUNT(*) FROM follows WHERE followed_id = ?', [$uid]) >= 5) give_badge($uid, 'popular');
    if ($u['points'] >= 100) give_badge($uid, 'centurion');
}
