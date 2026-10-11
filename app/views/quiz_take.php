<?php
$title = $a['quiz_title'];
$aid = (int) $a['id'];
?>
<?php partial('learn_assets') ?>
<div class="qt" id="qt" data-attempt="<?= $aid ?>" data-remaining="<?= $remaining === null ? '' : (int) $remaining ?>">
  <div class="qt-top">
    <b><?= e(excerpt($a['quiz_title'], 40)) ?></b><?php if (!empty($a['is_preview'])): ?><span class="lc-tag draft">Test run</span><?php endif ?>
    <div class="qt-bar" aria-hidden="true"><i style="width:0"></i></div>
    <span class="small muted" data-count></span>
    <span class="qt-save" aria-live="polite"></span>
    <?php if ($remaining !== null): ?><span class="qt-timer" role="timer" aria-label="Time left">--:--</span><?php endif ?>
  </div>
  <form id="qtForm" method="post" action="<?= e(url("quiz/attempt/$aid/submit")) ?>"><?= csrf_field() ?>
    <?php foreach ($questions as $i => $q): $s = $saved[(int) $q['id']] ?? null; ?>
      <section class="qt-card" data-q="<?= (int) $q['id'] ?>" data-type="<?= e($q['type']) ?>" aria-label="Question <?= $i + 1 ?>">
        <div class="qt-k"><span>Question <?= $i + 1 ?> of <?= count($questions) ?></span><span class="muted"><?= (int) $q['points'] ?> point<?= $q['points'] == 1 ? '' : 's' ?></span></div>
        <p class="qt-prompt"><?= e($q['prompt']) ?></p>
        <?php if ($q['type'] === 'mcq'): ?>
          <div class="qt-opts" role="radiogroup">
            <?php foreach ($q['options'] as $k => $o): ?>
              <label class="qt-opt"><input type="radio" name="q<?= (int) $q['id'] ?>" value="<?= $k ?>" <?= $s && $s['choice'] !== null && (int) $s['choice'] === $k ? 'checked' : '' ?>><span class="qt-key"><?= $k + 1 ?></span><span><?= e($o) ?></span></label>
            <?php endforeach ?>
          </div>
        <?php else: ?>
          <label class="sr" for="e<?= (int) $q['id'] ?>">Your answer</label>
          <textarea class="textarea qt-essay" id="e<?= (int) $q['id'] ?>" name="q<?= (int) $q['id'] ?>" placeholder="Write your answer…"><?= e((string) ($s['essay'] ?? '')) ?></textarea>
        <?php endif ?>
      </section>
    <?php endforeach ?>
    <div class="qt-nav">
      <button class="btn btn-lg" type="button" data-prev><?= icon('chevron-right', 16, 'flip') ?> Back</button>
      <button class="btn btn-primary btn-lg" type="button" data-next>Next <?= icon('arrow-right', 16) ?></button>
      <button class="btn btn-primary btn-lg" type="button" data-finish hidden><?= icon('check', 16) ?> Submit answers</button>
    </div>
  </form>
  <div class="qt-dots" aria-label="Jump to a question"><?php foreach ($questions as $i => $q): ?><button type="button" aria-label="Question <?= $i + 1 ?>"><?= $i + 1 ?></button><?php endforeach ?></div>
</div>
