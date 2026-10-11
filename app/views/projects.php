<?php
$title = 'Projects';
$mine = (bool) $mine;
$qs = fn(array $over) => url('projects' . (($p = http_build_query(array_filter(array_merge(['status' => $status, 'skill' => $skill, 'q' => $q, 'mine' => $mine ? 1 : '', 'sort' => $sort], $over)))) ? '?' . $p : ''));
$isMatch = fn(string $sk) => in_array(mb_strtolower(trim($sk)), $mySkills, true);
?>
<?php partial('project_assets') ?>

<section class="pj-board hb hb-proj">
  <div class="pj-board-text">
    <span class="pj-kicker"><?= icon('rocket', 15) ?> AAU projects</span>
    <h1>Build something real, with a team</h1>
    <p>Start an idea, find teammates with the right skills, plan it on a roadmap and ship it together. Finished projects earn vouches and reputation.</p>
    <div class="pj-cta">
      <a class="btn btn-lg pj-btn-main hb-btn is-main" href="<?= e(url('projects/new')) ?>"><span class="hb-ic"><?= icon('plus', 17) ?></span> Start a project</a>
      <a class="btn btn-lg pj-btn-light hb-btn is-light tone-violet" href="<?= e($qs(['mine' => 1, 'status' => ''])) ?>"><span class="hb-ic"><?= icon('briefcase', 17) ?></span> My projects</a>
      <?php if ($matches): ?><a class="btn btn-lg pj-btn-ghost hb-btn is-ghost tone-amber" href="#matches"><span class="hb-ic"><?= icon('sparkles', 17) ?></span> Matches my skills</a><?php endif ?>
    </div>
  </div>
  <dl class="pj-board-stats hb-stats">
    <div><dt><span class="hb-sic"><?= icon('user-plus', 14) ?></span>Open to join</dt><dd data-count><?= (int) $stats['open'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('users', 14) ?></span>Students building</dt><dd data-count><?= (int) $stats['builders'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('check-circle', 14) ?></span>Tasks done</dt><dd data-count><?= (int) $stats['tasks_done'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('trophy', 14) ?></span>Projects finished</dt><dd data-count><?= (int) $stats['completed'] ?></dd></div>
  </dl>
</section>

<?php if ($matches && !$q && !$skill && !$mine): ?>
<section class="pj-sec" id="matches">
  <div class="pj-sec-head"><h2><?= icon('sparkles', 18) ?> Looking for your skills</h2><span class="small muted">Open teams that need what you can do</span></div>
  <div class="pj-match-grid">
    <?php foreach ($matches as $m): $left = max(0, (int) $m['max_members'] - (int) $m['members']); ?>
      <a class="pj-match" href="<?= e(url('projects/' . $m['id'])) ?>#roles">
        <span class="pj-match-cover project-cover project-cover-t<?= project_cover_theme($m) ?> <?= !empty($m['cover_image']) ? 'has-image' : '' ?>" style="<?= e(project_cover_style($m)) ?>"></span>
        <span class="pj-match-body"><b><?= e($m['title']) ?></b>
          <span class="pj-youmatch"><?= icon('check-circle', 13) ?> You match: <?= e(implode(', ', array_slice($m['hit'], 0, 3))) ?></span>
          <span class="xs muted"><?= $left ?> spot<?= $left === 1 ? '' : 's' ?> left · by <?= e(first_name($m['full_name'])) ?></span></span>
        <span class="pj-match-go">Apply <?= icon('arrow-right', 14) ?></span>
      </a>
    <?php endforeach ?>
  </div>
</section>
<?php endif ?>

<nav class="pj-tabs" aria-label="Project status">
  <a class="<?= !$status && !$mine ? 'on' : '' ?>" href="<?= e($qs(['status' => '', 'mine' => ''])) ?>">Everything <span><?= array_sum(array_map('intval', $counts)) - (int) ($counts['closed'] ?? 0) ?></span></a>
  <a class="<?= $status === 'open' ? 'on' : '' ?>" href="<?= e($qs(['status' => 'open', 'mine' => ''])) ?>"><?= icon('user-plus', 15) ?> Open to join <span><?= (int) ($counts['open'] ?? 0) ?></span></a>
  <a class="<?= $status === 'in_progress' ? 'on' : '' ?>" href="<?= e($qs(['status' => 'in_progress', 'mine' => ''])) ?>"><?= icon('briefcase', 15) ?> In progress <span><?= (int) ($counts['in_progress'] ?? 0) ?></span></a>
  <a class="<?= $status === 'completed' ? 'on' : '' ?>" href="<?= e($qs(['status' => 'completed', 'mine' => ''])) ?>"><?= icon('trophy', 15) ?> Completed <span><?= (int) ($counts['completed'] ?? 0) ?></span></a>
  <a class="<?= $mine ? 'on' : '' ?>" href="<?= e($qs(['mine' => 1, 'status' => ''])) ?>"><?= icon('users', 15) ?> My projects</a>
</nav>
<form class="pj-search" method="get"><?php foreach (['status' => $status, 'mine' => $mine ? 1 : ''] as $k => $v) if ($v) echo '<input type="hidden" name="' . $k . '" value="' . e((string) $v) . '">'; ?>
  <div class="search grow"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search projects, ideas or skills…"></div>
  <select class="select" name="sort" data-autosubmit aria-label="Sort"><option value="">Newest</option><option value="active" <?= $sort === 'active' ? 'selected' : '' ?>>Recently active</option></select>
</form>
<?php if ($skills): ?><div class="pj-skills"><span class="small muted">Skills wanted:</span><?php foreach ($skills as $sk): ?><a class="chip sm <?= strtolower($skill) === strtolower($sk) ? 'on' : 'outline' ?><?= $isMatch($sk) ? ' pj-chip-match' : '' ?>" href="<?= e($qs(['skill' => strtolower($skill) === strtolower($sk) ? '' : $sk])) ?>"><?= $isMatch($sk) ? icon('check', 12) . ' ' : '' ?><?= e($sk) ?></a><?php endforeach ?></div><?php endif ?>

<div class="pj-grid">
  <?php foreach ($projects as $p): $pid = (int) $p['id'];
    $pct = $p['tasks'] ? (int) round($p['tasks_done'] / $p['tasks'] * 100) : ($p['milestones'] ? (int) round($p['milestones_done'] / $p['milestones'] * 100) : null);
    $left = max(0, (int) $p['max_members'] - (int) $p['members']); $team = $faces[$pid] ?? []; ?>
    <article class="card pj-card">
      <a class="pj-cover project-cover project-cover-t<?= project_cover_theme($p) ?> <?= !empty($p['cover_image']) ? 'has-image' : '' ?>" style="<?= e(project_cover_style($p)) ?>" href="<?= e(url("projects/$pid")) ?>" tabindex="-1" aria-hidden="true">
        <span class="status-pill st-<?= e($p['status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $p['status']))) ?></span>
        <?php if ($p['status'] === 'open' && $left): ?><span class="pj-spots"><?= icon('user-plus', 13) ?> <?= $left ?> spot<?= $left === 1 ? '' : 's' ?> open</span><?php endif ?>
        <?php if ($p['files']): ?><span class="pj-files"><?= icon('paperclip', 12) ?> <?= (int) $p['files'] ?></span><?php endif ?>
      </a>
      <div class="pj-body">
        <h3><a href="<?= e(url("projects/$pid")) ?>"><?= e($p['title']) ?></a></h3>
        <p><?= e(excerpt($p['description'], 110)) ?></p>
        <?php if ($sk = array_slice(csv_list($p['needed_skills']), 0, 4)): ?><div class="pj-chips"><?php foreach ($sk as $s): ?><span class="<?= $isMatch($s) ? 'is-match' : '' ?>"><?= $isMatch($s) ? icon('check', 11) . ' ' : '' ?><?= e($s) ?></span><?php endforeach ?></div><?php endif ?>
        <div class="pj-team">
          <span class="lc-stack"><?php foreach (array_slice($team, 0, 4) as $f) echo avatar($f, 30); ?><?php if (count($team) > 4): ?><span class="lc-stack-more" style="width:30px;height:30px">+<?= count($team) - 4 ?></span><?php endif ?></span>
          <span class="pj-team-txt"><b><?= (int) $p['members'] ?>/<?= (int) $p['max_members'] ?></b> in the team</span>
          <?php if ($pct !== null): ?><span class="qz-ring is-sm" style="--p:<?= $pct ?>" title="<?= $pct ?>% done"><b><?= $pct ?>%</b></span><?php endif ?>
        </div>
      </div>
      <div class="pj-foot"><a href="<?= e(url('profile/' . $p['owner_id'])) ?>"><?= avatar($p, 22) ?> <?= e($p['full_name']) ?></a><span class="xs muted"><?= icon('clock', 12) ?> active <?= e(time_ago($p['active_at'])) ?></span></div>
    </article>
  <?php endforeach ?>
</div>
<?php if (!$projects): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No projects found</h3><p>Start the first one and invite classmates to join.</p><a class="btn btn-primary" href="<?= e(url('projects/new')) ?>">Start a project</a></div><?php endif ?>
