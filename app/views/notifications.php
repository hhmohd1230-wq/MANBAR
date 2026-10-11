<?php
$title = 'Notifications';
$chip = fn(string $key, string $label, string $ic) => '<a class="nt-chip ' . ($f === $key ? 'on' : '') . '" href="' . e(url('notifications' . ($key === 'all' ? '' : '?f=' . $key))) . '">' . icon($ic, 15) . ' ' . e($label) . ((int) ($counts[$key] ?? 0) ? ' <span>' . (int) $counts[$key] . '</span>' : '') . '</a>';
?>
<section class="nt-board">
  <div>
    <span class="nt-kicker"><?= icon('bell', 15) ?> Notifications</span>
    <h1>What’s happening around you</h1>
    <p>Reactions, team updates, orders, courses and badges, all in one place.</p>
  </div>
  <div class="nt-board-side">
    <div class="nt-board-stat"><b><?= (int) $counts['unread'] ?></b><span>new since your last visit</span></div>
    <a class="btn nt-board-btn" href="<?= e(url('settings?tab=notifications')) ?>"><?= icon('settings', 16) ?> Notification settings</a>
  </div>
</section>

<div class="nt-page">
  <nav class="nt-chips" aria-label="Filter notifications">
    <?= $chip('all', 'All', 'layers') ?>
    <?= $chip('unread', 'New', 'sparkles') ?>
    <?php foreach (NOTIF_GROUPS as $g => [, , $ic, , $short]) if (!empty($counts[$g])) echo $chip($g, $short, $ic); ?>
  </nav>
  <div class="card nt-card">
    <?php partial('notif_items', ['items' => $items]) ?>
  </div>
</div>
