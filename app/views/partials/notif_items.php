<?php
/** A list of notifications grouped by day. Used by the bell pop-up ($compact) and the notifications page. */
$compact = $compact ?? false;
$dayLabel = function (string $at): string {
    $d = strtotime(date('Y-m-d', strtotime($at)));
    $today = strtotime(date('Y-m-d'));
    return match (true) {
        $d >= $today => 'Today',
        $d >= $today - 86400 => 'Yesterday',
        $d >= $today - 6 * 86400 => 'This week',
        default => 'Earlier',
    };
};
$last = null;
?>
<?php if (!$items): ?>
  <div class="nt-empty">
    <span class="nt-empty-art"><?= icon('bell', 28) ?></span>
    <b>You’re all caught up</b>
    <span>Reactions, comments, orders and course updates will show up here.</span>
  </div>
<?php else: ?>
  <div class="nt-list">
    <?php foreach ($items as $n): $m = notif_meta($n['type']); $day = $dayLabel($n['created_at']); ?>
      <?php if ($day !== $last): $last = $day; ?><div class="nt-day"><?= e($day) ?></div><?php endif ?>
      <a class="nt-item tone-<?= e($m['tone']) ?> <?= $n['is_read'] ? '' : 'is-unread' ?>" href="<?= e(url('notifications/' . $n['id'] . '/open')) ?>" data-group="<?= e($m['group']) ?>">
        <span class="nt-ic"><?= icon($m['icon'], $compact ? 17 : 19) ?></span>
        <span class="nt-body">
          <span class="nt-text"><?= e($n['text']) ?></span>
          <span class="nt-meta"><span class="nt-tag"><?= e($m['label']) ?></span> · <?= e(time_ago($n['created_at'])) ?></span>
        </span>
        <?php if (!$n['is_read']): ?><span class="nt-dot" aria-label="Unread"></span><?php endif ?>
      </a>
    <?php endforeach ?>
  </div>
<?php endif ?>
