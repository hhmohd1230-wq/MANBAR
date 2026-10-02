<?php $title = 'Edit profile'; $isStaff = in_array($u['role'], ['teacher', 'admin'], true); ?>
<div class="pagehead"><div><h1>Edit profile</h1><p>Keep your profile fresh — it’s your digital portfolio on campus.</p></div><a class="btn btn-ghost" href="<?= e(url('profile/' . $u['id'])) ?>">Cancel</a></div>
<form method="post" enctype="multipart/form-data" action="<?= e(url('profile/edit')) ?>" class="card" style="max-width:820px">
  <?= csrf_field() ?>
  <div class="row mb" style="gap:20px"><?= avatar($u, 84) ?><div class="grow"><label class="f">Profile photo</label><input class="input" type="file" name="avatar" accept="image/*"><div class="hint">Your Google photo is used by default. Upload to replace it (max <?= (int) cfg('upload_max_mb') ?> MB).</div></div></div>
  <div class="grid2">
    <div class="field"><label class="f">Full name</label><input class="input" name="full_name" value="<?= e($u['full_name']) ?>" <?= $roster ? 'readonly' : 'required' ?>><?php if ($roster): ?><div class="hint">Locked: matched with the university roster (#<?= e($u['student_id']) ?>).</div><?php endif ?></div>
    <div class="field"><label class="f">Email</label><input class="input" value="<?= e($u['email']) ?>" readonly></div>
  </div>
  <div class="field"><label class="f">Headline</label><input class="input" name="headline" maxlength="160" value="<?= e($u['headline']) ?>" placeholder="Software engineering student · UI/UX enthusiast"></div>
  <div class="field"><label class="f">About you</label><textarea class="textarea" name="bio" maxlength="1000"><?= e($u['bio']) ?></textarea></div>
  <div class="grid3">
    <div class="field"><label class="f">Faculty / College</label><select class="select" name="faculty"><option value="">—</option><?php foreach (FACULTIES as $fc): ?><option <?= $u['faculty'] === $fc ? 'selected' : '' ?>><?= e($fc) ?></option><?php endforeach ?></select></div>
    <div class="field"><label class="f">Major</label><input class="input" name="major" value="<?= e($u['major']) ?>"></div>
    <div class="field"><label class="f">Year of study</label><select class="select" name="year_level"><option value="">—</option><?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>" <?= (int) $u['year_level'] === $i ? 'selected' : '' ?>>Year <?= $i ?></option><?php endfor ?></select></div>
  </div>
  <?php if ($isStaff): ?><div class="field"><label class="f">Department</label><input class="input" name="department" value="<?= e($u['department']) ?>"></div><?php endif ?>
  <div class="field"><label class="f">Skills</label><div class="tags-in" data-tags="skills" data-initial="<?= e(implode(',', $p['skills'])) ?>" data-ph="Type a skill and press Enter"></div></div>
  <div class="field"><label class="f">Interests</label><div class="tags-in" data-tags="interests" data-initial="<?= e(implode(',', $p['interests'])) ?>" data-ph="Add an interest"></div></div>
  <div class="grid3">
    <div class="field"><label class="f">Website</label><input class="input" name="website" value="<?= e($u['website']) ?>" placeholder="https://"></div>
    <div class="field"><label class="f">LinkedIn</label><input class="input" name="linkedin" value="<?= e($u['linkedin']) ?>" placeholder="https://linkedin.com/in/…"></div>
    <div class="field"><label class="f">GitHub</label><input class="input" name="github" value="<?= e($u['github']) ?>" placeholder="https://github.com/…"></div>
  </div>
  <div class="field"><label class="f">Cover colour</label><div class="theme-pick"><?php foreach (THEMES as $i => $t): ?><label><input type="radio" name="theme" value="<?= $i ?>" <?= (int) $u['theme'] === $i ? 'checked' : '' ?>><span style="<?= theme_css($i) ?>"></span></label><?php endforeach ?></div></div>
  <?php if ($isStaff): $m = $mentor ?: ['expertise' => '', 'about' => '', 'availability' => '', 'active' => 0]; ?>
    <hr class="divider">
    <h3><?= icon('compass', 18) ?> Mentor profile</h3>
    <p class="muted small">Turn this on to appear in the Mentors directory and receive mentorship requests from students.</p>
    <div class="field"><label class="f">Areas of expertise</label><input class="input" name="m_expertise" value="<?= e($m['expertise']) ?>" placeholder="Software architecture, databases, research methods"></div>
    <div class="field"><label class="f">About your mentoring</label><textarea class="textarea" name="m_about"><?= e($m['about']) ?></textarea></div>
    <div class="grid2"><div class="field"><label class="f">Availability</label><input class="input" name="m_availability" value="<?= e($m['availability']) ?>" placeholder="Sun & Tue 2–4 pm"></div>
    <div class="field"><label class="f">Accepting mentees</label><label class="row" style="padding-top:10px;cursor:pointer"><input type="checkbox" name="m_active" value="1" <?= $m['active'] ? 'checked' : '' ?>> <span>Show me in the Mentors directory</span></label></div></div>
  <?php endif ?>
  <div class="row" style="justify-content:flex-end;margin-top:8px"><button class="btn btn-primary btn-lg" type="submit"><?= icon('check', 18) ?> Save profile</button></div>
</form>
