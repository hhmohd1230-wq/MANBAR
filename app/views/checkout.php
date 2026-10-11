<?php
$title = 'Checkout';
$amt = (int) $x['amount'];
$q = (int) ceil($amt / 4);
$isOrder = $x['type'] === 'order';
$cardLive = pay_live('card', $x['type']);
$held = $isOrder && $x['stage'] !== 'balance' && ($x['request']['status'] ?? '') === 'offered';
$saved = array_values(array_filter(billing_methods((int) $u['id']), fn($m) => $m['kind'] !== 'tabby' || $amt >= 20));
?>
<?php partial('market_assets') ?>
<?php partial('back_button', ['href' => $x['back'], 'label' => $isOrder ? 'Back to the listing' : 'Back to the course']) ?>

<div class="ck">
  <form class="card ck-main" method="post" action="<?= e(url("checkout/{$x['type']}/{$x['id']}")) ?>" data-checkout data-amount="<?= $amt ?>" data-card-live="<?= $cardLive ? '1' : '0' ?>"><?= csrf_field() ?>
    <div class="ck-head"><h1><?= icon('lock', 20) ?> Secure checkout</h1><?= pay_logos_row() ?></div>
    <?php if ($x['preview']): ?>
      <div class="ck-note is-preview"><?= icon('eye', 16) ?> <span><b>Preview as a student.</b> You manage this course, so nothing will be charged or saved. This is exactly what buyers see.</span></div>
    <?php else: ?>
      <div class="ck-note"><?= icon('info', 16) ?> <span><b>Demo checkout.</b> <?= $cardLive ? 'Card payments go through Stripe for real. PayPal, Apple Pay and tabby run in demo mode until they’re connected.' : 'No real money is charged yet. Each payment method is ready to connect to its provider later.' ?></span></div>
    <?php endif ?>

    <h2 class="ck-h2">Choose how to pay</h2>
    <div class="ck-methods" role="radiogroup" aria-label="Payment method">
      <?php foreach ($saved as $i => $m): ?>
        <label class="ck-method ck-saved"><input type="radio" name="method" value="saved-<?= (int) $m['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>><span class="ck-radio"></span><span class="ck-mtext"><b><?= e(billing_label($m)) ?></b><small><?= $m['kind'] === 'card' ? 'Saved card · expires ' . sprintf('%02d/%02d', $m['exp_month'], $m['exp_year'] % 100) : 'Saved in your billing' ?><?= $m['is_default'] ? ' · Default' : '' ?></small></span><span class="ck-mlogos"><?= pay_logo($m['kind'] === 'card' ? $m['brand'] : $m['kind']) ?></span></label>
      <?php endforeach ?>
      <?php if ($saved): ?><p class="ck-or">or pay another way · <a href="<?= e(url('settings?tab=billing')) ?>">manage saved methods</a></p><?php endif ?>
      <label class="ck-method"><input type="radio" name="method" value="card" <?= $saved ? '' : 'checked' ?>><span class="ck-radio"></span><span class="ck-mtext"><b>Debit or credit card</b><small>Visa, Mastercard, American Express</small></span><span class="ck-mlogos"><?= pay_logo('visa') . pay_logo('mastercard') . pay_logo('amex') ?></span></label>
      <label class="ck-method"><input type="radio" name="method" value="applepay"><span class="ck-radio"></span><span class="ck-mtext"><b>Apple Pay</b><small>Pay with Face ID or Touch ID</small></span><span class="ck-mlogos"><?= pay_logo('applepay') ?></span></label>
      <label class="ck-method"><input type="radio" name="method" value="paypal"><span class="ck-radio"></span><span class="ck-mtext"><b>PayPal</b><small>Log in to PayPal to pay</small></span><span class="ck-mlogos"><?= pay_logo('paypal') ?></span></label>
      <?php if ($amt >= 20): ?><label class="ck-method"><input type="radio" name="method" value="tabby"><span class="ck-radio"></span><span class="ck-mtext"><b>Pay in 4 with tabby</b><small>4 interest-free payments of AED <?= $q ?></small></span><span class="ck-mlogos"><?= pay_logo('tabby') ?></span></label><?php endif ?>
    </div>

    <div class="ck-panel" data-panel="card">
      <?php if ($cardLive): ?>
        <p class="ck-live"><?= icon('lock', 16) ?> You’ll enter your card on Stripe’s secure payment page, then come straight back to the course.</p>
      <?php else: ?>
        <div class="ck-card" aria-hidden="true"><span class="ck-card-chip"></span><span class="ck-card-brand" data-card-brand></span><span class="ck-card-num" data-card-show="num">•••• •••• •••• ••••</span><span class="ck-card-row"><span data-card-show="name">YOUR NAME</span><span data-card-show="exp">MM/YY</span></span></div>
        <div class="ck-fields">
          <div class="field ck-span2"><label class="f" for="ckNum">Card number</label><input class="input" id="ckNum" inputmode="numeric" autocomplete="off" maxlength="23" placeholder="1234 5678 9012 3456" data-card="num"></div>
          <div class="field ck-span2"><label class="f" for="ckName">Name on card</label><input class="input" id="ckName" autocomplete="off" maxlength="60" placeholder="As written on the card" data-card="name"></div>
          <div class="field"><label class="f" for="ckExp">Expiry</label><input class="input" id="ckExp" inputmode="numeric" autocomplete="off" maxlength="5" placeholder="MM/YY" data-card="exp"></div>
          <div class="field"><label class="f" for="ckCvc">CVC</label><input class="input" id="ckCvc" inputmode="numeric" autocomplete="off" maxlength="4" placeholder="123" data-card="cvc"></div>
        </div>
        <div class="ck-test"><button class="btn btn-sm btn-ghost" type="button" data-test-card><?= icon('sparkles', 14) ?> Use the test card</button><span class="xs muted">Demo: card details stay in your browser and are never sent or stored.</span></div>
        <p class="ck-err" data-card-err role="alert" hidden></p>
      <?php endif ?>
    </div>
    <div class="ck-panel" data-panel="applepay" hidden><p class="muted small">Confirm the payment on your iPhone, iPad or Mac.</p></div>
    <div class="ck-panel" data-panel="paypal" hidden><p class="muted small">You’ll be taken to PayPal to log in and approve AED <?= $amt ?>, then brought back here.</p></div>
    <div class="ck-panel" data-panel="tabby" hidden>
      <div class="ck-tabby"><?php foreach (['Today', 'In 2 weeks', 'In 4 weeks', 'In 6 weeks'] as $i => $when): ?><div class="<?= $i === 0 ? 'is-now' : '' ?>"><span class="ck-pie" style="--p:<?= ($i + 1) * 25 ?>"></span><b>AED <?= $i < 3 ? $q : max(0, $amt - 3 * $q) ?></b><small><?= $when ?></small></div><?php endforeach ?></div>
      <p class="xs muted">No interest, no fees when you pay on time. tabby checks eligibility in seconds.</p>
    </div>

    <button class="btn btn-primary btn-lg btn-block ck-pay" type="submit" data-pay-label><?= icon('lock', 18) ?> Pay AED <?= $amt ?></button>
    <p class="ck-fine"><?= $isOrder ? ($held ? 'MANBAR holds your money until you confirm the work was delivered. If the seller doesn’t deliver, you get it back.' : 'This payment goes to the seller when you confirm delivery.') : 'One payment, lifetime access. The course opens as soon as the payment goes through.' ?></p>
  </form>

  <aside class="card ck-sum">
    <div class="ck-item"><span class="ck-thumb" style="<?= $x['cover'] ?>"></span><span><b><?= e($x['title']) ?></b><span class="small muted"><?= e($x['whoLabel']) ?> <?= e($x['who']) ?></span></span></div>
    <p class="small muted ck-line"><?= e($x['line']) ?></p>
    <dl class="ck-rows">
      <div><dt><?= $isOrder ? e(['full' => 'Order total', 'deposit' => 'Deposit (50%)', 'balance' => 'Remaining balance'][$x['stage']]) : 'Course price' ?></dt><dd>AED <?= $amt ?></dd></div>
      <div><dt>Service fee</dt><dd class="ck-free">AED 0 <small>free for AAU students</small></dd></div>
      <div class="ck-total"><dt>Total today</dt><dd>AED <?= $amt ?></dd></div>
    </dl>
    <?php if ($isOrder && $x['terms']): ?>
      <div class="ck-terms"><b><?= icon('briefcase', 15) ?> <?= e(PAY_TERMS[$x['terms']][0]) ?></b><span class="small"><?= e(PAY_TERMS[$x['terms']][1]) ?></span></div>
    <?php endif ?>
    <ul class="ck-trust">
      <li><?= icon('shield', 15) ?> <?= $isOrder ? 'Buyer protection: your money is safe until you confirm' : 'Instant access after payment' ?></li>
      <li><?= icon('message', 15) ?> <?= $isOrder ? 'Chat with the seller any time in Messages' : 'Questions? Message the host' ?></li>
      <li><?= icon('refresh', 15) ?> <?= $isOrder ? 'Full refund if the work isn’t delivered' : 'Your access never expires' ?></li>
    </ul>
    <a class="btn btn-sm btn-ghost btn-block" href="<?= e(url('messages/' . $x['person']['id'])) ?>"><?= icon('message', 14) ?> Message <?= e(first_name($x['who'])) ?></a>
  </aside>
</div>
