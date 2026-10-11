<?php $title = 'My courses'; ?>
<?php partial('learn_assets') ?>
<?php partial('back_button', ['href' => 'learn', 'label' => 'Back to Learning center']) ?>

<section class="lc-board is-sm hb hb-learn">
  <div class="lc-board-text">
    <span class="lc-board-kicker"><?= icon('book', 15) ?> My courses</span>
    <h1>Pick up where you left off</h1>
    <p>The courses you joined and the ones you host, in one place.</p>
  </div>
  <dl class="lc-board-stats hb-stats">
    <div><dt><span class="hb-sic"><?= icon('book', 14) ?></span>Joined</dt><dd data-count><?= count($joined) ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('trophy', 14) ?></span>Finished</dt><dd data-count><?= count(array_filter($joined, fn($c) => $c['finished'])) ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('shield', 14) ?></span>Hosting</dt><dd data-count><?= count($hosting) ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('users', 14) ?></span>My students</dt><dd data-count><?= array_sum(array_column($hosting, 'students')) ?></dd></div>
  </dl>
</section>

<nav class="my-tabs" aria-label="My courses">
  <a class="<?= $tab === 'joined' ? 'on' : '' ?>" href="<?= e(url('learn/my')) ?>"><?= icon('book', 15) ?> Courses I joined <span class="chip sm"><?= count($joined) ?></span></a>
  <a class="<?= $tab === 'hosting' ? 'on' : '' ?>" href="<?= e(url('learn/my?tab=hosting')) ?>"><?= icon('shield', 15) ?> Courses I host <span class="chip sm"><?= count($hosting) ?></span></a>
</nav>

<?php if ($tab === 'joined'): ?>
  <div class="my-grid">
    <?php foreach ($joined as $c): $pc = $c['steps'] ? (int) round($c['at'] / $c['steps'] * 100) : 0; ?>
      <a class="card my-card" href="<?= e(url($c['next'] ? $c['next']['url'] : 'learn/' . $c['id'])) ?>">
        <span class="my-cover" style="<?= course_cover_css($c) ?>"><span class="my-state <?= $c['finished'] ? 'is-done' : ($c['started'] ? 'is-on' : '') ?>"><?= $c['finished'] ? '🎓 Finished' : ($c['started'] ? 'In progress' : 'Not started') ?></span></span>
        <span class="my-body">
          <b><?= e($c['title']) ?></b>
          <span class="my-host"><?= avatar($c, 20) ?> <?= e($c['full_name']) ?></span>
          <span class="progress" aria-label="<?= $pc ?>% done"><i style="width:<?= $pc ?>%"></i></span>
          <span class="my-meta"><?= $c['finished'] ? 'All ' . (int) $c['steps'] . ' steps done' . ((int) $c['runs'] > 1 ? ' · ' . (int) $c['runs'] . ' times' : '') : 'Step ' . min((int) $c['steps'], (int) $c['at'] + 1) . ' of ' . (int) $c['steps'] ?><?= $c['last'] ? ' · last visit ' . e(time_ago($c['last'] . ' 12:00:00')) : '' ?></span>
          <?php if ($c['next']): ?><span class="my-next"><?= icon('play', 13) ?> <?= e(course_step_label($c['next'])) ?>: <?= e(excerpt($c['next']['title'], 48)) ?></span><?php endif ?>
        </span>
        <span class="my-go"><?= $c['finished'] ? 'Review' : ($c['started'] ? 'Continue' : 'Start') ?> <?= icon('arrow-right', 15) ?></span>
      </a>
    <?php endforeach ?>
  </div>
  <?php if (!$joined): ?><div class="card empty"><h3>You haven’t joined a course yet</h3><p>Free courses open in one click. Paid ones unlock right after payment.</p><a class="btn btn-primary" href="<?= e(url('learn#courses')) ?>">Browse courses</a></div><?php endif ?>
<?php else: ?>
  <div class="my-grid">
    <?php if ($canCreate): ?><a class="card my-new" href="<?= e(url('learn/new')) ?>"><span><?= icon('plus', 26) ?></span><b>Create a new course</b><small>Lessons, summaries, quizzes and files</small></a><?php endif ?>
    <?php foreach ($hosting as $c): $fin = (int) $c['students'] ? (int) round($c['finished'] / $c['students'] * 100) : 0; ?>
      <div class="card my-card is-host">
        <a class="my-cover" href="<?= e(url('learn/' . $c['id'])) ?>" style="<?= course_cover_css($c) ?>"><span class="my-state <?= $c['status'] === 'published' ? 'is-on' : '' ?>"><?= $c['status'] === 'published' ? 'Published' : 'Hidden' ?></span><?php if ((float) $c['price_aed'] > 0): ?><span class="my-price"><?= e(format_aed((float) $c['price_aed'])) ?></span><?php endif ?></a>
        <span class="my-body">
          <b><?= e($c['title']) ?></b>
          <dl class="my-stats"><div><dt>Students</dt><dd><?= (int) $c['students'] ?></dd></div><div><dt>Finish rate</dt><dd><?= $fin ?>%</dd></div><div><dt>Steps</dt><dd><?= (int) $c['steps'] ?></dd></div><div><dt>Income</dt><dd><?= (float) $c['income'] > 0 ? 'AED ' . number_format((float) $c['income']) : '—' ?></dd></div></dl>
          <?php if ((int) $c['to_mark']): ?><span class="qz-tag is-wait"><?= (int) $c['to_mark'] ?> answer<?= (int) $c['to_mark'] === 1 ? '' : 's' ?> to mark</span><?php endif ?>
        </span>
        <span class="my-acts"><a class="btn btn-sm btn-primary" href="<?= e(url('learn/' . $c['id'] . '/dashboard')) ?>"><?= icon('chart', 14) ?> Dashboard</a><a class="btn btn-sm" href="<?= e(url('learn/' . $c['id'])) ?>">Open course</a></span>
      </div>
    <?php endforeach ?>
  </div>
  <?php if (!$hosting && !$canCreate): ?><div class="card empty"><h3>You don’t host a course yet</h3><p>Teachers and Gold-rank members can create courses.</p></div><?php endif ?>
<?php endif ?>
