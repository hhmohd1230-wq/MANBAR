<?php
/** "Report a scam" button + form. Needs $target (user row with id, full_name), $me (current user id) and $back (path to return to). */
if ((int) $target['id'] === (int) $me) return;
$dlg = 'scamDlg' . (int) $target['id'] . '_' . substr(md5($back), 0, 6);
$orders = scam_orders((int) $me, (int) $target['id']);
?>
<button class="<?= e($btnClass ?? 'btn btn-sm btn-ghost') ?> sr-open" type="button" data-people-dialog="<?= e($dlg) ?>"><?= icon('alert', 15) ?> <?= e($label ?? 'Report a scam') ?></button>
<dialog class="people-dialog sr-dialog" id="<?= e($dlg) ?>" aria-labelledby="<?= e($dlg) ?>T">
  <form method="post" action="<?= e(url('report/scam')) ?>"><?= csrf_field() ?>
    <input type="hidden" name="user" value="<?= (int) $target['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
    <div class="sr-head">
      <span class="sr-ic"><?= icon('alert', 22) ?></span>
      <div><h2 id="<?= e($dlg) ?>T">Report a scam</h2><p>About <b><?= e($target['full_name']) ?></b>. Only the MANBAR moderators see this.</p></div>
      <button class="icon-btn" type="button" data-dialog-close aria-label="Close"><?= icon('x', 19) ?></button>
    </div>
    <div class="sr-body">
      <label class="f">What happened?</label>
      <div class="sr-kinds">
        <?php foreach (SCAM_KINDS as $i => $k): ?><label><input type="radio" name="kind" value="<?= e($k) ?>" <?= $i === 0 ? 'checked' : '' ?>><span><?= e($k) ?></span></label><?php endforeach ?>
      </div>
      <div class="sr-two">
        <div><label class="f" for="<?= e($dlg) ?>O">Which order?</label>
          <select class="select" id="<?= e($dlg) ?>O" name="order"><option value="">Not about an order</option><?php foreach ($orders as $o): ?><option value="<?= (int) $o['id'] ?>">#<?= (int) $o['id'] ?> · <?= e(excerpt($o['title'], 34)) ?> · AED <?= (int) $o['price'] ?></option><?php endforeach ?></select></div>
        <div><label class="f" for="<?= e($dlg) ?>A">Money lost (AED)</label><input class="input" id="<?= e($dlg) ?>A" type="number" name="amount" min="0" max="100000" placeholder="0"></div>
      </div>
      <label class="f" for="<?= e($dlg) ?>D">Tell us the details</label>
      <textarea class="input" id="<?= e($dlg) ?>D" name="details" rows="4" minlength="15" maxlength="3000" required placeholder="What was agreed, what you paid and what went wrong. Dates help."></textarea>
      <p class="sr-tip"><?= icon('shield', 15) ?> If you paid through MANBAR and the money is still on hold, it stays frozen while we review. Never pay anyone outside MANBAR.</p>
    </div>
    <div class="sr-foot"><button class="btn btn-ghost" type="button" data-dialog-close>Cancel</button><button class="btn sr-send" type="submit"><?= icon('send', 16) ?> Send report</button></div>
  </form>
</dialog>
