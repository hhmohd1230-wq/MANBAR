<?php
$title = $quiz['title'];
$cid = (int) $c['id'];
$base = "learn/$cid/quiz/{$quiz['id']}";
$pts = array_sum(array_column($questions, 'points'));
$essays = count(array_filter($questions, fn($q) => $q['type'] === 'essay'));
$finished = array_values(array_filter($attempts, fn($a) => $a['status'] !== 'open'));
$best = $finished ? max(array_map('attempt_percent', $finished)) : null;
// the course path: where this quiz sits, and the "Continue" button once it's done (the host's Student view can always walk on)
$si = $path ? course_step_index($path['steps'], 'q' . $quiz['id']) : null;
$nx = $si !== null ? ($path['steps'][$si + 1] ?? null) : null;
$canGo = $si !== null && ($finished || !empty($path['preview']));
?>
<?php partial('learn_assets') ?>
<div class="qt">
<?php partial('back_button', ['href' => "learn/$cid#quizzes", 'label' => 'Back to ' . $c['title']]) ?>
<?php if (!empty($studentView)): ?><?php partial('student_view_bar', ['cid' => $cid]) ?><?php endif ?>
<?php if ($si !== null) partial('step_bar', ['cid' => $cid, 'path' => $path, 'i' => $si]); ?>

  <div class="card" style="margin-top:10px;padding:28px">
    <?php if ($quiz['status'] !== 'published'): ?><span class="lc-tag draft">Draft preview (students can’t see this yet)</span><?php endif ?>
    <h1 style="margin:8px 0"><?= e($quiz['title']) ?></h1>
    <?php if ($quiz['description']): ?><div class="prose" style="font-size:15.5px"><?= rich($quiz['description']) ?></div><?php endif ?>
    <div class="kpis" style="margin:18px 0 8px">
      <div class="kpi tone-green"><b><?= count($questions) ?></b><span>questions · <?= $pts ?> points</span></div>
      <div class="kpi tone-blue"><b><?= $quiz['time_limit_min'] ? (int) $quiz['time_limit_min'] . ' min' : '∞' ?></b><span><?= $quiz['time_limit_min'] ? 'time limit' : 'no time limit' ?></span></div>
      <div class="kpi tone-amber"><b><?= (int) $quiz['pass_percent'] ?>%</b><span>to pass</span></div>
      <div class="kpi tone-violet"><b><?= $left === null ? '∞' : $left ?></b><span>attempt<?= $left === 1 ? '' : 's' ?> left</span></div>
    </div>
    <?php if ($essays): ?><p class="small muted"><?= icon('edit', 14) ?> <?= $essays ?> written answer<?= $essays === 1 ? '' : 's' ?>: <?= $quiz['essay_auto'] ? 'some may be auto-marked; ' : '' ?>your host may mark them after you submit.</p><?php endif ?>
    <p class="small muted">Your answers save automatically. <?= $quiz['time_limit_min'] ? 'When the timer reaches zero, the quiz submits itself.' : '' ?> Use keys 1–6 to pick an answer and ← → to move.</p>
    <div class="row wrap" style="margin-top:16px">
      <?php if ($open): ?><a class="btn btn-primary btn-lg" href="<?= e(url('quiz/attempt/' . $open['id'])) ?>"><?= icon('play', 18) ?> Continue your attempt</a>
      <?php elseif (!$questions && !$manage): ?><span class="muted">No questions yet.</span>
      <?php elseif ($left === 0 && !$manage): ?><span class="chip">You’ve used all attempts</span>
      <?php else: ?><form method="post" action="<?= e(url("$base/start")) ?>"><?= csrf_field() ?><button class="btn <?= $canGo && $finished ? '' : 'btn-primary' ?> btn-lg" type="submit"><?= icon('play', 18) ?> <?= $manage ? 'Test quiz (not counted)' : ($finished ? 'Try again' : 'Start quiz') ?></button></form><?php endif ?>
      <?php if ($canGo && !$open): ?><form method="post" action="<?= e(url("learn/$cid/next")) ?>"><?= csrf_field() ?><input type="hidden" name="from" value="q<?= (int) $quiz['id'] ?>"><button class="btn <?= $finished ? 'btn-primary' : '' ?> btn-lg" type="submit"><?= $nx ? icon('arrow-right', 18) . ' Continue to the next step' : icon('flag', 18) . ($path['finished'] ? ' Back to the course' : ' Finish the course') ?></button></form><?php endif ?>
      <?php if ($manage): ?><a class="btn" href="<?= e(url("$base/edit")) ?>"><?= icon('edit', 16) ?> Edit</a><a class="btn" href="<?= e(url("$base/results")) ?>"><?= icon('chart', 16) ?> Results</a><?php endif ?>
    </div>
    <?php if ($canGo && !$open): ?><p class="small muted" style="margin:10px 0 0"><?= $nx ? 'Next: ' . e(course_step_label($nx)) . ' · ' . e(excerpt($nx['title'], 60)) : 'This quiz is the last step of the course.' ?><?= !empty($path['preview']) ? ' · Preview: students continue once they’ve submitted the quiz.' : '' ?></p><?php endif ?>
  </div>

  <?php if ($finished): ?>
    <div class="card" style="margin-top:16px"><div class="card-title"><h3>Your attempts</h3><?php if ($best !== null): ?><span class="lc-tag best">Best <?= $best ?>%</span><?php endif ?></div>
      <div class="table-wrap"><table class="t"><thead><tr><th>Date</th><th>Score</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($finished as $a): $p = attempt_percent($a); ?>
          <tr><td><?= e(date('M j, H:i', strtotime($a['submitted_at'] ?: $a['started_at']))) ?></td><td class="db-num"><?= (int) $a['auto_score'] + (int) $a['essay_score'] ?>/<?= (int) $a['max_score'] ?> · <?= $p ?>%</td>
            <td><?= $a['status'] === 'submitted' ? '<span class="lc-tag draft">Waiting for marking</span>' : ($p >= $quiz['pass_percent'] ? '<span class="lc-tag">Passed</span>' : '<span class="lc-tag" style="background:#fdecef;color:#a3304d">Not passed</span>') ?></td>
            <td><a class="btn btn-sm" href="<?= e(url('quiz/attempt/' . $a['id'] . '/result')) ?>">Review</a></td></tr>
        <?php endforeach ?>
      </tbody></table></div></div>
  <?php endif ?>
</div>
