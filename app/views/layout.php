<?php
$me = current_user();
$notifN = $me ? (int) qval('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$me['id']]) : 0;
$msgN = $me ? (int) qval('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0', [$me['id']]) : 0;
$lvl = $me ? level_for((int) $me['points']) : null;
$pendingMentor = $me ? (int) qval("SELECT COUNT(*) FROM mentorship_requests WHERE mentor_id = ? AND status = 'pending'", [$me['id']]) : 0;
$title = $title ?? 'MANBAR';
?><!doctype html>
<html lang="en">
<head>
<?php partial('head') ?>
<title><?= e($title) ?> · MANBAR</title>
</head>
<body class="app">
<?php if ($n = setting('site_notice')): ?><div class="notice-bar"><?= e($n) ?></div><?php endif ?>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(url('feed')) ?>"><img src="<?= e(asset('img/logo.svg')) ?>" alt=""><span>MAN<b>BAR</b><small>منبر · <?= e($me['uni_short']) ?> students</small></span></a>
    <nav class="nav">
      <a class="<?= trim(nav_on('/feed', '/post')) ?>" href="<?= e(url('feed')) ?>"><?= icon('home') ?> Home feed</a>
      <a class="<?= trim(nav_on('/projects')) ?>" href="<?= e(url('projects')) ?>"><?= icon('rocket') ?> Projects</a>
      <a class="<?= trim(nav_on('/marketplace')) ?>" href="<?= e(url('marketplace')) ?>"><?= icon('store') ?> Marketplace</a>
      <a class="<?= trim(nav_on('/learn')) ?>" href="<?= e(url('learn')) ?>"><?= icon('book') ?> Learning center</a>
      <a class="<?= trim(nav_on('/mentors', '/mentorship')) ?>" href="<?= e(url('mentors')) ?>"><?= icon('compass') ?> Mentors <?php if ($pendingMentor): ?><span class="count"><?= $pendingMentor ?></span><?php endif ?></a>
      <a class="<?= trim(nav_on('/people')) ?>" href="<?= e(url('people')) ?>"><?= icon('users') ?> People</a>
      <div class="nav-label">You</div>
      <a class="<?= trim(nav_on('/profile', '/me')) ?>" href="<?= e(url('me')) ?>"><?= icon('user') ?> My profile</a>
      <a class="<?= trim(nav_on('/achievements')) ?>" href="<?= e(url('achievements')) ?>"><?= icon('trophy') ?> Achievements</a>
      <a class="<?= trim(nav_on('/saved')) ?>" href="<?= e(url('saved')) ?>"><?= icon('bookmark') ?> Saved</a>
      <a class="<?= trim(nav_on('/messages')) ?>" href="<?= e(url('messages')) ?>"><?= icon('message') ?> Messages <?php if ($msgN): ?><span class="count"><?= $msgN ?></span><?php endif ?></a>
      <?php if ($me['role'] === 'admin'): ?><div class="nav-label">Administration</div><a href="<?= e(url('admin')) ?>"><?= icon('shield') ?> Admin dashboard</a><?php endif ?>
    </nav>
    <div class="side-card">
      <div class="xs" style="opacity:.85;font-weight:700;letter-spacing:.08em;text-transform:uppercase">Level <?= $lvl['n'] ?></div>
      <div class="lvl"><?= e($lvl['name']) ?></div>
      <div class="progress"><i style="width:<?= $lvl['pct'] ?>%"></i></div>
      <div class="xs" style="opacity:.9"><?= (int) $me['points'] ?> pts<?= $lvl['next'] ? ' · ' . ($lvl['next_at'] - $me['points']) . ' to ' . e($lvl['next']) : ' · Max level' ?></div>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <button class="icon-btn menu-btn" id="menuBtn" aria-label="Menu"><?= icon('menu', 22) ?></button>
      <form class="search" action="<?= e(url('search')) ?>" method="get" role="search"><?= icon('search', 18) ?><input class="input" name="q" placeholder="Search ideas, people, projects, services…" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off"></form>
      <a class="btn btn-primary btn-sm" href="<?= e(url('feed?compose=idea')) ?>" style="margin-left:auto"><?= icon('plus', 16) ?> <span class="hide-s">New post</span></a>
      <a class="icon-btn" href="<?= e(url('messages')) ?>" title="Messages"><?= icon('mail', 21) ?><?php if ($msgN): ?><span class="badge-n" data-count="messages"><?= $msgN ?></span><?php endif ?></a>
      <a class="icon-btn" href="<?= e(url('notifications')) ?>" title="Notifications"><?= icon('bell', 21) ?><?php if ($notifN): ?><span class="badge-n" data-count="notifications"><?= $notifN ?></span><?php endif ?></a>
      <div class="user-menu">
        <button class="icon-btn" id="userBtn" style="width:auto;padding:0 4px" aria-label="Account"><?= avatar($me, 36) ?></button>
        <div class="dropdown" id="userDrop">
          <div style="padding:10px 12px 6px"><b><?= e($me['full_name']) ?></b><div class="xs muted"><?= e($me['email']) ?></div></div>
          <hr class="divider" style="margin:6px 0">
          <a href="<?= e(url('me')) ?>"><?= icon('user', 18) ?> View profile</a>
          <a href="<?= e(url('profile/edit')) ?>"><?= icon('settings', 18) ?> Edit profile</a>
          <a href="<?= e(url('marketplace/orders')) ?>"><?= icon('briefcase', 18) ?> My orders</a>
          <a href="<?= e(url('mentorship')) ?>"><?= icon('compass', 18) ?> My mentorship</a>
          <?php if ($me['role'] === 'admin'): ?><a href="<?= e(url('admin')) ?>"><?= icon('shield', 18) ?> Admin dashboard</a><?php endif ?>
          <hr class="divider" style="margin:6px 0">
          <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout', 18) ?> Sign out</button></form>
        </div>
      </div>
    </header>
    <main class="page"><?= $content ?></main>
  </div>
</div>
<nav class="mobile-nav">
  <a class="<?= trim(nav_on('/feed', '/post')) ?>" href="<?= e(url('feed')) ?>"><?= icon('home', 21) ?>Home</a>
  <a class="<?= trim(nav_on('/projects')) ?>" href="<?= e(url('projects')) ?>"><?= icon('rocket', 21) ?>Projects</a>
  <a class="<?= trim(nav_on('/marketplace')) ?>" href="<?= e(url('marketplace')) ?>"><?= icon('store', 21) ?>Market</a>
  <a class="<?= trim(nav_on('/learn')) ?>" href="<?= e(url('learn')) ?>"><?= icon('book', 21) ?>Learn</a>
  <a class="<?= trim(nav_on('/profile', '/me')) ?>" href="<?= e(url('me')) ?>"><?= icon('user', 21) ?>Me</a>
</nav>
<?php partial('guide') ?>
<?php partial('toasts') ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
