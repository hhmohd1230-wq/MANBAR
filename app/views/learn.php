<?php $title = 'Learning center'; ?>
<div class="pagehead"><div><h1>Learning center</h1><p>Courses and lessons from your teachers and mentors. Enroll, learn at your pace and earn points.</p></div><?php if (is_teacher()): ?><a class="btn btn-primary" href="<?= e(url('learn/new')) ?>"><?= icon('plus', 17) ?> Create a course</a><?php endif ?></div>
<?php if ($mine): ?>
<section class="mb"><h3>Continue learning</h3>
  <div class="cards"><?php foreach ($mine as $m): $pct = $m['total'] ? round($m['done'] / $m['total'] * 100) : 0; ?>
    <a class="card row" href="<?= e(url('learn/' . $m['id'])) ?>" style="color:var(--ink);gap:14px"><span class="badge-ico tone-green" style="margin:0;<?= theme_css((int) $m['theme']) ?>color:#fff"><?= icon('play', 24) ?></span><span class="grow"><b><?= e($m['title']) ?></b><span class="progress" style="display:block;margin:7px 0 3px"><i style="width:<?= $pct ?>%"></i></span><span class="xs muted"><?= (int) $m['done'] ?>/<?= (int) $m['total'] ?> lessons · <?= $pct ?>%</span></span></a>
  <?php endforeach ?></div></section>
<?php endif ?>
<form class="row wrap mb" method="get">
  <a class="chip <?= !$cat ? 'on' : 'outline' ?>" href="<?= e(url('learn')) ?>">All</a>
  <?php foreach (COURSE_CATEGORIES as $k => $l): ?><a class="chip <?= $cat === $k ? 'on' : 'outline' ?>" href="<?= e(url('learn?cat=' . $k)) ?>"><?= e($l) ?></a><?php endforeach ?>
  <div class="search grow" style="min-width:220px;margin-left:auto;max-width:340px"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search courses"></div>
</form>
<div class="cards">
  <?php foreach ($courses as $c): ?>
  <article class="card item">
    <div class="item-cover" style="<?= theme_css((int) $c['theme']) ?>"><span class="chip sm" style="background:rgba(255,255,255,.9)"><?= e(COURSE_CATEGORIES[$c['category']] ?? 'General') ?></span><span class="ic-big"><?= icon('book', 30) ?></span></div>
    <div class="item-body"><h3><a href="<?= e(url('learn/' . $c['id'])) ?>"><?= e($c['title']) ?></a></h3><p><?= e(excerpt($c['description'], 105)) ?></p>
      <div class="row wrap small muted"><span class="chip sm outline"><?= e(ucfirst($c['level'])) ?></span><span><?= icon('layers', 13) ?> <?= (int) $c['lessons'] ?> lessons</span><span><?= icon('users', 13) ?> <?= (int) $c['students'] ?></span></div>
      <div class="item-foot"><a class="row gap-s small" href="<?= e(url('profile/' . $c['author_id'])) ?>" style="color:var(--ink2)"><?= avatar($c, 28) ?> <?= e($c['full_name']) ?></a><a class="btn btn-sm" href="<?= e(url('learn/' . $c['id'])) ?>">Open</a></div></div>
  </article>
  <?php endforeach ?>
</div>
<?php if (!$courses): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No courses yet</h3><p><?= is_teacher() ? 'Create the first course for your students.' : 'Teachers will publish courses here soon.' ?></p><?php if (is_teacher()): ?><a class="btn btn-primary" href="<?= e(url('learn/new')) ?>">Create a course</a><?php endif ?></div><?php endif ?>
