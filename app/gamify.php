<?php
/** Points, levels, badges, notifications. */

const LEVELS = [
    [0, 'Bronze'], [100, 'Silver'], [250, 'Gold'], [500, 'Platinum'],
    [900, 'Diamond'], [1500, 'Legend'], [2400, 'Master'], [4000, 'Celestial'],
];

const BADGE_CATALOG = [
    ['profile_pro', 'Profile Pro', 'Completed your profile', 'user', 'green'],
    ['first_post', 'First Voice', 'Published your first post', 'chat', 'blue'],
    ['idea_machine', 'Idea Machine', 'Shared 5 ideas', 'bulb', 'amber'],
    ['conversationalist', 'Conversationalist', 'Wrote 10 comments', 'chat', 'violet'],
    ['team_player', 'Team Player', 'Joined a project team', 'users', 'teal'],
    ['project_leader', 'Project Leader', 'Started a project', 'flag', 'rose'],
    ['service_pro', 'Service Pro', 'Listed a service in the marketplace', 'briefcase', 'orange'],
    ['five_star', 'Five Star', 'Received a 5-star review', 'star', 'amber'],
    ['learner', 'Curious Mind', 'Enrolled in a course', 'book', 'blue'],
    ['graduate', 'Graduate', 'Completed a course', 'cap', 'green'],
    ['mentee', 'Mentee', 'Got accepted by a mentor', 'heart', 'rose'],
    ['popular', 'Rising Star', 'Gained 5 followers', 'fire', 'orange'],
    ['centurion', 'Centurion', 'Reached 100 points', 'trophy', 'amber'],
    ['project_finisher', 'Mission Complete', 'Completed a project with your team', 'check-circle', 'green'],
    ['community_helper', 'Community Hero', 'Wrote 25 helpful comments', 'heart', 'rose'],
    ['knowledge_seeker', 'Knowledge Seeker', 'Completed 3 courses', 'book', 'blue'],
    ['mentor_milestone', 'Guided Growth', 'Completed a mentoring session', 'compass', 'teal'],
    ['campus_connector', 'Campus Connector', 'Gained 10 followers', 'users', 'violet'],
    ['innovator_rank', 'Gold League', 'Reached the Gold rank', 'rocket', 'amber'],
    ['pioneer_rank', 'Platinum League', 'Reached the Platinum rank', 'target', 'teal'],
    ['champion_rank', 'Diamond League', 'Reached the Diamond rank', 'award', 'blue'],
    ['legend_rank', 'Living Legend', 'Reached the Legend rank', 'fire', 'rose'],
    ['master_rank', 'MANBAR Master', 'Reached the Master rank', 'trophy', 'violet'],
    ['celestial_rank', 'Celestial', 'Reached MANBAR’s highest rank', 'sparkles', 'amber'],
];

function level_for(int $points): array
{
    $idx = 0;
    foreach (LEVELS as $i => [$min]) if ($points >= $min) $idx = $i;
    $next = LEVELS[$idx + 1] ?? null;
    $cur = LEVELS[$idx];
    $pct = $next ? (int) round(($points - $cur[0]) / ($next[0] - $cur[0]) * 100) : 100;
    return ['n' => $idx + 1, 'name' => $cur[1], 'current_at' => $cur[0], 'next' => $next[1] ?? null, 'next_at' => $next[0] ?? null, 'pct' => max(0, min(100, $pct))];
}

function rank_key(int $level): string
{
    return ['bronze', 'silver', 'gold', 'platinum', 'diamond', 'legend', 'master', 'celestial'][max(0, min(7, $level - 1))];
}

function ensure_reputation_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $columns = db_driver() === 'sqlite'
        ? array_column(qall('PRAGMA table_info(users)'), 'name')
        : array_column(qall('SHOW COLUMNS FROM users'), 'Field');
    if (!in_array('reputation_score', $columns, true)) qexec('ALTER TABLE users ADD COLUMN reputation_score INT NOT NULL DEFAULT 0');
    $pk = db_driver() === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $engine = db_driver() === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    qexec("CREATE TABLE IF NOT EXISTS recommendations (
        id $pk,
        author_id INT NOT NULL,
        subject_id INT NOT NULL,
        context_type VARCHAR(24) NOT NULL,
        context_id INT NULL,
        rating INT NOT NULL DEFAULT 5,
        body VARCHAR(800) NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        UNIQUE (author_id, subject_id),
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES users(id) ON DELETE CASCADE
    )$engine");
}

