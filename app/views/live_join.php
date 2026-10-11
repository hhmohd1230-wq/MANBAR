<?php
$title = 'Quizzes';
$pct = fn(array $a) => (int) $a['max_score'] ? (int) round(((int) $a['auto_score'] + (int) $a['essay_score']) / (int) $a['max_score'] * 100) : 0;
$show = $pick ? [$pick => $courses[$pick]] : $courses;
?>
<?php partial('learn_assets') ?>
<?php partial('back_button', ['href' => 'learn', 'label' => 'Back to Learning center']) ?>

<section class="lc-board is-sm hb hb-learn">
  <div class="lc-board-text">
    <span class="lc-board-kicker"><?= icon('check-circle', 15) ?> Quizzes &amp; live games</span>
    <h1>Pick a quiz and test yourself</h1>
    <p>Every quiz from the courses you joined or host, your latest scores, and live games happening right now.</p>
    <form class="lc-code" method="post" action="<?= e(url('live/join')) ?>"><?= csrf_field() ?>
      <label class="sr" for="code">Live game code</label>
      <input id="code" name="code" inputmode="numeric" maxlength="7" pattern="[0-9 ]{6,7}" autocomplete="off" placeholder="Have a code? 123 456" value="<?= e($code) ?>" required>
      <button class="btn" type="submit"><?= icon('play', 16) ?> Join</button>
    </form>
  </div>
  <dl class="lc-board-stats hb-stats">
    <div><dt><span class="hb-sic"><?= icon('check-circle', 14) ?></span>Quizzes for you</dt><dd data-count><?= (int) $stats['quizzes'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('target', 14) ?></span>Taken</dt><dd data-count><?= (int) $stats['taken'] ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('chart', 14) ?></span>Your average</dt><dd data-count><?= $stats['avg'] !== null ? (int) $stats['avg'] . '%' : '—' ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('play', 14) ?></span>Live now</dt><dd data-count><?= (int) $stats['live'] ?></dd></div>
  </dl>
</section>

<?php if ($open): ?>
<section class="qz-sec">
  <div class="lc-pop-head"><h2><span class="lc-live-dot"></span> Live now</h2><span class="small muted">Join with one tap, no code needed</span></div>
  <div class="qz-live">
    <?php foreach ($open as $o): $hostIt = (int) $o['host_id'] === (int) $u['id']; ?>
      <div class="qz-livecard">
        <span class="qz-livecover" style="<?= course_cover_css($o) ?>"><span class="qz-livebadge"><span class="lc-live-dot"></span> LIVE</span></span>
        <span class="qz-livebody"><b><?= e($o['title']) ?></b><span class="small muted"><?= e($o['course']) ?></span><span class="xs muted"><?= (int) $o['players'] ?> joined · <?= $o['state'] === 'lobby' ? 'starting soon' : 'in progress' ?></span></span>
        <?php if ($hostIt): ?><a class="btn" href="<?= e(url('live/' . $o['code'] . '/host')) ?>">Host screen</a>
        <?php else: ?><a class="btn btn-primary" href="<?= e(url('live/' . $o['code'])) ?>"><?= icon('play', 15) ?> Join</a><?php endif ?>
      </div>
    <?php endforeach ?>
  </div>
</section>
<?php endif ?>

<?php if ($recent): ?>
<section class="qz-sec">
  <div class="lc-pop-head"><h2><?= icon('clock', 18) ?> Your recent quizzes</h2></div>
  <div class="qz-recent">
    <?php foreach ($recent as $a): $p = $pct($a); $wait = $a['status'] === 'submitted'; $ok = $p >= (int) $a['pass_percent']; ?>
      <a class="qz-rcard" href="<?= e(url('quiz/attempt/' . $a['id'] . '/result')) ?>">
        <span class="qz-ring<?= $wait ? ' is-wait' : ($ok ? '' : ' is-low') ?>" style="--p:<?= $p ?>"><b><?= $p ?>%</b></span>
        <span class="qz-rbody"><b><?= e($a['title']) ?></b><span class="xs muted"><?= e($a['course']) ?></span>
          <span class="xs"><?= $wait ? '<span class="qz-tag is-wait">Waiting for marking</span>' : ($ok ? '<span class="qz-tag is-ok">Passed</span>' : '<span class="qz-tag is-low">Not passed</span>') ?> <span class="muted"><?= e(time_ago($a['submitted_at'])) ?></span></span></span>
      </a>
    <?php endforeach ?>
  </div>
</section>
<?php endif ?>

<section class="qz-sec">
  <div class="lc-pop-head"><h2><?= icon('book', 18) ?> Choose a quiz</h2><span class="small muted">From your courses</span></div>
  <?php if ($courses): ?>
    <nav class="qz-filter" aria-label="Course">
      <a class="<?= !$pick ? 'on' : '' ?>" href="<?= e(url('live')) ?>">All courses <span><?= array_sum(array_map(fn($c) => count($c['quizzes']), $courses)) ?></span></a>
      <?php foreach ($courses as $c): ?><a class="<?= $pick === (int) $c['id'] ? 'on' : '' ?>" href="<?= e(url('live?course=' . $c['id'])) ?>"><i style="<?= course_cover_css($c) ?>"></i><?= e(excerpt($c['title'], 34)) ?> <span><?= count($c['quizzes']) ?></span></a><?php endforeach ?>
    </nav>
    <?php foreach ($show as $c): $cid = (int) $c['id']; ?>
      <div class="qz-course">
        <a class="qz-chead" href="<?= e(url("learn/$cid")) ?>"><span class="qz-ccover" style="<?= course_cover_css($c) ?>"></span><span class="grow"><b><?= e($c['title']) ?></b><span class="xs muted"><?= $c['host'] ? 'You host this course' : 'Hosted by ' . e($c['full_name']) ?> · <?= count($c['quizzes']) ?> quiz<?= count($c['quizzes']) === 1 ? '' : 'zes' ?></span></span><?= icon('arrow-right', 16) ?></a>
        <div class="qz-grid">
          <?php foreach ($c['quizzes'] as $z): $qid = (int) $z['id']; $best = $z['best'] !== null ? (int) $z['best'] : null; $left = $z['max_attempts'] ? max(0, (int) $z['max_attempts'] - (int) $z['tries']) : null; $passed = $best !== null && $best >= (int) $z['pass_percent']; ?>
            <div class="qz-card<?= $z['locked'] ? ' is-locked' : '' ?>">
              <div class="qz-top">
                <span class="qz-ico"><?= icon($z['locked'] ? 'lock' : 'check-circle', 20) ?></span>
                <?php if ($c['host']): ?><span class="qz-tag<?= $z['status'] !== 'published' ? ' is-wait' : ' is-ok' ?>"><?= $z['status'] !== 'published' ? 'Draft' : 'Published' ?></span>
                <?php elseif ($z['locked']): ?><span class="qz-tag">Locked</span>
                <?php elseif ($best !== null): ?><span class="qz-ring is-sm<?= $passed ? '' : ' is-low' ?>" style="--p:<?= $best ?>"><b><?= $best ?>%</b></span>
                <?php else: ?><span class="qz-tag is-new">New</span><?php endif ?>
              </div>
              <b class="qz-title"><?= e($z['title']) ?></b>
              <span class="qz-meta"><span><?= (int) $z['questions'] ?> questions</span><span><?= $z['time_limit_min'] ? (int) $z['time_limit_min'] . ' min' : 'No time limit' ?></span><span>Pass <?= (int) $z['pass_percent'] ?>%</span></span>
              <?php if ($c['host']): ?>
                <span class="qz-sub"><?= (int) $z['results'] ?> result<?= (int) $z['results'] === 1 ? '' : 's' ?><?= (int) $z['to_mark'] ? ' · <b>' . (int) $z['to_mark'] . ' to mark</b>' : '' ?></span>
                <div class="qz-acts">
                  <?php if ($z['status'] === 'published' && (int) $z['questions']): ?><form method="post" action="<?= e(url("learn/$cid/quiz/$qid/live")) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-sm" type="submit"><?= icon('play', 14) ?> Start live</button></form><?php endif ?>
                  <form method="post" action="<?= e(url("learn/$cid/quiz/$qid/test")) ?>"><?= csrf_field() ?><button class="btn btn-sm" type="submit">Test</button></form>
                  <a class="btn btn-sm btn-ghost" href="<?= e(url("learn/$cid/quiz/$qid/results")) ?>">Results</a>
                </div>
              <?php elseif ($z['locked']): ?>
                <span class="qz-sub">Opens when you reach this step in the course.</span>
                <div class="qz-acts"><a class="btn btn-sm" href="<?= e(url("learn/$cid")) ?>">Go to the course</a></div>
              <?php else: ?>
                <span class="qz-sub"><?= $z['open_aid'] ? 'You have an attempt in progress.' : ($best !== null ? 'Best ' . $best . '% · ' . (int) $z['tries'] . ' attempt' . ((int) $z['tries'] === 1 ? '' : 's') . ($left !== null ? ' · ' . $left . ' left' : '') : 'Not taken yet' . ($left !== null ? ' · ' . $left . ' attempts' : '')) ?></span>
                <div class="qz-acts">
                  <?php if ($z['open_aid']): ?><a class="btn btn-primary btn-sm" href="<?= e(url('quiz/attempt/' . $z['open_aid'])) ?>"><?= icon('play', 14) ?> Continue</a>
                  <?php elseif ($left === 0): ?><span class="qz-tag">No attempts left</span>
                  <?php else: ?><form method="post" action="<?= e(url("learn/$cid/quiz/$qid/start")) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-sm" type="submit"><?= icon('play', 14) ?> <?= $best !== null ? 'Try again' : 'Start quiz' ?></button></form><?php endif ?>
                  <?php if ($z['last_aid']): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('quiz/attempt/' . $z['last_aid'] . '/result')) ?>">Review</a><?php endif ?>
                </div>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      </div>
    <?php endforeach ?>
  <?php else: ?>
    <div class="card empty"><h3>No quizzes yet</h3><p>Join a course to see its quizzes here.</p><a class="btn btn-primary" href="<?= e(url('learn')) ?>">Browse courses</a></div>
  <?php endif ?>
</section>
