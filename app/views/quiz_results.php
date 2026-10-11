<?php
$title = 'Results · ' . $quiz['title'];
$cid = (int) $c['id'];
$base = "learn/$cid/quiz/{$quiz['id']}";
$graded = array_values(array_filter($attempts, fn($a) => $a['status'] === 'graded'));
$avg = $graded ? (int) round(array_sum(array_map('attempt_percent', $graded)) / count($graded)) : null;
$passed = count(array_filter($graded, fn($a) => attempt_percent($a) >= (int) $quiz['pass_percent']));
$toMark = count(array_filter($attempts, fn($a) => $a['status'] === 'submitted'));
?>
<?php partial('learn_assets') ?>
<?php partial('back_button', ['href' => "learn/$cid/dashboard", 'label' => 'Back to dashboard']) ?>
<div class="pagehead"><div><h1><?= e($quiz['title']) ?>: results</h1></div>
  <div class="row wrap"><a class="btn" href="<?= e(url("$base/edit")) ?>"><?= icon('edit', 16) ?> Edit quiz</a></div></div>

<div class="kpis">
  <div class="kpi tone-green"><b><?= count($attempts) ?></b><span>submitted attempts</span></div>
  <div class="kpi tone-blue"><b><?= $avg === null ? '–' : $avg . '%' ?></b><span>average score</span></div>
  <div class="kpi tone-amber"><b><?= $graded ? round($passed / count($graded) * 100) . '%' : '–' ?></b><span>pass rate (<?= (int) $quiz['pass_percent'] ?>%+)</span></div>
  <div class="kpi tone-rose"><b><?= $toMark ?></b><span>waiting for marking</span></div>
</div>

<div class="card mb"><div class="card-title"><h3>Attempts</h3></div>
  <?php if ($attempts): ?><div class="table-wrap"><table class="t"><thead><tr><th>Student</th><th>Submitted</th><th>Time taken</th><th>Score</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($attempts as $a): $p = attempt_percent($a); $mins = $a['submitted_at'] ? max(1, round((strtotime($a['submitted_at']) - strtotime($a['started_at'])) / 60)) : null; ?>
      <tr><td><div class="row gap-s"><?= avatar($a, 28) ?><b><?= e($a['full_name']) ?></b></div></td><td><?= e(date('M j, H:i', strtotime($a['submitted_at']))) ?></td><td><?= $mins ? $mins . ' min' : '–' ?></td>
        <td><div class="db-pct"><span class="db-num"><?= $p ?>%</span><div class="progress"><i style="width:<?= $p ?>%"></i></div></div></td>
        <td><?= $a['status'] === 'submitted' ? '<span class="lc-tag draft">To mark</span>' : ($p >= $quiz['pass_percent'] ? '<span class="lc-tag">Passed</span>' : '<span class="lc-tag" style="background:#fdecef;color:#a3304d">Not passed</span>') ?></td>
        <td><a class="btn btn-sm <?= $a['status'] === 'submitted' ? 'btn-primary' : '' ?>" href="<?= e(url('quiz/attempt/' . $a['id'] . ($a['status'] === 'submitted' ? '/grade' : '/result'))) ?>"><?= $a['status'] === 'submitted' ? 'Mark' : 'View' ?></a></td></tr>
    <?php endforeach ?></tbody></table></div>
  <?php else: ?><p class="muted">No one has finished this quiz yet.</p><?php endif ?></div>

<div class="card"><div class="card-title"><h3>Question by question</h3></div>
  <div class="qb-list">
    <?php foreach ($stats as $i => $s): $opts = $s['options'] ? (json_decode($s['options'], true) ?: []) : []; $n = (int) $s['answered']; $rate = $n ? round((int) $s['correct'] / $n * 100) : null; ?>
      <div class="qb-item" style="grid-template-columns:auto 1fr auto"><span class="qb-n"><?= $i + 1 ?></span>
        <div><p><?= e($s['prompt']) ?></p>
          <?php if ($s['type'] === 'mcq'): $cc = $choices[(int) $s['id']] ?? []; $sum = max(1, array_sum($cc)); ?>
            <?php foreach ($opts as $k => $o): $v = $cc[$k] ?? 0; ?>
              <div class="bar-row"><span class="lbl" style="width:auto;min-width:0;flex:0 0 45%;<?= $k === (int) $s['correct_index'] ? 'color:var(--g700)' : '' ?>"><?= $k === (int) $s['correct_index'] ? '✔ ' : '' ?><?= e(excerpt($o, 50)) ?></span><span class="bar"><i style="width:<?= round($v / $sum * 100) ?>%;<?= $k === (int) $s['correct_index'] ? '' : 'background:var(--line2)' ?>"></i></span><span class="db-num"><?= $v ?></span></div>
            <?php endforeach ?>
          <?php else: ?><div class="qb-meta">Essay · average <?= $s['avg_points'] !== null ? round((float) $s['avg_points'], 1) : '–' ?> / <?= (int) $s['points'] ?> pts</div><?php endif ?></div>
        <b class="db-num" style="font-size:18px;color:<?= $rate === null ? 'var(--muted)' : ($rate < 50 ? '#c0392b' : 'var(--g700)') ?>"><?= $rate === null ? '–' : $rate . '%' ?></b>
      </div>
    <?php endforeach ?>
  </div></div>