function reputation_breakdown(int $uid): array
{
    ensure_reputation_schema();
    $activity = (int) qval('SELECT points FROM users WHERE id = ?', [$uid]);
    $postReactions = (int) qval("SELECT COUNT(*) FROM reactions r JOIN posts p ON p.id = r.target_id WHERE r.target_type = 'post' AND p.user_id = ? AND r.user_id <> ?", [$uid, $uid]);
    $commentReactions = (int) qval("SELECT COUNT(*) FROM reactions r JOIN comments c ON c.id = r.target_id WHERE r.target_type = 'comment' AND c.user_id = ? AND r.user_id <> ?", [$uid, $uid]);
    $review = qrow('SELECT COUNT(*) AS total, COALESCE(AVG(r.rating),0) AS average, COALESCE(SUM(CASE WHEN r.rating = 5 THEN 45 WHEN r.rating = 4 THEN 30 WHEN r.rating = 3 THEN 10 ELSE 0 END),0) AS xp FROM service_reviews r JOIN services s ON s.id = r.service_id WHERE s.user_id = ?', [$uid]);
    $recommendation = qrow('SELECT COUNT(*) AS total, COALESCE(AVG(rating),0) AS average, COALESCE(SUM(rating * 12),0) AS xp FROM recommendations WHERE subject_id = ?', [$uid]);
    $serviceCompletions = (int) qval("SELECT COUNT(*) FROM service_requests r JOIN services s ON s.id = r.service_id WHERE s.user_id = ? AND r.status = 'completed'", [$uid]);
    $projectCompletions = (int) qval("SELECT COUNT(DISTINCT p.id) FROM projects p LEFT JOIN project_members pm ON pm.project_id = p.id WHERE p.status = 'completed' AND (p.owner_id = ? OR pm.user_id = ?)", [$uid, $uid]);
    $mentorCompletions = (int) qval("SELECT COUNT(*) FROM mentorship_requests WHERE status = 'completed' AND (student_id = ? OR mentor_id = ?)", [$uid, $uid]);
    $qualitySignals = (int) ($review['total'] ?? 0) + (int) ($recommendation['total'] ?? 0);
    $qualityAverage = $qualitySignals
        ? (((float) ($review['average'] ?? 0) * (int) ($review['total'] ?? 0)) + ((float) ($recommendation['average'] ?? 0) * (int) ($recommendation['total'] ?? 0))) / $qualitySignals
        : 0.0;
    $parts = [
        'activity' => $activity,
        'appreciation' => ($postReactions * 2) + $commentReactions,
        'reviews' => (int) ($review['xp'] ?? 0),
        'recommendations' => (int) ($recommendation['xp'] ?? 0),
        'completed_work' => ($serviceCompletions * 20) + ($projectCompletions * 30) + ($mentorCompletions * 20),
    ];
    return $parts + [
        'score' => array_sum($parts), 'post_reactions' => $postReactions, 'comment_reactions' => $commentReactions,
        'review_count' => (int) ($review['total'] ?? 0), 'recommendation_count' => (int) ($recommendation['total'] ?? 0),
        'quality_average' => round($qualityAverage, 1), 'trust_percent' => $qualitySignals ? (int) round($qualityAverage / 5 * 100) : null,
        'service_completions' => $serviceCompletions, 'project_completions' => $projectCompletions, 'mentor_completions' => $mentorCompletions,
    ];
}

function refresh_reputation_score(int $uid): int
{
    $score = (int) reputation_breakdown($uid)['score'];
    update('users', ['reputation_score' => $score], 'id = ?', [$uid]);
    return $score;
}

