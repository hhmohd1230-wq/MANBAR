<?php $title = 'People'; ?>
<div class="pagehead"><div><h1>People directory</h1><p>Find classmates and teachers by skill, major or interest.</p></div></div>
<form class="card mb" method="get" style="padding:16px">
  <div class="grid3" style="grid-template-columns:2fr 1fr 1fr 1fr">
    <div class="search"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search by name or student number"></div>
    <input class="input" name="skill" value="<?= e($skill) ?>" placeholder="Skill (e.g. Python)">
    <select class="select" name="major" data-autosubmit><option value="">Any major</option><?php foreach ($majors as $m): ?><option <?= $major === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach ?></select>
    <select class="select" name="role" data-autosubmit><option value="">Students & teachers</option><option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Students</option><option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>Teachers</option></select>
  </div>
  <?php if ($topSkills): ?><div class="row wrap" style="margin-top:12px"><span class="small muted">Popular skills:</span><?php foreach ($topSkills as $s): ?><a class="chip <?= strtolower($skill) === strtolower($s) ? 'on' : 'outline' ?> sm" href="<?= e(url('people?skill=' . urlencode($s))) ?>"><?= e($s) ?></a><?php endforeach ?></div><?php endif ?>
</form>
<div class="cards">
  <?php foreach ($people as $p): ?>
  <div class="card item person-card">
    <div class="pc-cover" style="<?= theme_css((int) $p['theme']) ?>"></div>
    <div style="display:grid;place-items:center"><a href="<?= e(url('profile/' . $p['id'])) ?>"><?= avatar($p, 68) ?></a></div>
    <div class="pc-body">
      <a href="<?= e(url('profile/' . $p['id'])) ?>" style="font:700 16.5px var(--font-d);color:var(--g950)"><?= e($p['full_name']) ?></a><?= verified_badge($p) ?>
      <div class="small muted"><?= e($p['headline'] ?: $p['major'] ?: role_label($p['role'])) ?></div>
      <div class="row wrap" style="justify-content:center;margin:10px 0;gap:5px"><?php foreach ($p['skills'] as $s): ?><span class="chip sm"><?= e($s) ?></span><?php endforeach ?></div>
      <div class="row" style="justify-content:center"><a class="btn btn-sm" href="<?= e(url('profile/' . $p['id'])) ?>">View</a><?php if ((int) $p['id'] !== (int) $u['id']): ?><button class="btn btn-sm btn-ghost" data-follow="<?= (int) $p['id'] ?>">Follow</button><a class="icon-btn" style="width:32px;height:32px" href="<?= e(url('messages/' . $p['id'])) ?>" title="Message"><?= icon('message', 17) ?></a><?php endif ?></div>
    </div>
  </div>
  <?php endforeach ?>
</div>
<?php if (!$people): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No matches</h3><p>Try fewer filters or another skill.</p></div><?php endif ?>
