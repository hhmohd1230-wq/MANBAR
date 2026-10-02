<?php
$me = current_user();
$openReports = (int) qval("SELECT COUNT(*) FROM reports WHERE status = 'open'");
$title = $title ?? 'Admin';
?><!doctype html>
<html lang="en">
<head>
<?php partial('head') ?>
<title><?= e($title) ?> · MANBAR Admin</title>
</head>
<body class="admin">
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(url('admin')) ?>"><img src="<?= e(asset('img/logo.svg')) ?>" alt=""><span>MAN<b>BAR</b><small>Admin console</small></span></a>
    <nav class="nav">
      <div class="nav-label">Overview</div>
      <a class="<?= req_path() === '/admin' ? 'on' : '' ?>" href="<?= e(url('admin')) ?>"><?= icon('dashboard') ?> Dashboard</a>
      <div class="nav-label">People</div>
      <a class="<?= trim(nav_on('/admin/users')) ?>" href="<?= e(url('admin/users')) ?>"><?= icon('users') ?> Users</a>
      <a class="<?= trim(nav_on('/admin/roster')) ?>" href="<?= e(url('admin/roster')) ?>"><?= icon('database') ?> Student roster</a>
      <div class="nav-label">Content</div>
      <a class="<?= trim(nav_on('/admin/moderation')) ?>" href="<?= e(url('admin/moderation')) ?>"><?= icon('flag') ?> Moderation <?php if ($openReports): ?><span class="count"><?= $openReports ?></span><?php endif ?></a>
      <a class="<?= trim(nav_on('/admin/content')) ?>" href="<?= e(url('admin/content')) ?>"><?= icon('layers') ?> Projects, services, courses</a>
      <div class="nav-label">Platform</div>
      <a class="<?= trim(nav_on('/admin/universities')) ?>" href="<?= e(url('admin/universities')) ?>"><?= icon('building') ?> Universities</a>
      <a class="<?= trim(nav_on('/admin/settings')) ?>" href="<?= e(url('admin/settings')) ?>"><?= icon('settings') ?> Settings &amp; audit</a>
      <div class="nav-label">Back</div>
      <a href="<?= e(url('feed')) ?>"><?= icon('arrow-right') ?> Open the community</a>
    </nav>
  </aside>
  <div class="main">
    <header class="topbar">
      <button class="icon-btn menu-btn" id="menuBtn" aria-label="Menu"><?= icon('menu', 22) ?></button>
      <div><b style="font-family:var(--font-d);font-size:18px"><?= e($title) ?></b></div>
      <span style="margin-left:auto" class="muted small hide-s"><?= date('l, j F Y') ?></span>
      <div class="user-menu">
        <button class="icon-btn" id="userBtn" style="width:auto;padding:0 4px"><?= avatar($me, 36) ?></button>
        <div class="dropdown" id="userDrop">
          <div style="padding:10px 12px 6px"><b><?= e($me['full_name']) ?></b><div class="xs muted"><?= e($me['email']) ?></div></div>
          <hr class="divider" style="margin:6px 0">
          <a href="<?= e(url('feed')) ?>"><?= icon('home', 18) ?> Community</a>
          <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout', 18) ?> Sign out</button></form>
        </div>
      </div>
    </header>
    <main class="page"><?= $content ?></main>
  </div>
</div>
<?php partial('toasts') ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
