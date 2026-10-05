<?php $title = 'Mentors'; ?>
<div class="mentors-page">
<section class="mentors-hero" aria-labelledby="mentorsTitle">
  <div class="mentors-hero-copy"><span class="mentors-kicker"><?= icon('compass', 15) ?> Learn from experience</span><h1 id="mentorsTitle">Find the right mentor for your next step.</h1><p>Connect with teachers, alumni, and campus experts for focused guidance on your work and career.</p></div>
  <div class="mentors-hero-actions"><?php if ($pending): ?><a class="btn btn-white" href="<?= e(url('mentorship')) ?>"><?= icon('bell', 16) ?> <?= $pending ?> pending request<?= $pending > 1 ? 's' : '' ?></a><?php endif ?><a class="btn mentor-outline-btn" href="<?= e(url('mentorship')) ?>"><?= icon('compass', 16) ?> My mentorship</a></div>
  <div class="mentors-hero-orbit" aria-hidden="true"><span class="orbit-main"><?= icon('users', 31) ?></span><span class="orbit-small orbit-a"><?= icon('chat', 18) ?></span><span class="orbit-small orbit-b"><?= icon('bulb', 18) ?></span><i></i><i></i><i></i></div>
</section>
<form class="mentor-search-panel" method="get"><div class="search"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search mentors by name, expertise, or department"></div><button class="btn btn-primary" type="submit">Search mentors</button></form>
<div class="cards mentor-grid">
  <?php foreach ($mentors as $m): ?>
  <article class="card item mentor-card">
    <div class="item-cover mentor-cover mentor-cover-t<?= abs((int) $m['uid']) % 4 ?>"><span class="mentor-cover-mark"><?= icon('compass', 25) ?></span></div>
    <div class="item-body mentor-card-body"><div class="mentor-card-meta"><a class="mentor-avatar" href="<?= e(url('mentors/' . $m['uid'])) ?>"><?= avatar($m, 68) ?></a><span class="role-pill <?= $m['role'] === 'admin' ? 'admin' : '' ?>"><?= e(role_label($m['role'])) ?></span></div>
      <h3><a href="<?= e(url('mentors/' . $m['uid'])) ?>"><?= e($m['full_name']) ?></a> <?= verified_badge($m) ?></h3>
      <div class="small muted"><?= e($m['department'] ?: $m['headline']) ?></div>
      <div class="row wrap" style="gap:5px"><?php foreach (array_slice(csv_list($m['expertise']), 0, 3) as $x): ?><span class="chip sm"><?= e($x) ?></span><?php endforeach ?></div>
      <div class="item-foot"><span class="small muted"><?= icon('users', 13) ?> <?= (int) $m['mentees'] ?> mentees</span><a class="btn btn-sm btn-primary" href="<?= e(url('mentors/' . $m['uid'])) ?>">View &amp; request</a></div></div>
  </article>
  <?php endforeach ?>
</div>
<?php if (!$mentors): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No mentors available yet</h3><p>Teachers can switch on their mentor profile from <a href="<?= e(url('profile/edit')) ?>">Edit profile</a>.</p></div><?php endif ?>
</div>
