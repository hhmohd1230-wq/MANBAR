<?php
$title = 'Dashboard';
$labels = array_map(fn($r) => date('M j', strtotime($r['d'])), $series['users']);
$palette = ['#34b06b', '#3b8fe0', '#f2b032', '#8b6fe0', '#e0587b', '#1fa5a0', '#ef8a3d', '#8da498'];
$maxFac = max(1, ...array_column($byFaculty, 'n'));
?>
<section class="ad-hero">
  <div class="ad-hero-main">
    <span class="ad-kicker"><?= icon('shield', 15) ?> Admin console · <?= e(date('l, j F')) ?></span>
    <h1>Welcome back, <?= e(explode(' ', $u['full_name'])[0]) ?> 👋</h1>
    <p>Here’s how MANBAR is doing across the campus<?= $stats['reports'] ? '. <b>' . $stats['reports'] . ' report' . ($stats['reports'] === 1 ? '' : 's') . '</b> need' . ($stats['reports'] === 1 ? 's' : '') . ' a look.' : ', and everything is calm. 🎉' ?></p>
    <form class="ad-find" method="get" action="<?= e(url('admin/users')) ?>"><?= icon('search', 18) ?><input name="q" placeholder="Find a member by name, email or student number" aria-label="Find a member"><button class="btn" type="submit">Find</button></form>
  </div>
  <div class="ad-attn">
    <span class="ad-attn-title"><?= icon('bell', 14) ?> Needs attention</span>
    <a class="ad-attn-item <?= $stats['scam'] ? 'is-alert' : '' ?>" href="<?= e(url('admin/moderation?tab=scam')) ?>"><span><?= icon('alert', 16) ?></span><b><?= $stats['scam'] ?></b><small>scam case<?= $stats['scam'] === 1 ? '' : 's' ?></small></a>
    <a class="ad-attn-item" href="<?= e(url('admin/moderation?tab=content')) ?>"><span><?= icon('flag', 16) ?></span><b><?= $stats['content_reports'] ?></b><small>content report<?= $stats['content_reports'] === 1 ? '' : 's' ?></small></a>
    <a class="ad-attn-item" href="<?= e(url('admin/users?status=suspended')) ?>"><span><?= icon('lock', 16) ?></span><b><?= $stats['suspended'] ?></b><small>banned account<?= $stats['suspended'] === 1 ? '' : 's' ?></small></a>
    <span class="ad-attn-item"><span><?= icon('wallet', 16) ?></span><b>AED <?= number_format($stats['held']) ?></b><small>held in <?= $stats['orders_open'] ?> open order<?= $stats['orders_open'] === 1 ? '' : 's' ?></small></span>
  </div>
</section>

<div class="kpis ad-kpis">
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

<div class="ad-tools">
  <a href="<?= e(url('admin/users')) ?>"><span class="tone-blue"><?= icon('users', 18) ?></span><b>Manage members</b><small>Ban, warn, give XP or badges</small></a>
  <a href="<?= e(url('admin/moderation?tab=scam')) ?>"><span class="tone-red"><?= icon('alert', 18) ?></span><b>Scam cases</b><small>Refund buyers, block scammers</small></a>
  <a href="<?= e(url('admin/moderation?tab=posts')) ?>"><span class="tone-amber"><?= icon('chat', 18) ?></span><b>Clean up posts</b><small>Search, hide or delete any post</small></a>
  <a href="<?= e(url('admin/content')) ?>"><span class="tone-violet"><?= icon('layers', 18) ?></span><b>Projects &amp; services</b><small>Courses, listings and projects</small></a>
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
  <div class="card span-4"><div class="card-title"><h3>Top by rank XP</h3></div>
    <div class="w-list"><?php foreach ($top as $i => $t): ?><div class="person"><span class="rank r<?= $i + 1 ?>"><?= $i + 1 ?></span><?= avatar($t, 34) ?><a class="grow small" href="<?= e(url('admin/users/' . $t['id'])) ?>"><b><?= e($t['full_name']) ?></b></a><b style="color:var(--g700)"><?= number_format((int) $t['reputation_score']) ?> XP</b></div><?php endforeach ?></div></div>
  <div class="card span-12"><div class="card-title"><h3>Recent moderation</h3><a class="small" href="<?= e(url('admin/settings')) ?>">Audit log</a></div>
    <?php if (!$actions): ?><p class="muted small" style="margin:0">No moderation actions yet. Bans, warnings, XP and badge changes will be listed here.</p>
    <?php else: ?><div class="ad-acts"><?php foreach ($actions as $m): ?><a class="ad-act" href="<?= e(url('admin/users/' . $m['user_id'])) ?>"><span class="ad-act-ic a-<?= e($m['action']) ?>"><?= icon(['ban' => 'lock', 'unban' => 'check-circle', 'warn' => 'alert', 'xp' => 'trending', 'badge_give' => 'award', 'badge_remove' => 'award', 'refund' => 'wallet'][$m['action']] ?? 'shield', 15) ?></span><span><b><?= e($m['target']) ?></b> · <?= e(ucfirst(str_replace('_', ' ', $m['action']))) ?><small><?= e(excerpt((string) $m['detail'], 70)) ?> · <?= e($m['admin'] ?: 'System') ?> · <?= e(time_ago($m['created_at'])) ?></small></span></a><?php endforeach ?></div><?php endif ?>
  </div>
  <div class="card span-12"><div class="card-title"><h3>Open reports</h3><a class="small" href="<?= e(url('admin/moderation')) ?>">View all</a></div>
    <?php if (!$openReports): ?><p class="muted" style="margin:0">🎉 Nothing to review right now.</p><?php else: ?><div class="table-wrap" style="border:0"><table class="t"><thead><tr><th>Type</th><th>Reason</th><th>Reported by</th><th>When</th></tr></thead><tbody><?php foreach ($openReports as $r): ?><tr><td><?php if (($r['category'] ?? '') === 'scam'): ?><a class="ad-scam-chip" href="<?= e(url('admin/cases/' . $r['id'])) ?>"><?= icon('alert', 13) ?> Scam · <?= e($r['accused']) ?></a><?php else: ?><span class="chip sm"><?= e($r['target_type']) ?> #<?= (int) $r['target_id'] ?></span><?php endif ?></td><td><?= e($r['reason']) ?></td><td><?= e($r['reporter']) ?></td><td class="muted small"><?= e(time_ago($r['created_at'])) ?></td></tr><?php endforeach ?></tbody></table></div><?php endif ?></div>
</div>
