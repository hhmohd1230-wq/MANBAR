<?php
$title = $c['title'];
$total = count($lessons);
$lessonCount = count(array_filter($lessons, fn($l) => $l['kind'] !== 'summary'));
$summaryCount = $total - $lessonCount;
$outline = course_outline($lessons, $quizzes);   // lessons, summaries and quizzes in the host's order
$stepCount = $path ? count($path['steps']) : count($outline);
$paid = course_is_paid($c);
$cid = (int) $c['id'];
$courseMats = array_values(array_filter($materials, fn($m) => !$m['lesson_id']));
$lessonMats = $lesson ? array_values(array_filter($materials, fn($m) => (int) $m['lesson_id'] === (int) $lesson['id'])) : [];
$meIn = $enrolled && empty($enrolled['preview']);   // a real student of this course (not the host's preview)
?>
<?php partial('learn_assets') ?>
<?php $here = $lesson ? "learn/$cid/lesson/{$lesson['id']}" : "learn/$cid"; ?>

<?php if ($lesson): ?><?php partial('back_button', ['href' => "learn/$cid", 'label' => 'Back to ' . $c['title']]) ?><?php else: ?><?php partial('back_button', ['href' => 'learn', 'label' => 'Back to Learning center']) ?><?php endif ?>

<?php if ($studentView): ?><?php partial('student_view_bar', ['cid' => $cid]) ?><?php endif ?>

<?php if ($manage):
  $tile = fn(string $href, string $ic, string $t, string $sub, string $tone) => '<a class="lc-ht-tile" href="' . e(url($href)) . '"><span class="lc-ht-tico ' . $tone . '">' . icon($ic, 20) . '</span><b>' . $t . '</b><span>' . $sub . '</span></a>'; ?>
  <section class="lc-ht" aria-label="<?= $isAuthor ? 'Host tools' : 'Admin tools' ?>">
    <div class="lc-ht-head">
      <span class="lc-ht-ico"><?= icon('shield', 20) ?></span>
      <span class="grow"><b><?= $isAuthor ? 'Host tools' : 'Admin tools' ?></b><span><?= $isAuthor ? 'Only you see this panel. Build your course here, then check it as a student.' : 'You’re managing someone else’s course as an admin.' ?></span></span>
      <form method="post" action="<?= e(url("learn/$cid/view")) ?>"><?= csrf_field() ?><input type="hidden" name="mode" value="student"><input type="hidden" name="back" value="<?= e($here) ?>"><button class="btn lc-sv lc-ht-sv" type="submit"><?= icon('eye', 18) ?> See it as a student</button></form>
    </div>
    <div class="lc-ht-grid">
      <?= $tile("learn/$cid/dashboard", 'chart', 'Dashboard', 'Students, marks and visits', 't-green') ?>
      <?= $tile("learn/$cid#add-lesson", 'plus', 'Add a lesson', 'Text, video, images, files', 't-blue') ?>
      <?= $tile("learn/$cid?kind=summary#add-lesson", 'bulb', 'Add a summary', 'Key points between steps', 't-amber') ?>
      <form method="post" action="<?= e(url("learn/$cid/quiz/new")) ?>"><?= csrf_field() ?><button class="lc-ht-tile" type="submit"><span class="lc-ht-tico t-violet"><?= icon('check-circle', 20) ?></span><b>New quiz</b><span>Multiple choice and essays</span></button></form>
      <?= $tile("learn/$cid#add-material", 'paperclip', 'Files &amp; links', 'Guides for the whole course', 't-teal') ?>
      <?php if (!$lesson): ?><label class="lc-ht-tile" for="coverFile"><span class="lc-ht-tico t-pink"><?= icon('image', 20) ?></span><b>Cover picture</b><span>Make the course stand out</span></label>
      <?php else: ?><?= $tile("learn/$cid#cover", 'image', 'Cover picture', 'Make the course stand out', 't-pink') ?><?php endif ?>
      <?= $tile("learn/$cid/dashboard#settings", 'settings', 'Settings', 'Title, price, visibility', 't-gray') ?>
    </div>
  </section>
