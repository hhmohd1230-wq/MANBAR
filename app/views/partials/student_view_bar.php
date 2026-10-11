<?php
/** Shown to the course host while "Student view" is on: what students see, with a way back to editing. Needs $cid. */
$back = ltrim(substr((string) ($_SERVER['REQUEST_URI'] ?? ''), strlen(base_path())), '/');
// an admin looking at someone else's course: they're reviewing it, not hosting it
$vbCourse = qrow('SELECT author_id FROM courses WHERE id = ?', [$cid]);
$reviewer = $vbCourse && (int) $vbCourse['author_id'] !== (int) (current_user()['id'] ?? 0);
?>
<div class="lc-viewbar" role="status">
  <span class="lc-viewbar-ico"><?= icon('eye', 18) ?></span>
  <span class="grow"><b>Student view</b><span class="small"> · <?= ($_SESSION['student_view_as'][$cid] ?? 'joined') === 'visitor' ? 'This is what people see before they join or pay. Try the Buy button: nothing is charged.' : ($reviewer ? 'You’re reviewing someone else’s course as a student sees it. Nothing you do here is saved.' : 'This is exactly what your students see. Nothing you do here is saved.') ?></span></span>
  <?php $asV = ($_SESSION['student_view_as'][$cid] ?? 'joined') === 'visitor'; ?>
  <form class="lc-viewas" method="post" action="<?= e(url("learn/$cid/view")) ?>"><?= csrf_field() ?><input type="hidden" name="mode" value="student"><input type="hidden" name="back" value="learn/<?= (int) $cid ?>">
    <span class="xs">See it as:</span><button class="<?= $asV ? '' : 'on' ?>" name="as" value="joined" type="submit">Joined student</button><button class="<?= $asV ? 'on' : '' ?>" name="as" value="visitor" type="submit">Not joined yet</button></form>
  <form method="post" action="<?= e(url("learn/$cid/view")) ?>"><?= csrf_field() ?><input type="hidden" name="mode" value="host"><input type="hidden" name="back" value="<?= e($back) ?>">
    <button class="btn btn-sm btn-primary" type="submit"><?= $reviewer ? icon('shield', 14) . ' Admin tools' : icon('edit', 14) . ' Back to host view' ?></button></form>
</div>
