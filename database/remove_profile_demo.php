<?php
/**
 * Removes the demo activity added to a profile (posts, the demo project and service with its orders, reviews,
 * vouches, recommendations, followers, points and the badges it unlocked) and restores the old profile fields.
 * The ids of every demo row are listed in database/profile_demo_<user id>.json.
 *
 *   php database/remove_profile_demo.php 15
 */
chdir(dirname(__DIR__));
require 'app/bootstrap.php';

$uid = (int) ($argv[1] ?? 0);
$file = __DIR__ . "/profile_demo_$uid.json";
if (!$uid || !is_file($file)) exit("Usage: php database/remove_profile_demo.php <user id>  (needs database/profile_demo_<id>.json)\n");
$d = json_decode(file_get_contents($file), true);
$in = fn(array $ids) => implode(',', array_map('intval', $ids)) ?: '0';

qexec('DELETE FROM reactions WHERE id IN (' . $in($d['reactions']) . ')');
qexec('DELETE FROM comments WHERE id IN (' . $in($d['comments']) . ')');
qexec('DELETE FROM posts WHERE id IN (' . $in($d['posts']) . ')');
qexec('DELETE FROM service_reviews WHERE id IN (' . $in($d['reviews']) . ')');
qexec('DELETE FROM market_payments WHERE id IN (' . $in($d['payments']) . ')');
qexec('DELETE FROM service_requests WHERE id IN (' . $in($d['requests']) . ')');
qexec('DELETE FROM services WHERE id IN (' . $in($d['services']) . ')');
qexec('DELETE FROM recommendations WHERE id IN (' . $in($d['recs']) . ')');
qexec('DELETE FROM project_vouches WHERE id IN (' . $in($d['vouches']) . ')');
qexec('DELETE FROM project_tasks WHERE id IN (' . $in($d['tasks']) . ')');
qexec('DELETE FROM project_milestones WHERE id IN (' . $in($d['milestones']) . ')');
qexec('DELETE FROM project_messages WHERE id IN (' . $in($d['pmsg']) . ')');
qexec('DELETE FROM project_files WHERE id IN (' . $in($d['pfiles']) . ')');
foreach ($d['members'] as [$pid, $mid]) qexec('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$pid, $mid]);
qexec('DELETE FROM projects WHERE id IN (' . $in($d['projects']) . ')');
qexec('DELETE FROM follows WHERE followed_id = ? AND follower_id IN (' . $in($d['follows']) . ')', [$uid]);
$pts = (int) qval('SELECT COALESCE(SUM(points), 0) FROM point_log WHERE id IN (' . $in($d['points']) . ')');
qexec('DELETE FROM point_log WHERE id IN (' . $in($d['points']) . ')');
qexec('UPDATE users SET points = GREATEST(0, points - ?) WHERE id = ?', [$pts, $uid]);
qexec('DELETE FROM user_badges WHERE user_id = ? AND badge_id IN (' . $in($d['badges']) . ')', [$uid]);
update('users', $d['user_before'] + [], 'id = ?', [$uid]);
foreach (['public/uploads/projects/a7c31e9f04b25d8e61f0.png', 'public/uploads/service-covers/5e0b9d27c4a18f63e2d1.png'] as $f) if (is_file($f)) unlink($f);
echo 'Removed. Rank XP is now ' . refresh_reputation_score($uid) . "\n";
