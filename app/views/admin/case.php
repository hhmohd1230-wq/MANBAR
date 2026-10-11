<?php
$title = 'Scam case #' . $r['id'];
$open = $r['status'] === 'open';
$heldSum = array_sum(array_map(fn($p) => $p['status'] === 'held' ? (int) $p['amount'] : 0, $payments));
$risk = min(100, $profile['scam_reports'] * 25 + ($profile['completed'] ? 0 : 20) + ($profile['age_days'] < 30 ? 15 : 0) + ($profile['rating'] !== null && (float) $profile['rating'] < 3.5 ? 20 : 0));
$riskLabel = $risk >= 60 ? ['High risk', 'red'] : ($risk >= 30 ? ['Medium risk', 'amber'] : ['Low risk', 'green']);
$payLabel = ['held' => 'On hold', 'released' => 'Paid to seller', 'cash' => 'Cash', 'refunded' => 'Refunded'];
?>
<a class="ad-back" href="<?= e(url('admin/moderation?tab=scam')) ?>"><?= icon('arrow-right', 15, 'flip') ?> Scam cases</a>

<section class="cs-hero <?= $open ? '' : 'is-closed' ?>">
  <div>
    <span class="cs-kicker"><?= icon('alert', 15) ?> Scam case #<?= (int) $r['id'] ?> · <?= $open ? 'Open' : e(ucfirst($r['status'])) ?></span>
    <h1><?= e($r['reason']) ?></h1>
    <p>Reported <?= e(time_ago($r['created_at'])) ?> by <b><?= e($r['reporter_name']) ?></b> against <b><?= e($acc['full_name']) ?></b><?= $r['amount'] ? ' · says they lost <b>AED ' . (int) $r['amount'] . '</b>' : '' ?>.</p>
  </div>
  <div class="cs-risk tone-<?= $riskLabel[1] ?>"><span class="cs-ring" style="--p:<?= $risk ?>"><b><?= $risk ?></b></span><span><b><?= $riskLabel[0] ?></b><small>Based on past reports, history and reviews</small></span></div>
</section>

