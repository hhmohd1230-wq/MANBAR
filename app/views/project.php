<?php
$title = $p['title'];
$cols = ['todo' => 'To do', 'doing' => 'In progress', 'done' => 'Done'];
$byStatus = ['todo' => [], 'doing' => [], 'done' => []];
foreach ($tasks as $t) $byStatus[$t['status']][] = $t;
$pct = $tasks ? round(count($byStatus['done']) / count($tasks) * 100) : 0;
$full = count($members) >= (int) $p['max_members'];
?>
<div class="page-grid">
  <div class="stack gap-l">
    <div class="card" style="padding:0;overflow:hidden">
      <div class="item-cover project-cover project-detail-cover project-cover-t<?= project_cover_theme($p) ?> <?= !empty($p['cover_image']) ? 'has-image' : '' ?>" style="<?= e(project_cover_style($p)) ?>"><span class="status-pill st-<?= e($p['status']) ?>"><?= e(str_replace('_', ' ', $p['status'])) ?></span><span class="ic-big"><?= icon('rocket', 34) ?></span></div>
      <div style="padding:22px 26px 26px">
        <h1 style="margin-bottom:6px"><?= e($p['title']) ?></h1>
        <div class="row small muted"><a class="row gap-s" href="<?= e(url('profile/' . $p['owner_id'])) ?>" style="color:var(--ink2)"><?= avatar(['full_name' => $p['owner_name'], 'avatar_url' => $p['owner_avatar'], 'email' => $p['owner_email']], 28) ?> <b><?= e($p['owner_name']) ?></b></a><span>· started <?= e(time_ago($p['created_at'])) ?></span></div>
        <div class="prose" style="margin-top:14px;font-size:15.5px"><?= rich($p['description']) ?></div>
        <?php if ($p['needed_skills']): ?><div class="row wrap" style="margin-top:14px"><span class="small muted">Looking for:</span><?php foreach (csv_list($p['needed_skills']) as $s): ?><a class="chip" href="<?= e(url('people?skill=' . urlencode($s))) ?>"><?= e($s) ?></a><?php endforeach ?></div><?php endif ?>
        <?php if ($p['outcome']): ?><div class="card flat mt" style="background:var(--g50)"><b><?= icon('trophy', 16) ?> Project outcome</b><p style="margin:6px 0 0"><?= rich($p['outcome']) ?></p></div><?php endif ?>
        <div class="row wrap" style="margin-top:20px">
          <?php if (!$isMember && $p['status'] === 'open'): ?>
            <?php if ($myApp): ?><span class="chip st-<?= e($myApp['status']) ?>">Your application: <?= e($myApp['status']) ?></span>
            <?php elseif ($full): ?><span class="chip">Team is full</span>
            <?php else: ?><button class="btn btn-primary" onclick="document.getElementById('applyBox').hidden=false;this.hidden=true"><?= icon('user-plus', 17) ?> Apply to join</button><?php endif ?>
          <?php endif ?>
          <?php if ($isMember && !$isOwner): ?><form method="post" action="<?= e(url('projects/' . $p['id'] . '/leave')) ?>" data-confirm="Leave this project?"><?= csrf_field() ?><button class="btn btn-ghost btn-sm">Leave project</button></form><?php endif ?>
          <button class="btn btn-ghost btn-sm" data-copy="<?= e(url('projects/' . $p['id'])) ?>"><?= icon('link', 15) ?> Copy link</button>
          <?php if (!$isOwner): ?><button class="btn btn-ghost btn-sm" data-report="project" data-id="<?= (int) $p['id'] ?>"><?= icon('flag', 15) ?> Report</button><?php endif ?>
        </div>
        <form id="applyBox" hidden method="post" action="<?= e(url('projects/' . $p['id'] . '/apply')) ?>" class="mt" data-ai-writing-form data-ai-context="project application"><?= csrf_field() ?>
          <label class="f">Tell the owner why you’re a great fit</label><textarea class="textarea" name="message" data-ai-writing="body" placeholder="Your skills, availability and what you’d like to contribute…"></textarea>
          <div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
          <button class="btn btn-primary mt" type="submit"><?= icon('send', 16) ?> Send application</button>
        </form>
      </div>
    </div>

    <?php if ($p['status'] === 'completed' && $isMember): ?>
    <section class="card team-vouch-hub" id="team-vouches" aria-labelledby="team-vouch-title">
      <div class="team-vouch-head">
        <div><span class="team-vouch-icon"><?= icon('award', 22) ?></span><div><h2 id="team-vouch-title">Vouch for your project team</h2><p>Review the people you worked with. A strong, specific five-star vouch can award one full rank star: up to <?= RANK_STAR_XP ?> reputation XP.</p></div></div>
        <span class="verified-work-chip"><?= icon('check-circle', 14) ?> Verified collaboration</span>
      </div>
      <div class="team-vouch-grid">
        <?php $vouchTargets = 0; foreach ($members as $member): if ((int) $member['user_id'] === (int) $u['id']) continue; $vouchTargets++; $existingVouch = $myVouches[(int) $member['user_id']] ?? null; $selectedTraits = csv_list((string) ($existingVouch['traits'] ?? '')); ?>
          <article class="team-vouch-card">
            <div class="team-vouch-person"><?= avatar($member, 48) ?><div><a href="<?= e(url('profile/' . $member['user_id'])) ?>"><?= e($member['full_name']) ?></a><span><?= e($member['role']) ?></span></div><?php if ($existingVouch): ?><b><?= icon('check-circle', 13) ?> Vouched · +<?= (int) $existingVouch['xp_value'] ?> XP</b><?php endif ?></div>
            <form method="post" action="<?= e(url('projects/' . $p['id'] . '/vouch/' . $member['user_id'])) ?>" data-ai-writing-form data-ai-context="verified teammate review">
              <?= csrf_field() ?>
              <fieldset class="vouch-rating"><legend>How was their contribution?</legend><?php for ($rating = 1; $rating <= 5; $rating++): ?><label><input type="radio" name="rating" value="<?= $rating ?>" <?= (int) ($existingVouch['rating'] ?? 5) === $rating ? 'checked' : '' ?>><span aria-hidden="true">★</span><span class="sr-only"><?= $rating ?> star<?= $rating === 1 ? '' : 's' ?></span></label><?php endfor ?></fieldset>
              <fieldset class="vouch-traits" data-max-choices="3"><legend>Choose up to three strengths</legend><div><?php foreach (['Collaborative', 'Reliable', 'Strong communicator', 'High-quality work', 'Problem solver', 'Supportive leader'] as $trait): ?><label><input type="checkbox" name="traits[]" value="<?= e($trait) ?>" <?= in_array($trait, $selectedTraits, true) ? 'checked' : '' ?>><span><?= e($trait) ?></span></label><?php endforeach ?></div></fieldset>
              <label class="vouch-note"><span>What did they do well?</span><textarea class="textarea" name="body" data-ai-writing="body" minlength="30" maxlength="800" required placeholder="Describe how they collaborated, communicated or delivered their work…"><?= e($existingVouch['body'] ?? '') ?></textarea></label>
              <div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
              <div class="team-vouch-submit"><span><?= icon('sparkles', 14) ?> Rating + verified strengths determine XP</span><button class="btn btn-primary" type="submit"><?= icon('award', 15) ?> <?= $existingVouch ? 'Update vouch' : 'Publish vouch' ?></button></div>
            </form>
          </article>
        <?php endforeach ?>
        <?php if (!$vouchTargets): ?><div class="team-vouch-empty"><?= icon('users', 25) ?><b>No teammates to review yet</b><span>Invite teammates before completing a future project to build verified collaboration history.</span></div><?php endif ?>
      </div>
    </section>
    <?php endif ?>

    <?php if ($isOwner && $apps): ?>
    <div class="card"><div class="card-title"><h3><?= icon('user-plus', 18) ?> Applications <span class="chip on sm"><?= count($apps) ?></span></h3></div>
      <div class="stack"><?php foreach ($apps as $a): ?>
        <div class="row" style="align-items:flex-start;gap:14px;padding-bottom:14px;border-bottom:1px solid var(--line)">
          <a href="<?= e(url('profile/' . $a['user_id'])) ?>"><?= avatar($a, 46) ?></a>
          <div class="grow"><a href="<?= e(url('profile/' . $a['user_id'])) ?>"><b><?= e($a['full_name']) ?></b></a><?= verified_badge($a) ?> <span class="xs muted">· <?= e($a['major'] ?: 'Student') ?></span><p class="small" style="margin:4px 0 0"><?= e($a['message'] ?: 'No message.') ?></p></div>
          <form method="post" action="<?= e(url('projects/' . $p['id'] . '/application/' . $a['id'])) ?>" class="row"><?= csrf_field() ?><button class="btn btn-primary btn-sm" name="decision" value="accept"><?= icon('check', 14) ?> Accept</button><button class="btn btn-ghost btn-sm" name="decision" value="reject">Decline</button></form>
        </div>
      <?php endforeach ?></div></div>
    <?php endif ?>

    <?php if ($isMember || is_admin()): ?>
    <div class="card" id="tasks">
      <div class="card-title"><h3><?= icon('grid', 18) ?> Task board</h3><span class="small muted"><?= count($byStatus['done']) ?>/<?= count($tasks) ?> done · <?= $pct ?>%</span></div>
      <div class="progress mb"><i style="width:<?= $pct ?>%"></i></div>
      <?php if ($isMember): ?>
      <form method="post" action="<?= e(url('projects/' . $p['id'] . '/tasks')) ?>" class="row wrap mb" data-ai-writing-form data-ai-context="project task"><?= csrf_field() ?>
        <input class="input grow" name="title" data-ai-writing="title" placeholder="Add a task…" required style="min-width:200px">
        <select class="select" name="assignee_id" style="width:auto"><option value="">Unassigned</option><?php foreach ($members as $m): ?><option value="<?= (int) $m['user_id'] ?>"><?= e(explode(' ', $m['full_name'])[0]) ?></option><?php endforeach ?></select>
        <input class="input" type="date" name="due_date" style="width:auto"><button class="btn btn-primary" type="submit"><?= icon('plus', 16) ?> Add</button>
        <div class="writing-assist writing-assist-row" data-writing-assist hidden aria-live="polite"></div>
      </form><?php endif ?>
      <div class="kanban">
        <?php foreach ($cols as $k => $label): ?><div class="kcol"><h4><span><?= $label ?></span><span><?= count($byStatus[$k]) ?></span></h4>
          <?php foreach ($byStatus[$k] as $t): ?><div class="ktask"><?= e($t['title']) ?>
            <div class="kmeta"><?php if ($t['assignee']): ?><span class="chip sm"><?= e(explode(' ', $t['assignee'])[0]) ?></span><?php endif ?><?php if ($t['due_date']): ?><span><?= icon('clock', 12) ?> <?= e(date('M j', strtotime($t['due_date']))) ?></span><?php endif ?>
              <?php if ($isMember): ?><form method="post" action="<?= e(url('projects/' . $p['id'] . '/tasks/' . $t['id'])) ?>" style="margin-left:auto;display:flex;gap:4px"><?= csrf_field() ?>
                <?php foreach ($cols as $k2 => $l2) if ($k2 !== $k): ?><button class="mv" name="status" value="<?= $k2 ?>" title="Move to <?= e($l2) ?>"><?= $k2 === 'todo' ? '←' : ($k2 === 'done' ? '✓' : ($k === 'todo' ? '→' : '←')) ?></button><?php endif ?>
                <button class="mv" name="delete" value="1" title="Delete" style="background:#fdeaea;color:#b83232">×</button></form><?php endif ?></div></div><?php endforeach ?>
        </div><?php endforeach ?>
      </div>
    </div>

    <div class="card" id="chat">
      <div class="card-title"><h3><?= icon('chat', 18) ?> Team chat &amp; updates</h3></div>
      <?php if ($isMember): ?><form method="post" action="<?= e(url('projects/' . $p['id'] . '/message')) ?>" class="row mb" style="align-items:flex-start"><?= csrf_field() ?>
        <textarea class="textarea grow" name="body" data-ai-writing="message" rows="2" placeholder="Message your team…" required style="min-height:56px"></textarea>
        <div class="stack gap-s"><button class="btn btn-ghost writing-quick" type="button" data-ai-quick aria-label="Improve writing" title="Improve writing"><?= icon('sparkles', 16) ?></button><button class="btn btn-primary" type="submit"><?= icon('send', 16) ?></button><label class="xs muted" style="cursor:pointer"><input type="checkbox" name="is_update" value="1"> Public update</label></div></form><?php endif ?>
      <div class="chat"><?php foreach ($msgs as $m): ?>
        <div class="msg <?= $m['is_update'] ? 'upd' : '' ?>"><?= avatar($m, 34) ?><div class="bub"><b class="small"><?= e($m['full_name']) ?></b> <span class="xs muted"><?= e(time_ago($m['created_at'])) ?></span><?= $m['is_update'] ? ' <span class="chip sm tchip tone-amber">Update</span>' : '' ?><div><?= rich($m['body']) ?></div></div></div>
      <?php endforeach ?><?php if (!$msgs): ?><p class="muted small tc">No messages yet.</p><?php endif ?></div>
    </div>
    <?php elseif ($updates): ?>
    <div class="card"><div class="card-title"><h3><?= icon('megaphone', 18) ?> Project updates</h3></div><div class="chat"><?php foreach ($updates as $m): ?><div class="msg upd"><?= avatar($m, 34) ?><div class="bub"><b class="small"><?= e($m['full_name']) ?></b> <span class="xs muted"><?= e(time_ago($m['created_at'])) ?></span><div><?= rich($m['body']) ?></div></div></div><?php endforeach ?></div></div>
    <?php endif ?>
  </div>

  <aside class="aside">
    <div class="card"><div class="card-title"><h3><?= icon('users', 18) ?> Team <span class="chip sm outline"><?= count($members) ?>/<?= (int) $p['max_members'] ?></span></h3></div>
      <div class="w-list"><?php foreach ($members as $m): ?><div class="person"><a href="<?= e(url('profile/' . $m['user_id'])) ?>"><?= avatar($m, 40) ?></a><div class="grow"><a class="nm" href="<?= e(url('profile/' . $m['user_id'])) ?>"><?= e($m['full_name']) ?></a><?= verified_badge($m) ?><div class="xs muted"><?= e($m['role']) ?></div></div></div><?php endforeach ?></div></div>
    <?php if ($isOwner): ?>
    <div class="card"><div class="card-title"><h3><?= icon('settings', 18) ?> Manage project</h3></div>
      <form method="post" action="<?= e(url('projects/' . $p['id'] . '/status')) ?>" enctype="multipart/form-data" data-ai-writing-form data-ai-context="project outcome"><?= csrf_field() ?>
        <div class="field"><label class="f">Status</label><select class="select" name="status" id="pstatus"><?php foreach (['open' => 'Open for applications', 'in_progress' => 'In progress', 'completed' => 'Completed', 'closed' => 'Closed'] as $k => $l): ?><option value="<?= $k ?>" <?= $p['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></div>
        <div class="field"><label class="f">Max team size</label><input class="input" type="number" name="max_members" min="2" max="30" value="<?= (int) $p['max_members'] ?>"></div>
        <div class="field"><label class="f">Cover background</label><div class="project-cover-options compact"><?php foreach (PROJECT_COVER_THEMES as $i => $label): ?><label class="project-cover-choice" title="<?= e($label) ?>"><input type="radio" name="cover_theme" value="<?= $i ?>" <?= project_cover_theme($p) === $i ? 'checked' : '' ?>><span class="project-cover-swatch project-cover project-cover-t<?= $i ?>"><i><?= icon('check', 13) ?></i></span></label><?php endforeach ?></div></div>
        <div class="field"><label class="f" for="projectCoverManage">Custom image</label><input class="input" id="projectCoverManage" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif"><?php if (!empty($p['cover_image'])): ?><label class="small muted row gap-s" style="margin-top:8px"><input type="checkbox" name="remove_cover_image" value="1"> Remove current custom image</label><?php endif ?></div>
        <div class="field"><label class="f">Outcome (shown when completed)</label><textarea class="textarea" name="outcome" data-ai-writing="body" style="min-height:70px" placeholder="What did you achieve?"><?= e($p['outcome']) ?></textarea></div>
        <div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
        <button class="btn btn-primary btn-block" type="submit">Save</button>
        <div class="hint">Completion gives every member +30 activity XP. Verified teammate vouches can award up to +<?= RANK_STAR_XP ?> reputation XP each.</div>
      </form>
      <form method="post" action="<?= e(url('projects/' . $p['id'] . '/delete')) ?>" data-confirm="Delete this project and all its tasks?" class="mt"><?= csrf_field() ?><button class="btn btn-danger btn-block btn-sm"><?= icon('trash', 15) ?> Delete project</button></form>
    </div>
    <?php endif ?>
  </aside>
</div>
