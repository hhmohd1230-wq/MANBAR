<?php
$title = 'Result · ' . $a['quiz_title'];
$cid = (int) $c['id'];
$score = (int) $a['auto_score'] + (int) $a['essay_score'];
$pct = attempt_percent($a);
$pending = $a['status'] === 'submitted';
$pass = $pct >= (int) $a['pass_percent'];
$test = !empty($a['is_preview']);
// essays still waiting for a mark (in a test run, these are the ones the host would mark by hand)
$open = 0;
foreach ($questions as $q) if ($q['type'] === 'essay' && (($answers[(int) $q['id']]['points'] ?? null) === null)) $open += (int) $q['points'];
$bestPct = $a['max_score'] ? (int) round(($score + $open) / (int) $a['max_score'] * 100) : 0;
?>
<?php partial('learn_assets') ?>
<div class="qt">
<?php partial('back_button', ['href' => $isOwner ? "learn/$cid/quiz/{$a['quiz_id']}" : "learn/$cid/quiz/{$a['quiz_id']}/results", 'label' => $isOwner ? 'Back to quiz' : 'Back to results']) ?>

  <?php if ($test): ?>
    <div class="lc-hostbar" style="margin:10px 0 0"><b><?= icon('check-circle', 15) ?> Test run</b><span class="grow small" style="opacity:.9">This is how students see the quiz and how it marks them. Not saved to results, averages or attempts.</span>
      <form method="post" action="<?= e(url("learn/$cid/quiz/{$a['quiz_id']}/test")) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-primary" type="submit"><?= icon('play', 14) ?> Test again</button></form>
      <a class="btn btn-sm" href="<?= e(url("learn/$cid/quiz/{$a['quiz_id']}/edit")) ?>"><?= icon('edit', 14) ?> Back to editor</a></div>
  <?php endif ?>
  <div class="card" style="margin-top:10px;padding:26px">
    <div class="qr-head">
      <div class="qr-ring <?= !$pending && !$pass ? 'fail' : '' ?>" style="--p:<?= $pct ?>"><div><span><b><?= $pct ?>%</b><br><small><?= $score ?>/<?= (int) $a['max_score'] ?></small></span></div></div>
      <div>
        <?php if (!$isOwner): ?><div class="row" style="margin-bottom:6px"><?= avatar($student, 30) ?><b><?= e($student['full_name']) ?></b></div><?php endif ?>
        <?php if ($test): ?>
          <h1 style="margin:0 0 6px">You’d get <?= $score ?>/<?= (int) $a['max_score'] ?> (<?= $pct ?>%)</h1>
          <p class="muted" style="margin:0"><?= $open ? 'Up to <b>' . ($score + $open) . '/' . (int) $a['max_score'] . ' (' . $bestPct . '%)</b> once you mark the written answers: essays without keywords are marked by you. ' : '' ?>The pass mark is <?= (int) $a['pass_percent'] ?>%: this run would <b><?= $pass ? 'pass' : ($bestPct >= $a['pass_percent'] ? 'pass only with good essay marks' : 'not pass') ?></b>.</p>
        <?php else: ?>
        <h1 style="margin:0 0 6px"><?= $pending ? 'Submitted: waiting for marking' : ($pass ? 'Passed 🎉' : 'Not passed this time') ?></h1>
        <p class="muted" style="margin:0"><?= $pending ? 'Multiple-choice answers are marked. Your host will mark the written answers, and you’ll get a notification.' : ($pass ? 'Great work. You reached the ' . (int) $a['pass_percent'] . '% pass mark.' : 'The pass mark is ' . (int) $a['pass_percent'] . '%. Review the answers below and try again.') ?></p>
        <?php endif ?>
        <div class="row wrap" style="margin-top:14px">
          <?php if (!$isOwner && course_can_manage($c, $u)): ?><a class="btn btn-primary" href="<?= e(url("quiz/attempt/{$a['id']}/grade")) ?>"><?= icon('edit', 16) ?> Mark / change marks</a><?php endif ?>
          <?php if ($nextStep !== null): ?><form method="post" action="<?= e(url("learn/$cid/next")) ?>"><?= csrf_field() ?><input type="hidden" name="from" value="q<?= (int) $a['quiz_id'] ?>"><button class="btn btn-primary" type="submit"><?= $nextStep ? icon('arrow-right', 16) . ' Continue: ' . e(excerpt($nextStep['title'], 40)) : icon('flag', 16) . ' Finish the course' ?></button></form><?php endif ?>
          <?php if (!$pending && !$pass && $isOwner && !$test): ?><a class="btn" href="<?= e(url("learn/$cid/quiz/{$a['quiz_id']}")) ?>"><?= icon('refresh', 16) ?> Try again</a><?php endif ?>
          <a class="btn" href="<?= e(url("learn/$cid")) ?>">Back to course</a>
        </div>
      </div>
    </div>
  </div>

  <h3 style="margin:22px 0 10px">Answers</h3>
  <?php foreach ($questions as $i => $q): $ans = $answers[(int) $q['id']] ?? null;
    $marked = $ans && $ans['points'] !== null;   // essays have no points until auto-marked or marked by the host
    $state = $q['type'] === 'essay' ? ($marked ? ((int) $ans['points'] >= $q['points'] * .5 ? 'ok' : 'bad') : 'wait') : ($ans && $ans['is_correct'] ? 'ok' : 'bad'); ?>
    <div class="qr-item <?= $state ?>">
      <div class="qt-k"><span>Question <?= $i + 1 ?></span><span class="muted"><?= $ans && $ans['points'] !== null ? (int) $ans['points'] : 0 ?>/<?= (int) $q['points'] ?> pts</span></div>
      <p style="font-weight:600;margin:0 0 8px"><?= e($q['prompt']) ?></p>
      <?php if ($q['type'] === 'mcq'): ?>
        <?php foreach ($q['options'] as $k => $o): $mine = $ans && $ans['choice'] !== null && (int) $ans['choice'] === $k; $right = $k === (int) $q['correct_index']; ?>
          <?php if ($mine || $right): ?><div class="qr-opt <?= $right ? 'right' : 'wrong' ?>"><?= $right ? '✔' : '✘' ?> <?= e($o) ?><?= $mine ? ' <span class="small muted">(your answer)</span>' : '' ?></div><?php endif ?>
        <?php endforeach ?>
        <?php if (!$ans || $ans['choice'] === null): ?><p class="small muted">Not answered.</p><?php endif ?>
      <?php else: ?>
        <div class="qr-essay"><?= $ans && trim((string) $ans['essay']) !== '' ? e($ans['essay']) : '<span class="muted">No answer written.</span>' ?></div>
        <?php if ($ans && $ans['feedback']): ?><p class="qr-feedback"><?= icon('chat', 14) ?> <?= e($ans['feedback']) ?></p><?php elseif ($state === 'wait'): ?><p class="qr-feedback"><?= icon('clock', 14) ?> <?= $test ? 'You mark this one for students (worth ' . (int) $q['points'] . ' pts). Add keywords to auto-mark it.' : 'Waiting for your host to mark this.' ?></p><?php endif ?>
      <?php endif ?>
      <?php if ($q['explanation']): ?><p class="qr-feedback"><?= icon('info', 14) ?> <?= e($q['explanation']) ?></p><?php endif ?>
    </div>
  <?php endforeach ?>
</div>
