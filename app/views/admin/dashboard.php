<?php
$title = 'Dashboard';
$labels = array_map(fn($r) => date('M j', strtotime($r['d'])), $series['users']);
$palette = ['#34b06b', '#3b8fe0', '#f2b032', '#8b6fe0', '#e0587b', '#1fa5a0', '#ef8a3d', '#8da498'];
$maxFac = max(1, ...array_column($byFaculty, 'n'));
?>
<div class="banner mb"><h2>Welcome back, <?= e(explode(' ', $u['full_name'])[0]) ?> 👋</h2><p>Here’s how MANBAR is doing across the campus. <?= $stats['reports'] ? '<b>' . $stats['reports'] . ' report(s)</b> are waiting for moderation.' : 'No open reports — all calm.' ?></p>
  <a class="btn btn-white" href="<?= e(url('admin/moderation')) ?>"><?= icon('flag', 16) ?> Open moderation</a></div>
<div class="kpis">
  <?php foreach ([
    ['users', 'green', $stats['users'], 'Total users', '+' . $stats['new_week'] . ' this week'],
    ['cap', 'blue', $stats['students'], 'Students', $stats['teachers'] . ' teachers'],
    ['chat', 'amber', $stats['posts'], 'Posts', $stats['comments'] . ' comments'],
    ['heart', 'rose', $stats['reactions'], 'Reactions', ''],
    ['rocket', 'violet', $stats['projects'], 'Projects', ''],
    ['store', 'orange', $stats['services'], 'Services', ''],
    ['book', 'teal', $stats['courses'], 'Courses', $stats['enrollments'] . ' enrollments'],
    ['compass', 'green', $stats['mentorships'], 'Mentorships', 'active / done'],
  ] as [$ic, $tone, $val, $lbl, $note]): ?>
    <div class="kpi tone-<?= $tone ?>"><div class="ico"><?= icon($ic, 22) ?></div><b><?= number_format($val) ?></b><span><?= e($lbl) ?></span><?php if ($note): ?><em><?= e($note) ?></em><?php endif ?></div>
  <?php endforeach ?>
</div>
<div class="admin-grid">
  <div class="card span-8"><div class="card-title"><h3>Activity — last 14 days</h3><div class="legend"><span><i style="background:#34b06b"></i>New users</span><span><i style="background:#3b8fe0"></i>Posts</span><span><i style="background:#f2b032"></i>Comments</span></div></div>
    <?= chart_lines([array_column($series['users'], 'n'), array_column($series['posts'], 'n'), array_column($series['comments'], 'n')], ['#34b06b', '#3b8fe0', '#f2b032'], $labels) ?></div>
  <div class="card span-4"><div class="card-title"><h3>Posts by type</h3></div>
    <div class="donut-wrap"><?= chart_donut($byType, $palette) ?><div class="stack gap-s small"><?php $i = 0; foreach ($byType as $t => $n): ?><div class="row"><i style="width:10px;height:10px;border-radius:3px;background:<?= $palette[$i++ % 8] ?>"></i><span><?= e(post_type($t)['label']) ?></span><b style="margin-left:auto"><?= $n ?></b></div><?php endforeach ?><?php if (!$byType): ?><span class="muted">No posts yet</span><?php endif ?></div></div></div>
  <div class="card span-4"><div class="card-title"><h3>Students by faculty</h3></div>
    <?php foreach ($byFaculty as $f): ?><div class="bar-row"><span class="lbl" style="width:140px;font-size:12.5px"><?= e(excerpt($f['f'], 22)) ?></span><div class="bar"><i style="width:<?= round($f['n'] / $maxFac * 100) ?>%"></i></div><b><?= (int) $f['n'] ?></b></div><?php endforeach ?></div>
  <div class="card span-4"><div class="card-title"><h3>Newest members</h3><a class="small" href="<?= e(url('admin/users')) ?>">Manage</a></div>
    <div class="w-list"><?php foreach ($recentUsers as $r): ?><div class="person"><?= avatar($r, 38) ?><div class="grow"><b class="small"><?= e($r['full_name']) ?></b><?= verified_badge($r) ?><div class="xs muted"><?= e($r['email']) ?></div></div><span class="role-pill <?= e($r['role']) ?>"><?= e(role_label($r['role'])) ?></span></div><?php endforeach ?></div></div>
  <div class="card span-4"><div class="card-title"><h3>Top contributors</h3></div>
    <div class="w-list"><?php foreach ($top as $i => $t): ?><div class="person"><span class="rank r<?= $i + 1 ?>"><?= $i + 1 ?></span><?= avatar($t, 34) ?><span class="grow small"><b><?= e($t['full_name']) ?></b></span><b style="color:var(--g700)"><?= (int) $t['points'] ?></b></div><?php endforeach ?></div></div>
  <div class="card span-12"><div class="card-title"><h3>Open reports</h3><a class="small" href="<?= e(url('admin/moderation')) ?>">View all</a></div>
    <?php if (!$openReports): ?><p class="muted" style="margin:0">🎉 Nothing to review right now.</p><?php else: ?><div class="table-wrap" style="border:0"><table class="t"><thead><tr><th>Type</th><th>Reason</th><th>Reported by</th><th>When</th></tr></thead><tbody><?php foreach ($openReports as $r): ?><tr><td><span class="chip sm"><?= e($r['target_type']) ?> #<?= (int) $r['target_id'] ?></span></td><td><?= e($r['reason']) ?></td><td><?= e($r['reporter']) ?></td><td class="muted small"><?= e(time_ago($r['created_at'])) ?></td></tr><?php endforeach ?></tbody></table></div><?php endif ?></div>
</div>
