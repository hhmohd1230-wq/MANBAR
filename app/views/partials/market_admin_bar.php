<?php /** For admins: browse the marketplace as a student, or switch on the admin tools. Needs $adminMode, $back. */ ?>
<div class="lc-viewbar mk-adminbar<?= $adminMode ? ' is-admin' : '' ?>" role="status">
  <span class="lc-viewbar-ico"><?= icon($adminMode ? 'shield' : 'eye', 18) ?></span>
  <span class="grow"><b><?= $adminMode ? 'Admin tools on' : 'Student view' ?></b><span class="small"> · <?= $adminMode ? 'You can edit, pause or delete any listing.' : 'You see the marketplace exactly as students do: request, chat and pay like a buyer.' ?></span></span>
  <form method="post" action="<?= e(url('marketplace/admin-mode')) ?>"><?= csrf_field() ?><input type="hidden" name="mode" value="<?= $adminMode ? 'student' : 'admin' ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
    <button class="btn btn-sm btn-primary" type="submit"><?= $adminMode ? icon('eye', 14) . ' Back to student view' : icon('shield', 14) . ' Admin tools' ?></button></form>
</div>
