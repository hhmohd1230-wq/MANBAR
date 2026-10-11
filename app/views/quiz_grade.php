<?php
$title = 'Mark · ' . $a['quiz_title'];
$cid = (int) $c['id'];
$essays = array_values(array_filter($questions, fn($q) => $q['type'] === 'essay'));
$mcqScore = (int) $a['auto_score'];
$mcqMax = array_sum(array_map(fn($q) => $q['type'] === 'mcq' ? (int) $q['points'] : 0, $questions));
?>
<?php partial('learn_assets') ?>
<div class="qt">
<?php partial('back_button', ['href' => "learn/$cid/quiz/{$a['quiz_id']}/results", 'label' => 'Back to results']) ?>

  <div class="card" style="margin-top:10px"><div class="row"><?= avatar($student, 44) ?><div class="grow"><h2 style="margin:0"><?= e($student['full_name']) ?></h2><span class="small muted">Submitted <?= e(date('M j, H:i', strtotime((string) $a['submitted_at']))) ?> · multiple choice <?= $mcqScore ?>/<?= $mcqMax ?> (auto-marked)</span></div></div></div>

  <form method="post" action="<?= e(url("quiz/attempt/{$a['id']}/grade")) ?>"><?= csrf_field() ?>
    <?php if (!$essays): ?><div class="card" style="margin-top:14px"><p class="muted">This quiz has no written answers: everything was marked automatically.</p></div><?php endif ?>
    <?php foreach ($essays as $q): $ans = $answers[(int) $q['id']] ?? null; ?>
      <div class="qr-item <?= $ans && $ans['points'] !== null ? 'ok' : 'wait' ?>" style="margin-top:14px">
        <div class="qt-k"><span>Written answer</span><span class="muted">out of <?= (int) $q['points'] ?></span></div>
        <p style="font-weight:600;margin:0 0 8px"><?= e($q['prompt']) ?></p>
        <div class="qr-essay"><?= $ans && trim((string) $ans['essay']) !== '' ? e($ans['essay']) : '<span class="muted">No answer written.</span>' ?></div>
        <?php if ($q['keywords']): ?><p class="qr-feedback">Keywords: <?= e($q['keywords']) ?><?= $ans && $ans['auto_marked'] && $ans['points'] !== null ? ' · suggested ' . (int) $ans['points'] . '/' . (int) $q['points'] : '' ?></p><?php endif ?>
        <div class="grid2" style="grid-template-columns:140px 1fr;margin-top:10px">
          <div class="field"><label class="f" for="p<?= (int) $q['id'] ?>">Mark</label><input class="input" id="p<?= (int) $q['id'] ?>" type="number" name="p<?= (int) $q['id'] ?>" min="0" max="<?= (int) $q['points'] ?>" value="<?= $ans && $ans['points'] !== null ? (int) $ans['points'] : '' ?>" required></div>
          <div class="field"><label class="f" for="f<?= (int) $q['id'] ?>">Feedback for the student (optional)</label><input class="input" id="f<?= (int) $q['id'] ?>" name="f<?= (int) $q['id'] ?>" maxlength="800" value="<?= e($ans && !$ans['auto_marked'] ? (string) $ans['feedback'] : '') ?>" placeholder="Good structure. Mention the border next time."></div>
        </div>
      </div>
    <?php endforeach ?>
    <?php if ($essays): ?><div class="row" style="margin-top:16px"><button class="btn btn-primary btn-lg" type="submit"><?= icon('check', 18) ?> Save marks &amp; notify student</button><a class="btn" href="<?= e(url("quiz/attempt/{$a['id']}/result")) ?>">View full attempt</a></div><?php endif ?>
  </form>
</div>
