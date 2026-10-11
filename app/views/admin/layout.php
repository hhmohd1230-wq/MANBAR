<?php
$me = current_user();
$openReports = (int) qval("SELECT COUNT(*) FROM reports WHERE status = 'open'");
$title = $title ?? 'Admin';
?><!doctype html>
<html lang="en">
<head>
<?php partial('head') ?>
<title><?= e($title) ?> · MANBAR Admin</title>
<link rel="stylesheet" href="<?= e(asset('css/sidebar.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(url('admin')) ?>" aria-label="MANBAR admin"><img src="<?= e(asset('img/logo.svg')) ?>" alt=""><span class="brand-copy">MAN<b>BAR</b><small>Admin console</small></span></a>
    <nav class="nav">
      <div class="nav-label">Overview</div>
      <a class="<?= req_path() === '/admin' ? 'on' : '' ?>" href="<?= e(url('admin')) ?>" title="Dashboard" aria-label="Dashboard"><?= icon('dashboard') ?><span class="nav-text">Dashboard</span></a>
      <div class="nav-label">People</div>
      <a class="<?= trim(nav_on('/admin/users')) ?>" href="<?= e(url('admin/users')) ?>" title="Users" aria-label="Users"><?= icon('users') ?><span class="nav-text">Users</span></a>
      <a class="<?= trim(nav_on('/admin/roster')) ?>" href="<?= e(url('admin/roster')) ?>" title="Student roster" aria-label="Student roster"><?= icon('database') ?><span class="nav-text">Student roster</span></a>
      <div class="nav-label">Content</div>
      <a class="<?= trim(nav_on('/admin/moderation')) ?>" href="<?= e(url('admin/moderation')) ?>" title="Moderation" aria-label="Moderation<?= $openReports ? ' — ' . $openReports . ' open' : '' ?>"><?= icon('flag') ?><span class="nav-text">Moderation</span><?php if ($openReports): ?><span class="count"><?= $openReports ?></span><?php endif ?></a>
      <a class="<?= trim(nav_on('/admin/content')) ?>" href="<?= e(url('admin/content')) ?>" title="Projects, services, courses" aria-label="Projects, services, courses"><?= icon('layers') ?><span class="nav-text">Projects, services, courses</span></a>
      <div class="nav-label">Platform</div>
      <a class="<?= trim(nav_on('/admin/universities')) ?>" href="<?= e(url('admin/universities')) ?>" title="Universities" aria-label="Universities"><?= icon('building') ?><span class="nav-text">Universities</span></a>
      <a class="<?= trim(nav_on('/admin/settings')) ?>" href="<?= e(url('admin/settings')) ?>" title="Settings &amp; audit" aria-label="Settings and audit"><?= icon('settings') ?><span class="nav-text">Settings &amp; audit</span></a>
      <div class="nav-label">Back</div>
      <a href="<?= e(url('feed')) ?>" title="Open the community" aria-label="Open the community"><?= icon('arrow-right') ?><span class="nav-text">Open the community</span></a>
    </nav>
  </aside>
  <button class="icon-btn sidebar-collapse" id="sidebarCollapse" type="button" aria-label="Collapse sidebar" aria-expanded="true" title="Collapse sidebar"><span class="sidebar-collapse-hide"><?= icon('panel-left-close', 19) ?></span><span class="sidebar-collapse-show"><?= icon('panel-left-open', 19) ?></span></button>
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
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