<div class="cs-grid">
  <div class="au-col">
    <section class="card au-card">
      <div class="au-head"><h2><?= icon('message', 18) ?> What happened</h2></div>
      <div class="cs-people">
        <a class="cs-person" href="<?= e(url('admin/users/' . $r['reporter_uid'])) ?>"><?= avatar(['full_name' => $r['reporter_name'], 'email' => $r['reporter_email'], 'avatar_url' => $r['reporter_avatar']], 44) ?><span><small>Reported by</small><b><?= e($r['reporter_name']) ?></b></span></a>
        <span class="cs-vs"><?= icon('arrow-right', 18) ?></span>
        <a class="cs-person is-accused" href="<?= e(url('admin/users/' . $acc['id'])) ?>"><?= avatar($acc, 44) ?><span><small>Accused</small><b><?= e($acc['full_name']) ?></b></span></a>
      </div>
      <blockquote class="cs-quote"><?= nl2br(e($r['details'] ?: 'No details were written.')) ?></blockquote>
      <?php if ($profile['messages']): ?><p class="cs-note"><?= icon('info', 15) ?> They have exchanged <?= (int) $profile['messages'] ?> direct messages. Private chats stay private; ask the reporter for screenshots if you need proof.</p><?php endif ?>
    </section>

    <section class="card au-card">
      <div class="au-head"><h2><?= icon('wallet', 18) ?> The order and the money</h2></div>
      <?php if ($order): ?>
        <div class="cs-order">
          <a href="<?= e(url('marketplace/' . $order['sid'])) ?>" target="_blank" rel="noopener"><b><?= e($order['title']) ?></b></a>
          <small>Order #<?= (int) $order['id'] ?> · <?= e(ucfirst($order['status'])) ?> · AED <?= (int) ($order['agreed_price'] ?: 0) ?> · started <?= e(date('j M Y', strtotime($order['created_at']))) ?></small>
        </div>
        <?php if ($payments): ?><div class="cs-pays"><?php foreach ($payments as $p): ?>
          <div class="cs-pay st-<?= e($p['status']) ?>"><span><?= pay_logo($p['method'] === 'card' ? 'visa' : $p['method']) ?></span><span class="grow"><b>AED <?= (int) $p['amount'] ?></b><small><?= e($p['ref']) ?> · <?= e(date('j M', strtotime($p['created_at']))) ?></small></span><span class="cs-pay-st"><?= e($payLabel[$p['status']] ?? $p['status']) ?></span></div>
        <?php endforeach ?></div><?php else: ?><p class="muted small">No payments were made for this order.</p><?php endif ?>
        <?php if ($heldSum): ?><p class="cs-held"><?= icon('lock', 15) ?> <b>AED <?= $heldSum ?></b> is still held by MANBAR and can be refunded to the buyer.</p><?php endif ?>
      <?php else: ?><p class="muted small">The reporter didn’t link an order.</p><?php endif ?>
    </section>

    <section class="card au-card">
      <div class="au-head"><h2><?= icon('store', 18) ?> Their listings</h2><span class="chip sm"><?= count($services) ?></span></div>
      <?php if ($services): ?><div class="au-list"><?php foreach ($services as $s): ?>
        <a class="au-li" href="<?= e(url('marketplace/' . $s['id'])) ?>" target="_blank" rel="noopener"><span class="au-li-ic"><?= icon('store', 14) ?></span><span class="grow"><b><?= e(excerpt($s['title'], 60)) ?></b><small>AED <?= (int) $s['price'] ?></small></span><span class="au-st st-<?= e($s['status']) ?>"><?= e($s['status']) ?></span></a>
      <?php endforeach ?></div><?php else: ?><p class="muted small">No listings.</p><?php endif ?>
    </section>
  </div>

  <div class="au-col">
    <section class="card au-card">
      <div class="au-head"><h2><?= icon('user', 18) ?> About the accused</h2></div>
      <dl class="cs-facts">
        <div><dt>Account age</dt><dd><?= $profile['age_days'] ?> days</dd></div>
        <div><dt>Status</dt><dd><?= e(ban_label($acc)) ?></dd></div>
        <div><dt>Orders delivered</dt><dd><?= $profile['completed'] ?></dd></div>
        <div><dt>Rating</dt><dd><?= $profile['rating'] !== null ? '★ ' . e($profile['rating']) . ' (' . $profile['reviews'] . ')' : '—' ?></dd></div>
        <div><dt>Trust</dt><dd><?= $profile['trust'] !== null ? (int) $profile['trust'] . '%' : '—' ?></dd></div>
        <div class="<?= $profile['scam_reports'] > 1 ? 'is-warn' : '' ?>"><dt>Scam reports</dt><dd><?= $profile['scam_reports'] ?><?= $profile['other_reports'] ? ' · from ' . $profile['other_reports'] . ' other ' . ($profile['other_reports'] === 1 ? 'person' : 'people') : '' ?></dd></div>
      </dl>
      <a class="btn btn-ghost btn-block au-mt" href="<?= e(url('admin/users/' . $acc['id'])) ?>"><?= icon('settings', 15) ?> Open their control panel</a>
    </section>

    <?php if ($open): ?>
    <form class="card au-card cs-decide" method="post" action="<?= e(url('admin/cases/' . $r['id'])) ?>" data-confirm="Close this case with the chosen action?"><?= csrf_field() ?>
      <div class="au-head"><h2><?= icon('check-circle', 18) ?> Decision</h2></div>
      <div class="cs-outcomes">
        <?php foreach ([['dismiss', 'Not a scam', 'Close the report, no action', 'check-circle', 'green'], ['warn', 'Warn the seller', 'Send an official warning', 'alert', 'amber'],
          ['hide', 'Warn + hide listings', 'Their services disappear from the marketplace', 'eye', 'orange'], ['ban', 'Ban the seller', 'Warn, hide listings and block the account', 'lock', 'red']] as $i => [$v, $l, $d, $ic, $tone]): ?>
          <label class="cs-out tone-<?= $tone ?>"><input type="radio" name="outcome" value="<?= $v ?>" <?= $i === 1 ? 'checked' : '' ?> data-outcome><span class="cs-out-ic"><?= icon($ic, 16) ?></span><span><b><?= e($l) ?></b><small><?= e($d) ?></small></span></label>
        <?php endforeach ?>
      </div>
      <div class="au-len cs-ban-len" data-ban-len hidden><?php foreach (BAN_LENGTHS as $d => $l): ?><label><input type="radio" name="days" value="<?= e((string) $d) ?>" <?= $d === '30' ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div>
      <?php if ($heldSum): ?><label class="cs-refund"><input type="checkbox" name="refund" value="1" checked><span><?= icon('wallet', 16) ?> Refund the held <b>AED <?= $heldSum ?></b> to <?= e($r['reporter_name']) ?></span></label><?php endif ?>
      <textarea class="input au-mt" name="note" rows="3" maxlength="600" placeholder="Note about your decision. It’s sent to the reporter (and to the seller with a warning)."></textarea>
      <button class="btn btn-primary btn-block au-mt" type="submit"><?= icon('check', 16) ?> Close the case</button>
    </form>
    <?php else: ?>
    <section class="card au-card cs-closed">
      <div class="au-head"><h2><?= icon('check-circle', 18) ?> Case closed</h2></div>
      <p><b><?= e($r['outcome'] ?: ucfirst($r['status'])) ?></b></p>
      <?php if ($r['admin_note']): ?><p class="small"><?= e($r['admin_note']) ?></p><?php endif ?>
      <p class="small muted">by <?= e($r['handler'] ?: 'an admin') ?><?= $r['handled_at'] ? ' · ' . e(time_ago($r['handled_at'])) : '' ?></p>
    </section>
    <?php endif ?>
  </div>
</div>