function recommendation_eligibility(int $authorId, int $subjectId): ?array
{
    if ($authorId === $subjectId) return null;
    $service = qrow("SELECT r.id FROM service_requests r JOIN services s ON s.id = r.service_id WHERE r.status = 'completed' AND ((r.buyer_id = ? AND s.user_id = ?) OR (r.buyer_id = ? AND s.user_id = ?)) ORDER BY r.id DESC LIMIT 1", [$authorId, $subjectId, $subjectId, $authorId]);
    if ($service) return ['type' => 'service', 'id' => (int) $service['id'], 'label' => 'Completed marketplace work'];
    $project = qrow("SELECT p.id FROM projects p WHERE p.status = 'completed' AND (p.owner_id = ? OR EXISTS (SELECT 1 FROM project_members x WHERE x.project_id = p.id AND x.user_id = ?)) AND (p.owner_id = ? OR EXISTS (SELECT 1 FROM project_members y WHERE y.project_id = p.id AND y.user_id = ?)) ORDER BY p.id DESC LIMIT 1", [$authorId, $authorId, $subjectId, $subjectId]);
    if ($project) return ['type' => 'project', 'id' => (int) $project['id'], 'label' => 'Completed project collaboration'];
    $mentor = qrow("SELECT id FROM mentorship_requests WHERE status = 'completed' AND ((student_id = ? AND mentor_id = ?) OR (student_id = ? AND mentor_id = ?)) ORDER BY id DESC LIMIT 1", [$authorId, $subjectId, $subjectId, $authorId]);
    if ($mentor) return ['type' => 'mentorship', 'id' => (int) $mentor['id'], 'label' => 'Completed mentoring session'];
    return null;
}

function sync_badge_catalog(): void
{
    foreach (BADGE_CATALOG as [$code, $name, $description, $icon, $tone]) {
        if (qval('SELECT COUNT(*) FROM badges WHERE code = ?', [$code])) {
            update('badges', compact('name', 'description', 'icon', 'tone'), 'code = ?', [$code]);
        } else {
            insert('badges', compact('code', 'name', 'description', 'icon', 'tone'));
        }
    }
}

function achievement_stats(int $uid): array
{
    $u = qrow('SELECT points, profile_complete FROM users WHERE id = ?', [$uid]);
    $posts = qrow("SELECT COUNT(*) AS total, SUM(CASE WHEN type = 'idea' THEN 1 ELSE 0 END) AS ideas FROM posts WHERE user_id = ?", [$uid]);
    $learning = qrow('SELECT COUNT(*) AS enrolled, SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed FROM enrollments WHERE user_id = ?', [$uid]);
    $mentoring = qrow("SELECT COUNT(*) AS accepted, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed FROM mentorship_requests WHERE student_id = ? AND status IN ('accepted','completed')", [$uid]);
    return [
        'profile' => !empty($u['profile_complete']) ? 1 : 0,
        'posts' => (int) ($posts['total'] ?? 0),
        'ideas' => (int) ($posts['ideas'] ?? 0),
        'comments' => (int) qval('SELECT COUNT(*) FROM comments WHERE user_id = ?', [$uid]),
        'teams' => (int) qval('SELECT COUNT(*) FROM project_members WHERE user_id = ?', [$uid]),
        'projects' => (int) qval('SELECT COUNT(*) FROM projects WHERE owner_id = ?', [$uid]),
        'completed_projects' => (int) qval("SELECT COUNT(DISTINCT pm.project_id) FROM project_members pm JOIN projects p ON p.id = pm.project_id WHERE pm.user_id = ? AND p.status = 'completed'", [$uid]),
        'services' => (int) qval('SELECT COUNT(*) FROM services WHERE user_id = ?', [$uid]),
        'five_star_reviews' => (int) qval('SELECT COUNT(*) FROM service_reviews r JOIN services s ON s.id = r.service_id WHERE s.user_id = ? AND r.rating = 5', [$uid]),
        'enrollments' => (int) ($learning['enrolled'] ?? 0),
        'courses_completed' => (int) ($learning['completed'] ?? 0),
        'mentor_accepted' => (int) ($mentoring['accepted'] ?? 0),
        'mentor_completed' => (int) ($mentoring['completed'] ?? 0),
        'followers' => (int) qval('SELECT COUNT(*) FROM follows WHERE followed_id = ?', [$uid]),
        'points' => (int) ($u['points'] ?? 0),
    ];
}

