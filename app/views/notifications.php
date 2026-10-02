<?php
$title = 'Notifications';
$icons = ['welcome' => 'sparkles', 'badge' => 'award', 'reaction' => 'heart', 'comment' => 'chat', 'reply' => 'chat', 'follow' => 'user-plus', 'post' => 'bulb', 'share' => 'share', 'application' => 'user-plus', 'accepted' => 'check-circle', 'rejected' => 'x', 'service_request' => 'store', 'review' => 'star', 'mentor_request' => 'compass', 'mentor_accept' => 'check-circle', 'message' => 'mail', 'enroll' => 'book', 'completed' => 'trophy'];
?>
<div class="pagehead"><div><h1>Notifications</h1><p>What’s happening around you.</p></div></div>
<div class="stack" style="max-width:760px">
  <?php foreach ($items as $n): ?><a class="notif <?= $n['is_read'] ? '' : 'unread' ?>" href="<?= e(url($n['link'] ?: 'feed')) ?>"><span class="nic"><?= icon($icons[$n['type']] ?? 'bell', 21) ?></span><span class="grow"><?= e($n['text']) ?><br><span class="xs muted"><?= e(time_ago($n['created_at'])) ?></span></span><?= icon('chevron-right', 18, 'faint') ?></a><?php endforeach ?>
  <?php if (!$items): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>You’re all caught up</h3><p>Reactions, comments and requests will show up here.</p></div><?php endif ?>
</div>
