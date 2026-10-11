<?php
$title = 'Wallet';
$methodName = fn(string $m) => $m === 'cash' ? 'Cash' : (PAY_METHODS[$m][0] ?? ucfirst($m));
$stage = ['full' => 'Full payment', 'deposit' => 'Deposit', 'balance' => 'Balance'];
?>
<?php partial('market_assets') ?>
<?php partial('back_button', ['href' => 'marketplace/orders', 'label' => 'Back to my orders']) ?>

<section class="mk-hero mk-wallet hb hb-market">
  <div class="mk-hero-text">
    <span class="mk-kicker"><?= icon('wallet', 15) ?> Your wallet</span>
    <h1>AED <?= number_format($w['available']) ?> <small>available</small></h1>
    <p>Money from your marketplace orders. Buyers’ payments are held by MANBAR until they confirm delivery, then they land here and you can cash out.</p>
  </div>
  <dl class="mk-hero-stats hb-stats">
    <div><dt><span class="hb-sic"><?= icon('clock', 14) ?></span>On hold</dt><dd data-count>AED <?= number_format($w['held']) ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('chart', 14) ?></span>Total earned</dt><dd data-count>AED <?= number_format($w['earned']) ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('wallet', 14) ?></span>Paid in cash</dt><dd data-count>AED <?= number_format($w['cash']) ?></dd></div>
    <div><dt><span class="hb-sic"><?= icon('send', 14) ?></span>Cashed out</dt><dd data-count>AED <?= number_format($w['paid_out']) ?></dd></div>
  </dl>
</section>

<div class="page-grid">
  <div class="stack gap-l">
    <section class="card"><div class="card-title"><h2 class="lc-h2">Money in</h2><span class="chip sm"><?= count($tx) ?></span></div>
      <div class="table-wrap"><table class="t"><thead><tr><th>Order</th><th>From</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($tx as $t): ?><tr>
          <td><a href="<?= e(url('marketplace/' . $t['sid'])) ?>"><?= e(excerpt($t['title'], 44)) ?></a><div class="xs muted"><?= e($stage[$t['stage']] ?? '') ?> · <?= e($t['ref']) ?> · <?= e(date('j M Y', strtotime($t['created_at']))) ?></div></td>
          <td><?= e($t['buyer']) ?></td><td><?= e($methodName($t['method'])) ?></td><td class="db-num">AED <?= (int) $t['amount'] ?></td>
          <td><span class="mk-pay st-<?= e($t['status']) ?>"><?= e(['held' => 'On hold', 'released' => 'Released', 'cash' => 'Cash', 'refunded' => 'Refunded'][$t['status']] ?? $t['status']) ?></span></td></tr><?php endforeach ?>
        <?php if (!$tx): ?><tr><td colspan="5" class="muted tc" style="padding:28px">No payments yet. When buyers pay for your services, they show up here.</td></tr><?php endif ?>
      </tbody></table></div></section>

    <section class="card"><div class="card-title"><h2 class="lc-h2">Money out</h2><span class="chip sm"><?= count($spent) ?></span></div>
      <div class="table-wrap"><table class="t"><thead><tr><th>Order</th><th>Paid to</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($spent as $t): ?><tr>
          <td><a href="<?= e(url('marketplace/' . $t['sid'])) ?>"><?= e(excerpt($t['title'], 44)) ?></a><div class="xs muted"><?= e($t['ref']) ?> · <?= e(date('j M Y', strtotime($t['created_at']))) ?></div></td>
          <td><?= e($t['seller']) ?></td><td><?= e($methodName($t['method'])) ?></td><td class="db-num">AED <?= (int) $t['amount'] ?></td>
          <td><span class="mk-pay st-<?= e($t['status']) ?>"><?= e(['held' => 'Held by MANBAR', 'released' => 'Paid', 'cash' => 'Cash', 'refunded' => 'Refunded'][$t['status']] ?? $t['status']) ?></span></td></tr><?php endforeach ?>
        <?php if (!$spent): ?><tr><td colspan="5" class="muted tc" style="padding:28px">You haven’t paid for any orders yet.</td></tr><?php endif ?>
      </tbody></table></div></section>
  </div>

  <aside class="aside">
    <form class="card" method="post" action="<?= e(url('marketplace/earnings/payout')) ?>"><?= csrf_field() ?>
      <div class="card-title"><h3><?= icon('wallet', 18) ?> Cash out</h3></div>
      <div class="ck-note"><?= icon('info', 15) ?> <span>Demo mode: cash-outs are recorded but no real transfer is made yet.</span></div>
      <div class="field"><label class="f">Amount (AED)</label><input class="input" type="number" name="amount" min="10" max="<?= max(10, $w['available']) ?>" value="<?= $w['available'] ?>"<?= $w['available'] < 10 ? ' disabled' : '' ?>></div>
      <div class="mk-kindpick mk-paypick">
        <label><input type="radio" name="method" value="bank" checked><span><?= icon('building', 18) ?><b>Bank (IBAN)</b><small>1–2 working days</small></span></label>
        <label><input type="radio" name="method" value="paypal"><span><?= pay_logo('paypal') ?><b>PayPal</b><small>Within minutes</small></span></label>
      </div>
      <div class="field mt"><label class="f">IBAN or PayPal email</label><input class="input" name="account" autocomplete="off" placeholder="AE07 0331 2345 6789 0123 456"><span class="hint">We only keep the last 4 digits.</span></div>
      <button class="btn btn-primary btn-block" type="submit"<?= $w['available'] < 10 ? ' disabled' : '' ?>><?= icon('send', 16) ?> Cash out</button>
      <?php if ($w['available'] < 10): ?><p class="xs muted" style="margin:8px 0 0">You can cash out from AED 10.</p><?php endif ?>
    </form>
    <?php if ($payouts): ?>
    <div class="card"><div class="card-title"><h3>Cash-outs</h3></div>
      <?php foreach ($payouts as $p): ?><div class="mk-payout"><span><b>AED <?= (int) $p['amount'] ?></b><span class="xs muted"><?= e($p['account_hint']) ?> · <?= e(date('j M Y', strtotime($p['created_at']))) ?></span></span><span class="mk-pay st-released">Sent</span></div><?php endforeach ?>
    </div>
    <?php endif ?>
  </aside>
</div>
