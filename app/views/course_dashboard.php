<?php
$title = 'Dashboard · ' . $c['title'];
$cid = (int) $c['id'];
$paid = course_is_paid($c);
$n = count($students);
$avgProgress = $n ? (int) round(array_sum(array_column($students, 'progress')) / $n) : 0;
$completed = count(array_filter($students, fn($s) => $s['completed_at']));
$quizAvgs = array_values(array_filter(array_column($students, 'quiz_avg'), fn($v) => $v !== null));
$avgQuiz = $quizAvgs ? (int) round(array_sum($quizAvgs) / count($quizAvgs)) : null;
$maxDay = max(1, ...array_values($days));
?>
<?php partial('learn_assets') ?>
<?php partial('back_button', ['href' => "learn/$cid", 'label' => 'Back to course page']) ?>
<div class="pagehead"><div><h1><?= e($c['title']) ?></h1><p>Host dashboard · <?= $paid ? e(format_aed((float) $c['price_aed'])) : 'Free course' ?> · <?= $c['status'] === 'published' ? 'Published' : 'Hidden' ?></p></div>
  <div class="row wrap"><form method="post" action="<?= e(url("learn/$cid/quiz/new")) ?>"><?= csrf_field() ?><button class="btn btn-primary"><?= icon('plus', 16) ?> New quiz</button></form><a class="btn" href="<?= e(url("learn/$cid/students.csv")) ?>"><?= icon('download', 16) ?> Export CSV</a></div></div>

<nav class="lc-tabs db-tabs" aria-label="Dashboard sections"><a href="#overview">Overview</a><a href="#marking">To mark (<?= count($toMark) ?>)</a><a href="#students">Students (<?= $n ?>)</a><a href="#quizzes">Quizzes (<?= count($quizzes) ?>)</a><a href="#live">Live</a><?php if ($paid): ?><a href="#payments">Payments</a><?php endif ?><a href="#settings">Settings</a></nav>

<section id="overview">
  <div class="kpis">
    <div class="kpi tone-green"><div class="ico"><?= icon('users', 22) ?></div><b><?= $n ?></b><span>students</span> <em><?= $completed ?> completed</em></div>
    <div class="kpi tone-blue"><div class="ico"><?= icon('eye', 22) ?></div><b><?= $active7 ?></b><span>active in the last 7 days</span></div>
    <div class="kpi tone-amber"><div class="ico"><?= icon('trending', 22) ?></div><b><?= $avgProgress ?>%</b><span>average lesson progress</span></div>
    <div class="kpi tone-violet"><div class="ico"><?= icon('check-circle', 22) ?></div><b><?= $avgQuiz === null ? '–' : $avgQuiz . '%' ?></b><span>average quiz score</span></div>
    <?php if ($paid): ?><div class="kpi tone-rose"><div class="ico"><?= icon('wallet', 22) ?></div><b><?= e(format_aed($revenue)) ?></b><span>earned by card</span></div><?php endif ?>
  </div>
  <div class="card mb"><div class="card-title"><h3>Students visiting the course</h3><span class="small muted">last 14 days</span></div>
    <div class="db-chart" role="img" aria-label="Daily visitors over the last 14 days"><?php foreach ($days as $d => $v): ?><div title="<?= e(date('M j', strtotime($d))) ?>: <?= $v ?> student<?= $v === 1 ? '' : 's' ?>"><i style="height:<?= max(2, round($v / $maxDay * 100)) ?>%"></i><span><?= date('j', strtotime($d)) ?></span></div><?php endforeach ?></div></div>
</section>

<div class="grid2 mb" style="align-items:start">
  <section class="card" id="marking"><div class="card-title"><h3><?= icon('edit', 18) ?> Waiting for marking</h3><span class="chip sm"><?= count($toMark) ?></span></div>
    <?php if ($toMark): foreach ($toMark as $t): ?>
      <div class="db-hard"><span class="grow"><b><?= e($t['full_name']) ?></b><br><span class="small muted"><?= e($t['title']) ?> · <?= e(date('M j, H:i', strtotime($t['submitted_at']))) ?></span></span><a class="btn btn-sm btn-primary" href="<?= e(url('quiz/attempt/' . $t['id'] . '/grade')) ?>">Mark</a></div>
    <?php endforeach; else: ?><p class="muted small">Nothing to mark. Multiple-choice and keyword essays are marked automatically.</p><?php endif ?></section>
  <section class="card"><div class="card-title"><h3><?= icon('alert', 18) ?> Hardest questions</h3></div>
    <?php if ($hardest): foreach ($hardest as $h): ?>
      <div class="db-hard"><b><?= round((int) $h['correct'] / max(1, (int) $h['answered']) * 100) ?>%</b><span class="grow small"><?= e(excerpt($h['prompt'], 90)) ?><br><span class="muted"><?= e($h['title']) ?> · <?= (int) $h['answered'] ?> answers</span></span></div>
    <?php endforeach; else: ?><p class="muted small">Shows up once students answer quizzes.</p><?php endif ?></section>