<?php endif ?>

<?php if ($live && $access && !$manage): ?>
  <a class="lc-live-banner" href="<?= e(url('live/' . $live['code'])) ?>"><span class="lc-live-dot"></span><span class="grow"><b>Live quiz happening now</b><br><span><?= e($live['title']) ?> · code <?= e($live['code']) ?></span></span><span class="btn btn-white btn-sm">Join now</span></a>
<?php endif ?>

<div class="page-grid">
  <div class="stack gap-l">
    <?php if ($lesson): $emb = video_embed_url($lesson['video_url']); $isDone = in_array((int) $lesson['id'], $done, true); $isSum = $lesson['kind'] === 'summary';
      $si = $path ? course_step_index($path['steps'], 'l' . $lesson['id']) : null; ?>
      <?php if ($si !== null) partial('step_bar', ['cid' => $cid, 'path' => $path, 'i' => $si]); ?>
      <div class="card<?= $isSum ? ' lc-sumcard' : '' ?>">
        <?php if ($isSum): ?><span class="chip sm lc-chip-sum"><?= icon('bulb', 14) ?> Summary · key points</span><?php else: ?><span class="chip sm">Lesson <?= (int) $lesson['position'] ?></span><?php endif ?><h1 style="margin-top:8px"><?= e($lesson['title']) ?></h1>
        <?php if ($emb): ?><iframe class="video mb" src="<?= e($emb) ?>" allowfullscreen loading="lazy" title="Lesson video" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"></iframe>
        <?php elseif ($lesson['video_url']): ?><p><a class="btn" target="_blank" rel="noopener" href="<?= e($lesson['video_url']) ?>"><?= icon('play', 16) ?> Open lesson video · <?= e(link_host($lesson['video_url'])) ?></a></p><?php endif ?>
        <div class="prose<?= $isSum ? ' lc-summary' : '' ?>"><?= rich($lesson['content']) ?></div>
        <?php if ($lessonMats): ?>
          <div class="lm-head"><h3><?= icon('paperclip', 18) ?> <?= $isSum ? 'Files &amp; links for this summary' : 'Lesson files &amp; links' ?></h3><span class="chip sm"><?= count($lessonMats) ?></span></div>
          <?php partial('materials', ['mats' => $lessonMats, 'cid' => $cid, 'access' => $access, 'manage' => $manage, 'inLesson' => true]) ?>
        <?php endif ?>
<?php if ($si !== null): $nx = $path['steps'][$si + 1] ?? null; ?>
          <form method="post" action="<?= e(url("learn/$cid/next")) ?>" class="mt-l lc-continue"><?= csrf_field() ?><input type="hidden" name="from" value="l<?= (int) $lesson['id'] ?>">
            <button class="btn btn-primary btn-lg" type="submit"><?= $nx ? icon('arrow-right', 18) : icon('flag', 18) ?> <?= $nx ? ($isSum ? 'Got it, continue' : ($isDone ? 'Continue' : 'Mark complete &amp; continue')) : ($path['finished'] ? 'Back to the course' : 'Finish the course') ?></button>
            <span class="small muted"><?= $nx ? 'Next: ' . e(course_step_label($nx)) . ' · ' . e(excerpt($nx['title'], 60)) : 'This is the last step.' ?><?= !empty($path['preview']) ? ' · Preview: nothing is saved.' : '' ?></span>
          </form>
        <?php endif ?>
      </div>
      <?php if ($manage): ?>
        <details class="card lc-edit" id="edit-lesson"<?= input('edit') ? ' open' : '' ?>>
          <summary><?= icon('edit', 18) ?> Edit this <?= $isSum ? 'summary' : 'lesson' ?></summary>
          <form method="post" action="<?= e(url("learn/$cid/lesson/{$lesson['id']}/edit")) ?>"><?= csrf_field() ?>
            <div class="field"><label class="f">Title</label><input class="input" name="title" value="<?= e($lesson['title']) ?>" required maxlength="200"></div>
            <div class="field"><label class="f">Content</label><textarea class="textarea" name="content" rows="8" required><?= e($lesson['content']) ?></textarea></div>
            <div class="field"><label class="f">Video link (YouTube, Vimeo or any site; leave empty for none)</label><input class="input" name="video_url" value="<?= e((string) $lesson['video_url']) ?>" placeholder="https://www.youtube.com/watch?v=…"></div>
            <div class="row wrap"><button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> Save changes</button><span class="small muted">Files and links for this lesson are below.</span></div>
          </form>
        </details>
        <form class="card" method="post" action="<?= e(url("learn/$cid/materials")) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
          <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id'] ?>">
          <div class="card-title"><h3><?= icon('upload', 18) ?> Add files &amp; links to this <?= $isSum ? 'summary' : 'lesson' ?></h3></div>
          <?php partial('attach_fields') ?>
          <button class="btn btn-primary mt" type="submit"><?= icon('plus', 16) ?> Add</button>
        </form>
      <?php endif ?>

    <?php else: ?>
