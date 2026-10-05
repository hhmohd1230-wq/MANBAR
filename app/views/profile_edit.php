<?php $title = 'Edit profile'; $isStaff = in_array($u['role'], ['teacher', 'admin'], true); $activeNameStyle = $p['name_style'] ?? 'classic'; $activeEffect = $p['profile_effect'] ?? 'none'; ?>
<div class="pagehead profile-edit-head"><div><h1>Make your profile yours</h1><p>Shape how classmates see your work, personality and ways to connect.</p></div><a class="btn btn-ghost" href="<?= e(url('profile/' . $u['id'])) ?>">Cancel</a></div>
<form method="post" enctype="multipart/form-data" action="<?= e(url('profile/edit')) ?>" class="card profile-edit-form">
  <?= csrf_field() ?>
  <section class="profile-edit-section profile-edit-media">
    <div class="profile-edit-section-head"><span><?= icon('image', 20) ?></span><div><h2>Profile media</h2><p>Your photo and cover set the first impression.</p></div></div>
    <div class="row mb profile-photo-field" style="gap:20px"><?= avatar($p, 84) ?><div class="grow"><label class="f">Profile photo</label><input class="input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif"><div class="hint">Your Google photo is used by default. Upload JPG, PNG, WebP or GIF to replace it (max <?= (int) cfg('upload_max_mb') ?> MB).</div></div></div>
    <div class="profile-cover-editor">
      <div class="profile-cover-edit-preview <?= !empty($p['cover_image']) ? 'has-image' : '' ?>" id="profileCoverPreview" style="<?= e(!empty($p['cover_image']) ? profile_cover_style($p) : theme_css((int) $p['theme'])) ?>"><span>Cover preview</span></div>
      <div class="profile-cover-edit-controls"><label class="f" for="profileCoverInput">Custom cover image or GIF</label><input class="input" id="profileCoverInput" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif"><div class="hint">Animated GIF covers play automatically, similar to a Discord Nitro profile.</div><?php if (!empty($p['cover_image'])): ?><label class="profile-remove-cover"><input type="checkbox" name="remove_cover_image" value="1"> Remove the current custom cover</label><?php endif ?></div>
    </div>
  </section>

  <section class="profile-edit-section">
    <div class="profile-edit-section-head"><span><?= icon('user', 20) ?></span><div><h2>Identity</h2><p>Keep it clear, useful and easy to recognize.</p></div></div>
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
  </section>

  <section class="profile-edit-section">
    <div class="profile-edit-section-head"><span><?= icon('link', 20) ?></span><div><h2>Links and contact</h2><p>Each link gets its own branded color and icon on your profile.</p></div></div>
    <div class="profile-social-fields">
      <label class="profile-social-field website"><span class="profile-social-input-icon"><?= icon('globe', 18) ?></span><span class="grow"><b>Website</b><input class="input" name="website" value="<?= e($p['website']) ?>" placeholder="https://yourportfolio.com"></span></label>
      <label class="profile-social-field linkedin"><span class="profile-social-input-icon"><?= icon('linkedin', 18) ?></span><span class="grow"><b>LinkedIn</b><input class="input" name="linkedin" value="<?= e($p['linkedin']) ?>" placeholder="https://linkedin.com/in/…"></span></label>
      <label class="profile-social-field github"><span class="profile-social-input-icon"><?= icon('github', 18) ?></span><span class="grow"><b>GitHub</b><input class="input" name="github" value="<?= e($p['github']) ?>" placeholder="https://github.com/…"></span></label>
      <label class="profile-social-field discord"><span class="profile-social-input-icon"><?= icon('discord', 18) ?></span><span class="grow"><b>Discord</b><input class="input" name="discord" value="<?= e($p['discord'] ?? '') ?>" placeholder="https://discord.com/users/…"></span></label>
      <label class="profile-social-field whatsapp"><span class="profile-social-input-icon"><?= icon('whatsapp', 18) ?></span><span class="grow"><b>WhatsApp</b><input class="input" name="whatsapp" value="<?= e($p['whatsapp'] ?? '') ?>" placeholder="+971 50 000 0000" inputmode="tel"></span></label>
      <label class="profile-social-field phone"><span class="profile-social-input-icon"><?= icon('phone', 18) ?></span><span class="grow"><b>Phone</b><input class="input" name="phone" value="<?= e($p['phone'] ?? '') ?>" placeholder="+971 50 000 0000" inputmode="tel"></span></label>
    </div>
    <p class="hint">Only add contact details you are comfortable showing to the MANBAR community.</p>
  </section>

  <section class="profile-edit-section">
    <div class="profile-edit-section-head"><span><?= icon('sparkles', 20) ?></span><div><h2>Profile style</h2><p>Choose the type treatment and a subtle effect around your photo.</p></div></div>
    <div class="profile-style-preview" id="profileStylePreview" data-effect="<?= e($activeEffect) ?>"><span class="profile-style-avatar"><?= avatar($p, 54) ?></span><strong class="profile-name profile-name-<?= e($activeNameStyle) ?>" id="profileNamePreview"><?= e($p['full_name']) ?></strong><small>Live preview</small></div>
    <fieldset class="profile-choice-field"><legend>Name style</legend><div class="profile-style-options">
      <?php foreach (['classic' => 'Classic', 'rounded' => 'Rounded', 'wide' => 'Wide', 'code' => 'Code'] as $style => $label): ?><label><input type="radio" name="name_style" value="<?= e($style) ?>" <?= $activeNameStyle === $style ? 'checked' : '' ?>><span class="profile-name profile-name-<?= e($style) ?>"><?= e($label) ?><small><?= $style === 'classic' ? 'Clean and confident' : ($style === 'rounded' ? 'Friendly and soft' : ($style === 'wide' ? 'Bold and spaced' : 'Technical and focused')) ?></small></span></label><?php endforeach ?>
    </div></fieldset>
    <fieldset class="profile-choice-field"><legend>Profile effect</legend><div class="profile-effect-options">
      <?php foreach (['none' => ['None', 'Calm and clean'], 'aura' => ['Aura', 'Soft energy glow'], 'orbit' => ['Orbit', 'Moving campus ring'], 'spark' => ['Spark', 'Small points of light']] as $effect => [$label, $copy]): ?><label><input type="radio" name="profile_effect" value="<?= e($effect) ?>" <?= $activeEffect === $effect ? 'checked' : '' ?>><span><i class="effect-sample effect-sample-<?= e($effect) ?>"></i><b><?= e($label) ?></b><small><?= e($copy) ?></small></span></label><?php endforeach ?>
    </div></fieldset>
    <div class="field"><label class="f">Fallback cover colour</label><div class="theme-pick" id="profileThemePicker"><?php foreach (THEMES as $i => $t): ?><label><input type="radio" name="theme" value="<?= $i ?>" <?= (int) $p['theme'] === $i ? 'checked' : '' ?>><span style="<?= theme_css($i) ?>"></span></label><?php endforeach ?></div><div class="hint">This color appears whenever you do not use a custom image or GIF.</div></div>
  </section>
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
