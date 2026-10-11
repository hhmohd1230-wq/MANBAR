<?php
/** The buyer's view of their order: steps, the seller's offer, what to pay and the next button. Needs $r (request), $s (listing), $money (['paid','held']). */
$sid = (int) $s['id'];
$first = first_name($s['full_name']);
$price = (int) ($r['agreed_price'] ?? 0);
$terms = $r['pay_terms'] ?? null;
[$due] = order_due($r);
$paid = (int) ($money['paid'] ?? 0);
$held = (int) ($money['held'] ?? 0);
$steps = ['pending' => 'Requested', 'offered' => 'Offer', 'accepted' => 'In progress', 'delivered' => 'Delivered', 'completed' => 'Done'];
$keys = array_keys($steps);
$at = array_search($r['status'], $keys, true);
?>
<div class="mk-track">
  <span class="lc-action-k">Your order</span>
  <ol class="mk-steps"><?php foreach ($steps as $k => $label): $i = array_search($k, $keys, true); ?>
    <li class="<?= $at !== false && $i < $at ? 'is-done' : ($i === $at ? 'is-here' : '') ?>"><span></span><?= e($label) ?></li><?php endforeach ?></ol>

  <?php if ($r['status'] === 'pending'): ?>
    <p class="mk-track-msg"><?= icon('clock', 16) ?> Waiting for <?= e($first) ?> to send you an offer with the price, delivery time and when you pay.</p>
  <?php elseif ($r['status'] === 'offered'): ?>
    <div class="mk-offer">
      <div class="mk-offer-top"><span>Offer from <?= e($first) ?></span><b><?= $price ? 'AED ' . $price : 'Free' ?></b></div>
      <div class="small muted"><?= icon('clock', 13) ?> <?= (int) $r['agreed_days'] ?>-day delivery</div>
      <div class="mk-offer-terms"><b><?= e(PAY_TERMS[$terms][0]) ?></b><span class="small"><?= e(PAY_TERMS[$terms][1]) ?></span></div>
      <?php if ($r['offer_note']): ?><p class="small mk-offer-note">“<?= e($r['offer_note']) ?>”</p><?php endif ?>
    </div>
    <form method="post" action="<?= e(url("marketplace/$sid/order/{$r['id']}")) ?>"><?= csrf_field() ?><input type="hidden" name="act" value="accept">
      <button class="btn btn-primary btn-block btn-lg" type="submit"><?= $due ? icon('lock', 17) . ' Accept &amp; pay AED ' . $due : icon('check', 17) . ' Accept offer' ?></button></form>
    <?php if ($due): ?><div class="mk-paywith"><?= pay_logos_row() ?></div><?php endif ?>
    <form method="post" action="<?= e(url("marketplace/$sid/order/{$r['id']}")) ?>" data-confirm="Decline this offer and cancel your request?"><?= csrf_field() ?><input type="hidden" name="act" value="decline"><button class="btn btn-ghost btn-block btn-sm mt" type="submit">Decline offer</button></form>
  <?php elseif ($r['status'] === 'accepted'): ?>
    <p class="mk-track-msg"><?= icon('briefcase', 16) ?> <?= e($first) ?> is working on it<?= $r['decided_at'] && $r['agreed_days'] ? ' · due ' . e(date('j M', strtotime($r['decided_at']) + (int) $r['agreed_days'] * 86400)) : '' ?>.</p>
    <?php if ($held): ?><div class="mk-held"><?= icon('shield', 15) ?> AED <?= $held ?> paid, held safely by MANBAR until you confirm delivery.</div>
    <?php elseif ($terms === 'cash'): ?><div class="mk-held"><?= icon('wallet', 15) ?> You’ll pay AED <?= $price ?> in cash when you meet.</div>
    <?php elseif ($price): ?><div class="mk-held"><?= icon('wallet', 15) ?> You’ll pay AED <?= $price ?> after delivery.</div><?php endif ?>
  <?php elseif ($r['status'] === 'delivered'): ?>
    <p class="mk-track-msg is-go"><?= icon('check-circle', 16) ?> <?= e($first) ?> delivered your order. Check the work, then confirm.</p>
    <form method="post" action="<?= e(url("marketplace/$sid/order/{$r['id']}")) ?>"><?= csrf_field() ?><input type="hidden" name="act" value="confirm">
      <button class="btn btn-primary btn-block btn-lg" type="submit"><?= $due ? icon('lock', 17) . ' Confirm &amp; pay AED ' . $due : ($terms === 'cash' && $price ? icon('wallet', 17) . ' I paid AED ' . $price . ' in cash' : icon('check', 17) . ($held ? ' Confirm &amp; release AED ' . $held : ' Confirm delivery')) ?></button></form>
    <p class="xs muted" style="margin:8px 0 0">Something wrong? Message <?= e($first) ?> first. Don’t confirm until you’re happy.</p>
  <?php elseif ($r['status'] === 'completed'): ?>
    <p class="mk-track-msg is-go"><?= icon('check-circle', 16) ?> Completed<?= $r['completed_at'] ? ' on ' . e(date('j M Y', strtotime($r['completed_at']))) : '' ?>.<?= $paid ? ' AED ' . $paid . ' paid to ' . e($first) . '.' : '' ?></p>
  <?php endif ?>
  <a class="btn btn-block mt" href="<?= e(url('messages/' . $s['user_id'])) ?>"><?= icon('message', 16) ?> Chat with <?= e($first) ?></a>
</div>
