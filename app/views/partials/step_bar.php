<?php
/** The course path on a step page: "Step 3 of 6", one segment per step (done / here / locked), and the step before. Needs $cid, $path, $i. */
$steps = $path['steps'];
$n = count($steps);
$prev = $steps[$i - 1] ?? null;
?>
<nav class="lc-steps" aria-label="Course path">
  <div class="lc-steps-top">
    <b>Step <?= $i + 1 ?> of <?= $n ?></b><span class="muted"> · <?= e(course_step_label($steps[$i])) ?></span>
    <?php if ($prev): ?><a class="lc-steps-prev" href="<?= e(url($prev['url'])) ?>"><?= icon('chevron-right', 14, 'flip') ?> Previous: <?= e(excerpt($prev['title'], 40)) ?></a><?php endif ?>
  </div>
  <ol class="lc-steps-bar">
    <?php foreach ($steps as $k => $s): $open = course_step_open($path, $k); $cls = $k === $i ? 'is-here' : (!empty($path['done'][$s['key']]) ? 'is-done' : ($open ? '' : 'is-locked'));
      $label = 'Step ' . ($k + 1) . ' · ' . course_step_label($s) . ': ' . $s['title'] . ($open ? '' : ' (locked)'); ?>
      <li class="<?= $cls ?> t-<?= $s['type'] ?>"><?php if ($open && $k !== $i): ?><a href="<?= e(url($s['url'])) ?>" title="<?= e($label) ?>" aria-label="<?= e($label) ?>"></a><?php else: ?><span title="<?= e($label) ?>" aria-label="<?= e($label) ?>"<?= $k === $i ? ' aria-current="step"' : '' ?>></span><?php endif ?></li>
    <?php endforeach ?>
  </ol>
</nav>