</div>

<section class="card mb" id="students"><div class="card-title"><h3><?= icon('users', 18) ?> Students</h3><span class="chip sm"><?= $n ?></span></div>
  <?php if ($students): ?><div class="table-wrap"><table class="t"><thead><tr><th>Student</th><th>Joined</th><th>Last visit</th><th>Visits</th><th>Lessons</th><th>Quiz average</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($students as $s): ?>
      <tr><td><a class="row gap-s" href="<?= e(url('profile/' . $s['user_id'])) ?>" style="color:var(--ink)"><?= avatar($s, 30) ?><span><b><?= e($s['full_name']) ?></b><br><span class="xs muted"><?= e($s['email']) ?></span></span></a></td>
        <td class="small"><?= e(date('M j', strtotime($s['joined']))) ?></td>
        <td class="small"><?= $s['last_seen'] ? e(date('M j', strtotime($s['last_seen']))) : '<span class="muted">Not yet</span>' ?></td>
        <td class="db-num"><?= (int) $s['visits'] ?></td>
        <td><div class="db-pct"><span class="db-num small"><?= (int) $s['lessons_done'] ?>/<?= $lessons ?></span><div class="progress"><i style="width:<?= (int) $s['progress'] ?>%"></i></div></div></td>
        <td class="db-num"><?= $s['quiz_avg'] === null ? '<span class="muted">–</span>' : (int) $s['quiz_avg'] . '% <span class="xs muted">(' . (int) $s['attempts'] . ')</span>' ?></td>
        <td><?= $s['completed_at'] ? '<span class="lc-tag">Completed</span>' : '<span class="lc-tag" style="background:var(--g50)">Learning</span>' ?><?= $paid || $s['payment'] ? ' ' . match ($s['payment']) { 'stripe' => '<span class="lc-tag best">Paid by card</span>', 'manual' => '<span class="lc-tag draft">Access given</span>', default => '<span class="lc-tag" style="background:var(--g50)">Joined free</span>' } : '' ?></td>
        <td><form method="post" action="<?= e(url("learn/$cid/students/{$s['user_id']}/remove")) ?>" data-confirm="Remove <?= e($s['full_name']) ?> from the course?"><?= csrf_field() ?><button class="icon-btn" aria-label="Remove student"><?= icon('x', 15) ?></button></form></td></tr>
    <?php endforeach ?></tbody></table></div>
  <?php else: ?><p class="muted">No students yet. Share the course link, or add someone below.</p><?php endif ?>
  <form class="row wrap" method="post" action="<?= e(url("learn/$cid/students")) ?>" style="margin-top:14px"><?= csrf_field() ?>
    <input class="input" name="email" placeholder="Student number or email" style="max-width:300px" required><input type="hidden" name="domain" value="aau.ac.ae">
    <button class="btn" type="submit"><?= icon('user-plus', 16) ?> Give access</button><span class="hint"><?= $paid ? 'For students who paid you another way, or free invites.' : 'Adds them to the course straight away.' ?></span></form>
</section>

<section class="card mb" id="quizzes"><div class="card-title"><h3><?= icon('check-circle', 18) ?> Quizzes</h3></div>
  <?php if ($quizzes): ?><div class="table-wrap"><table class="t"><thead><tr><th>Quiz</th><th>Questions</th><th>Attempts</th><th>Average</th><th>Pass rate</th><th>To mark</th><th></th></tr></thead><tbody>
    <?php foreach ($quizzes as $z): ?>
      <tr><td><b><?= e($z['title']) ?></b> <?= $z['status'] !== 'published' ? '<span class="lc-tag draft">Draft</span>' : '' ?></td><td class="db-num"><?= (int) $z['questions'] ?></td><td class="db-num"><?= (int) $z['attempts'] ?></td>
        <td class="db-num"><?= $z['avg_pct'] === null ? '–' : (int) $z['avg_pct'] . '%' ?></td><td class="db-num"><?= $z['graded'] ? round((int) $z['passed'] / (int) $z['graded'] * 100) . '%' : '–' ?></td>
        <td class="db-num"><?= (int) $z['to_mark'] ? '<b style="color:#c0392b">' . (int) $z['to_mark'] . '</b>' : '0' ?></td>
        <td><div class="row gap-s"><form method="post" action="<?= e(url("learn/$cid/quiz/{$z['id']}/test")) ?>"><?= csrf_field() ?><button class="btn btn-sm" type="submit">Test</button></form><a class="btn btn-sm" href="<?= e(url("learn/$cid/quiz/{$z['id']}/results")) ?>">Results</a><a class="btn btn-sm" href="<?= e(url("learn/$cid/quiz/{$z['id']}/edit")) ?>">Edit</a></div></td></tr>
    <?php endforeach ?></tbody></table></div>
  <?php else: ?><p class="muted">No quizzes yet.</p><?php endif ?></section>

