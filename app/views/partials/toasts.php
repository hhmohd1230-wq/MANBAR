<div class="toasts" id="toasts">
<?php foreach (flash() ?? [] as [$type, $msg]): ?>
  <div class="toast <?= e($type) ?>"><?= icon($type === 'error' ? 'alert' : 'check-circle', 20) ?><div><?= e($msg) ?></div></div>
<?php endforeach ?>
<?php if (!empty($_SESSION['toast_badge'])): ?>
  <div class="toast badge"><?= icon('award', 20) ?><div><b>New badge unlocked!</b><br><?= e($_SESSION['toast_badge']) ?></div></div>
<?php unset($_SESSION['toast_badge']); endif ?>
</div>
