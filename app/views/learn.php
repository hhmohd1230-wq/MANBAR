<?php $title = 'Learning center'; ?>
<?php partial('learn_assets') ?>
<section class="lc-board hb hb-learn">
  <div class="lc-board-text">
    <span class="lc-board-kicker"><?= icon('book', 15) ?> AAU Learning Center</span>
    <h1>Learn from your teachers and the best students</h1>
    <p>Step-by-step courses with lessons, summaries, quizzes and live games. Free and paid, at your own pace, with points and a finish badge at the end.</p>
    <div class="lc-board-cta">
      <a class="btn btn-lg lc-board-main hb-btn is-main" href="#courses"><span class="hb-ic"><?= icon('search', 17) ?></span> Browse courses</a>
      <a class="btn btn-lg lc-board-light hb-btn is-light tone-blue" href="<?= e(url('learn/my')) ?>"><span class="hb-ic"><?= icon('book', 17) ?></span> My courses</a>
      <a class="btn btn-lg lc-board-light hb-btn is-light tone-violet" href="<?= e(url('live')) ?>"><span class="hb-ic"><?= icon('play', 17) ?></span> Quizzes &amp; live</a>
      <?php if ($canCreate): ?><a class="btn btn-lg lc-board-ghost hb-btn is-ghost tone-teal" href="<?= e(url('learn/new')) ?>"><span class="hb-ic"><?= icon('plus', 17) ?></span> Create a course</a><?php endif ?>
    </div>
  </div>
  <dl class="lc-board-stats hb-stats">
    <div><dt><span class="hb-sic"><?= icon('book', 14) ?></span>Courses</dt><dd data-count><?= (int) $stats['courses'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('users', 14) ?></span>Students learning</dt><dd data-count><?= (int) $stats['learners'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('layers', 14) ?></span>Lessons &amp; summaries</dt><dd data-count><?= (int) $stats['steps'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('trophy', 14) ?></span>Courses finished</dt><dd data-count><?= (int) $stats['finished'] ?></dd></div>
  </dl>
</section>

<?php if ($popular && !$q && !$cat): ?>
<section class="lc-pop" aria-label="Most popular courses">
  <div class="lc-pop-head"><h2><?= icon('fire', 18) ?> Most popular right now</h2><span class="small muted">Ranked by students enrolled</span></div>
  <div class="lc-pop-grid">
    <?php foreach ($popular as $i => $p): $fin = (int) $p['students'] ? round($p['finished'] / $p['students'] * 100) : 0; ?>
      <a class="lc-pop-card" href="<?= e(url('learn/' . $p['id'])) ?>">
        <span class="lc-pop-cover" style="<?= course_cover_css($p) ?>"><span class="lc-pop-rank">#<?= $i + 1 ?></span></span>
        <span class="lc-pop-body"><b><?= e($p['title']) ?></b>
          <span class="lc-pop-host"><?= avatar($p, 20) ?> <?= e($p['full_name']) ?></span>
          <span class="lc-pop-meta"><span><?= icon('users', 13) ?> <?= (int) $p['students'] ?></span><span><?= icon('check-circle', 13) ?> <?= $fin ?>% finish</span><span class="lc-pop-price"><?= (float) $p['price_aed'] > 0 ? e(format_aed((float) $p['price_aed'])) : 'Free' ?></span></span></span>
      </a>
    <?php endforeach ?>
  </div>
</section>
<?php endif ?>
<?php if ($hosting): ?>
<section class="mb"><h3>Courses you host</h3>
  <div class="cards"><?php foreach ($hosting as $h): ?>
    <a class="card row" href="<?= e(url('learn/' . $h['id'] . '/dashboard')) ?>" style="color:var(--ink);gap:14px"><span class="badge-ico tone-green" style="margin:0;<?= theme_css((int) $h['theme']) ?>color:#fff"><?= icon('chart', 24) ?></span><span class="grow"><b><?= e($h['title']) ?></b><span class="xs muted" style="display:block"><?= (int) $h['students'] ?> students<?= $h['to_mark'] ? ' · <b style="color:#c0392b">' . (int) $h['to_mark'] . ' to mark</b>' : '' ?></span></span><span class="btn btn-sm">Dashboard</span></a>
  <?php endforeach ?></div></section>
<?php endif ?>
<?php if ($mine): ?>
<section class="mb"><h3>Continue learning</h3>
  <div class="cards"><?php foreach ($mine as $m): $pct = $m['total'] ? round($m['done'] / $m['total'] * 100) : 0; ?>
    <a class="card row" href="<?= e(url('learn/' . $m['id'])) ?>" style="color:var(--ink);gap:14px"><span class="badge-ico tone-green" style="margin:0;<?= course_cover_css($m) ?>color:#fff"><?= empty($m['cover_image']) ? icon('play', 24) : '' ?></span><span class="grow"><b><?= e($m['title']) ?></b><span class="progress" style="display:block;margin:7px 0 3px"><i style="width:<?= $pct ?>%"></i></span><span class="xs muted"><?= $m['finished'] ? 'Finished 🎓 · start again any time' : ($m['done'] ? 'Step ' . ($m['done'] + 1) . ' of ' . (int) $m['total'] . ' · ' . $pct . '%' : (int) $m['total'] . ' steps · not started') ?></span></span></a>
  <?php endforeach ?></div></section>
<?php endif ?>
<form class="row wrap mb" method="get" id="courses">
  <a class="chip <?= !$cat ? 'on' : 'outline' ?>" href="<?= e(url('learn')) ?>">All</a>
  <?php foreach (COURSE_CATEGORIES as $k => $l): ?><a class="chip <?= $cat === $k ? 'on' : 'outline' ?>" href="<?= e(url('learn?cat=' . $k)) ?>"><?= e($l) ?></a><?php endforeach ?>
  <div class="search grow" style="min-width:220px;margin-left:auto;max-width:340px"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search courses"></div>
</form>
<div class="cards">
  <?php foreach ($courses as $c): ?>
  <article class="card item">
    <div class="item-cover<?= empty($c['cover_image']) ? '' : ' lc-has-cover' ?>" style="<?= course_cover_css($c) ?>"><span class="chip sm" style="background:rgba(255,255,255,.9)"><?= e(COURSE_CATEGORIES[$c['category']] ?? 'General') ?></span><span class="ic-big"><?= icon('book', 30) ?></span></div>
    <div class="item-body"><h3><a href="<?= e(url('learn/' . $c['id'])) ?>"><?= e($c['title']) ?></a></h3><p><?= e(excerpt($c['description'], 105)) ?></p>
      <div class="row wrap small muted"><?= (float) $c['price_aed'] > 0 ? '<span class="lc-price" style="font-size:12.5px;padding:3px 8px">' . e(format_aed((float) $c['price_aed'])) . '</span>' : '<span class="lc-free" style="padding:3px 8px">Free</span>' ?><span class="chip sm outline"><?= e(ucfirst($c['level'])) ?></span><span><?= icon('layers', 13) ?> <?= (int) $c['lessons'] ?> lessons</span><?php if ($c['quizzes']): ?><span><?= icon('check-circle', 13) ?> <?= (int) $c['quizzes'] ?> quizzes</span><?php endif ?><span><?= icon('users', 13) ?> <?= (int) $c['students'] ?></span></div>
      <div class="item-foot"><a class="row gap-s small" href="<?= e(url('profile/' . $c['author_id'])) ?>" style="color:var(--ink2)"><?= avatar($c, 28) ?> <?= e($c['full_name']) ?></a><a class="btn btn-sm" href="<?= e(url('learn/' . $c['id'])) ?>">Open</a></div></div>
  </article>
  <?php endforeach ?>
</div>
<?php if (!$courses): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No courses yet</h3><p><?= $canCreate ? 'Create the first course for your students.' : 'Teachers will publish courses here soon.' ?></p><?php if ($canCreate): ?><a class="btn btn-primary" href="<?= e(url('learn/new')) ?>">Create a course</a><?php endif ?></div><?php endif ?>