<section class="card mb" id="live"><div class="card-title"><h3><?= icon('play', 18) ?> Live quiz sessions</h3></div>
  <?php if ($liveRuns): ?><div class="table-wrap"><table class="t"><thead><tr><th>Quiz</th><th>Date</th><th>Players</th><th>Winner</th><th>State</th><th></th></tr></thead><tbody>
    <?php foreach ($liveRuns as $r): ?><tr><td><b><?= e($r['title']) ?></b></td><td class="small"><?= e(date('M j, H:i', strtotime($r['created_at']))) ?></td><td class="db-num"><?= (int) $r['players'] ?></td><td><?= $r['winner'] ? '🏆 ' . e($r['winner']) : '–' ?></td><td><?= $r['state'] === 'ended' ? 'Finished' : '<span class="lc-tag best">Running</span>' ?></td>
      <td><?php if ($r['state'] !== 'ended'): ?><a class="btn btn-sm btn-primary" href="<?= e(url('live/' . $r['code'] . '/host')) ?>">Open</a><?php endif ?></td></tr><?php endforeach ?></tbody></table></div>
  <?php else: ?><p class="muted small">Start one from any quiz: everyone answers together, with a live leaderboard.</p><?php endif ?></section>

<?php if ($paid): ?>
<section class="card mb" id="payments"><div class="card-title"><h3><?= icon('wallet', 18) ?> Payments</h3><b><?= e(format_aed($revenue)) ?></b></div>
  <?php if (!stripe_ready()): ?><p class="small" style="background:#fff8e6;padding:10px 12px;border-radius:10px">Card payments are off until the site admin adds Stripe keys. You can still give access from the Students list.</p><?php endif ?>
  <?php if ($payments): ?><div class="table-wrap"><table class="t"><thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead><tbody>
    <?php foreach ($payments as $p): ?><tr><td><?= e($p['full_name']) ?></td><td class="db-num"><?= $p['provider'] === 'stripe' ? e(format_aed((float) $p['amount'])) : '–' ?></td><td><?= $p['provider'] === 'stripe' ? 'Card' : 'Given by host' ?></td><td class="small"><?= e(date('M j, H:i', strtotime((string) $p['paid_at']))) ?></td></tr><?php endforeach ?></tbody></table></div>
  <?php else: ?><p class="muted small">No payments yet.</p><?php endif ?></section>
<?php endif ?>

<form class="card" id="settings" method="post" action="<?= e(url("learn/$cid/settings")) ?>"><?= csrf_field() ?>
  <div class="card-title"><h3><?= icon('settings', 18) ?> Course settings</h3></div>
  <div class="field"><label class="f">Title</label><input class="input" name="title" value="<?= e($c['title']) ?>" maxlength="200" required></div>
  <div class="field"><label class="f">Description</label><textarea class="textarea" name="description" rows="4" required><?= e($c['description']) ?></textarea></div>
  <div class="grid3"><div class="field"><label class="f">Price (AED, 0 = free)</label><input class="input" type="number" name="price_aed" min="0" max="9999" step="0.5" value="<?= e((string) (float) $c['price_aed']) ?>"></div>
    <div class="field"><label class="f">Level</label><select class="select" name="level"><?php foreach (['beginner', 'intermediate', 'advanced'] as $lv): ?><option value="<?= $lv ?>" <?= $c['level'] === $lv ? 'selected' : '' ?>><?= ucfirst($lv) ?></option><?php endforeach ?></select></div>
    <div class="field"><label class="f">Category</label><select class="select" name="category"><?php foreach (COURSE_CATEGORIES as $k => $l): ?><option value="<?= $k ?>" <?= $c['category'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></div></div>
  <div class="field" style="max-width:320px"><label class="f">Visibility</label><select class="select" name="status"><option value="published" <?= $c['status'] === 'published' ? 'selected' : '' ?>>Published</option><option value="hidden" <?= $c['status'] !== 'published' ? 'selected' : '' ?>>Hidden (only you)</option></select></div>
  <button class="btn btn-primary" type="submit">Save settings</button>
</form>
