<?php $title = 'My mentorship'; $isStaff = in_array($u['role'], ['teacher', 'admin'], true); ?>
<div class="pagehead"><div><h1>My mentorship</h1><p>Requests you sent and requests you received.</p></div><a class="btn btn-primary" href="<?= e(url('mentors')) ?>"><?= icon('compass', 17) ?> Find a mentor</a></div>
<div class="stack gap-l">
<?php if ($isStaff || $incoming): ?>
<section><h3>Requests from students <span class="chip sm"><?= count($incoming) ?></span></h3>
  <div class="stack"><?php foreach ($incoming as $r): ?>
    <div class="card"><div class="row" style="align-items:flex-start;gap:14px"><a href="<?= e(url('profile/' . $r['student_id'])) ?>"><?= avatar($r, 48) ?></a>
      <div class="grow"><div class="row wrap"><b><?= e($r['full_name']) ?></b><span class="status-pill st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span><span class="xs muted"><?= e(time_ago($r['created_at'])) ?></span></div>
        <div style="font-weight:600;margin-top:4px"><?= e($r['topic']) ?></div><p class="small muted" style="margin:2px 0 0"><?= e($r['message']) ?></p>
        <?php if ($r['session_at']): ?><div class="chip mt"><?= icon('calendar', 14) ?> <?= e(date('D, M j · H:i', strtotime($r['session_at']))) ?></div><?php endif ?>
        <?php if ($r['feedback']): ?><div class="card flat mt" style="background:var(--g50);padding:12px"><b class="small">Feedback:</b> <?= e($r['feedback']) ?></div><?php endif ?>
        <?php if ($r['status'] === 'pending'): ?><form method="post" action="<?= e(url('mentorship/' . $r['id'])) ?>" class="row wrap mt"><?= csrf_field() ?><input class="input" type="datetime-local" name="session_at" style="width:auto"><button class="btn btn-primary btn-sm" name="decision" value="accept"><?= icon('check', 14) ?> Accept &amp; schedule</button><button class="btn btn-ghost btn-sm" name="decision" value="decline">Decline</button></form>
        <?php elseif ($r['status'] === 'accepted'): ?><form method="post" action="<?= e(url('mentorship/' . $r['id'])) ?>" class="stack mt"><?= csrf_field() ?><div class="row wrap"><input class="input" type="datetime-local" name="session_at" style="width:auto"><button class="btn btn-sm" name="decision" value="schedule">Reschedule</button><a class="btn btn-sm btn-ghost" href="<?= e(url('messages/' . $r['student_id'])) ?>">Message</a></div>
          <textarea class="textarea" name="feedback" style="min-height:64px" placeholder="Session feedback for the student…"></textarea><button class="btn btn-primary btn-sm" style="width:fit-content" name="decision" value="complete"><?= icon('check-circle', 14) ?> Mark completed &amp; send feedback</button></form><?php endif ?>
      </div></div></div>
  <?php endforeach ?><?php if (!$incoming): ?><div class="card empty"><h3>No requests yet</h3><p class="muted">Make sure your mentor profile is switched on in <a href="<?= e(url('profile/edit')) ?>">Edit profile</a>.</p></div><?php endif ?></div></section>
<?php endif ?>
<section><h3>My requests <span class="chip sm"><?= count($outgoing) ?></span></h3>
  <div class="stack"><?php foreach ($outgoing as $r): ?>
    <div class="card row" style="align-items:flex-start;gap:14px"><a href="<?= e(url('mentors/' . $r['mentor_id'])) ?>"><?= avatar($r, 48) ?></a>
      <div class="grow"><div class="row wrap"><b><?= e($r['full_name']) ?></b><span class="status-pill st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span><span class="xs muted"><?= e(time_ago($r['created_at'])) ?></span></div><div style="font-weight:600"><?= e($r['topic']) ?></div>
        <?php if ($r['session_at']): ?><div class="chip mt"><?= icon('calendar', 14) ?> <?= e(date('D, M j · H:i', strtotime($r['session_at']))) ?></div><?php endif ?>
        <?php if ($r['feedback']): ?><div class="card flat mt" style="background:var(--g50);padding:12px"><b class="small">Mentor feedback:</b> <?= e($r['feedback']) ?></div><?php endif ?></div>
      <a class="btn btn-sm btn-ghost" href="<?= e(url('messages/' . $r['mentor_id'])) ?>"><?= icon('message', 14) ?></a></div>
  <?php endforeach ?><?php if (!$outgoing): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No requests yet</h3><a class="btn btn-primary" href="<?= e(url('mentors')) ?>">Browse mentors</a></div><?php endif ?></div></section>
</div>
