<?php
$title = $p['title'];
$cols = ['todo' => 'To do', 'doing' => 'In progress', 'done' => 'Done'];
$byStatus = ['todo' => [], 'doing' => [], 'done' => []];
foreach ($tasks as $t) $byStatus[$t['status']][] = $t;
$pct = $tasks ? round(count($byStatus['done']) / count($tasks) * 100) : 0;
$full = count($members) >= (int) $p['max_members'];
?>
<?php
$pid = (int) $p['id'];
$left = max(0, (int) $p['max_members'] - count($members));
$progress = $taskTotal ? (int) round($doneTasks / $taskTotal * 100) : null;
$msDone = count(array_filter($milestones, fn($m) => (int) $m['done'] === 1));
$isMatch = fn(string $sk) => in_array(mb_strtolower(trim($sk)), $mySkills, true);
$openRoles = array_values(array_filter($roles, fn($r) => (int) $r['filled'] < (int) $r['slots']));
$canApply = !$isMember && $p['status'] === 'open' && !$myApp && !$full;
?>
<?php partial('project_assets') ?>
<?php partial('back_button', ['href' => 'projects', 'label' => 'Back to projects']) ?>
<div class="page-grid">
  <div class="stack gap-l">
    <section class="card pj-head">
      <div class="pj-banner project-cover project-cover-t<?= project_cover_theme($p) ?> <?= !empty($p['cover_image']) ? 'has-image' : '' ?>" style="<?= e(project_cover_style($p)) ?>">
        <span class="status-pill st-<?= e($p['status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $p['status']))) ?></span>
        <?php if ($p['status'] === 'open' && $left): ?><span class="pj-spots"><?= icon('user-plus', 13) ?> <?= $left ?> spot<?= $left === 1 ? '' : 's' ?> open</span><?php endif ?>
      </div>
      <div class="pj-headbody">
        <div class="pj-titlerow">
          <div class="grow">
            <h1><?= e($p['title']) ?></h1>
            <div class="pj-owner"><a href="<?= e(url('profile/' . $p['owner_id'])) ?>"><?= avatar(['full_name' => $p['owner_name'], 'avatar_url' => $p['owner_avatar'], 'email' => $p['owner_email']], 28) ?> <b><?= e($p['owner_name']) ?></b></a><span>· started <?= e(time_ago($p['created_at'])) ?></span><span>· active <?= e(time_ago($lastActive)) ?></span></div>
          </div>
          <div class="pj-cta-row">
            <?php if ($canApply): ?><a class="btn btn-primary btn-lg" href="#apply"><?= icon('user-plus', 17) ?> Apply to join</a>
            <?php elseif ($myApp && !$isMember): ?><span class="chip st-<?= e($myApp['status']) ?>">Your application: <?= e($myApp['status']) ?></span>
            <?php elseif ($isMember): ?><span class="chip on"><?= icon('check', 14) ?> <?= $isOwner ? 'Your project' : 'You’re in the team' ?></span>
            <?php elseif ($full && $p['status'] === 'open'): ?><span class="chip">Team is full</span><?php endif ?>
            <button class="btn btn-ghost btn-sm" data-copy="<?= e(url("projects/$pid")) ?>"><?= icon('link', 15) ?> Copy link</button>
            <?php if ($isMember && !$isOwner): ?><form method="post" action="<?= e(url("projects/$pid/leave")) ?>" data-confirm="Leave this project?"><?= csrf_field() ?><button class="btn btn-ghost btn-sm">Leave</button></form><?php endif ?>
            <?php if (!$isOwner): ?><button class="btn btn-ghost btn-sm" data-report="project" data-id="<?= $pid ?>" aria-label="Report"><?= icon('flag', 15) ?></button><?php endif ?>
          </div>
        </div>
        <dl class="pj-quick">
          <div><dt><?= icon('users', 14) ?> Team</dt><dd><?= count($members) ?><small>/<?= (int) $p['max_members'] ?></small></dd></div>
          <div><dt><?= icon('user-plus', 14) ?> Open spots</dt><dd><?= $p['status'] === 'open' ? $left : '—' ?></dd></div>
          <div><dt><?= icon('check-circle', 14) ?> Tasks done</dt><dd><?= $progress !== null ? $progress . '%' : '—' ?><?php if ($taskTotal): ?><small> <?= $doneTasks ?>/<?= $taskTotal ?></small><?php endif ?></dd></div>
          <div><dt><?= icon('flag', 14) ?> Milestones</dt><dd><?= $milestones ? $msDone . '<small>/' . count($milestones) . '</small>' : '—' ?></dd></div>
          <div><dt><?= icon('paperclip', 14) ?> Files</dt><dd><?= (int) $fileCount ?></dd></div>
        </dl>
      </div>
    </section>

    <section class="card">
      <h2 class="lc-h2">About the project</h2>
      <div class="prose pj-desc"><?= rich($p['description']) ?></div>
      <?php if ($p['needed_skills']): ?><h3 class="pj-h3">Skills we need</h3><div class="pj-chips is-lg"><?php foreach (csv_list($p['needed_skills']) as $s): ?><a class="<?= $isMatch($s) ? 'is-match' : '' ?>" href="<?= e(url('people?skill=' . urlencode($s))) ?>"><?= $isMatch($s) ? icon('check', 12) . ' ' : '' ?><?= e($s) ?></a><?php endforeach ?></div>
        <?php if (!$isMember && array_filter(csv_list($p['needed_skills']), $isMatch)): ?><p class="pj-youmatch mt"><?= icon('sparkles', 14) ?> You have <?= count(array_filter(csv_list($p['needed_skills']), $isMatch)) ?> of the skills this team needs.</p><?php endif ?><?php endif ?>
      <?php if ($p['outcome']): ?><div class="pj-outcome"><b><?= icon('trophy', 16) ?> What we achieved</b><div><?= rich($p['outcome']) ?></div></div><?php endif ?>
    </section>

    <?php if ($roles || $isOwner || $canApply): ?>
    <section class="card" id="roles">
      <div class="card-title"><h2 class="lc-h2">Open roles</h2><span class="small muted"><?= count($openRoles) ?> open · apply for the one that fits you</span></div>
      <?php if ($roles): ?><div class="pj-roles">
        <?php foreach ($roles as $r): $full_r = (int) $r['filled'] >= (int) $r['slots']; ?>
          <div class="pj-role<?= $full_r ? ' is-full' : '' ?>">
            <div class="pj-role-top"><b><?= e($r['title']) ?></b><span class="pj-role-slots"><?php for ($i = 0; $i < (int) $r['slots']; $i++): ?><i class="<?= $i < (int) $r['filled'] ? 'on' : '' ?>"></i><?php endfor ?></span></div>
            <?php if ($r['skills']): ?><div class="pj-chips"><?php foreach (csv_list($r['skills']) as $s): ?><span class="<?= $isMatch($s) ? 'is-match' : '' ?>"><?= e($s) ?></span><?php endforeach ?></div><?php endif ?>
            <div class="pj-role-foot"><span class="xs muted"><?= (int) $r['filled'] ?>/<?= (int) $r['slots'] ?> filled</span>
              <?php if ($canApply && !$full_r): ?><a class="btn btn-sm btn-primary" href="#apply" data-pick-role="<?= (int) $r['id'] ?>">Apply for this role</a><?php elseif ($full_r): ?><span class="qz-tag is-ok">Filled</span><?php endif ?>
              <?php if ($isOwner): ?><form method="post" action="<?= e(url("projects/$pid/roles")) ?>" data-confirm="Remove the role “<?= e($r['title']) ?>”?"><?= csrf_field() ?><button class="icon-btn" name="delete" value="<?= (int) $r['id'] ?>" aria-label="Remove role"><?= icon('trash', 14) ?></button></form><?php endif ?></div>
          </div>
        <?php endforeach ?>
      </div><?php elseif (!$isOwner): ?><p class="muted small">No specific roles: apply and tell the team how you’d help.</p><?php endif ?>
      <?php if ($isOwner): ?>
        <details class="pj-add"<?= $roles ? '' : ' open' ?>><summary><?= icon('plus', 15) ?> Add a role</summary>
          <form method="post" action="<?= e(url("projects/$pid/roles")) ?>" class="pj-addform"><?= csrf_field() ?>
            <input class="input" name="title" maxlength="80" placeholder="e.g. UI designer" required>
            <input class="input" name="skills" maxlength="200" placeholder="Skills, comma separated">
            <input class="input" type="number" name="slots" min="1" max="10" value="1" aria-label="People needed" style="max-width:90px">
            <button class="btn btn-primary" type="submit">Add</button>
          </form></details>
      <?php endif ?>
      <?php if ($canApply): ?>
        <form id="apply" method="post" action="<?= e(url("projects/$pid/apply")) ?>" class="pj-apply" data-ai-writing-form data-ai-context="project application"><?= csrf_field() ?>
          <b class="pj-apply-title"><?= icon('user-plus', 17) ?> Apply to join</b>
          <?php if ($openRoles): ?><div class="field"><label class="f" for="pjRole">Role</label><select class="select" id="pjRole" name="role_id"><option value="">Any role, I’m flexible</option><?php foreach ($openRoles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['title']) ?></option><?php endforeach ?></select></div><?php endif ?>
          <label class="f">Why you’re a great fit</label><textarea class="textarea" name="message" data-ai-writing="body" placeholder="Your skills, past work, and when you’re free…"></textarea>
          <div class="writing-assist" data-writing-assist hidden aria-live="polite"></div>
          <button class="btn btn-primary mt" type="submit"><?= icon('send', 16) ?> Send application</button>
        </form>
      <?php endif ?>
    </section>
    <?php endif ?>

    <?php if ($milestones || $isOwner): ?>
    <section class="card" id="roadmap">
      <div class="card-title"><h2 class="lc-h2">Roadmap</h2><span class="small muted"><?= $msDone ?>/<?= count($milestones) ?> milestones reached</span></div>
      <?php if ($milestones): ?><div class="progress mb"><i style="width:<?= count($milestones) ? round($msDone / count($milestones) * 100) : 0 ?>%"></i></div>
        <ol class="pj-road">
          <?php foreach ($milestones as $m): $late = !$m['done'] && $m['due_date'] && strtotime($m['due_date']) < strtotime('today'); ?>
            <li class="<?= $m['done'] ? 'is-done' : '' ?><?= $late ? ' is-late' : '' ?>">
              <?php if ($isMember): ?><form method="post" action="<?= e(url("projects/$pid/milestones")) ?>"><?= csrf_field() ?><button class="pj-dot" name="toggle" value="<?= (int) $m['id'] ?>" aria-label="<?= $m['done'] ? 'Mark as not reached' : 'Mark as reached' ?>: <?= e($m['title']) ?>"><?= $m['done'] ? icon('check', 14) : '' ?></button></form>
              <?php else: ?><span class="pj-dot"><?= $m['done'] ? icon('check', 14) : '' ?></span><?php endif ?>
              <span class="pj-road-body"><b><?= e($m['title']) ?></b><span class="xs muted"><?= $m['done'] ? 'Reached ' . e(date('j M', strtotime($m['done_at'] ?: 'now'))) : ($m['due_date'] ? ($late ? 'Overdue · was due ' : 'Due ') . e(date('j M Y', strtotime($m['due_date']))) : 'No date yet') ?></span></span>
              <?php if ($isOwner): ?><form method="post" action="<?= e(url("projects/$pid/milestones")) ?>" data-confirm="Remove this milestone?"><?= csrf_field() ?><button class="icon-btn" name="delete" value="<?= (int) $m['id'] ?>" aria-label="Remove milestone"><?= icon('trash', 14) ?></button></form><?php endif ?>
            </li>
          <?php endforeach ?>
        </ol>
      <?php endif ?>
      <?php if ($isOwner): ?>
        <details class="pj-add"<?= $milestones ? '' : ' open' ?>><summary><?= icon('plus', 15) ?> Add a milestone</summary>
          <form method="post" action="<?= e(url("projects/$pid/milestones")) ?>" class="pj-addform"><?= csrf_field() ?>
            <input class="input" name="title" maxlength="160" placeholder="e.g. Clickable prototype ready" required>
            <input class="input" type="date" name="due_date" style="max-width:170px" aria-label="Due date">
            <button class="btn btn-primary" type="submit">Add</button>
          </form></details>
      <?php endif ?>
    </section>
    <?php endif ?>

    <?php if ($files || $isMember || $fileCount): ?>
    <section class="card" id="files">
      <div class="card-title"><h2 class="lc-h2">Files &amp; links</h2><span class="small muted"><?= $p['status'] === 'completed' ? 'Shared publicly as proof of work' : 'Only the team can open these' ?></span></div>
      <?php if ($files): ?><?php partial('project_files', ['files' => $files, 'pid' => $pid, 'u' => $u, 'isOwner' => $isOwner]) ?>
      <?php elseif (!$canSeeFiles && $fileCount): ?><p class="pj-locked"><?= icon('lock', 15) ?> <?= (int) $fileCount ?> file<?= $fileCount === 1 ? '' : 's' ?> shared with the team. Join to see them, or check back when the project is finished.</p>
      <?php else: ?><p class="muted small">Designs, documents, prototypes, repos… share everything the team needs in one place.</p><?php endif ?>
      <?php if ($isMember): ?>
        <details class="pj-add"<?= $files ? '' : ' open' ?>><summary><?= icon('upload', 15) ?> Share files or links</summary>
          <form method="post" action="<?= e(url("projects/$pid/files")) ?>" enctype="multipart/form-data"><?= csrf_field() ?><?php partial('attach_fields') ?><button class="btn btn-primary mt" type="submit"><?= icon('plus', 16) ?> Share with the team</button></form>
        </details>
      <?php endif ?>
    </section>
    <?php endif ?>

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
          <div class="grow"><a href="<?= e(url('profile/' . $a['user_id'])) ?>"><b><?= e($a['full_name']) ?></b></a><?= verified_badge($a) ?> <span class="xs muted">· <?= e($a['major'] ?: 'Student') ?></span><?php if ($a['role_title']): ?> <span class="chip sm">For: <?= e($a['role_title']) ?></span><?php endif ?><p class="small" style="margin:4px 0 0"><?= e($a['message'] ?: 'No message.') ?></p></div>
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
