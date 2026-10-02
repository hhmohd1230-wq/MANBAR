<?php $title = 'Mentors'; ?>
<div class="pagehead"><div><h1>Mentors</h1><p>Get guidance from teachers, alumni and experts. Request a session and learn from experience.</p></div>
  <div class="row"><?php if ($pending): ?><a class="btn" href="<?= e(url('mentorship')) ?>"><?= icon('bell', 16) ?> <?= $pending ?> pending request<?= $pending > 1 ? 's' : '' ?></a><?php endif ?><a class="btn btn-ghost" href="<?= e(url('mentorship')) ?>"><?= icon('compass', 16) ?> My mentorship</a></div></div>
<form class="mb" method="get"><div class="search" style="max-width:480px"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search by name or expertise"></div></form>
<div class="cards">
  <?php foreach ($mentors as $m): ?>
  <article class="card item">
    <div class="item-cover" style="height:84px;<?= theme_css((int) $m['uid']) ?>"></div>
    <div class="item-body" style="padding-top:0"><div class="row" style="margin-top:-30px;align-items:flex-end"><a href="<?= e(url('mentors/' . $m['uid'])) ?>"><?= avatar($m, 64) ?></a><span class="role-pill <?= $m['role'] === 'admin' ? 'admin' : '' ?>" style="margin-left:auto"><?= e(role_label($m['role'])) ?></span></div>
      <h3><a href="<?= e(url('mentors/' . $m['uid'])) ?>"><?= e($m['full_name']) ?></a> <?= verified_badge($m) ?></h3>
      <div class="small muted"><?= e($m['department'] ?: $m['headline']) ?></div>
      <div class="row wrap" style="gap:5px"><?php foreach (array_slice(csv_list($m['expertise']), 0, 3) as $x): ?><span class="chip sm"><?= e($x) ?></span><?php endforeach ?></div>
      <div class="item-foot"><span class="small muted"><?= icon('users', 13) ?> <?= (int) $m['mentees'] ?> mentees</span><a class="btn btn-sm btn-primary" href="<?= e(url('mentors/' . $m['uid'])) ?>">View &amp; request</a></div></div>
  </article>
  <?php endforeach ?>
</div>
<?php if (!$mentors): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No mentors available yet</h3><p>Teachers can switch on their mentor profile from <a href="<?= e(url('profile/edit')) ?>">Edit profile</a>.</p></div><?php endif ?>
