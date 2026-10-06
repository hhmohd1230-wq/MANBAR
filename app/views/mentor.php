<?php $title = $m['full_name'] . ' · Mentor'; $own = (int) $m['uid'] === (int) $u['id']; ?>
<div class="page-grid">
  <div class="stack gap-l">
    <div class="card" style="padding:0;overflow:hidden"><div class="cover" style="height:140px;<?= theme_css((int) $m['uid']) ?>"></div>
      <div class="profile-top" style="margin-top:-48px"><?= avatar($m, 96) ?><div class="info"><h1><?= e($m['full_name']) ?> <?= verified_badge($m) ?></h1><div class="muted"><?= e($m['headline'] ?: $m['department']) ?></div><div class="row wrap" style="margin-top:8px"><?php foreach (csv_list($m['expertise']) as $x): ?><span class="chip"><?= e($x) ?></span><?php endforeach ?></div></div>
        <a class="btn btn-ghost" href="<?= e(url('profile/' . $m['uid'])) ?>">View profile</a></div>
      <div class="stats"><div class="stat"><b><?= $mentees ?></b><span>Mentees</span></div><div class="stat"><b><?= e($m['availability'] ?: 'Flexible') ?></b><span>Availability</span></div></div></div>
    <div class="card"><h3>About this mentor</h3><p class="muted" style="margin:0"><?= $m['about'] ? nl2br(e($m['about'])) : 'This mentor has not written an introduction yet.' ?></p></div>
  </div>
  <aside class="aside"><div class="card">
    <h3><?= icon('compass', 18) ?> Request mentorship</h3>
    <?php if ($own): ?><p class="muted small">This is your public mentor profile.</p><a class="btn btn-block" href="<?= e(url('profile/edit')) ?>">Edit mentor profile</a>
    <?php elseif ($open): ?><div class="chip st-<?= e($open['status']) ?>">Your request is <?= e($open['status']) ?></div><a class="btn btn-block mt" href="<?= e(url('mentorship')) ?>">View in My mentorship</a>
    <?php else: ?><form method="post" action="<?= e(url('mentors/' . $m['uid'] . '/request')) ?>" data-ai-writing-form data-ai-context="mentorship"><?= csrf_field() ?>
      <div class="field"><label class="f">What do you want help with?</label><input class="input" name="topic" data-ai-writing="title" required maxlength="200" placeholder="e.g. Choosing a capstone topic"></div>
      <div class="field"><label class="f">Tell the mentor more</label><textarea class="textarea" name="message" data-ai-writing="body" placeholder="Background, goals and when you’re free…"></textarea></div>
      <div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
      <button class="btn btn-primary btn-block" type="submit"><?= icon('send', 16) ?> Send request</button></form><?php endif ?>
  </div></aside>
</div>