<?php
      // ----- overview counts -----
      $matsByLesson = [];
      foreach ($materials as $m) if ($m['lesson_id']) $matsByLesson[(int) $m['lesson_id']] = ($matsByLesson[(int) $m['lesson_id']] ?? 0) + 1;
      $pubQuizzes = array_values(array_filter($quizzes, fn($z) => $z['status'] === 'published'));
      $quizCount = $manage ? count($quizzes) : count($pubQuizzes);
      ?>
      <?php
        $others = array_values(array_filter($classmates, fn($m) => (int) $m['id'] !== (int) $u['id']));
        $first = fn(array $m) => first_name($m['full_name']);
        $crowd = '';
        if ($classmateCount) {
            $named = array_map(fn($m) => '<b>' . e($first($m)) . '</b>', array_slice($others, 0, $meIn ? 1 : 2));
            if ($meIn) array_unshift($named, '<b>You</b>');
            $rest = $classmateCount - count($named);
            $crowd = implode(', ', $named) . ($rest > 0 ? ' and <b>' . $rest . ' other' . ($rest === 1 ? '' : 's') . '</b>' : '') . ($classmateCount === 1 && !$meIn ? ' is' : ' are') . ' taking this course';
        }
      ?>
      <section class="card lc-hero2" id="cover">
        <div class="lc-banner<?= empty($c['cover_image']) ? ' is-plain' : '' ?>" style="<?= course_cover_css($c) ?>">
          <div class="lc-banner-top">
            <span class="lc-pill"><?= icon('book', 14) ?> <?= e(COURSE_CATEGORIES[$c['category']] ?? 'General') ?></span><span class="lc-pill"><?= e(ucfirst($c['level'])) ?></span>
            <?php if ($c['status'] !== 'published'): ?><span class="lc-pill is-warn">Hidden from students</span><?php endif ?>
            <?php if ($manage): ?><form class="lc-cover-form" method="post" action="<?= e(url("learn/$cid/cover")) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
              <label class="lc-pill is-btn"><input id="coverFile" type="file" name="cover" accept="image/png,image/jpeg,image/webp,image/gif" data-autosubmit hidden><?= icon('image', 14) ?> <?= empty($c['cover_image']) ? 'Add a cover picture' : 'Change cover' ?></label>
              <?php if (!empty($c['cover_image'])): ?><button class="lc-pill is-btn" type="submit" name="remove" value="1">Remove</button><?php endif ?>
            </form><?php endif ?>
          </div>
          <div class="lc-banner-body">
            <h1><?= e($c['title']) ?></h1>
            <a class="lc-hostline" href="<?= e(url('profile/' . $c['author_id'])) ?>"><?= avatar($c, 30) ?><span>Hosted by <b><?= e($c['full_name']) ?></b></span></a>
          </div>
        </div>
        <div class="lc-hero-body">
          <div class="lc-hero-main">
            <div class="prose lc-hero-desc"><?= rich($c['description']) ?></div>
            <?php if ($classmateCount): ?>
              <div class="lc-crowd"><span class="lc-stack"><?php foreach (array_slice($classmates, 0, 5) as $m) echo avatar($m, 36); ?><?php if ($classmateCount > 5): ?><span class="lc-stack-more">+<?= $classmateCount - 5 ?></span><?php endif ?></span><span><?= $crowd ?></span></div>
            <?php endif ?>
            <dl class="lc-facts">
              <div><dt><?= icon('flag', 14) ?> Steps</dt><dd><?= $stepCount ?></dd></div>
              <div><dt><?= icon('book', 14) ?> Lessons</dt><dd><?= $lessonCount ?></dd></div>
              <div><dt><?= icon('check-circle', 14) ?> Quizzes</dt><dd><?= $quizCount ?></dd></div>
              <div><dt><?= icon('users', 14) ?> Students</dt><dd><?= $students ?></dd></div>
            </dl>
          </div>

        <div class="lc-action">
          <?php if ($manage): ?>
            <span class="lc-action-k">You host this course</span>
            <b class="lc-action-big"><?= $students ?> student<?= $students === 1 ? '' : 's' ?></b>
            <a class="btn btn-primary btn-block" href="<?= e(url("learn/$cid/dashboard")) ?>"><?= icon('chart', 16) ?> Open dashboard</a>
            <a class="btn btn-block" href="<?= e(url("learn/$cid#add-lesson")) ?>"><?= icon('plus', 16) ?> Add a lesson</a>
          <?php elseif ($access): $P = $path; $nS = count($P['steps']); $at = $P['cur'] !== null ? $P['steps'][$P['cur']] : null; ?>
            <?php if (!$nS): ?>
              <span class="lc-action-k">Your path</span><b class="lc-action-big">Coming soon</b><span class="lc-action-next">The host hasn’t added any steps yet.</span>
            <?php elseif ($P['finished']): ?>
              <span class="lc-action-k">Course finished 🎓</span>
              <b class="lc-action-big"><?= $P['runs'] > 1 ? 'Finished ' . $P['runs'] . ' times' : 'All ' . $nS . ' steps done' ?></b>
              <div class="progress" aria-label="100% complete"><i style="width:100%"></i></div>
              <form method="post" action="<?= e(url("learn/$cid/start")) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-block btn-lg" type="submit"><?= icon('refresh', 16) ?> Start again</button></form>
              <span class="lc-action-next">Go through all the steps again in order, or open any step below.</span>
            <?php elseif (!$P['started']): ?>
              <span class="lc-action-k">Your path</span>
              <b class="lc-action-big"><?= $nS ?> step<?= $nS === 1 ? '' : 's' ?>, in order</b>
              <form method="post" action="<?= e(url("learn/$cid/start")) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-block btn-lg" type="submit"><?= icon('play', 16) ?> Start course</button></form>
              <span class="lc-action-next">Step 1 · <?= e(course_step_label($at)) ?>: <?= e(excerpt($at['title'], 60)) ?></span>
            <?php else: ?>
              <span class="lc-action-k"><?= $P['runs'] ? 'Going through it again' : 'Your progress' ?></span>
              <b class="lc-action-big">Step <?= $P['cur'] + 1 ?> of <?= $nS ?></b>
              <div class="progress" aria-label="<?= round($P['cur'] / $nS * 100) ?>% complete"><i style="width:<?= round($P['cur'] / $nS * 100) ?>%"></i></div>
              <a class="btn btn-primary btn-block btn-lg" href="<?= e(url($at['url'])) ?>"><?= icon('play', 16) ?> Continue</a>
              <span class="lc-action-next"><?= e(course_step_label($at)) ?>: <?= e(excerpt($at['title'], 70)) ?></span>
            <?php endif ?>
          <?php elseif ($paid): ?>
            <span class="lc-action-k">One-time payment</span>
            <b class="lc-action-big"><?= e(format_aed((float) $c['price_aed'])) ?></b>
            <a class="btn btn-primary btn-block btn-lg" href="<?= e(url("checkout/course/$cid")) ?>"><?= icon('lock', 16) ?> Buy course</a>
            <div class="mk-paywith"><?= pay_logos_row() ?></div>
            <span class="lc-action-next">Unlocks all lessons, files, quizzes and live sessions.</span>
          <?php else: ?>
            <span class="lc-action-k">Free course</span>
            <b class="lc-action-big">Join in one click</b>
            <form method="post" action="<?= e(url("learn/$cid/enroll")) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-block btn-lg"><?= icon('rocket', 16) ?> Enroll for free</button></form>
            <span class="lc-action-next">Unlocks all lessons, files and quizzes.</span>
          <?php endif ?>
        </div>

        </div>
      </section>

      <section class="card" id="content">
        <div class="card-title"><h2 class="lc-h2">Course content</h2><?php if ($manage && count($outline) > 1): ?><span class="lc-order-hint small" data-order-status aria-live="polite">Drag <span class="lc-grip-mini" aria-hidden="true">⠿</span> to change the order</span><?php endif ?><span class="small muted"><?= $lessonCount ?> lesson<?= $lessonCount === 1 ? '' : 's' ?><?= $summaryCount ? ' · ' . $summaryCount . ' summar' . ($summaryCount === 1 ? 'y' : 'ies') : '' ?><?= $quizCount ? ' · ' . $quizCount . ' quiz' . ($quizCount === 1 ? '' : 'zes') : '' ?></span></div>
        <?php if ($path && $path['steps'] && !$path['finished']): ?><p class="lc-path-note"><?= icon('flag', 15) ?> Go through it in order: each step opens when you finish the one before.</p><?php endif ?>
        <?php if (!$lessons && !$quizCount): ?>
          <p class="muted"><?= $manage ? 'Nothing here yet. Add your first lesson below, then drag steps into the order students should follow.' : 'The host hasn’t added lessons yet.' ?></p>
        <?php endif ?>
        <ol class="lc-outline<?= $path ? ' is-path' : '' ?>"<?= $manage ? ' data-reorder="/learn/' . $cid . '/order"' : '' ?>>
          <?php foreach ($outline as $it):
            $r = $it['row']; $isQuiz = $it['type'] === 'quiz';
            if ($isQuiz && !$manage && $r['status'] !== 'published') continue;
            $isSum = !$isQuiz && $r['kind'] === 'summary';
            $key = ($isQuiz ? 'q' : 'l') . $r['id'];
            $si = $path ? course_step_index($path['steps'], $key) : null;
            $isD = $path && !empty($path['done'][$key]);
            $isNext = $path && !$path['finished'] && $si !== null && $si === $path['cur'];
            $locked = !$access || ($path && $si !== null && !course_step_open($path, $si));
            $nf = $isQuiz ? 0 : ($matsByLesson[(int) $r['id']] ?? 0);
            $state = $locked ? icon('lock', 15) . ' Locked' : ($isNext ? ($path['started'] ? 'Up next' : 'Start here') : ($isQuiz && !$manage && $r['best'] !== null ? 'Best ' . (int) $r['best'] . '%' : ($isD ? 'Done' : ($isQuiz && !$manage ? 'Take quiz' : 'Open')))); ?>
            <li data-key="<?= $key ?>" class="lc-row<?= $isQuiz ? ' is-quiz' : '' ?><?= $isSum ? ' is-summary' : '' ?><?= $isD ? ' is-done' : '' ?><?= $isNext ? ' is-next' : '' ?><?= $locked ? ' is-locked' : '' ?>">
              <?php if ($manage): ?><button class="lc-grip" type="button" data-grip aria-label="Move <?= e($r['title']) ?>. Drag, or use the arrow keys." title="Drag to move"><svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true"><g fill="currentColor"><circle cx="4" cy="3" r="1.6"/><circle cx="10" cy="3" r="1.6"/><circle cx="4" cy="9" r="1.6"/><circle cx="10" cy="9" r="1.6"/><circle cx="4" cy="15" r="1.6"/><circle cx="10" cy="15" r="1.6"/></g></svg></button><?php endif ?>
              <a class="lc-row-link" href="<?= e(url($isQuiz ? "learn/$cid/quiz/{$r['id']}" : "learn/$cid/lesson/{$r['id']}")) ?>">
                <span class="lc-row-n"><?= $isD ? icon('check', 16) : ($isQuiz ? icon('check-circle', 16) : ($isSum ? icon('bulb', 16) : '<span data-lnum>' . (int) $r['position'] . '</span>')) ?></span>
                <span class="lc-row-body"><b><?= e($r['title']) ?></b>
                  <span class="lc-row-meta"><?php if ($si !== null): ?><span class="lc-stepno">Step <?= $si + 1 ?></span><?php endif ?>
                    <?php if ($isQuiz): ?><span>Quiz</span><span><?= (int) $r['questions'] ?> question<?= (int) $r['questions'] === 1 ? '' : 's' ?></span><span><?= $r['time_limit_min'] ? (int) $r['time_limit_min'] . ' min' : 'No time limit' ?></span><span>Pass <?= (int) $r['pass_percent'] ?>%</span><?php if ($r['status'] !== 'published'): ?><span class="lc-tag draft">Draft · only you</span><?php endif ?>
                    <?php elseif ($isSum): ?><span>Summary · key points</span>
                    <?php else: ?><span>Lesson <span data-lnum><?= (int) $r['position'] ?></span></span><?php if ($r['video_url']): ?><span><?= icon('play', 13) ?> Video</span><?php endif ?><?php endif ?>
                    <?php if ($nf): ?><span><?= icon('download', 13) ?> <?= $nf ?> file<?= $nf === 1 ? '' : 's' ?></span><?php endif ?></span></span>
                <span class="lc-row-state"><?= $state ?></span>
              </a>
              <?php if ($manage && $isQuiz): ?><span class="lc-row-tools">
                <form method="post" action="<?= e(url("learn/$cid/quiz/{$r['id']}/test")) ?>"><?= csrf_field() ?><button class="btn btn-sm" type="submit" title="Take it like a student. Not counted."><?= icon('play', 13) ?> Test</button></form>
                <a class="btn btn-sm" href="<?= e(url("learn/$cid/quiz/{$r['id']}/edit")) ?>">Edit</a><a class="btn btn-sm" href="<?= e(url("learn/$cid/quiz/{$r['id']}/results")) ?>">Results</a></span>
              <?php elseif ($manage): ?><form method="post" action="<?= e(url("learn/$cid/delete")) ?>" data-confirm="Delete “<?= e($r['title']) ?>”?"><?= csrf_field() ?><input type="hidden" name="lesson" value="<?= (int) $r['id'] ?>"><button class="icon-btn" aria-label="Delete <?= e($r['title']) ?>"><?= icon('trash', 15) ?></button></form><?php endif ?>
            </li>
          <?php endforeach ?>
        </ol>
        <?php if ($manage): ?><div class="row wrap" style="margin-top:12px"><a class="btn btn-sm" href="<?= e(url("learn/$cid#add-lesson")) ?>"><?= icon('plus', 14) ?> Add lesson</a><a class="btn btn-sm" href="<?= e(url("learn/$cid?kind=summary#add-lesson")) ?>"><?= icon('bulb', 14) ?> Add summary</a><form method="post" action="<?= e(url("learn/$cid/quiz/new")) ?>"><?= csrf_field() ?><button class="btn btn-sm"><?= icon('plus', 14) ?> Add quiz</button></form></div><?php endif ?>
        <?php if (!$access && ($lessons || $quizCount)): ?><p class="small muted" style="margin-top:10px"><?= icon('lock', 14) ?> <?= $paid ? 'Buy the course' : 'Enroll' ?> to open the lessons and quizzes.</p><?php endif ?>
      </section>

      <?php if ($courseMats || $manage): ?>
      <section class="card" id="materials">
        <div class="card-title"><h2 class="lc-h2">Files &amp; links</h2><span class="small muted">for the whole course</span></div>
        <?php if ($courseMats): ?><?php partial('materials', ['mats' => $courseMats, 'cid' => $cid, 'access' => $access, 'manage' => $manage]) ?><?php else: ?><p class="muted small">No course-wide files yet<?= $materials ? ' (some lessons have their own)' : '' ?>. Add guides or links below.</p><?php endif ?>
      </section>
      <?php endif ?>

      <?php if ($manage): ?>
        <form class="card" id="add-material" method="post" action="<?= e(url("learn/$cid/materials")) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
          <div class="card-title"><h3><?= icon('paperclip', 18) ?> Add files &amp; links</h3></div>
          <div class="field"><label class="f">Show them in</label><select class="select" name="lesson_id"><option value="">The whole course</option><?php foreach ($lessons as $l): ?><option value="<?= (int) $l['id'] ?>"><?= $l['kind'] === 'summary' ? 'Summary' : 'Lesson ' . (int) $l['position'] ?>: <?= e(excerpt($l['title'], 40)) ?></option><?php endforeach ?></select></div>
          <?php partial('attach_fields') ?>
          <button class="btn btn-primary mt" type="submit"><?= icon('plus', 16) ?> Add</button>
          <?php if ($paid): ?><p class="hint" style="margin-top:8px">Files are private: only people who bought the course can open them.</p><?php endif ?>
        </form>

        <form class="card" id="add-lesson" method="post" action="<?= e(url("learn/$cid/lessons")) ?>" enctype="multipart/form-data" data-ai-writing-form data-ai-context="lesson"><?= csrf_field() ?>
          <div class="card-title"><h3><?= icon('plus', 18) ?> Add a step</h3></div>
          <div class="lc-switch" data-switch><label><input type="radio" name="kind" value="lesson"<?= input('kind') !== 'summary' ? ' checked' : '' ?>><span>Lesson</span></label><label><input type="radio" name="kind" value="summary"<?= input('kind') === 'summary' ? ' checked' : '' ?>><span><?= icon('bulb', 14) ?> Summary</span></label></div>
          <p class="hint" data-when="summary" style="margin:-4px 0 12px">A short page of the most important points. Students get it as its own step, wherever you place it (for example right after a quiz).</p>
          <div class="field"><label class="f">Title</label><input class="input" name="title" data-ai-writing="title" required maxlength="200" placeholder="e.g. Joins in SQL"></div>
          <div class="field"><label class="f">Content</label><textarea class="textarea" name="content" data-ai-writing="body" rows="6" required placeholder="Write the lesson or the key points. Line breaks and links are supported."></textarea></div>
          <div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
          <div class="field" data-when="lesson"><label class="f">Main video (optional: YouTube, Vimeo or any site)</label><input class="input" name="video_url" placeholder="https://www.youtube.com/watch?v=…"></div>
          <label class="f">Images, files &amp; links (optional)</label>
          <?php partial('attach_fields') ?>
          <div class="row wrap mt"><button class="btn btn-primary" type="submit"><?= icon('plus', 16) ?> Add to the course</button>          <div class="row wrap"><button class="btn btn-primary" type="submit"><?= icon('plus', 16) ?> Add to the course</button><span class="small muted">It goes to the end of Course content. Drag it to where it belongs.</span></div>
        </form>
      <?php endif ?>
    <?php endif ?>
  </div>

  <aside class="aside">
    <?php if ($lesson): ?>
    <div class="card" id="lessons-list"><div class="card-title"><h3>Course path</h3><span class="chip sm"><?= $stepCount ?> step<?= $stepCount === 1 ? '' : 's' ?></span></div>
      <div class="lesson-list"><?php $n = 0; foreach ($outline as $it): $r = $it['row']; $isQuiz = $it['type'] === 'quiz';
          if ($isQuiz && !$manage && $r['status'] !== 'published') continue;
          $key = ($isQuiz ? 'q' : 'l') . $r['id']; $si = $path ? course_step_index($path['steps'], $key) : null;
          if ($path && $si === null) continue;
          $n++; $d = $path && !empty($path['done'][$key]); $lk = $path && !course_step_open($path, $si);
          $on = !$isQuiz && (int) $lesson['id'] === (int) $r['id']; ?>
        <a class="<?= $on ? 'on' : '' ?><?= $lk ? ' is-locked' : '' ?>" href="<?= e(url($isQuiz ? "learn/$cid/quiz/{$r['id']}" : "learn/$cid/lesson/{$r['id']}")) ?>"><span class="lesson-num <?= $d ? 'done' : '' ?>"><?= $d ? icon('check', 15) : ($lk ? icon('lock', 13) : $n) ?></span><span class="grow small"><b><?= e($r['title']) ?></b><span class="xs muted lc-side-kind"><?= $isQuiz ? 'Quiz' : ($r['kind'] === 'summary' ? 'Summary' : 'Lesson') ?></span></span></a>
      <?php endforeach ?><?php if (!$n): ?><p class="muted small">No steps yet.</p><?php endif ?></div></div>
    <?php endif ?>
    <div class="card"><div class="card-title"><h3>Host</h3></div>
      <div class="person"><a href="<?= e(url('profile/' . $c['author_id'])) ?>"><?= avatar($c, 50) ?></a><div class="grow"><a class="nm" href="<?= e(url('profile/' . $c['author_id'])) ?>"><?= e($c['full_name']) ?></a><?= verified_badge($c) ?><div class="xs muted"><?= e($c['headline'] ?: 'Course host') ?></div></div></div>
      <?php if (!$manage && (int) $c['author_id'] !== (int) $u['id']): ?><a class="btn btn-sm btn-block mt" href="<?= e(url('messages/' . $c['author_id'])) ?>"><?= icon('message', 14) ?> Message host</a><?php endif ?>
      <?php if ($manage): ?><form method="post" action="<?= e(url("learn/$cid/delete")) ?>" data-confirm="Delete the whole course, its quizzes and files?" class="mt"><?= csrf_field() ?><button class="btn btn-danger btn-sm btn-block"><?= icon('trash', 14) ?> Delete course</button></form><?php endif ?></div>
    <?php if ($classmateCount && $access): ?>
    <div class="card lc-mates"><div class="card-title"><h3>Classmates</h3><span class="chip sm"><?= $classmateCount ?></span></div>
      <p class="small muted lc-mates-sub"><?= $meIn ? 'You’re learning together with ' . ($classmateCount - 1) . ' other' . ($classmateCount - 1 === 1 ? '' : 's') . '.' : 'People taking this course.' ?></p>
      <?php foreach ($classmates as $m): $isMe = (int) $m['id'] === (int) $u['id']; ?>
        <a class="lc-mate" href="<?= e(url('profile/' . $m['id'])) ?>"><?= avatar($m, 40) ?><span class="grow"><b><?= e($m['full_name']) ?><?= $isMe ? ' <span class="muted">(you)</span>' : '' ?></b><span><?= e($m['major'] ?: 'Student') ?></span></span>
          <?= $m['completed_at'] ? '<span class="lc-mate-done" title="Finished the course">🎓 Finished</span>' : '<span class="lc-mate-when">' . e(time_ago($m['joined_at'])) . '</span>' ?></a>
      <?php endforeach ?>
      <?php if ($classmateCount > count($classmates)): ?><p class="small muted" style="margin:8px 0 0">+ <?= $classmateCount - count($classmates) ?> more</p><?php endif ?>
    </div>
    <?php endif ?>
    <?php if ($live && $access && !$manage): ?><div class="card"><div class="card-title"><h3><span class="lc-live-dot" style="display:inline-block;margin-right:6px"></span>Live quiz now</h3></div><p class="small muted"><?= e($live['title']) ?></p><a class="btn btn-primary btn-block" href="<?= e(url('live/' . $live['code'])) ?>">Join now</a></div><?php endif ?>
  </aside>
</div>
