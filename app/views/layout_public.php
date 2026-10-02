<?php $title = $title ?? 'MANBAR'; ?><!doctype html>
<html lang="en">
<head>
<?php partial('head') ?>
<link rel="stylesheet" href="<?= e(asset('css/landing.css')) ?>">
<title><?= e($title === 'MANBAR' ? 'MANBAR · The student platform for Al Ain University' : $title . ' · MANBAR') ?></title>
<meta name="description" content="MANBAR — share ideas, build projects, offer services, learn and find mentors with students at your university.">
</head>
<body class="public">
<?= $content ?>
<?php partial('toasts') ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
