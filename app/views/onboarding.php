<?php $title = 'Set up your profile'; $first = explode(' ', $u['full_name'])[0]; ?>
<div class="auth" style="grid-template-columns:.8fr 1.2fr">
  <section class="auth-art">
    <a class="brand" href="<?= e(url('')) ?>"><img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="46" height="46"><span>MAN<b>BAR</b><small>منبر</small></span></a>
    <div>
      <h2>Almost there, <?= e($first) ?>!</h2>
      <p>A complete profile gets you noticed by teammates and mentors — and earns your first badge and <b>+20 points</b>.</p>
      <?php if ($roster): ?>
        <div class="card" style="background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.3);color:#fff;margin-top:24px;backdrop-filter:blur(8px)">
          <div class="row"><?= icon('check-circle', 26) ?><div><b>Found you in the <?= e($u['uni_short']) ?> roster</b><div class="small" style="opacity:.9">Student #<?= e($u['student_id']) ?> · we pre-filled your details.</div></div></div>
        </div>
      <?php endif ?>
    </div>
    <div class="small" style="opacity:.8"><?= e($u['email']) ?></div>
  </section>
  <section class="auth-main" style="align-items:start;padding-top:48px">
    <form class="auth-box" style="max-width:640px" method="post" action="<?= e(url('onboarding')) ?>">
      <?= csrf_field() ?>
      <h1>Tell us about you</h1>
      <p class="muted">You can change everything later from your profile.</p>
      <div class="card mb" style="display:flex;gap:16px;align-items:center"><?= avatar($u, 64) ?><div class="grow"><b><?= e($u['full_name']) ?></b> <?= verified_badge($u) ?><div class="small muted"><?= e($u['email']) ?> · <span class="role-pill <?= e($u['role']) ?>"><?= e(role_label($u['role'])) ?></span></div></div></div>
      <div class="field"><label class="f">Full name</label><input class="input" name="full_name" value="<?= e($u['full_name']) ?>" <?= $roster ? 'readonly' : 'required' ?>><?php if ($roster): ?><div class="hint">Taken from the university roster.</div><?php endif ?></div>
      <div class="grid2">
        <div class="field"><label class="f">Faculty / College</label><select class="select" name="faculty"><option value="">Select…</option><?php foreach (FACULTIES as $fc): ?><option <?= ($u['faculty'] ?? '') === $fc ? 'selected' : '' ?>><?= e($fc) ?></option><?php endforeach ?></select></div>
        <div class="field"><label class="f"><?= $u['role'] === 'student' ? 'Major' : 'Department' ?></label><input class="input" name="<?= $u['role'] === 'student' ? 'major' : 'department' ?>" value="<?= e($u['role'] === 'student' ? $u['major'] : $u['department']) ?>" placeholder="e.g. Software Engineering"></div>
      </div>
      <?php if ($u['role'] === 'student'): ?><div class="field"><label class="f">Year of study</label><select class="select" name="year_level"><option value="">Select…</option><?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>" <?= (int) ($u['year_level'] ?? 0) === $i ? 'selected' : '' ?>>Year <?= $i ?></option><?php endfor ?></select></div><?php endif ?>
      <div class="field"><label class="f">Headline</label><input class="input" name="headline" maxlength="160" placeholder="<?= $u['role'] === 'student' ? 'Software engineering student · UI/UX enthusiast' : 'Lecturer in Computer Science' ?>" value="<?= e($u['headline'] ?? '') ?>"></div>
      <div class="field"><label class="f">About you</label><textarea class="textarea" name="bio" maxlength="1000" placeholder="What are you passionate about? What do you want to build or learn?"><?= e($u['bio'] ?? '') ?></textarea></div>
      <div class="field"><label class="f">Your skills</label><div class="tags-in" data-tags="skills" data-initial="<?= e(implode(',', $skills)) ?>" data-ph="Type a skill and press Enter (e.g. Python, Figma)"></div></div>
      <div class="field"><label class="f">Interests</label><div class="tags-in" data-tags="interests" data-initial="<?= e(implode(',', $interests)) ?>" data-ph="Startups, AI, photography…"></div></div>
      <div class="field"><label class="f">Profile cover</label><div class="theme-pick"><?php foreach (THEMES as $i => $t): ?><label><input type="radio" name="theme" value="<?= $i ?>" <?= (int) $u['theme'] === $i ? 'checked' : '' ?>><span style="<?= theme_css($i) ?>"></span></label><?php endforeach ?></div></div>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Finish &amp; enter MANBAR <?= icon('arrow-right', 18) ?></button>
    </form>
  </section>
</div>
