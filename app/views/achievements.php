<?php $title = 'Achievements'; $earned = count(array_filter($badges, fn($b) => $b['earned'])); ?>
<div class="pagehead"><div><h1>Achievements</h1><p>Points, badges and levels — recognition for being a great campus citizen.</p></div></div>
<div class="banner mb">
  <div class="row wrap between"><div><div class="xs" style="opacity:.85;font-weight:700;letter-spacing:.1em;text-transform:uppercase">Your level</div><h2 style="font-size:34px">Level <?= $lvl['n'] ?> · <?= e($lvl['name']) ?></h2>
    <div style="max-width:420px"><div class="progress" style="background:rgba(255,255,255,.3);margin:10px 0 6px"><i style="background:#fff;width:<?= $lvl['pct'] ?>%"></i></div><span class="small"><?= (int) $u['points'] ?> points<?= $lvl['next'] ? ' · ' . ($lvl['next_at'] - $u['points']) . ' to reach ' . e($lvl['next']) : ' · max level reached 🎉' ?></span></div></div>
    <div class="row" style="gap:28px"><div class="tc"><div style="font:800 40px var(--font-d)">#<?= $rank ?></div><div class="small" style="opacity:.9">Campus rank</div></div><div class="tc"><div style="font:800 40px var(--font-d)"><?= $earned ?>/<?= count($badges) ?></div><div class="small" style="opacity:.9">Badges</div></div></div></div>
</div>
<div class="page-grid">
  <div class="stack gap-l">
    <section><h3>Badges</h3><div class="badge-grid"><?php foreach ($badges as $b): ?><div class="badge-card tone-<?= e($b['tone']) ?> <?= $b['earned'] ? '' : 'locked' ?>"><div class="badge-ico"><?= icon($b['icon'], 28) ?></div><b><?= e($b['name']) ?></b><small><?= e($b['description']) ?></small><?php if ($b['earned']): ?><span class="chip on sm" style="margin-top:8px"><?= icon('check', 12) ?> Earned</span><?php else: ?><span class="chip outline sm" style="margin-top:8px"><?= icon('lock', 12) ?> Locked</span><?php endif ?></div><?php endforeach ?></div></section>
    <section class="card"><h3>How to earn points</h3>
      <div class="table-wrap" style="border:0"><table class="t"><tbody>
        <?php foreach ([['Complete your profile', '+20'], ['Publish a post', '+10'], ['Start a project', '+15'], ['Join a project team', '+10'], ['Complete a project', '+30'], ['Write a comment', '+3'], ['Receive a reaction', '+1'], ['List a service', '+10'], ['Finish a course', '+25'], ['Complete a mentoring session', '+5 / +20']] as [$a, $p]): ?><tr><td><?= e($a) ?></td><td style="text-align:right"><b style="color:var(--g700)"><?= $p ?></b></td></tr><?php endforeach ?></tbody></table></div></section>
  </div>
  <aside class="aside">
    <div class="card"><div class="card-title"><h3><?= icon('trophy', 18) ?> Leaderboard</h3></div>
      <div class="w-list"><?php foreach ($board as $i => $b): ?><div class="person" style="<?= (int) $b['id'] === (int) $u['id'] ? 'background:var(--g50);border-radius:12px;padding:6px' : '' ?>"><span class="rank r<?= $i + 1 ?>"><?= $i + 1 ?></span><a href="<?= e(url('profile/' . $b['id'])) ?>"><?= avatar($b, 36) ?></a><a class="nm grow" href="<?= e(url('profile/' . $b['id'])) ?>"><?= e($b['full_name']) ?></a><b class="small" style="color:var(--g700)"><?= (int) $b['points'] ?></b></div><?php endforeach ?></div></div>
    <div class="card"><div class="card-title"><h3>Recent points</h3></div><div class="w-list"><?php foreach ($log as $l): ?><div class="row between small"><span><?= e($l['reason']) ?></span><b style="color:var(--g700)">+<?= (int) $l['points'] ?></b></div><?php endforeach ?><?php if (!$log): ?><span class="muted small">Nothing yet.</span><?php endif ?></div></div>
  </aside>
</div>