function badge_progress(string $code, array $stats): array
{
    $rules = [
        'profile_pro' => ['profile', 1, 'Complete your profile', 'profile/edit'],
        'first_post' => ['posts', 1, 'Publish your first post', 'feed?compose=idea'],
        'idea_machine' => ['ideas', 5, 'Share five ideas', 'feed?compose=idea'],
        'conversationalist' => ['comments', 10, 'Join ten conversations', 'feed'],
        'team_player' => ['teams', 1, 'Join a project team', 'projects'],
        'project_leader' => ['projects', 1, 'Start a project', 'projects/new'],
        'service_pro' => ['services', 1, 'Offer a service', 'marketplace/new'],
        'five_star' => ['five_star_reviews', 1, 'Earn a five-star review', 'marketplace'],
        'learner' => ['enrollments', 1, 'Enroll in a course', 'learn'],
        'graduate' => ['courses_completed', 1, 'Complete a course', 'learn'],
        'mentee' => ['mentor_accepted', 1, 'Connect with a mentor', 'mentors'],
        'popular' => ['followers', 5, 'Reach five followers', 'people'],
        'centurion' => ['points', 100, 'Earn 100 points', 'achievements'],
        'project_finisher' => ['completed_projects', 1, 'Complete a team project', 'projects'],
        'community_helper' => ['comments', 25, 'Write 25 helpful comments', 'feed'],
        'knowledge_seeker' => ['courses_completed', 3, 'Complete three courses', 'learn'],
        'mentor_milestone' => ['mentor_completed', 1, 'Complete a mentoring session', 'mentors'],
        'campus_connector' => ['followers', 10, 'Reach ten followers', 'people'],
        'innovator_rank' => ['rank_score', 250, 'Reach the Gold rank', 'achievements'],
        'pioneer_rank' => ['rank_score', 500, 'Reach the Platinum rank', 'achievements'],
        'champion_rank' => ['rank_score', 900, 'Reach the Diamond rank', 'achievements'],
        'legend_rank' => ['rank_score', 1500, 'Reach the Legend rank', 'achievements'],
        'master_rank' => ['rank_score', 2400, 'Reach the Master rank', 'achievements'],
        'celestial_rank' => ['rank_score', 4000, 'Reach the Celestial rank', 'achievements'],
    ];
    [$key, $target, $label, $path] = $rules[$code] ?? ['points', 1, 'Keep contributing', 'feed'];
    $current = min((int) ($stats[$key] ?? 0), $target);
    return ['current' => $current, 'target' => $target, 'pct' => (int) round($current / max(1, $target) * 100), 'label' => $label, 'url' => url($path)];
}

function badge_tier(string $code): string
{
    if (in_array($code, ['legend_rank', 'master_rank', 'celestial_rank'], true)) return 'legendary';
    if (in_array($code, ['champion_rank', 'pioneer_rank', 'innovator_rank', 'campus_connector', 'knowledge_seeker'], true)) return 'epic';
    if (in_array($code, ['centurion', 'project_finisher', 'community_helper', 'mentor_milestone', 'five_star', 'popular'], true)) return 'rare';
    return 'standard';
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
    sync_badge_catalog();
    $rankScore = refresh_reputation_score($uid);
    $stats = achievement_stats($uid);
    $stats['rank_score'] = $rankScore;
    $rules = [
        'profile_pro' => ['profile', 1], 'first_post' => ['posts', 1], 'idea_machine' => ['ideas', 5],
        'conversationalist' => ['comments', 10], 'team_player' => ['teams', 1], 'project_leader' => ['projects', 1],
        'service_pro' => ['services', 1], 'five_star' => ['five_star_reviews', 1], 'learner' => ['enrollments', 1],
        'graduate' => ['courses_completed', 1], 'mentee' => ['mentor_accepted', 1], 'popular' => ['followers', 5],
        'centurion' => ['points', 100], 'project_finisher' => ['completed_projects', 1],
        'community_helper' => ['comments', 25], 'knowledge_seeker' => ['courses_completed', 3],
        'mentor_milestone' => ['mentor_completed', 1], 'campus_connector' => ['followers', 10],
        'innovator_rank' => ['rank_score', 250], 'pioneer_rank' => ['rank_score', 500], 'champion_rank' => ['rank_score', 900],
        'legend_rank' => ['rank_score', 1500], 'master_rank' => ['rank_score', 2400], 'celestial_rank' => ['rank_score', 4000],
    ];
    foreach ($rules as $code => [$key, $target]) if (($stats[$key] ?? 0) >= $target) give_badge($uid, $code);
}
