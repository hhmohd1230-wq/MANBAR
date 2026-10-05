<?php $title = 'Projects'; ?>
<div class="pagehead"><div><h1>Projects</h1><p>Join a team or start your own — turn ideas into real work.</p></div><a class="btn btn-primary" href="<?= e(url('projects/new')) ?>"><?= icon('plus', 17) ?> Start a project</a></div>
<form class="card mb project-search-panel" method="get">
  <div class="row wrap">
    <div class="search grow" style="min-width:220px"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search projects"></div>
    <select class="select" name="status" style="width:auto" data-autosubmit><option value="">Any status</option><?php foreach (['open' => 'Open for applications', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $k => $l): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select>
    <label class="chip <?= $mine ? 'on' : 'outline' ?>" style="cursor:pointer"><input type="checkbox" name="mine" value="1" <?= $mine ? 'checked' : '' ?> data-autosubmit hidden> My projects</label>
    <button class="btn" type="submit">Search</button>
  </div>
  <?php if ($skills): ?><div class="row wrap" style="margin-top:12px"><span class="small muted">Skills wanted:</span><?php foreach ($skills as $s): ?><a class="chip <?= strtolower($skill) === strtolower($s) ? 'on' : 'outline' ?> sm" href="<?= e(url('projects?skill=' . urlencode($s))) ?>"><?= e($s) ?></a><?php endforeach ?></div><?php endif ?>
</form>
<div class="cards project-grid">
  <?php foreach ($projects as $p): $pct = $p['tasks'] ? round($p['tasks_done'] / $p['tasks'] * 100) : 0; ?>
  <article class="card item project-card">
    <div class="item-cover project-cover project-cover-t<?= project_cover_theme($p) ?> <?= !empty($p['cover_image']) ? 'has-image' : '' ?>" style="<?= e(project_cover_style($p)) ?>"><span class="status-pill st-<?= e($p['status']) ?>"><?= e(str_replace('_', ' ', $p['status'])) ?></span><span class="ic-big"><?= icon('rocket', 30) ?></span></div>
    <div class="item-body">
      <h3><a href="<?= e(url('projects/' . $p['id'])) ?>"><?= e($p['title']) ?></a></h3>
      <p><?= e(excerpt($p['description'], 120)) ?></p>
      <div class="row wrap" style="gap:5px"><?php foreach (array_slice(csv_list($p['needed_skills']), 0, 4) as $s): ?><span class="chip sm"><?= e($s) ?></span><?php endforeach ?></div>
      <?php if ($p['tasks']): ?><div><div class="row between xs muted"><span>Progress</span><span><?= $p['tasks_done'] ?>/<?= $p['tasks'] ?> tasks</span></div><div class="progress" style="margin-top:4px"><i style="width:<?= $pct ?>%"></i></div></div><?php endif ?>
      <div class="item-foot"><a class="row gap-s small" href="<?= e(url('profile/' . $p['owner_id'])) ?>" style="color:var(--ink2)"><?= avatar($p, 26) ?> <?= e(explode(' ', $p['full_name'])[0]) ?></a><span class="chip sm outline"><?= icon('users', 13) ?> <?= (int) $p['members'] ?>/<?= (int) $p['max_members'] ?></span></div>
    </div>
  </article>
  <?php endforeach ?>
</div>
<?php if (!$projects): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No projects found</h3><p>Start the first one and invite classmates to join.</p><a class="btn btn-primary" href="<?= e(url('projects/new')) ?>">Start a project</a></div><?php endif ?>
