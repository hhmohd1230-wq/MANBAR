<?php $title = $title ?? 'MANBAR'; $cinema = $cinema ?? null; ?><!doctype html>
<html lang="en"<?= $cinema ? ' class="cine-doc"' : '' ?>>
<head>
<?php partial('head') ?>
<?php if ($cinema): ?>
<script>document.documentElement.classList.add('js');</script>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,300..900&family=Geist:wght@400..700&family=Geist+Mono:wght@400..600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/cinema.css')) ?>">
<?php else: ?>
<link rel="stylesheet" href="<?= e(asset('css/landing.css')) ?>">
<?php endif ?>
<title><?= e($title === 'MANBAR' ? 'MANBAR · The student platform for Al Ain University' : $title . ' · MANBAR') ?></title>
<meta name="description" content="MANBAR — share ideas, build projects, offer services, learn and find mentors with students at your university.">
<meta property="og:title" content="MANBAR · منبر — the stage for every student idea">
<meta property="og:description" content="Ideas, teams, projects, services, courses and mentors for verified Al Ain University students.">
<meta property="og:image" content="<?= e(asset('img/logo.svg')) ?>">
</head>
<body class="public<?= $cinema ? ' cine cine-' . e($cinema) : '' ?>">
<?= $content ?>
<?php partial('toasts') ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php if ($cinema): ?><script src="<?= e(asset('js/cinema.js')) ?>" defer></script><?php endif ?>
</body>
</html>
